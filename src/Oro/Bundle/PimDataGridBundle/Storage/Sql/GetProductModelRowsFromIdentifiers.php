<?php

declare(strict_types=1);

namespace Oro\Bundle\PimDataGridBundle\Storage\Sql;

use Akeneo\Pim\Enrichment\Component\Product\Factory\ValueCollectionFactoryInterface;
use Akeneo\Pim\Enrichment\Component\Product\Grid\ReadModel;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\PimDataGridBundle\Normalizer\IdEncoder;
use Oro\Bundle\PimDataGridBundle\Storage\GetRowsFromIdentifiersQuery;
use Oro\Bundle\PimDataGridBundle\Storage\GetRowsQueryParameters;

/**
 * @copyright 2018 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class GetProductModelRowsFromIdentifiers implements GetRowsFromIdentifiersQuery
{
    /** @var Connection */
    private $connection;

    /** @var ValueCollectionFactoryInterface */
    private $valueCollectionFactory;

    public function __construct(Connection $connection, ValueCollectionFactoryInterface $valueCollectionFactory)
    {
        $this->connection = $connection;
        $this->valueCollectionFactory = $valueCollectionFactory;
    }

    /**
     * @inheritDoc
     */
    public function fetch(array $identifiers, GetRowsQueryParameters $queryParameters): array
    {
        $valueCollections = $this->getValueCollection($identifiers);

        $rows = array_replace_recursive(
            $this->getProperties($identifiers),
            $this->getAttributeAsLabel($identifiers, $valueCollections, $queryParameters->channel(), $queryParameters->locale()),
            $this->getAttributeAsImage($identifiers, $valueCollections),
            $this->getChildrenCompletenesses($identifiers, $queryParameters->channel(), $queryParameters->locale()),
            $this->getFamilyLabels($identifiers, $queryParameters->locale()),
            $valueCollections
        );

        $platform = $this->connection->getDatabasePlatform();

        $productModels = [];
        foreach ($rows as $row) {
            $productModels[] = new ReadModel\Row(
                $row['identifier'],
                $row['family_label'],
                [],
                null,
                Type::getType(Type::DATETIME)->convertToPhpValue($row['created'], $platform),
                Type::getType(Type::DATETIME)->convertToPhpValue($row['updated'], $platform),
                $row['label'],
                $row['image'],
                null,
                IdEncoder::PRODUCT_MODEL_TYPE,
                (int) $row['id'],
                IdEncoder::encode(IdEncoder::PRODUCT_MODEL_TYPE, (int) $row['id']),
                true,
                $row['children_completeness'],
                $row['parent_code'],
                $row['value_collection']
            );
        }

        return $productModels;
    }

    private function getProperties(array $identifiers): array
    {
        $sql = <<<SQL
            SELECT 
                pm.id,
                pm.code as identifier,
                pm.created,
                pm.updated,
                parent.code as parent_code
            FROM
                pim_catalog_product_model pm
                LEFT JOIN pim_catalog_product_model parent ON parent.id = pm.parent_id
            WHERE 
                pm.code IN (:identifiers)
SQL;

        $rows = $this->connection->executeQuery(
            $sql,
            ['identifiers' => $identifiers],
            ['identifiers' => \Doctrine\DBAL\Connection::PARAM_STR_ARRAY]
        )->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['identifier']] = $row;
        }

        return $result;
    }

    private function getAttributeAsLabel(array $identifiers, array $valueCollections, string $channel, string $locale): array
    {
        $result = [];
        foreach ($identifiers as $identifier) {
            $result[$identifier]['label'] = null;
        }

        $sql = <<<SQL
            SELECT 
                pm.code as identifier,
                a_label.code as label_code,
                a_label.is_localizable,
                a_label.is_scopable
            FROM
                pim_catalog_product_model pm
                JOIN pim_catalog_family_variant fv ON fv.id = pm.family_variant_id
                JOIN pim_catalog_family f ON f.id = fv.family_id
                JOIN pim_catalog_attribute a_label ON a_label.id = f.label_attribute_id
            WHERE 
                pm.code IN (:identifiers)
SQL;

        $rows = $this->connection->executeQuery(
            $sql,
            ['identifiers' => $identifiers],
            ['identifiers' => \Doctrine\DBAL\Connection::PARAM_STR_ARRAY]
        )->fetchAll();

        foreach ($rows as $row) {
            $label = $valueCollections[$row['identifier']]['value_collection']->getByCodes(
                $row['label_code'],
                $row['is_scopable'] ? $channel : null,
                $row['is_localizable'] ? $locale : null
            );

            $result[$row['identifier']]['label'] = $label ?? null;
        }

        return $result;
    }

    private function getAttributeAsImage(array $identifiers, array $valueCollections): array
    {
        $result = [];
        foreach ($identifiers as $identifier) {
            $result[$identifier]['image'] = null;
        }

        /**
         * @fixme
         *        ex for product model "model-tshirt-divided"  :
         *          the code of the image attribute of the family is "variation_image"
         *          but "variation_image" is the image of the variant product. So it's the attribute "image" that is used
         */
        $sql = <<<SQL
            SELECT 
                pm.code as identifier,
                a_image.code as image_code
            FROM
                pim_catalog_product_model pm
                JOIN pim_catalog_family_variant fv ON fv.id = pm.family_variant_id
                JOIN pim_catalog_family f ON f.id = fv.family_id
                JOIN pim_catalog_attribute a_image ON a_image.id = f.image_attribute_id
            WHERE 
                pm.code IN (:identifiers)
SQL;

        $rows = $this->connection->executeQuery(
            $sql,
            ['identifiers' => $identifiers],
            ['identifiers' => \Doctrine\DBAL\Connection::PARAM_STR_ARRAY]
        )->fetchAll();

        foreach ($rows as $row) {
            $image = $valueCollections[$row['identifier']]['value_collection']->getByCodes($row['image_code']);
            $result[$row['identifier']]['image'] = $image ?? null;
        }

        return $result;
    }

    private function getChildrenCompletenesses(array $identifiers, string $channel, string $locale): array
    {
        $result = [];
        foreach ($identifiers as $identifier) {
            $result[$identifier]['children_completeness'] = [
                'total'    => 0,
                'complete' => 0,
            ];
        }

        $sql = <<<SQL
            SELECT pm.code as identifier,
              COUNT(p_child.id) AS nb_children,
              SUM(IF(completeness.ratio = 100, 1, 0)) AS nb_children_complete
            FROM pim_catalog_product_model pm
                LEFT JOIN pim_catalog_product_model pm_child ON pm_child.parent_id = pm.id
                LEFT JOIN pim_catalog_product p_child ON p_child.product_model_id = COALESCE(pm_child.id, pm.id)
                LEFT JOIN pim_catalog_completeness completeness ON completeness.product_id = p_child.id
                LEFT JOIN pim_catalog_channel channel ON channel.id = completeness.channel_id
                LEFT JOIN pim_catalog_locale locale ON locale.id = completeness.locale_id
            WHERE pm.code IN (:identifiers)
                AND channel.code = :channel
                AND locale.code = :locale
            GROUP BY pm.code
SQL;
        $rows = $this->connection->executeQuery(
            $sql,
            [
                'identifiers' => $identifiers,
                'channel' => $channel,
                'locale' => $locale,
            ],
            [
                'identifiers' => Connection::PARAM_STR_ARRAY,
                'channel' => \PDO::PARAM_STR,
                'locale' => \PDO::PARAM_STR,
            ]
        )->fetchAll();

        foreach ($rows as $row) {
            $result[$row['identifier']]['children_completeness'] = [
                'total'    => (int) $row['nb_children'],
                'complete' => (int) $row['nb_children_complete'],
            ];
        }

        return $result;
    }

    private function getFamilyLabels(array $identifiers, string $locale): array
    {
        $result = [];
        foreach ($identifiers as $identifier) {
            $result[$identifier]['family_label'] = null;
        }

        $sql = <<<SQL
            SELECT 
                pm.code as identifier,
                COALESCE(ft.label, CONCAT("[", f.code, "]")) as family_label
            FROM
                pim_catalog_product_model pm
                JOIN pim_catalog_family_variant fv ON fv.id = pm.family_variant_id
                JOIN pim_catalog_family f ON f.id = fv.family_id
                LEFT JOIN pim_catalog_family_translation ft ON ft.foreign_key = f.id AND ft.locale = :locale_code
            WHERE 
                pm.code IN (:identifiers)
SQL;

        $rows = $this->connection->executeQuery(
            $sql,
            ['identifiers' => $identifiers, 'locale_code' => $locale],
            ['identifiers' => \Doctrine\DBAL\Connection::PARAM_STR_ARRAY]
        )->fetchAll();

        foreach ($rows as $row) {
            $result[$row['identifier']]['family_label'] = $row['family_label'];
        }

        return $result;
    }

    private function getValueCollection(array $identifiers): array
    {
        // TODO : handle recursivity when level > 2?
        $sql = <<<SQL
            SELECT 
                pm.code as identifier,
                JSON_MERGE(COALESCE(parent.raw_values, '{}'), pm.raw_values) as raw_values
            FROM
                pim_catalog_product_model pm
                LEFT JOIN pim_catalog_product_model parent on parent.id = pm.parent_id
            WHERE 
                pm.code IN (:identifiers)
SQL;

        $rows = $this->connection->executeQuery(
            $sql,
            ['identifiers' => $identifiers],
            ['identifiers' => \Doctrine\DBAL\Connection::PARAM_STR_ARRAY]
        )->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['identifier']]['value_collection'] = $this->valueCollectionFactory->createFromStorageFormat(
                json_decode($row['raw_values'], true)
            );
        }

        return $result;
    }
}

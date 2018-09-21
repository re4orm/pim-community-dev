<?php

declare(strict_types=1);

namespace Oro\Bundle\PimDataGridBundle\Datasource;

use Akeneo\Pim\Enrichment\Component\Product\Grid\ReadModel\Row;
use Akeneo\Pim\Enrichment\Component\Product\Query\ProductQueryBuilderFactoryInterface;
use Akeneo\Pim\Enrichment\Component\Product\Query\ProductQueryBuilderInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\PimDataGridBundle\EventSubscriber\FilterEntityWithValuesSubscriber;
use Oro\Bundle\PimDataGridBundle\EventSubscriber\FilterEntityWithValuesSubscriberConfiguration;
use Oro\Bundle\PimDataGridBundle\Extension\Pager\PagerExtension;
use Oro\Bundle\PimDataGridBundle\Storage\GetRowsQuery;
use Oro\Bundle\PimDataGridBundle\Storage\GetRowsQueryParameters;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @copyright 2018 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductRowDatasource extends Datasource
{
    /** @var ProductQueryBuilderInterface */
    protected $pqb;

    /** @var ProductQueryBuilderFactoryInterface */
    protected $factory;

    /** @var NormalizerInterface */
    protected $normalizer;

    /** @var FilterEntityWithValuesSubscriber */
    protected $filterEntityWithValuesSubscriber;

    /** @var GetRowsQuery */
    private $getRowsQuery;

    /**
     * @param ObjectManager                       $om
     * @param ProductQueryBuilderFactoryInterface $factory
     * @param NormalizerInterface                 $serializer
     * @param FilterEntityWithValuesSubscriber    $filterEntityWithValuesSubscriber
     * @param GetRowsQuery                        $getRowsQuery
     */
    public function __construct(
        ObjectManager $om,
        ProductQueryBuilderFactoryInterface $factory,
        NormalizerInterface $serializer,
        FilterEntityWithValuesSubscriber $filterEntityWithValuesSubscriber,
        GetRowsQuery $getRowsQuery
    ) {
        $this->om = $om;
        $this->factory = $factory;
        $this->normalizer = $serializer;
        $this->filterEntityWithValuesSubscriber = $filterEntityWithValuesSubscriber;
        $this->getRowsQuery = $getRowsQuery;
    }

    /**
     * {@inheritdoc}
     */
    public function getResults()
    {
        $attributesToDisplay = $this->getAttributeCodesToDisplay();
        $this->filterEntityWithValuesSubscriber->configure(
            FilterEntityWithValuesSubscriberConfiguration::filterEntityValues($attributesToDisplay)
        );

        $channel = $this->getConfiguration('scope_code');
        $locale = $this->getConfiguration('locale_code');

        $getRowsQueryParameters = new GetRowsQueryParameters(
            $this->pqb,
            $attributesToDisplay,
            $channel,
            $locale,
            0 // @todo get UserId
        );

        $rows = $this->getRowsQuery->fetch($getRowsQueryParameters);

        $context = [
            'locales'             => [$locale],
            'channels'            => [$channel],
            'data_locale'         => $this->getParameters()['dataLocale'],
            'association_type_id' => $this->getConfiguration('association_type_id', false),
            'current_group_id'    => $this->getConfiguration('current_group_id', false),
        ];
        $normalizedRows = ['data' => []];

        foreach ($rows['rows'] as $row) {
            $normalizedItem = $this->normalizeEntityWithValues($row, $context);
            $normalizedRows['data'][] = new ResultRecord($normalizedItem);
        }

        $normalizedRows['totalRecords'] = $rows['total'];

        return $normalizedRows;
    }

    /**
     * @return ProductQueryBuilderInterface
     */
    public function getProductQueryBuilder()
    {
        return $this->pqb;
    }

    /**
     * @param string $method the query builder creation method
     * @param array  $config the query builder creation config
     *
     * @return Datasource
     */
    protected function initializeQueryBuilder($method, array $config = [])
    {
        $factoryConfig['repository_parameters'] = $config;
        $factoryConfig['repository_method'] = $method;
        $factoryConfig['default_locale'] = $this->getConfiguration('locale_code');
        $factoryConfig['default_scope'] = $this->getConfiguration('scope_code');
        $factoryConfig['limit'] = (int) $this->getConfiguration(PagerExtension::PER_PAGE_PARAM);
        $factoryConfig['from'] = null !== $this->getConfiguration('from', false) ?
            (int) $this->getConfiguration('from', false) : 0;

        $this->pqb = $this->factory->create($factoryConfig);
        $this->qb = $this->pqb->getQueryBuilder();

        return $this;
    }

    /**
     * Normalizes an entity with values with the complete set of fields required to show it.
     *
     * @param Row   $item
     * @param array $context
     *
     * @return array
     */
    private function normalizeEntityWithValues(Row $item, array $context): array
    {
        $defaultNormalizedItem = [
            'id'               => $item->technicalId(),
            'dataLocale'       => $this->getParameters()['dataLocale'],
            'family'           => null,
            'values'           => [],
            'created'          => null,
            'updated'          => null,
            'label'            => null,
            'image'            => null,
            'groups'           => null,
            'enabled'          => null,
            'completeness'     => null,
            'variant_products' => null,
            'document_type'    => null,
        ];

        $normalizedItem = array_merge(
            $defaultNormalizedItem,
            $this->normalizer->normalize($item, 'datagrid', $context)
        );

        return $normalizedItem;
    }

    /**
     * @return array array of attribute codes
     */
    private function getAttributeCodesToDisplay(): array
    {
        $attributeIdsToDisplay = $this->getConfiguration('displayed_attribute_ids');
        $attributes = $this->getConfiguration('attributes_configuration');

        $attributeCodes = [];
        foreach ($attributes as $attribute) {
            if (in_array($attribute['id'], $attributeIdsToDisplay)) {
                $attributeCodes[] = $attribute['code'];
            }
        }

        return $attributeCodes;
    }
}

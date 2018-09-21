<?php

declare(strict_types=1);

namespace Pim\Bundle\DataGridBundle\Storage\ElasticsearchAndSql;

use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductModelInterface;
use Pim\Bundle\DataGridBundle\Storage\GetRowsFromIdentifiersQuery;
use Pim\Bundle\DataGridBundle\Storage\GetRowsQuery;
use Pim\Bundle\DataGridBundle\Storage\GetRowsQueryParameters;

/**
 * @author    Laurent Petard <laurent.petard@akeneo.com>
 * @copyright 2018 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @todo rename and move namespace
 */
class GetRows implements GetRowsQuery
{
    /** @var GetRowsFromIdentifiersQuery */
    private $getProductRowsFromIdentifiers;

    /** @var GetRowsFromIdentifiersQuery */
    private $getProductModelRowsFromIdentifiers;

    /**
     * @param GetRowsFromIdentifiersQuery         $getProductRowsFromIdentifiers
     * @param GetRowsFromIdentifiersQuery         $getProductModelRowsFromIdentifiers
     */
    public function __construct(
        GetRowsFromIdentifiersQuery $getProductRowsFromIdentifiers,
        GetRowsFromIdentifiersQuery $getProductModelRowsFromIdentifiers
    ) {

        $this->getProductRowsFromIdentifiers = $getProductRowsFromIdentifiers;
        $this->getProductModelRowsFromIdentifiers = $getProductModelRowsFromIdentifiers;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(GetRowsQueryParameters $queryParameters): array
    {
        $productIdentifiersCursor = $queryParameters->productQueryBuilder()->execute();
        $identifiers = iterator_to_array($productIdentifiersCursor);
        $productIdentifiers = [];
        $productModelIdentifiers = [];

        foreach ($identifiers as $identifier) {
            if ($identifier->getType() === ProductInterface::class) {
                $productIdentifiers[] = $identifier->getIdentifier();
            } elseif ($identifier->getType() === ProductModelInterface::class) {
                $productModelIdentifiers[] = $identifier->getIdentifier();
            }
        }

        $productRows = $this->getProductRowsFromIdentifiers->fetch($productIdentifiers, $queryParameters);
        $productModelRows = $this->getProductModelRowsFromIdentifiers->fetch($productModelIdentifiers, $queryParameters);

        $rows = array_merge($productRows, $productModelRows);
        $sortedRows = [];
        foreach ($identifiers as $identifier) {
            foreach ($rows as $row) {
                if ($identifier->getIdentifier() === $row->identifier()) {
                    $sortedRows[] = $row;
                }
            }
        }

        // @todo find a better way to give the total number of products/product_models find
        return [
            'rows' => $sortedRows,
            'total' => $productIdentifiersCursor->count()
        ];
    }
}

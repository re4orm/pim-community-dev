<?php

namespace spec\Oro\Bundle\PimDataGridBundle\Storage\ElasticsearchAndSql;

use Akeneo\Pim\Enrichment\Bundle\Elasticsearch\IdentifierResult;
use Akeneo\Pim\Enrichment\Component\Product\Grid\ReadModel\Row;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductModelInterface;
use Akeneo\Pim\Enrichment\Component\Product\Query\ProductQueryBuilderInterface;
use Oro\Bundle\PimDataGridBundle\Storage\Elasticsearch\ProductIdentifierCursor;
use Oro\Bundle\PimDataGridBundle\Storage\GetRowsFromIdentifiersQuery;
use Oro\Bundle\PimDataGridBundle\Storage\GetRowsQuery;
use Oro\Bundle\PimDataGridBundle\Storage\GetRowsQueryParameters;
use PhpSpec\ObjectBehavior;

class GetRowsSpec extends ObjectBehavior
{
    function let(
        GetRowsFromIdentifiersQuery $getProductRowsFromIdentifiers,
        GetRowsFromIdentifiersQuery $getProductModelRowsFromIdentifiers
    ) {
        $this->beConstructedWith($getProductRowsFromIdentifiers, $getProductModelRowsFromIdentifiers);
    }

    function it_is_a_get_rows_query()
    {
        $this->shouldImplement(GetRowsQuery::class);
    }

    function it_fetches_rows(
        $getProductRowsFromIdentifiers,
        $getProductModelRowsFromIdentifiers,
        ProductQueryBuilderInterface $productQueryBuilder,
        GetRowsQueryParameters $queryParameters,
        Row $row1,
        Row $row2,
        Row $row3
    ) {
        $queryParameters->productQueryBuilder()->willReturn($productQueryBuilder);

        $identifiers = [
            new IdentifierResult('product_1', ProductInterface::class),
            new IdentifierResult('product_model_2', ProductModelInterface::class),
            new IdentifierResult('product_3', ProductInterface::class),
        ];

        $productQueryBuilder->execute()->willReturn(new ProductIdentifierCursor($identifiers, 42));

        $row1->identifier()->willReturn('product_1');
        $row2->identifier()->willReturn('product_model_2');
        $row3->identifier()->willReturn('product_3');

        $getProductRowsFromIdentifiers->fetch(['product_1', 'product_3'], $queryParameters)->willReturn([$row1, $row3]);
        $getProductModelRowsFromIdentifiers->fetch(['product_model_2'], $queryParameters)->willReturn([$row2]);

        $this->fetch($queryParameters)->shouldReturn([
            'rows' => [$row1, $row2, $row3],
            'total' => 42,
        ]);
    }
}

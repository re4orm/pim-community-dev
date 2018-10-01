<?php

declare(strict_types=1);

namespace Akeneo\Pim\Enrichment\Bundle\tests\Integration\Storage\Sql\ProductGrid;

use Akeneo\Pim\Enrichment\Component\Product\Grid\ReadModel\Row;
use Akeneo\Pim\Enrichment\Component\Product\Model\ValueCollection;
use Akeneo\Pim\Enrichment\Component\Product\Value\ScalarValue;
use Akeneo\Pim\Structure\Component\AttributeTypes;
use Akeneo\Pim\Structure\Component\Model\Attribute;
use Akeneo\Test\Integration\Configuration;
use Akeneo\Test\Integration\TestCase;
use PHPUnit\Framework\Assert;

class FetchProductRowsFromIdentifiersIntegration extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
    }

    public function test_fetch_products_from_identifiers()
    {
        $userId = $this
            ->get('database_connection')
            ->fetchColumn('SELECT id from oro_user where username = "admin"', [], 0);

        $identifiers = $this->createProducts();
        $query = $this->get('akeneo.pim.enrichment.product.grid.query.fetch_product_rows_from_identifiers');
        $rows = $query($identifiers, ['attribute_text'], 'en_US', 'ecommerce', $userId);

        $sku = new Attribute();
        $sku->setCode('sku');

        $yesNo = new Attribute();
        $yesNo->setCode('a_yes_no');

        $expectedRows = [
            Row::fromProduct(
                'foo',
                'A family A',
                [],
                true,
                new \DateTimeImmutable(),
                new \DateTimeImmutable(),
                new ScalarValue($sku, null, null, 'foo'),
                null,
                10,
                1,
                null,
                new ValueCollection([
                    new ScalarValue($sku, null, null, 'foo'),
                    new ScalarValue($yesNo, null, null, false)
                ])
            ),
            Row::fromProduct(
                'baz',
                null,
                [],
                true,
                new \DateTimeImmutable(),
                new \DateTimeImmutable(),
                null,
                null,
                null,
                2,
                null,
                new ValueCollection([
                    new ScalarValue($sku, null, null, 'baz'),
                ])
            ),
        ];

        Assert::assertCount(count($expectedRows), $rows);
        foreach ($expectedRows as $index => $expectedRow) {
            $this->assertSameRow($expectedRow, $rows[$index]);

        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfiguration(): Configuration
    {
        return $this->catalog->useTechnicalCatalog();
    }

    private function createProducts(): array
    {
        $product1 = $this->get('pim_catalog.builder.product')->createProduct('foo', 'familyA');
        $product2 = $this->get('pim_catalog.builder.product')->createProduct('baz', null);

        $errors = $this->get('validator')->validate($product1);
        Assert::assertCount(0, $errors);
        $errors = $this->get('validator')->validate($product2);
        Assert::assertCount(0, $errors);

        $this->get('pim_catalog.saver.product')->saveAll([$product1, $product2]);

        return ['foo', 'baz'];
    }

    private function assertSameRow(Row $expectedRow, Row $row): void
    {
        Assert::assertSame($expectedRow->identifier(), $row->identifier());
        Assert::assertSame($expectedRow->parent(), $row->parent());
        Assert::assertSame($expectedRow->image(), $row->image());
        Assert::assertSame($expectedRow->completeness(), $row->completeness());
        Assert::assertSame($expectedRow->childrenCompleteness(), $row->childrenCompleteness());
        Assert::assertSame($expectedRow->checked(), $row->checked());
        Assert::assertSame($expectedRow->groups(), $row->groups());
        Assert::assertSame($expectedRow->family(), $row->family());
        Assert::assertSame($expectedRow->technicalId(), $row->technicalId());
        Assert::assertSame($expectedRow->searchId(), $row->searchId());
        Assert::assertSame($expectedRow->documentType(), $row->documentType());
        Assert::assertNotNull($row->updated());
        Assert::assertNotNull($row->created());

        null !== $expectedRow->label() ?
            Assert::assertTrue($expectedRow->label()->isEqual($row->label())):
            Assert::assertNull($row->label());

        foreach ($expectedRow->values() as $value) {
            Assert::assertNotNull($row->values()->getSame($value));
        }
    }
}


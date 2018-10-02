<?php

declare(strict_types=1);

namespace Akeneo\Pim\Enrichment\Bundle\tests\Integration\Storage\Sql\ProductGrid;

use Akeneo\Pim\Enrichment\Component\Product\Grid\ReadModel\Row;
use Akeneo\Pim\Enrichment\Component\Product\Model\ValueCollection;
use Akeneo\Pim\Enrichment\Component\Product\Value\MediaValue;
use Akeneo\Pim\Enrichment\Component\Product\Value\ScalarValue;
use Akeneo\Pim\Structure\Component\AttributeTypes;
use Akeneo\Pim\Structure\Component\Model\Attribute;
use Akeneo\Test\Integration\Configuration;
use Akeneo\Test\Integration\TestCase;
use Akeneo\Tool\Component\FileStorage\Model\FileInfo;
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

        [$foo, $baz] = $this->createProducts();
        $query = $this->get('akeneo.pim.enrichment.product.grid.query.fetch_product_rows_from_identifiers');
        $rows = $query(['baz', 'foo'], ['sku', 'a_localizable_image', 'a_scopable_image'], 'en_US', 'ecommerce', $userId);

        $sku = new Attribute();
        $sku->setCode('sku');

        $yesNo = new Attribute();
        $yesNo->setCode('a_yes_no');

        $anImage = new Attribute();
        $anImage->setCode('an_image');

        $aLocalizableImage = new Attribute();
        $aLocalizableImage->setCode('a_localizable_image');
        $aLocalizableImage->setLocalizable(true);

        $aScopableImage = new Attribute();
        $aScopableImage->setCode('a_scopable_image');
        $aScopableImage->setScopable(true);

        $akeneoImage = current($this
            ->get('akeneo_file_storage.repository.file_info')
            ->findAll($this->getFixturePath('akeneo.jpg')));


        $expectedRows = [
            Row::fromProduct(
                'foo',
                'A family A',
                [],
                true,
                $foo->getCreated(),
                $foo->getUpdated(),
                new ScalarValue($sku, null, null, 'foo'),
                new MediaValue($anImage, null, null, $akeneoImage),
                15,
                $foo->getId(),
                null,
                new ValueCollection([
                    new ScalarValue($sku, null, null, 'foo'),
                    new MediaValue($anImage, null, null, $akeneoImage)
                ])
            ),
            Row::fromProduct(
                'baz',
                null,
                [],
                true,
                $foo->getCreated(),
                $foo->getUpdated(),
                null,
                null,
                null,
                $baz->getId(),
                null,
                new ValueCollection([
                    new ScalarValue($sku, null, null, 'baz'),
                    new MediaValue($aLocalizableImage, null, 'en_US', $akeneoImage),
                    new MediaValue($aLocalizableImage, null, 'fr_FR', $akeneoImage),
                    new MediaValue($aScopableImage, 'ecommerce', null, $akeneoImage),
                    new MediaValue($aScopableImage, 'tablet', null, $akeneoImage),
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
        $this->get('pim_catalog.updater.product')->update($product1, [
            'values' => [
                'an_image' => [
                    ['data' => $this->getFixturePath('akeneo.jpg'), 'locale' => null, 'scope' => null],
                ]
            ]
        ]);

        $product2 = $this->get('pim_catalog.builder.product')->createProduct('baz', null);
        $this->get('pim_catalog.updater.product')->update($product2, [
            'values' => [
                'a_localizable_image' => [
                    ['data' => $this->getFixturePath('akeneo.jpg'), 'locale' => 'en_US', 'scope' => null],
                    ['data' => $this->getFixturePath('akeneo.jpg'), 'locale' => 'fr_FR', 'scope' => null],
                ],
                'a_scopable_image' => [
                    ['data' => $this->getFixturePath('akeneo.jpg'), 'locale' => null, 'scope' => 'ecommerce'],
                    ['data' => $this->getFixturePath('akeneo.jpg'), 'locale' => null, 'scope' => 'tablet'],
                ],
                'a_yes_no' => [
                    ['data' => true, 'locale' => null, 'scope' => null]
                ]
            ]
        ]);

        $errors = $this->get('validator')->validate($product1);
        Assert::assertCount(0, $errors);
        $errors = $this->get('validator')->validate($product2);
        Assert::assertCount(0, $errors);

        $this->get('pim_catalog.saver.product')->saveAll([$product1, $product2]);

        return [$product1, $product2];
    }

    private function assertSameRow(Row $expectedRow, Row $row): void
    {
        Assert::assertSame($expectedRow->identifier(), $row->identifier());
        Assert::assertSame($expectedRow->parent(), $row->parent());
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

        null !== $expectedRow->image() ?
            Assert::assertTrue($expectedRow->image()->isEqual($row->image())):
            Assert::assertNull($row->image());

        null !== $expectedRow->label() ?
            Assert::assertTrue($expectedRow->label()->isEqual($row->label())):
            Assert::assertNull($row->label());

        Assert::assertSame($expectedRow->values()->count(), $row->values()->count());
        foreach ($expectedRow->values() as $value) {
            Assert::assertNotNull($row->values()->getSame($value));
        }
    }
}


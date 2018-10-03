<?php

declare(strict_types=1);

namespace Akeneo\Pim\Enrichment\Bundle\tests\Integration\Storage\Sql\;

use PHPUnit\Framework\Assert;
use Psr\Container\ContainerInterface;

final class ProductGridFixturesLoader
{
    /** @var  ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function createProductAndProductModels()
    {
        return $this->createProducts();
    }


    private function createProducts(): array
    {
        $product1 = $this->container->get('pim_catalog.builder.product')->createProduct('foo', 'familyA');
        $this->container->get('pim_catalog.updater.product')->update($product1, [
            'values' => [
                'an_image' => [
                    ['data' => $this->getFixturePath('akeneo.jpg'), 'locale' => null, 'scope' => null],
                ]
            ]
        ]);

        $product2 = $this->container->get('pim_catalog.builder.product')->createProduct('baz', null);
        $this->container->get('pim_catalog.updater.product')->update($product2, [
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

        $errors = $this->container->get('validator')->validate($product1);
        Assert::assertCount(0, $errors);
        $errors = $this->container->get('validator')->validate($product2);
        Assert::assertCount(0, $errors);

        $this->container->get('pim_catalog.saver.product')->saveAll([$product1, $product2]);

        return [$product1, $product2];
    }
}

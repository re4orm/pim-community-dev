<?php

namespace spec\Oro\Bundle\PimDataGridBundle\Normalizer;

use Akeneo\Channel\Component\Model\ChannelInterface;
use Akeneo\Channel\Component\Model\LocaleInterface;
use Akeneo\Pim\Enrichment\Bundle\Filter\CollectionFilterInterface;
use Akeneo\Pim\Enrichment\Component\Product\Grid\ReadModel\Row;
use Akeneo\Pim\Enrichment\Component\Product\Model\Completeness;
use Akeneo\Pim\Enrichment\Component\Product\Model\GroupInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\GroupTranslationInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductModelInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ValueCollection;
use Akeneo\Pim\Enrichment\Component\Product\Model\ValueCollectionInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ValueInterface;
use Akeneo\Pim\Enrichment\Component\Product\Value\MediaValue;
use Akeneo\Pim\Enrichment\Component\Product\Value\ScalarValue;
use Akeneo\Pim\Structure\Component\Model\Attribute;
use Akeneo\Pim\Structure\Component\Model\FamilyInterface;
use Akeneo\Pim\Structure\Component\Model\FamilyTranslationInterface;
use Akeneo\Tool\Component\FileStorage\Model\FileInfo;
use Oro\Bundle\PimDataGridBundle\Normalizer\ProductNormalizer;
use PhpSpec\ObjectBehavior;
use Pim\Bundle\EnrichBundle\Normalizer\ImageNormalizer;
use Prophecy\Argument;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ProductNormalizerSpec extends ObjectBehavior
{
    function let(NormalizerInterface $normalizer, CollectionFilterInterface $filter, ImageNormalizer $imageNormalizer)
    {
        $this->beConstructedWith($filter, $imageNormalizer);

        $normalizer->implement(NormalizerInterface::class);
        $this->setNormalizer($normalizer);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(ProductNormalizer::class);
        $this->shouldBeAnInstanceOf(NormalizerAwareInterface::class);
    }

    function it_is_a_normalizer()
    {
        $this->shouldImplement('Symfony\Component\Serializer\Normalizer\NormalizerInterface');
    }

    function it_supports_datagrid_format_and_product_value()
    {
        $product = Row::fromProduct(
            'identifier',
            'family label',
            ['group_1', 'group_2'],
            true,
            new \DateTime('2018-05-23 15:55:50', new \DateTimeZone('UTC')),
            new \DateTime('2018-05-23 15:55:50', new \DateTimeZone('UTC')),
            null,
            null,
            90,
            1,
            'parent_code',
            new ValueCollection([])
        );

        $this->supportsNormalization($product, 'datagrid')->shouldReturn(true);
        $this->supportsNormalization($product, 'other_format')->shouldReturn(false);
        $this->supportsNormalization(new \stdClass(), 'other_format')->shouldReturn(false);
        $this->supportsNormalization(new \stdClass(), 'datagrid')->shouldReturn(false);
    }

    function it_normalizes_a_product_with_label(
        $normalizer,
        $filter,
        $imageNormalizer
    ) {
        $scalarAttribute = new Attribute();
        $scalarAttribute->setCode('scalar_attribute');

        $mediaAttribute = new Attribute();
        $mediaAttribute->setCode('media_attribute');
        $values = new ValueCollection([new ScalarValue($scalarAttribute, null, null, 'data')]);

        $row = Row::fromProduct(
            'identifier',
            'family label',
            ['group_1', 'group_2'],
            true,
            new \DateTime('2018-05-23 15:55:50', new \DateTimeZone('UTC')),
            new \DateTime('2018-05-23 15:55:50', new \DateTimeZone('UTC')),
            new ScalarValue($scalarAttribute, null, null, 'data'),
            new MediaValue($mediaAttribute, null, null, new FileInfo()),
            90,
            1,
            'parent_code',
            $values
        );

        $context = [
            'filter_types' => ['pim.transform.product_value.structured'],
            'locales'      => ['en_US'],
            'channels'     => ['ecommerce'],
            'data_locale'  => 'en_US',
        ];

        $filter->filterCollection($values, 'pim.transform.product_value.structured', $context)->willReturn($values);

        $normalizer->normalize($values, 'datagrid', $context)->willReturn([
            'scalar_attribute' => [
                [
                    'locale' => null,
                    'scope'  => null,
                    'data'   => 'data',
                ]
            ]
        ]);

        $normalizer->normalize($row->created(), 'datagrid', $context)->willReturn('2018-05-23T15:55:50+01:00');
        $normalizer->normalize($row->updated(), 'datagrid', $context)->willReturn('2018-05-23T15:55:50+01:00');

        $imageNormalizer->normalize($row->image(), 'en_US')->willReturn([
            'filePath'         => '/p/i/m/4/all.png',
            'originalFileName' => 'all.png',
        ]);

        $data = [
            'identifier'   => 'identifier',
            'family'       => 'family label',
            'groups'       => 'group_1,group_2',
            'enabled'      => true,
            'values'       => [
                'scalar_attribute' => [
                    [
                        'locale' => null,
                        'scope'  => null,
                        'data'   => 'data',
                    ]
                ]
            ],
            'created'      => '2018-05-23T15:55:50+01:00',
            'updated'      => '2018-05-23T15:55:50+01:00',
            'label'        => 'data',
            'image'        => [
                'filePath'         => '/p/i/m/4/all.png',
                'originalFileName' => 'all.png',
            ],
            'completeness' => 90,
            'document_type' => 'product',
            'technical_id' => 1,
            'search_id' => 'product_1',
            'is_checked' => true,
            'complete_variant_product' => [],
            'parent' => 'parent_code',
        ];

        $this->normalize($row, 'datagrid', $context)->shouldReturn($data);
    }

    function it_normalizes_a_product_without_label(
        $normalizer,
        $filter,
        $imageNormalizer
    ) {
        $scalarAttribute = new Attribute();
        $scalarAttribute->setCode('scalar_attribute');

        $mediaAttribute = new Attribute();
        $mediaAttribute->setCode('media_attribute');
        $values = new ValueCollection([new ScalarValue($scalarAttribute, null, null, 'data')]);

        $row = Row::fromProduct(
            'identifier',
            'family label',
            ['group_1', 'group_2'],
            true,
            new \DateTime('2018-05-23 15:55:50', new \DateTimeZone('UTC')),
            new \DateTime('2018-05-23 15:55:50', new \DateTimeZone('UTC')),
            null,
            new MediaValue($mediaAttribute, null, null, new FileInfo()),
            90,
            1,
            'parent_code',
            $values
        );

        $context = [
            'filter_types' => ['pim.transform.product_value.structured'],
            'locales'      => ['en_US'],
            'channels'     => ['ecommerce'],
            'data_locale'  => 'en_US',
        ];

        $filter->filterCollection($values, 'pim.transform.product_value.structured', $context)->willReturn($values);

        $normalizer->normalize($values, 'datagrid', $context)->willReturn([
            'scalar_attribute' => [
                [
                    'locale' => null,
                    'scope'  => null,
                    'data'   => 'data',
                ]
            ]
        ]);

        $normalizer->normalize($row->created(), 'datagrid', $context)->willReturn('2018-05-23T15:55:50+01:00');
        $normalizer->normalize($row->updated(), 'datagrid', $context)->willReturn('2018-05-23T15:55:50+01:00');

        $imageNormalizer->normalize($row->image(), 'en_US')->willReturn([
            'filePath'         => '/p/i/m/4/all.png',
            'originalFileName' => 'all.png',
        ]);

        $data = [
            'identifier'   => 'identifier',
            'family'       => 'family label',
            'groups'       => 'group_1,group_2',
            'enabled'      => true,
            'values'       => [
                'scalar_attribute' => [
                    [
                        'locale' => null,
                        'scope'  => null,
                        'data'   => 'data',
                    ]
                ]
            ],
            'created'      => '2018-05-23T15:55:50+01:00',
            'updated'      => '2018-05-23T15:55:50+01:00',
            'label'        => 'identifier',
            'image'        => [
                'filePath'         => '/p/i/m/4/all.png',
                'originalFileName' => 'all.png',
            ],
            'completeness' => 90,
            'document_type' => 'product',
            'technical_id' => 1,
            'search_id' => 'product_1',
            'is_checked' => true,
            'complete_variant_product' => [],
            'parent' => 'parent_code',
        ];

        $this->normalize($row, 'datagrid', $context)->shouldReturn($data);
    }
}

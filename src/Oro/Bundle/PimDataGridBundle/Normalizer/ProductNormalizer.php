<?php

namespace Oro\Bundle\PimDataGridBundle\Normalizer;

use Akeneo\Pim\Enrichment\Bundle\Filter\CollectionFilterInterface;
use Akeneo\Pim\Enrichment\Component\Product\Grid\ReadModel\Row;
use Akeneo\Pim\Enrichment\Component\Product\Model\EntityWithFamilyInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ValueCollectionInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ValueInterface;
use Pim\Bundle\EnrichBundle\Normalizer\ImageNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Webmozart\Assert\Assert;

/**
 * Product normalizer for datagrid
 *
 * @author    Marie Bochu <marie.bochu@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    /** @var CollectionFilterInterface */
    private $filter;

    /** @var ImageNormalizer */
    protected $imageNormalizer;

    /**
     * @param CollectionFilterInterface $filter
     * @param ImageNormalizer           $imageNormalizer
     */
    public function __construct(
        CollectionFilterInterface $filter,
        ImageNormalizer $imageNormalizer
    ) {
        $this->filter = $filter;
        $this->imageNormalizer = $imageNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($product, $format = null, array $context = [])
    {
        Assert::isInstanceOf($this->normalizer, NormalizerInterface::class);
        Assert::isInstanceOf($product, Row::class);
        Assert::eq($format, 'datagrid');

        $context = array_merge(['filter_types' => ['pim.transform.product_value.structured']], $context);
        $data = [];

        $data['identifier'] = $product->identifier();
        $data['family'] = $product->family();
        $data['groups'] = implode(',', $product->groups());
        $data['enabled'] = $product->enabled();
        $data['values'] = $this->normalizeValues($product->values(), $context);
        $data['created'] = $this->normalizer->normalize($product->created(), $format, $context);
        $data['updated'] = $this->normalizer->normalize($product->updated(), $format, $context);
        $data['label'] = null === $product->label() || empty($product->label()->getData()) ?
            $product->identifier() : $product->label()->getData();
        $data['image'] = $this->imageNormalizer->normalize($product->image(), $context['data_locale']);
        $data['completeness'] = $product->completeness();
        $data['document_type'] = $product->documentType();
        $data['technical_id'] = $product->technicalId();
        $data['search_id'] = $product->searchId();
        $data['is_checked'] = $product->checked();
        $data['complete_variant_product'] = $product->childrenCompleteness();
        $data['parent'] = $product->parent();

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Row && 'datagrid' === $format;
    }

    /**
     * Normalize the values of the product
     *
     * @param ValueCollectionInterface $values
     * @param array                    $context
     *
     * @return array
     */
    private function normalizeValues(ValueCollectionInterface $values, array $context = [])
    {
        foreach ($context['filter_types'] as $filterType) {
            $values = $this->filter->filterCollection($values, $filterType, $context);
        }

        $data = $this->normalizer->normalize($values, 'datagrid', $context);

        return $data;
    }
}

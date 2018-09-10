<?php

namespace Oro\Bundle\PimDataGridBundle\Normalizer\Product;

use Akeneo\Pim\Enrichment\Component\Product\Model\ReferenceDataInterface;
use Akeneo\Pim\Enrichment\Component\Product\Repository\ReferenceDataRepositoryResolverInterface;
use Akeneo\Pim\Enrichment\Component\Product\Value\ReferenceDataCollectionValueInterface;
use Akeneo\Tool\Component\StorageUtils\Repository\CachedObjectRepositoryInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author    Marie Bochu <marie.bochu@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ReferenceDataCollectionNormalizer implements NormalizerInterface
{
    /** @var CachedObjectRepositoryInterface */
    protected $attributeRepository;

    /** @var ReferenceDataRepositoryResolverInterface */
    protected $repositoryResolver;

    public function __construct(
        CachedObjectRepositoryInterface $attributeRepository,
        ReferenceDataRepositoryResolverInterface $repositoryResolver
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->repositoryResolver = $repositoryResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($referenceDataCollectionValue, $format = null, array $context = [])
    {
        $attribute = $this->attributeRepository->findOneByIdentifier($referenceDataCollectionValue->getAttributeCode());

        if (null === $attribute) {
            return [];
        }

        $repository = $this->repositoryResolver->resolve($attribute->getReferenceDataName());

        $labels = [];
        foreach ($referenceDataCollectionValue->getData() as $referenceDataCode) {
            $referenceData = $repository->findOneBy(['code' => $referenceDataCode]);

            if (null !== $referenceData) {
                $labels[] = $this->getLabel($referenceData);
            }
        }

        sort($labels);

        return [
            'locale' => $referenceDataCollectionValue->getLocaleCode(),
            'scope'  => $referenceDataCollectionValue->getScopeCode(),
            'data'   => implode(', ', $labels),
        ];
    }

    /**
     *
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return 'datagrid' === $format && $data instanceof ReferenceDataCollectionValueInterface;
    }

    /**
     * Get the reference data label (or the [code] is no label is present).
     *
     * @param ReferenceDataInterface $referenceData
     *
     * @return string
     */
    protected function getLabel(ReferenceDataInterface $referenceData)
    {
        if (null !== $labelProperty = $referenceData::getLabelProperty()) {
            $getter = 'get' . ucfirst($labelProperty);
            $label = $referenceData->$getter();

            if (!empty($label)) {
                return $label;
            }
        }

        return sprintf('[%s]', $referenceData->getCode());
    }
}

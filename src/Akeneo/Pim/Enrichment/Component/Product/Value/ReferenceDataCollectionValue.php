<?php

namespace Akeneo\Pim\Enrichment\Component\Product\Value;

use Akeneo\Pim\Enrichment\Component\Product\Model\AbstractValue;
use Akeneo\Pim\Enrichment\Component\Product\Model\ReferenceDataInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ValueInterface;
use Akeneo\Pim\Structure\Component\Model\AttributeInterface;

/**
 * Product value for a collection of reference data
 *
 * @author    Marie Bochu <marie.bochu@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ReferenceDataCollectionValue extends AbstractValue implements
    ReferenceDataCollectionValueInterface
{
    /** @var ReferenceDataInterface[] */
    protected $data;

    protected function __construct(string $attributeCode, ?array $data = [], ?string $scopeCode, ?string $localeCode)
    {
        parent::__construct($attributeCode, $data, $scopeCode, $localeCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function getReferenceDataCodes() : array
    {
        return $this->data !== null ? $this->data : [];
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->data !== null ? implode(', ', $codes) : '';
    }

    /**
     * {@inheritdoc}
     */
    public function isEqual(ValueInterface $value)
    {
        if (!$value instanceof ReferenceDataCollectionValueInterface ||
            $this->getScope() !== $value->getScope() ||
            $this->getLocale() !== $value->getLocale()) {
            return false;
        }

        $comparedRefDataCollection = $value->getData();
        $thisRefDataCollection = $this->getData();

        if (count($comparedRefDataCollection) !== count($thisRefDataCollection)) {
            return false;
        }

        foreach ($comparedRefDataCollection as $comparedRefData) {
            $refDataFound = false;
            foreach ($thisRefDataCollection as $thisRefData) {
                if ($comparedRefData->getCode() === $thisRefData->getCode()) {
                    $refDataFound = true;
                    break;
                }
            }

            if (!$refDataFound) {
                return false;
            }
        }

        return true;
    }
}

<?php

declare(strict_types=1);

namespace Oro\Bundle\PimDataGridBundle\Storage\Sql;

use Akeneo\Pim\Enrichment\Component\Product\Model\ValueCollection;
use Akeneo\Pim\Enrichment\Component\Product\Grid\ReadModel;
use Oro\Bundle\PimDataGridBundle\Normalizer\IdEncoder;
use Oro\Bundle\PimDataGridBundle\Storage\GetRowsFromIdentifiersQuery;
use Oro\Bundle\PimDataGridBundle\Storage\GetRowsQueryParameters;

/**
 * @copyright 2018 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class GetProductModelRowsFromIdentifiers implements GetRowsFromIdentifiersQuery
{
    /**
     * @inheritDoc
     */
    public function fetch(array $identifiers, GetRowsQueryParameters $queryParameters): array
    {
        $rows = [];
        foreach ($identifiers as $identifier) {
            $id = rand(1, 1000);
            $rows[] = new ReadModel\Row(
                $identifier,
                'family',
                ['group_1', 'group_2'],
                true,
                new \DateTime(),
                new \DateTime(),
                null,
                null,
                10,
                IdEncoder::PRODUCT_MODEL_TYPE,
                $id,
                IdEncoder::encode(IdEncoder::PRODUCT_MODEL_TYPE, $id),
                true,
                true,
                null,
                new ValueCollection([])
            );
        }

        return $rows;
    }
}

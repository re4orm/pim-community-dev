<?php

declare(strict_types=1);

namespace Oro\Bundle\PimDataGridBundle\Storage;

use Akeneo\Pim\Enrichment\Component\Product\Grid\ReadModel\Row;

/**
 * @author    Laurent Petard <laurent.petard@akeneo.com>
 * @copyright 2018 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @todo rename and move namespace
 */
interface GetRowsQuery
{
    /**
     * @param GetRowsQueryParameters $queryParameters
     *
     * @return Row[]
     */
    public function fetch(GetRowsQueryParameters $queryParameters): array;
}

<?php

declare(strict_types=1);

namespace Pim\Bundle\DataGridBundle\Storage;

/**
 * @copyright 2018 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @todo Rename and move namespace
 */
interface GetRowsFromIdentifiersQuery
{
    /**
     * @param array                  $identifiers
     * @param GetRowsQueryParameters $queryParameters
     *
     * @return Row[]
     */
    public function fetch(array $identifiers, GetRowsQueryParameters $queryParameters): array;
}

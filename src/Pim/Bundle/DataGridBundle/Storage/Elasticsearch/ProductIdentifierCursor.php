<?php

declare(strict_types=1);

namespace Pim\Bundle\DataGridBundle\Storage\Elasticsearch;

use Akeneo\Tool\Component\StorageUtils\Cursor\CursorInterface;

/**
 * @author    Laurent Petard <laurent.petard@akeneo.com>
 * @copyright 2018 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductIdentifierCursor implements CursorInterface
{
    /** @var \ArrayIterator $identifiers */
    private $identifiers;

    /** @var int */
    private $totalCount;

    /**
     * @param array $identifiers
     * @param int   $totalCount
     */
    public function __construct(array $identifiers, int $totalCount)
    {
        $this->identifiers = new \ArrayIterator($identifiers);
        $this->totalCount = $totalCount;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->totalCount;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->identifiers->current();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->identifiers->key();
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->identifiers->next();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->identifiers->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->identifiers->valid();
    }
}

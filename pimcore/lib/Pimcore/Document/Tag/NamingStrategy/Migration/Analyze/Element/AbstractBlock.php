<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\Element;

/**
 * Represents a block element (block, areablock)
 */
abstract class AbstractBlock extends AbstractElement
{
    /**
     * @var int[]
     */
    private $childIndexes = [];

    public function __construct($name, $type, array $data, AbstractBlock $parent = null)
    {
        parent::__construct($name, $type, $parent);

        $this->childIndexes = $this->resolveChildIndexes($data);
    }

    /**
     * Get a list of available child indexes from block data
     *
     * @param array $data
     *
     * @return array
     */
    abstract protected function resolveChildIndexes(array $data): array;

    /**
     * Get available indexes
     *
     * @return array
     */
    public function getChildIndexes()
    {
        return $this->childIndexes;
    }

    /**
     * Check if index exists in block
     *
     * @param int $index
     *
     * @return bool
     */
    public function hasChildIndex(int $index)
    {
        return in_array($index, $this->childIndexes, true);
    }

    public function getEditableMatchString(): string
    {
        $parts = [];
        foreach ($this->getParents() as $parent) {
            $parts[] = $parent->getName();
        }

        $parts[] = $this->getName();

        return implode('_', $parts);
    }
}

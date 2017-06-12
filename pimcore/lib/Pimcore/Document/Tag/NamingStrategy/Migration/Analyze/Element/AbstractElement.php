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

use Pimcore\Document\Tag\Block\BlockName;
use Pimcore\Document\Tag\Block\BlockState;
use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\ElementTree;
use Pimcore\Document\Tag\NamingStrategy\NamingStrategyInterface;

/**
 * Represents all document editables (blocks + other editables)
 */
abstract class AbstractElement
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $realName;

    /**
     * @var string
     */
    private $type;

    /**
     * @var int|null
     */
    private $index;

    /**
     * @var AbstractBlock|null
     */
    private $parent;

    /**
     * @var AbstractBlock[]
     */
    private $parents = [];

    public function __construct(string $name, string $type, AbstractBlock $parent = null)
    {
        $this->name = $name;
        $this->type = $type;

        // process and validate parent
        $this->setParent($parent);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRealName(): string
    {
        return $this->realName;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return int|null
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @return AbstractBlock|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return AbstractBlock[]
     */
    public function getParents(): array
    {
        return $this->parents;
    }

    public function getLevel(): int
    {
        return count($this->getParents());
    }

    public function getBlockState(): BlockState
    {
        $blockState = new BlockState();
        foreach ($this->getParents() as $parent) {
            $blockState->pushBlock(BlockName::createFromNames($parent->getName(), $parent->getRealName()));

            if (null !== $parent->getIndex()) {
                $blockState->pushIndex($parent->getIndex());
            }
        }

        if (null !== $this->getIndex()) {
            $blockState->pushIndex($this->getIndex());
        }

        return $blockState;
    }

    public function getNameForStrategy(NamingStrategyInterface $strategy): string
    {
        $blockState = $this->getBlockState();

        return $strategy->buildTagName($this->getRealName(), $this->getType(), $blockState);
    }

    private function setParent(AbstractBlock $parent = null)
    {
        // no parent (root level): we have no index and the realName is the
        // same as the full name
        if (null === $parent) {
            $this->realName = $this->name;
            $this->index    = null;
            $this->parent   = null;
            $this->parents  = [];

            return;
        }

        // find parent names and build pattern to match against
        // e.g.:
        //
        // input:       accordionAB_AB-BAB3_AB-B-ABAB_AB-BAB33_13_1_2
        // realName:    accordion
        // indexes:     3_1_2
        //
        // the string between the real name and the index suffix is built
        // from parent block names

        $parentList      = $this->buildParentList($parent);
        $parentIndexes   = [];
        $parentNameParts = [];

        foreach ($parentList as $currentParent) {
            $parentNameParts[] = $currentParent->getName();

            if (null !== $currentParentIndex = $currentParent->getIndex()) {
                $parentIndexes[] = $currentParentIndex;
            }
        }

        /*
        if (null === $parent->getIndex()) {
            throw new \LogicException(sprintf(
                'The parent element for "%s" has no index. Parent is "%s"',
                $this->name,
                $parent->getName()
            ));
        }
        */

        $parentNameParts = array_reverse($parentNameParts);
        $parentNames     = implode('_', array_reverse($parentNameParts));
        $pattern         = ElementTree::buildNameMatchingPattern($parentNames);

        if (!preg_match_all($pattern, $this->name, $matches, PREG_SET_ORDER)) {
            throw new \LogicException(sprintf(
                'Failed to match "%s" against pattern "%s"',
                $this->name, $pattern
            ));
        }

        if (count($matches) === 0) {
            throw new \LogicException(sprintf(
                'No matches found for name "%s" and pattern "%s"',
                $this->name, $pattern
            ));
        } elseif (count($matches) > 1) {
            throw new \LogicException(sprintf(
                'Ambiguous amount of %s matches found for name "%s" and pattern "%s"',
                count($matches),
                $this->name, $pattern
            ));
        }

        $match    = $matches[0];
        $realName = (string)$match['realName'];
        $index    = null;

        // get index from index suffix and check if remaining indexes match parent indexes
        if (!empty($match['indexes'])) {
            $indexes = explode('_', $match['indexes']);
            $indexes = array_map(function ($index) {
                return (int)$index;
            }, $indexes);

            $index = array_pop($indexes);

            // check if remaining indexes match with parent indexes
            // e.g. indexes resulted in 3_2_1 -> our index is 1 and we expect
            // parent indexes to be [3, 2]
            if ($indexes !== $parentIndexes) {
                throw new \LogicException(sprintf(
                    'Parent indexes do not match index hierarchy for block "%s". Indexes: %s, Parent: %s',
                    $this->name,
                    json_encode($indexes),
                    json_encode($parentIndexes)
                ));
            }
        }

        if (null === $index) {
            throw new \LogicException(sprintf(
                'Nested element "%s" is expected to have an index, but no index was found',
                $this->name
            ));
        }

        /** @var int $index */
        if (!$parent->hasChildIndex($index)) {
            throw new \LogicException(sprintf(
                'Element "%s" has index %d, but parent "%s" does not have this index in its child list',
                $this->name,
                $index,
                $parent->getName()
            ));
        }

        $this->parent   = $parent;
        $this->parents  = $parentList;
        $this->realName = $realName;
        $this->index    = $index;
    }

    /**
     * @param AbstractBlock|null $parent
     *
     * @return AbstractBlock[]
     */
    private function buildParentList(AbstractBlock $parent): array
    {
        $parents = [];
        while (null !== $parent) {
            $parents[] = $parent;
            $parent    = $parent->getParent();
        }

        $parents = array_reverse($parents);

        return $parents;
    }
}

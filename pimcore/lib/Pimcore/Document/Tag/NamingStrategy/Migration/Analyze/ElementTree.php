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

namespace Pimcore\Document\Tag\NamingStrategy\Migration\Analyze;

use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\Element\AbstractBlock;
use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\Element\AbstractElement;
use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\Element\Areablock;
use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\Element\Block;
use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\Element\Editable;
use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\Exception\BuildEditableException;
use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\Exception\LogicException;
use Pimcore\Model\Document;

final class ElementTree
{
    /**
     * @var Document\PageSnippet
     */
    private $document;

    /**
     * @var EditableConflictResolver
     */
    private $editableConflictResolver;

    /**
     * Map of elements by name => type
     *
     * @var array
     */
    private $map = [];

    /**
     * Element data indexed by name
     *
     * @var array
     */
    private $data = [];

    /**
     * @var array
     */
    private $inheritedElements = [];

    /**
     * @var AbstractElement[]
     */
    private $elements = [];

    /**
     * @var bool
     */
    private $processed = false;

    /**
     * @var array
     */
    private $blockTypes = [
        'block'     => Block::class,
        'areablock' => Areablock::class
    ];

    /**
     * @param Document\PageSnippet $document
     * @param EditableConflictResolver $editableConflictResolver
     */
    public function __construct(
        Document\PageSnippet $document,
        EditableConflictResolver $editableConflictResolver
    )
    {
        $this->document                 = $document;
        $this->editableConflictResolver = $editableConflictResolver;
    }

    /**
     * Add an element mapping
     *
     * @param string $name
     * @param string $type
     * @param mixed $data
     * @param bool $inherited
     */
    public function add(string $name, string $type, $data, bool $inherited = false)
    {
        // do not overwrite document elements with inherited ones
        if ($inherited && isset($this->map[$name])) {
            return;
        }

        $this->map[$name]  = $type;
        $this->data[$name] = $data;

        if ($inherited && !in_array($name, $this->inheritedElements)) {
            $this->inheritedElements[] = $name;
        }

        $this->reset();
    }

    /**
     * @return AbstractElement[]
     */
    public function getElements(): array
    {
        $this->process();

        return $this->elements;
    }

    /**
     * @param string $name
     *
     * @return AbstractElement
     */
    public function getElement(string $name): AbstractElement
    {
        $this->process();

        if (!isset($this->elements[$name])) {
            throw new \InvalidArgumentException(sprintf('Element with name "%s" does not exist', $name));
        }

        return $this->elements[$name];
    }

    private function reset()
    {
        $this->processed = false;
        $this->elements  = [];
    }

    private function process()
    {
        if ($this->processed) {
            return;
        }

        ksort($this->map);

        $this->inheritedElements = array_unique($this->inheritedElements);
        sort($this->inheritedElements);

        $blockNames            = $this->getBlockNames();
        $blockParentCandidates = $this->findBlockParentCandidates($blockNames);
        $blockParents          = $this->resolveBlockParents($blockParentCandidates);
        $blocks                = $this->buildBlocks($blockNames, $blockParents);
        $editables             = $this->buildEditables($this->getBlocksSortedByLevel($blocks));

        // just add not inherited elements to elements array
        $this->elements = [];
        foreach (array_merge($blocks, $editables) as $name => $element) {
            if (in_array($name, $this->inheritedElements)) {
                continue;
            }

            $this->elements[$name] = $element;
        }

        $this->processed = true;
    }

    /**
     * @param AbstractBlock[] $blocks
     *
     * @return array
     */
    private function buildEditables(array $blocks): array
    {
        $editables = [];
        foreach ($this->map as $name => $type) {
            if ($this->isBlock($type)) {
                continue;
            }

            $editables[$name] = $this->buildEditable($name, $blocks);
        }

        return $editables;
    }

    /**
     * @param string $name
     * @param AbstractBlock[] $blocks
     *
     * @return Editable
     */
    private function buildEditable(string $name, array $blocks): Editable
    {
        $parentBlocks = [];
        foreach ($blocks as $block) {
            $pattern = self::buildNameMatchingPattern($block->getEditableMatchString());

            if (preg_match($pattern, $name, $matches)) {
                $parentBlocks[] = $block;
            }
        }

        // no parent blocks -> root element without parent
        if (count($parentBlocks) === 0) {
            return new Editable($name, $this->map[$name], $this->data[$name]);
        }

        /** @var Editable[] $editables */
        $editables = [];

        $errors = [];
        foreach ($parentBlocks as $parentBlock) {
            try {
                $editables[] = new Editable($name, $this->map[$name], $this->data[$name], $parentBlock);
            } catch (LogicException $e) {
                // noop - failed to build editable (e.g. because indexes do not match)
                $errors[] = $e;
            }
        }

        if (count($editables) === 0) {
            $exception = new BuildEditableException(sprintf(
                'Failed to build an editable for element "%s"',
                $name
            ));

            $exception->setErrors($errors);
            $exception->setElementData($this->data[$name]);

            throw $exception;
        } elseif (count($editables) === 1) {
            return $editables[0];
        } elseif (count($editables) > 1) {
            return $this->editableConflictResolver->resolve($this->document, $name, $this->map[$name], $editables, $errors);
        }
    }

    private function buildBlocks(array $blockNames, array $blockParents): array
    {
        $hierarchies = [];
        foreach ($blockNames as $blockName) {
            $hierarchy = [];

            $currentBlockName = $blockName;
            while (isset($blockParents[$currentBlockName])) {
                $currentBlockName = $blockParents[$currentBlockName];
                $hierarchy[] = $currentBlockName;
            }

            $hierarchies[$blockName] = array_reverse($hierarchy);
        }

        uasort($hierarchies, function ($a, $b) {
            if (count($a) === count($b)) {
                return 0;
            }

            return count($a) < count($b) ? -1 : 1;
        });

        $blocks = [];
        foreach ($hierarchies as $blockName => $parentNames) {
            $parent = null;
            if (count($parentNames) > 0) {
                $lastParentName = (array_reverse($parentNames))[0];
                if (!isset($blocks[$lastParentName])) {
                    throw new \LogicException(sprintf('Block info for parent "%s" was not found', $lastParentName));
                }

                $parent = $blocks[$lastParentName];
            }

            $blockType = $this->map[$blockName];
            if (!isset($this->blockTypes[$blockType])) {
                throw new \InvalidArgumentException(sprintf('Invalid block type "%s"', $blockType));
            }

            $blockClass = $this->blockTypes[$blockType];

            $blocks[$blockName] = new $blockClass($blockName, $this->map[$blockName], $this->data[$blockName], $parent);
        }

        return $blocks;
    }

    /**
     * Tries to find a list of blocks which could be a block's parent. Example:
     *
     *      name:     AB-B-ABAB_AB-BAB33_1
     *      parents:  [
     *          AB,
     *          AB-B
     *      ]
     *
     * We need to catch the AB-B parent, not its ancestor AB, so we first try to find
     * all candidates, then resolve in resolveBlockParents until only one candidate
     * is left in the list. As soon as we know AB is AB-B's parent, we can safely
     * remove AB from the list of candidates for AB-B-ABAB_AB-BAB33_1
     *
     * @param array $blockNames
     *
     * @return array
     */
    private function findBlockParentCandidates(array $blockNames): array
    {
        $parentCandidates = [];
        foreach ($blockNames as $blockName) {
            $pattern = self::buildNameMatchingPattern($blockName);

            foreach ($blockNames as $matchingBlockName) {
                if ($blockName === $matchingBlockName) {
                    continue;
                }

                if (preg_match($pattern, $matchingBlockName, $match)) {
                    $parentCandidates[$matchingBlockName][] = $blockName;
                }
            }
        }

        return $parentCandidates;
    }

    /**
     * @param array $parentCandidates
     *
     * @return array
     */
    private function resolveBlockParents(array $parentCandidates): array
    {
        $changed = true;
        $parents = [];

        // iterate list until we narrowed down the list of candidates to 1 for
        // every block
        while ($changed) {
            $changed = false;

            foreach ($parentCandidates as $name => $candidates) {
                if (count($candidates) === 0) {
                    throw new \LogicException('Expected at least one parent candidate');
                }

                if (count($candidates) === 1) {
                    if (!isset($parents[$name])) {
                        $parents[$name] = $candidates[0];
                        $changed = true;
                    }
                } else {
                    $indexesToRemove = [];
                    foreach ($candidates as $candidate) {
                        if (isset($parents[$candidate])) {
                            // check if the parent of the candidate is in our candidates list
                            // if found (array_keys has a result), remove the parent from our candidates list
                            $parent = $parents[$candidate];
                            $indexesToRemove = array_merge($indexesToRemove, array_keys($candidates, $parent));
                        }
                    }

                    // remove all parent candidates we found
                    if (count($indexesToRemove) > 0) {
                        $changed = true;

                        foreach ($indexesToRemove as $indexToRemove) {
                            unset($candidates[$indexToRemove]);
                        }

                        $parentCandidates[$name] = array_values($candidates);
                    }
                }
            }
        }

        return $parents;
    }

    /**
     * Builds a list of names for all block elements
     *
     * @return array
     */
    private function getBlockNames(): array
    {
        $blockNames = [];
        foreach ($this->map as $name => $type) {
            if ($this->isBlock($type)) {
                $blockNames[] = $name;
            }
        }

        return $blockNames;
    }

    /**
     * Get blocks sorted by deepest level first. If they are on the same level,
     * prefer those which have a number at the end (mitigates errors when
     * having blocks named something like "content" and "content1" simultaneosly
     *
     * @param AbstractBlock[] $blocks
     *
     * @return AbstractBlock[]
     */
    private function getBlocksSortedByLevel(array $blocks): array
    {
        $compareByTrailingNumber = function (string $a, string $b): int {
            $numberPattern = '/(?<number>\d+)$/';

            $matchesA = (bool)preg_match($numberPattern, $a, $aMatches);
            $matchesB = (bool)preg_match($numberPattern, $b, $bMatches);

            if ($matchesA && !$matchesB) {
                return -1;
            }

            if (!$matchesA && $matchesB) {
                return 1;
            }

            if ($matchesA && $matchesB) {
                $aLen = strlen((string)$aMatches['number']);
                $bLen = strlen((string)$bMatches['number']);

                if ($aLen === $bLen) {
                    return 0;
                }

                return $aLen > $bLen ? -1 : 1;
            }

            return 0;
        };

        uasort($blocks, function(AbstractBlock $a, AbstractBlock $b) use ($compareByTrailingNumber) {
            if ($a->getLevel() === $b->getLevel()) {
                return $compareByTrailingNumber($a->getRealName(), $b->getRealName());
            }

            return $a->getLevel() < $b->getLevel() ? 1 : -1;
        });

        return $blocks;
    }

    private function isBlock(string $type): bool
    {
        return in_array($type, array_keys($this->blockTypes));
    }

    public static function buildNameMatchingPattern(string $identifier): string
    {
        return '/^(?<realName>.+)' . self::escapeRegexString($identifier) . '(?<indexes>[\d_]*)$/';
    }

    public static function escapeRegexString(string $string): string
    {
        $string = str_replace('.', '\\.', $string);
        $string = str_replace('-', '\\-', $string);

        return $string;
    }
}

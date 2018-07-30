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

namespace Pimcore\Tests\Unit\Document\Tag\NamingStrategy\Migration\Analyze;

use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\ConflictResolverInterface;
use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\Element\AbstractBlock;
use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\Element\AbstractElement;
use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\Element\Editable;
use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\Exception\BuildEditableException;
use Pimcore\Document\Tag\NamingStrategy\NamingStrategyInterface;
use Pimcore\Model\Document;

/**
 * TODO replace with a stub. Debug why mock object returned mocked editables without data
 */
class MappingConflictResolver implements ConflictResolverInterface
{
    /**
     * @var NamingStrategyInterface
     */
    private $namingStrategy;

    /**
     * @var array
     */
    private $map;

    public function __construct(NamingStrategyInterface $namingStrategy, array $map = [])
    {
        $this->namingStrategy = $namingStrategy;
        $this->map            = $map;
    }

    /**
     * @inheritdoc
     */
    public function resolveBuildFailed(Document\PageSnippet $document, BuildEditableException $exception): BuildEditableException
    {
        return $exception;
    }

    /**
     * @inheritDoc
     */
    public function resolveBlockConflict(Document\PageSnippet $document, BuildEditableException $exception, array $blocks): AbstractBlock
    {
        /** @var AbstractBlock $block */
        $block = $this->findElement($blocks, $exception);

        return $block;
    }

    /**
     * @inheritdoc
     */
    public function resolveEditableConflict(Document\PageSnippet $document, BuildEditableException $exception, array $editables): Editable
    {
        /** @var Editable $editable */
        $editable = $this->findElement($editables, $exception);

        return $editable;
    }

    /**
     * @param AbstractElement[] $elements
     * @param BuildEditableException $exception
     *
     * @return AbstractElement
     */
    private function findElement(array $elements, BuildEditableException $exception): AbstractElement
    {
        $name    = $exception->getName();
        $newName = $this->map[$name] ?? null;

        if ($newName) {
            foreach ($elements as $element) {
                if ($element->getNameForStrategy($this->namingStrategy) === $newName) {
                    return $element;
                }
            }
        }

        throw $exception;
    }
}

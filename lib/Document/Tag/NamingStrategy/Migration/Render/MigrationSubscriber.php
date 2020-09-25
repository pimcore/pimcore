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

namespace Pimcore\Document\Tag\NamingStrategy\Migration\Render;

use Pimcore\Document\Tag\Block\BlockName;
use Pimcore\Document\Tag\Block\BlockState;
use Pimcore\Document\Tag\NamingStrategy\NamingStrategyInterface;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\Document\TagNameEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @deprecated
 * This listener is not intended to be always registered on the dispatcher, but instead
 * is added manually when needed in the MigrateTagNamingStrategy CLI command. The listener
 * collects all rendered tag names and creates a matching new tag name mapping which can
 * be used to perform tag name migrations after rendering a document.
 */
class MigrationSubscriber implements EventSubscriberInterface
{
    /**
     * The new naming strategy to use
     *
     * @var NamingStrategyInterface
     */
    private $namingStrategy;

    /**
     * @var array
     */
    private $nameMapping = [];

    /**
     * @param NamingStrategyInterface $namingStrategy
     */
    public function __construct(NamingStrategyInterface $namingStrategy)
    {
        $this->namingStrategy = $namingStrategy;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            DocumentEvents::TAG_NAME => 'onBuildTagName',
        ];
    }

    public function onBuildTagName(TagNameEvent $event)
    {
        if (null === $document = $event->getDocument()) {
            throw new \RuntimeException('Need a document to migrate tag naming strategy.');
        }

        $documentNames = isset($this->nameMapping[$document->getId()])
            ? $this->nameMapping[$document->getId()]
            : [];

        $mappedBlockState = $this->buildMappedBlockState($event->getBlockState(), $documentNames);

        $newName = $this->namingStrategy->buildTagName(
            $event->getInputName(),
            $event->getType(),
            $mappedBlockState
        );

        // only set the new name if it is not the same as the existing one
        if ($newName !== $event->getTagName()) {
            $documentNames[$event->getTagName()] = $newName;
        }

        if (!empty($documentNames)) {
            $this->nameMapping[$document->getId()] = $documentNames;
        }
    }

    /**
     * As the block building logic relies on the parent block name, we need to make sure the block
     * state for the new name contains also new names in previous states.
     *
     * @param BlockState $blockState
     * @param array $mapping
     *
     * @return BlockState
     */
    private function buildMappedBlockState(BlockState $blockState, array $mapping): BlockState
    {
        $mappedState = clone $blockState;

        $mappedBlocks = [];
        foreach ($mappedState->getBlocks() as $block) {
            $name = $block->getName();
            if (isset($mapping[$name])) {
                $name = $mapping[$name];
            }

            $mappedBlocks[] = BlockName::createFromNames($name, $block->getRealName());
        }

        $mappedState->clearBlocks();

        foreach ($mappedBlocks as $mappedBlock) {
            $mappedState->pushBlock($mappedBlock);
        }

        return $mappedState;
    }

    /**
     * Returns the mapping result
     *
     * @return array
     */
    public function getNameMapping(): array
    {
        return $this->nameMapping;
    }

    /**
     * @param array $nameMapping
     */
    public function setNameMapping(array $nameMapping)
    {
        $this->nameMapping = $nameMapping;
    }
}

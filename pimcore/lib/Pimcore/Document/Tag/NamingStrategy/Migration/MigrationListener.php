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

namespace Pimcore\Document\Tag\NamingStrategy\Migration;

use Pimcore\Document\Tag\NamingStrategy\NamingStrategyInterface;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\Document\TagNameEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This listener is not intended to be always registered on the dispatched, but instead
 * is added manually when needed in the MigrateTagNamingStrategy CLI command. The listener
 * collects all rendered tag names and creates a matching new tag name mapping which can
 * be used to perform tag name migrations after rendering a document.
 */
class MigrationListener implements EventSubscriberInterface
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
            DocumentEvents::TAG_NAME => 'onBuildTagName'
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


        $newName = $this->namingStrategy->buildTagName(
            $event->getInputName(),
            $event->getType(),
            $event->getBlockState()
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
     * Returns the mapping result
     *
     * @return array
     */
    public function getNameMapping(): array
    {
        return $this->nameMapping;
    }
}

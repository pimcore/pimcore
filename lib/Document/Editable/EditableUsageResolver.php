<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Document\Editable;

use Pimcore\Document\Renderer\DocumentRenderer;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Editable\Block;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
class EditableUsageResolver
{
    protected ?UsageRecorderSubscriber $subscriber = null;

    protected EventDispatcherInterface $dispatcher;

    protected DocumentRenderer $renderer;

    public function __construct(EventDispatcherInterface $eventDispatcher, DocumentRenderer $documentRenderer)
    {
        $this->dispatcher = $eventDispatcher;
        $this->renderer = $documentRenderer;
    }

    public function getUsedEditableNames(Document\PageSnippet $document): array
    {
        $this->registerEventSubscriber();

        // we render in editmode, so that we can ensure all elements that can be edited are present in the export
        // this is especially necessary when lazy loading certain elements on a page (eg. using ajax-include and similar solutions)
        $this->renderer->render($document, [
            EditmodeResolver::ATTRIBUTE_EDITMODE => true,
            Block::ATTRIBUTE_IGNORE_EDITMODE_INDICES => true,
            ]);
        $names = $this->subscriber->getRecordedEditableNames();
        $this->unregisterEventSubscriber();

        $names = array_unique($names);

        return $names;
    }

    protected function registerEventSubscriber(): void
    {
        if (!$this->subscriber) {
            $this->subscriber = new UsageRecorderSubscriber();
            $this->dispatcher->addSubscriber($this->subscriber);
        }
    }

    protected function unregisterEventSubscriber(): void
    {
        if ($this->subscriber) {
            $this->dispatcher->removeSubscriber($this->subscriber);
            $this->subscriber = null;
        }
    }
}

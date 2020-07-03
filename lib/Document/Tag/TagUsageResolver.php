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

namespace Pimcore\Document\Tag;

use Pimcore\Bundle\CoreBundle\EventListener\Frontend\ElementListener;
use Pimcore\Document\Renderer\DocumentRenderer;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Model\Document;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TagUsageResolver
{
    /**
     * @var UsageRecorderSubscriber
     */
    protected $subscriber;
    protected $dispatcher;
    protected $renderer;

    public function __construct(EventDispatcherInterface $eventDispatcher, DocumentRenderer $documentRenderer)
    {
        $this->dispatcher = $eventDispatcher;
        $this->renderer = $documentRenderer;
    }

    public function getUsedTagnames(Document\PageSnippet $document)
    {
        $this->registerEventSubscriber();

        // we render in editmode, so that we can ensure all elements that can be edited are present in the export
        // this is especially necessary when lazy loading certain elements on a page (eg. using ajax-include and similar solutions)
        $this->renderer->render($document, [EditmodeResolver::ATTRIBUTE_EDITMODE => true, ElementListener::FORCE_ALLOW_PROCESSING_UNPUBLISHED_ELEMENTS => true]);
        $names = $this->subscriber->getRecordedTagNames();
        $this->unregisterEventSubscriber();

        $names = array_unique($names);

        return $names;
    }

    protected function registerEventSubscriber()
    {
        if (!$this->subscriber) {
            $this->subscriber = new UsageRecorderSubscriber();
            $this->dispatcher->addSubscriber($this->subscriber);
        }
    }

    protected function unregisterEventSubscriber()
    {
        if ($this->subscriber) {
            $this->dispatcher->removeSubscriber($this->subscriber);
            $this->subscriber = null;
        }
    }
}

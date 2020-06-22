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

namespace Pimcore\Targeting\EventListener;

use Pimcore\Event\Targeting\AssignDocumentTargetGroupEvent;
use Pimcore\Event\Targeting\TargetingEvent;
use Pimcore\Event\TargetingEvents;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Model\Document;
use Pimcore\Model\Staticroute;
use Pimcore\Targeting\ActionHandler\ActionHandlerInterface;
use Pimcore\Targeting\ActionHandler\DelegatingActionHandler;
use Pimcore\Targeting\Model\VisitorInfo;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles target groups configured on the document settings panel. If a document
 * has configured target groups, the assign_target_group will be manually called
 * for that target group before starting to match other conditions.
 */
class DocumentTargetGroupListener implements EventSubscriberInterface
{
    /**
     * @var DocumentResolver
     */
    private $documentResolver;

    /**
     * @var ActionHandlerInterface|DelegatingActionHandler
     */
    private $actionHandler;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(
        DocumentResolver $documentResolver,
        ActionHandlerInterface $actionHandler,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->documentResolver = $documentResolver;
        $this->actionHandler = $actionHandler;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents()
    {
        return [
            TargetingEvents::PRE_RESOLVE => 'onVisitorInfoResolve',
        ];
    }

    public function onVisitorInfoResolve(TargetingEvent $event)
    {
        $request = $event->getRequest();
        $document = $this->documentResolver->getDocument($request);

        if ($document) {
            $this->assignDocumentTargetGroups($document, $event->getVisitorInfo());
        }
    }

    private function assignDocumentTargetGroups(Document $document, VisitorInfo $visitorInfo)
    {
        if (!$document || !$document instanceof Document\Page || null !== Staticroute::getCurrentRoute()) {
            return;
        }

        // get target groups from document
        $targetGroups = $document->getTargetGroups();

        if (empty($targetGroups)) {
            return;
        }

        foreach ($targetGroups as $targetGroup) {
            $this->actionHandler->apply($visitorInfo, [
                'type' => 'assign_target_group',
                'targetGroup' => $targetGroup,
            ]);

            $this->eventDispatcher->dispatch(
                TargetingEvents::ASSIGN_DOCUMENT_TARGET_GROUP,
                new AssignDocumentTargetGroupEvent($visitorInfo, $document, $targetGroup)
            );
        }
    }
}

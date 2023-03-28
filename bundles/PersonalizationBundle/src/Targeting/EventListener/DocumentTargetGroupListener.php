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

namespace Pimcore\Bundle\PersonalizationBundle\Targeting\EventListener;

use Pimcore\Bundle\PersonalizationBundle\Event\Targeting\AssignDocumentTargetGroupEvent;
use Pimcore\Bundle\PersonalizationBundle\Event\Targeting\TargetingEvent;
use Pimcore\Bundle\PersonalizationBundle\Event\TargetingEvents;
use Pimcore\Bundle\PersonalizationBundle\Model\Document\Page;
use Pimcore\Bundle\PersonalizationBundle\Targeting\ActionHandler\ActionHandlerInterface;
use Pimcore\Bundle\PersonalizationBundle\Targeting\ActionHandler\DelegatingActionHandler;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Model\VisitorInfo;
use Pimcore\Bundle\StaticRoutesBundle\Model\Staticroute;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Model\Document;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Handles target groups configured on the document settings panel. If a document
 * has configured target groups, the assign_target_group will be manually called
 * for that target group before starting to match other conditions.
 */
class DocumentTargetGroupListener implements EventSubscriberInterface
{
    private DocumentResolver $documentResolver;

    private ActionHandlerInterface|DelegatingActionHandler $actionHandler;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        DocumentResolver $documentResolver,
        ActionHandlerInterface $actionHandler,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->documentResolver = $documentResolver;
        $this->actionHandler = $actionHandler;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            TargetingEvents::PRE_RESOLVE => 'onVisitorInfoResolve',
        ];
    }

    public function onVisitorInfoResolve(TargetingEvent $event): void
    {
        $request = $event->getRequest();
        $document = $this->documentResolver->getDocument($request);

        if ($document) {
            $this->assignDocumentTargetGroups($document, $event->getVisitorInfo());
        }
    }

    private function assignDocumentTargetGroups(Document $document, VisitorInfo $visitorInfo): void
    {
        if (!$document instanceof Page) {
            return;
        }

        if (class_exists(Staticroute::class) && null !== Staticroute::getCurrentRoute()) {
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
                new AssignDocumentTargetGroupEvent($visitorInfo, $document, $targetGroup),
                TargetingEvents::ASSIGN_DOCUMENT_TARGET_GROUP
            );
        }
    }
}

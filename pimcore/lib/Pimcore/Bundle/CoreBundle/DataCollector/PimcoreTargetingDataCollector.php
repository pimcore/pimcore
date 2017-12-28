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

namespace Pimcore\Bundle\CoreBundle\DataCollector;

use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Targeting\TargetingDocumentInterface;
use Pimcore\Model\Tool\Targeting\TargetGroup;
use Pimcore\Targeting\Document\DocumentTargetingConfigurator;
use Pimcore\Targeting\Model\VisitorInfo;
use Pimcore\Targeting\Storage\TargetingStorageInterface;
use Pimcore\Targeting\VisitorInfoStorageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\Stopwatch\Stopwatch;

class PimcoreTargetingDataCollector extends DataCollector
{
    /**
     * @var VisitorInfoStorageInterface
     */
    private $visitorInfoStorage;

    /**
     * @var TargetingStorageInterface
     */
    private $targetingStorage;

    /**
     * @var DocumentResolver
     */
    private $documentResolver;

    /**
     * @var DocumentTargetingConfigurator
     */
    private $targetingConfigurator;

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    public function __construct(
        VisitorInfoStorageInterface $visitorInfoStorage,
        TargetingStorageInterface $targetingStorage,
        DocumentResolver $documentResolver,
        DocumentTargetingConfigurator $targetingConfigurator,
        Stopwatch $stopwatch = null
    ) {
        $this->visitorInfoStorage    = $visitorInfoStorage;
        $this->targetingStorage      = $targetingStorage;
        $this->documentResolver      = $documentResolver;
        $this->targetingConfigurator = $targetingConfigurator;
        $this->stopwatch             = $stopwatch;
    }

    public function getName()
    {
        return 'pimcore_targeting';
    }

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = [];

        if (!$this->visitorInfoStorage->hasVisitorInfo()) {
            return;
        }

        $document    = $this->documentResolver->getDocument($request);
        $visitorInfo = $this->visitorInfoStorage->getVisitorInfo();

        $this->collectVisitorInfo($visitorInfo);
        $this->collectStorage($visitorInfo);
        $this->collectMatchedRules($visitorInfo);
        $this->collectTargetGroups($visitorInfo);
        $this->collectDocumentTargetGroup($document);
        $this->collectDocumentTargetGroupMapping();

        $this->data = $this->cloneVar($this->data);
    }

    public function reset()
    {
        $this->data = [];
    }

    private function collectVisitorInfo(VisitorInfo $visitorInfo)
    {
        $this->data['visitor_info'] = [
            'visitorId' => $visitorInfo->getVisitorId(),
            'sessionId' => $visitorInfo->getSessionId(),
            'actions'   => $visitorInfo->getActions(),
            'data'      => $visitorInfo->getData(),
        ];
    }

    private function collectStorage(VisitorInfo $visitorInfo)
    {
        $this->data['storage'] = [];

        foreach (TargetingStorageInterface::VALID_SCOPES as $scope) {
            $created = $this->targetingStorage->getCreatedAt($visitorInfo, $scope);
            $updated = $this->targetingStorage->getCreatedAt($visitorInfo, $scope);

            $this->data['storage'][$scope] = array_merge([
                'created' => $created ? $created->format('c') : null,
                'updated' => $updated ? $updated->format('c') : null
            ], $this->targetingStorage->all($visitorInfo, $scope));
        }
    }

    private function collectMatchedRules(VisitorInfo $visitorInfo)
    {
        $rules = [];
        foreach ($visitorInfo->getMatchingTargetingRules() as $rule) {
            $duration = null;
            if (null !== $this->stopwatch) {
                try {
                    $event    = $this->stopwatch->getEvent(sprintf('Targeting:match:%s', $rule->getName()));
                    $duration = $event->getDuration();
                } catch (\Throwable $e) {
                    // noop
                }
            }

            $rules[] = [
                'id'         => $rule->getId(),
                'name'       => $rule->getName(),
                'duration'   => $duration,
                'conditions' => $rule->getConditions(),
                'actions'    => $rule->getActions(),
            ];
        }

        $this->data['rules'] = $rules;
    }

    private function collectTargetGroups(VisitorInfo $visitorInfo)
    {
        $targetGroups = [];

        foreach ($visitorInfo->getTargetGroupAssignments() as $assignment) {
            $targetGroups[] = [
                'id'        => $assignment->getTargetGroup()->getId(),
                'name'      => $assignment->getTargetGroup()->getName(),
                'threshold' => $assignment->getTargetGroup()->getThreshold(),
                'count'     => $assignment->getCount(),
            ];
        }

        $this->data['target_groups'] = $targetGroups;
    }

    private function collectDocumentTargetGroup(Document $document = null)
    {
        if (!$document instanceof TargetingDocumentInterface) {
            return;
        }

        $targetGroupId = $document->getUseTargetGroup();
        if (!$targetGroupId) {
            return;
        }

        $targetGroup = TargetGroup::getById($targetGroupId);
        if ($targetGroup) {
            $this->data['document_target_group'] = [
                'id'   => $targetGroup->getId(),
                'name' => $targetGroup->getName()
            ];
        }
    }

    private function collectDocumentTargetGroupMapping()
    {
        $resolvedMapping = $this->targetingConfigurator->getResolvedTargetGroupMapping();
        $mapping         = [];

        /** @var TargetGroup $targetGroup */
        foreach ($resolvedMapping as $documentId => $targetGroup) {
            $document = Document::getById($documentId);

            $mapping[] = [
                'document'    => [
                    'id'   => $document->getId(),
                    'path' => $document->getRealFullPath(),
                ],
                'targetGroup' => [
                    'id'   => $targetGroup->getId(),
                    'name' => $targetGroup->getName(),
                ],
            ];
        }

        $this->data['document_target_groups'] = $mapping;
    }

    public function getVisitorInfo()
    {
        return $this->data['visitor_info'];
    }

    public function getStorage()
    {
        return $this->data['storage'];
    }

    public function getRules()
    {
        return $this->data['rules'];
    }

    public function getTargetGroups()
    {
        return $this->data['target_groups'];
    }

    public function getDocumentTargetGroup()
    {
        return $this->data['document_target_group'] ?? null;
    }

    public function getDocumentTargetGroups()
    {
        return $this->data['document_target_groups'];
    }

    public function hasData(): bool
    {
        return !empty($this->data);
    }
}

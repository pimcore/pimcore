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

namespace Pimcore\Bundle\PersonalizationBundle\Targeting\Debug;

use Pimcore\Bundle\PersonalizationBundle\Debug\Traits\StopwatchTrait;
use Pimcore\Bundle\PersonalizationBundle\Model\Document\Targeting\TargetingDocumentInterface;
use Pimcore\Bundle\PersonalizationBundle\Model\Tool\Targeting\TargetGroup;
use Pimcore\Bundle\PersonalizationBundle\Targeting\DataProvider\TargetingStorage;
use Pimcore\Bundle\PersonalizationBundle\Targeting\DataProvider\VisitedPagesCounter;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Document\DocumentTargetingConfigurator;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Model\VisitorInfo;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Storage\TargetingStorageInterface;
use Pimcore\Model\Document;

class TargetingDataCollector
{
    use StopwatchTrait;

    private TargetingStorageInterface $targetingStorage;

    private DocumentTargetingConfigurator $targetingConfigurator;

    private array $filteredVisitorInfoDataObjecKeys = [
        TargetingStorage::PROVIDER_KEY,
        VisitedPagesCounter::PROVIDER_KEY,
    ];

    public function __construct(
        TargetingStorageInterface $targetingStorage,
        DocumentTargetingConfigurator $targetingConfigurator
    ) {
        $this->targetingStorage = $targetingStorage;
        $this->targetingConfigurator = $targetingConfigurator;
    }

    public function collectVisitorInfo(VisitorInfo $visitorInfo): array
    {
        return [
            'visitorId' => $visitorInfo->getVisitorId(),
            'sessionId' => $visitorInfo->getSessionId(),
            'actions' => $visitorInfo->getActions(),
            'data' => $this->filterVisitorInfoData($visitorInfo->getData()),
        ];
    }

    public function getFilteredVisitorInfoDataObjecKeys(): array
    {
        return $this->filteredVisitorInfoDataObjecKeys;
    }

    public function setFilteredVisitorInfoDataObjecKeys(array $filteredVisitorInfoDataObjecKeys): void
    {
        $this->filteredVisitorInfoDataObjecKeys = $filteredVisitorInfoDataObjecKeys;
    }

    protected function filterVisitorInfoData(array $data): array
    {
        // only show a string reference naming the class instead of serializing objects in the list
        foreach ($this->filteredVisitorInfoDataObjecKeys as $key) {
            if (isset($data[$key]) && is_object($data[$key])) {
                $data[$key] = sprintf(
                    'object(%s)',
                    (new \ReflectionObject($data[$key]))->getShortName()
                );
            }
        }

        return $data;
    }

    public function collectStorage(VisitorInfo $visitorInfo): array
    {
        $storage = [];

        foreach (TargetingStorageInterface::VALID_SCOPES as $scope) {
            $created = $this->targetingStorage->getCreatedAt($visitorInfo, $scope);
            $updated = $this->targetingStorage->getCreatedAt($visitorInfo, $scope);

            $storage[$scope] = array_merge([
                'created' => $created ? $created->format('c') : null,
                'updated' => $updated ? $updated->format('c') : null,
            ], $this->targetingStorage->all($visitorInfo, $scope));
        }

        return $storage;
    }

    public function collectMatchedRules(VisitorInfo $visitorInfo): array
    {
        $rules = [];

        foreach ($visitorInfo->getMatchingTargetingRules() as $rule) {
            $duration = null;
            if (null !== $this->stopwatch) {
                try {
                    $event = $this->stopwatch->getEvent(sprintf('Targeting:match:%s', $rule->getName()));
                    $duration = $event->getDuration();
                } catch (\Throwable $e) {
                    // noop
                }
            }

            $rules[] = [
                'id' => $rule->getId(),
                'name' => $rule->getName(),
                'duration' => $duration,
                'conditions' => $rule->getConditions(),
                'actions' => $rule->getActions(),
            ];
        }

        return $rules;
    }

    public function collectTargetGroups(VisitorInfo $visitorInfo): array
    {
        $targetGroups = [];

        foreach ($visitorInfo->getTargetGroupAssignments() as $assignment) {
            $targetGroups[] = [
                'id' => $assignment->getTargetGroup()->getId(),
                'name' => $assignment->getTargetGroup()->getName(),
                'threshold' => $assignment->getTargetGroup()->getThreshold(),
                'count' => $assignment->getCount(),
            ];
        }

        return $targetGroups;
    }

    /**
     * @param Document|null $document
     *
     * @return array|null
     */
    public function collectDocumentTargetGroup(Document $document = null): ?array
    {
        if (!$document instanceof TargetingDocumentInterface) {
            return null;
        }

        $targetGroupId = $document->getUseTargetGroup();
        if (!$targetGroupId) {
            return null;
        }

        $targetGroup = TargetGroup::getById($targetGroupId);
        if ($targetGroup) {
            return [
                'id' => $targetGroup->getId(),
                'name' => $targetGroup->getName(),
            ];
        }

        return null;
    }

    public function collectDocumentTargetGroupMapping(): array
    {
        $resolvedMapping = $this->targetingConfigurator->getResolvedTargetGroupMapping();
        $mapping = [];

        /** @var TargetGroup $targetGroup */
        foreach ($resolvedMapping as $documentId => $targetGroup) {
            $document = Document::getById($documentId);

            $mapping[] = [
                'document' => [
                    'id' => $document->getId(),
                    'path' => $document->getRealFullPath(),
                ],
                'targetGroup' => [
                    'id' => $targetGroup->getId(),
                    'name' => $targetGroup->getName(),
                ],
            ];
        }

        return $mapping;
    }
}

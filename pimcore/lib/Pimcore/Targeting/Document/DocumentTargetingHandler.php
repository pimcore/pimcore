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

namespace Pimcore\Targeting\Document;

use Pimcore\Cache\Core\CoreHandlerInterface;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Tool\Targeting\Persona as TargetGroup;
use Pimcore\Targeting\VisitorInfoStorageInterface;

class DocumentTargetingHandler
{
    /**
     * @var VisitorInfoStorageInterface
     */
    private $visitorInfoStorage;

    /**
     * @var CoreHandlerInterface
     */
    private $cache;

    /**
     * @var array
     */
    private $targetGroupMapping = [];

    public function __construct(
        VisitorInfoStorageInterface $visitorInfoStorage,
        CoreHandlerInterface $cache
    )
    {
        $this->visitorInfoStorage = $visitorInfoStorage;
        $this->cache              = $cache;
    }

    /**
     * Configure target group to use on the document by reading the most relevant
     * target group from the visitor info.
     *
     * @param Document $document
     */
    public function configureTargetGroup(Document $document)
    {
        if (!$document instanceof Page) {
            return;
        }

        // already configured
        if (isset($this->targetGroupMapping[$document->getId()])) {
            return;
        }

        $matchingTargetGroups = $this->getMatchingTargetGroups($document);
        if (count($matchingTargetGroups) > 0) {
            $targetGroup = $matchingTargetGroups[0];

            $this->targetGroupMapping[$document->getId()] = $targetGroup->getId();
            $document->setUsePersona($targetGroup->getId());
        }
    }

    /**
     * Resolve all target groups which were matched and which are valid for
     * the document
     *
     * @param Document $document
     *
     * @return TargetGroup[]
     */
    public function getMatchingTargetGroups(Document $document): array
    {
        if (!$this->visitorInfoStorage->hasVisitorInfo()) {
            return [];
        }

        $configuredTargetGroups = $this->getTargetGroupsForDocument($document);
        if (empty($configuredTargetGroups)) {
            return [];
        }

        $visitorInfo = $this->visitorInfoStorage->getVisitorInfo();

        $result = [];
        foreach ($visitorInfo->getAssignedTargetGroups() as $targetGroup) {
            if (in_array($targetGroup->getId(), $configuredTargetGroups)) {
                $result[$targetGroup->getId()] = $targetGroup;
            }
        }

        return array_values($result);
    }

    /**
     * Resolves valid target groups for a document. A target group is seen as valid
     * if it has at least one element configured for that target group.
     *
     * @param Document $document
     *
     * @return array
     */
    public function getTargetGroupsForDocument(Document $document): array
    {
        if (!$document instanceof Document\PageSnippet) {
            return [];
        }

        $cacheKey = sprintf('document_target_groups_%d', $document->getId());

        if ($targetGroups = $this->cache->load($cacheKey)) {
            return $targetGroups;
        }

        $targetGroups = [];
        foreach ($document->getElements() as $key => $tag) {
            $pattern = '/^' . Page::PERSONA_ELEMENT_PREFIX_PREFIXPART . '([0-9]+)' . Page::PERSONA_ELEMENT_PREFIX_SUFFIXPART . '/';
            if (preg_match($pattern, $key, $matches)) {
                $targetGroups[] = (int)$matches[1];
            }
        }

        $targetGroups = array_unique($targetGroups);
        $targetGroups = array_filter($targetGroups, function ($id) {
            return TargetGroup::isIdActive($id);
        });

        $this->cache->save($cacheKey, $targetGroups, [sprintf('document_%d', $document->getId())]);

        return $targetGroups;
    }
}

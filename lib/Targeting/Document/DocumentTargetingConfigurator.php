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

use Pimcore\Bundle\AdminBundle\Security\User\UserLoader;
use Pimcore\Cache\Core\CoreHandlerInterface;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Targeting\TargetingDocumentInterface;
use Pimcore\Model\Tool\Targeting\TargetGroup;
use Pimcore\Targeting\VisitorInfoStorageInterface;

class DocumentTargetingConfigurator
{
    /**
     * @var VisitorInfoStorageInterface
     */
    private $visitorInfoStorage;

    /**
     * @var RequestHelper
     */
    private $requestHelper;

    /**
     * @var UserLoader
     */
    private $userLoader;

    /**
     * @var CoreHandlerInterface
     */
    private $cache;

    /**
     * @var array
     */
    private $targetGroupMapping = [];

    /**
     * @var TargetGroup|null
     */
    private $overrideTargetGroup;

    public function __construct(
        VisitorInfoStorageInterface $visitorInfoStorage,
        RequestHelper $requestHelper,
        UserLoader $userLoader,
        CoreHandlerInterface $cache
    ) {
        $this->visitorInfoStorage = $visitorInfoStorage;
        $this->requestHelper = $requestHelper;
        $this->userLoader = $userLoader;
        $this->cache = $cache;
    }

    /**
     * Configure target group to use on the document by reading the most relevant
     * target group from the visitor info.
     *
     * @param Document $document
     */
    public function configureTargetGroup(Document $document)
    {
        if (!$document instanceof TargetingDocumentInterface) {
            return;
        }

        // already configured
        if (isset($this->targetGroupMapping[$document->getId()])) {
            return;
        }

        if ($this->isConfiguredByAdminParam($document)) {
            return;
        }

        if ($this->isConfiguredByOverride($document)) {
            return;
        }

        $matchingTargetGroups = $this->getMatchingTargetGroups($document);
        if (count($matchingTargetGroups) > 0) {
            $targetGroup = $matchingTargetGroups[0];

            $this->targetGroupMapping[$document->getId()] = $targetGroup;
            $document->setUseTargetGroup($targetGroup->getId());
        }
    }

    /**
     * Handle _ptg admin param here only if there's a valid user session
     *
     * @param TargetingDocumentInterface $document
     *
     * @return bool
     */
    private function isConfiguredByAdminParam(TargetingDocumentInterface $document): bool
    {
        if (!$this->requestHelper->hasMasterRequest()) {
            return false;
        }

        $request = $this->requestHelper->getMasterRequest();
        if (!$this->requestHelper->isFrontendRequestByAdmin($request)) {
            return false;
        }

        // IMPORTANT: check there is an authenticated admin user before allowing
        // to set target groups via parameter
        $user = $this->userLoader->getUser();
        if (!$user) {
            return false;
        }

        // ptg = pimcore target group = will be used from the admin UI to show target specific data
        // in editmode
        if ($ptg = $request->get('_ptg')) {
            $targetGroup = TargetGroup::getById((int)$ptg);

            if ($targetGroup) {
                $this->targetGroupMapping[$document->getId()] = $targetGroup;
                $document->setUseTargetGroup($targetGroup->getId());

                return true;
            }
        }

        return false;
    }

    private function isConfiguredByOverride(TargetingDocumentInterface $document): bool
    {
        if (null !== $this->overrideTargetGroup) {
            $this->targetGroupMapping[$document->getId()] = $this->overrideTargetGroup;
            $document->setUseTargetGroup($this->overrideTargetGroup->getId());

            return true;
        }

        return false;
    }

    /**
     * @param Document $document
     *
     * @return TargetGroup|null
     */
    public function getConfiguredTargetGroup(Document $document)
    {
        if (isset($this->targetGroupMapping[$document->getId()])) {
            return $this->targetGroupMapping[$document->getId()];
        }

        return null;
    }

    public function getResolvedTargetGroupMapping(): array
    {
        return $this->targetGroupMapping;
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
        if (!$document instanceof TargetingDocumentInterface) {
            return [];
        }
        /** @var Document\TargetingDocument $document */
        $cacheKey = sprintf('document_target_groups_%d', $document->getId());

        if ($targetGroups = $this->cache->load($cacheKey)) {
            return $targetGroups;
        }

        $targetGroups = [];
        foreach ($document->getEditables() as $key => $tag) {
            $pattern = '/^' . preg_quote(TargetingDocumentInterface::TARGET_GROUP_EDITABLE_PREFIX, '/') . '([0-9]+)' . preg_quote(TargetingDocumentInterface::TARGET_GROUP_EDITABLE_SUFFIX, '/') . '/';
            if (preg_match($pattern, (string) $key, $matches)) {
                $targetGroups[] = (int)$matches[1];
            }
        }

        $targetGroups = array_unique($targetGroups);
        $targetGroups = array_filter($targetGroups, function ($id) {
            return TargetGroup::isIdActive($id);
        });

        $this->cache->save($cacheKey, $targetGroups, [sprintf('document_%d', $document->getId()), 'target_groups']);

        return $targetGroups;
    }

    public function setOverrideTargetGroup(TargetGroup $overrideTargetGroup = null)
    {
        $this->overrideTargetGroup = $overrideTargetGroup;
    }

    /**
     * @return null|TargetGroup
     */
    public function getOverrideTargetGroup()
    {
        return $this->overrideTargetGroup;
    }
}

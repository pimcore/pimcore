<?php
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
use Pimcore\Targeting\Document\DocumentTargetingHandler;
use Pimcore\Targeting\Model\VisitorInfo;
use Pimcore\Targeting\VisitorInfoStorageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class PimcoreTargetingDataCollector extends DataCollector
{
    /**
     * @var VisitorInfoStorageInterface
     */
    private $visitorInfoStorage;

    /**
     * @var DocumentTargetingHandler
     */
    private $documentTargetingHandler;

    /**
     * @var DocumentResolver
     */
    private $documentResolver;

    public function __construct(
        VisitorInfoStorageInterface $visitorInfoStorage,
        DocumentTargetingHandler $documentTargetingHandler,
        DocumentResolver $documentResolver
    )
    {
        $this->visitorInfoStorage       = $visitorInfoStorage;
        $this->documentTargetingHandler = $documentTargetingHandler;
        $this->documentResolver         = $documentResolver;
    }

    /**
     * @inheritDoc
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = [];

        if (!$this->visitorInfoStorage->hasVisitorInfo()) {
            return;
        }

        $visitorInfo = $this->visitorInfoStorage->getVisitorInfo();

        $this->collectMatchedRules($visitorInfo);
        $this->collectAssignedTargetGroups($visitorInfo);
        $this->collectDocumentTargetGroup($request);
    }

    private function collectMatchedRules(VisitorInfo $visitorInfo)
    {
        $rules = [];
        foreach ($visitorInfo->getMatchingTargetingRules() as $rule) {
            $rules[] = [
                'id'   => $rule->getId(),
                'name' => $rule->getName()
            ];
        }

        $this->data['rules'] = $rules;
    }

    private function collectAssignedTargetGroups(VisitorInfo $visitorInfo)
    {
        $targetGroups = [];

        foreach ($visitorInfo->getTargetGroupAssignments() as $assignment) {
            $targetGroups[] = [
                'id'    => $assignment->getTargetGroup()->getId(),
                'name'  => $assignment->getTargetGroup()->getName(),
                'count' => $assignment->getCount()
            ];
        }

        $this->data['target_groups'] = $targetGroups;
    }

    private function collectDocumentTargetGroup(Request $request)
    {
        $document = $this->documentResolver->getDocument($request);
        if (!$document) {
            return;
        }

        $targetGroup = $this->documentTargetingHandler->getConfiguredTargetGroup($document);

        if (!$targetGroup) {
            return;
        }

        $this->data['document_target_group'] = [
            'id'   => $targetGroup->getId(),
            'name' => $targetGroup->getName()
        ];
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'pimcore_targeting';
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
        return $this->data['document_target_group'];
    }

    public function hasData(): bool
    {
        return count($this->getRules()) > 0 || count($this->getTargetGroups()) > 0;
    }
}

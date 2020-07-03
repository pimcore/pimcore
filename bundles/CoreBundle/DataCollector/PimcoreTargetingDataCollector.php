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
use Pimcore\Targeting\Debug\TargetingDataCollector;
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
     * @var DocumentResolver
     */
    private $documentResolver;

    /**
     * @var TargetingDataCollector
     */
    private $targetingDataCollector;

    public function __construct(
        VisitorInfoStorageInterface $visitorInfoStorage,
        DocumentResolver $documentResolver,
        TargetingDataCollector $targetingDataCollector
    ) {
        $this->visitorInfoStorage = $visitorInfoStorage;
        $this->documentResolver = $documentResolver;
        $this->targetingDataCollector = $targetingDataCollector;
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

        $document = $this->documentResolver->getDocument($request);
        $visitorInfo = $this->visitorInfoStorage->getVisitorInfo();
        $tdc = $this->targetingDataCollector;

        $data = [
            'visitor_info' => $tdc->collectVisitorInfo($visitorInfo),
            'storage' => $tdc->collectStorage($visitorInfo),
            'rules' => $tdc->collectMatchedRules($visitorInfo),
            'target_groups' => $tdc->collectTargetGroups($visitorInfo),
            'document_target_group' => $tdc->collectDocumentTargetGroup($document),
            'document_target_groups' => $tdc->collectDocumentTargetGroupMapping(),
        ];

        $this->data = $this->cloneVar($data);
    }

    public function reset()
    {
        $this->data = [];
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
        return $this->data['document_target_group'];
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

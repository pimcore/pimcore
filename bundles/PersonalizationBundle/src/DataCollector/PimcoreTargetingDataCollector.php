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

namespace Pimcore\Bundle\PersonalizationBundle\DataCollector;

use Pimcore\Bundle\PersonalizationBundle\Targeting\Debug\TargetingDataCollector;
use Pimcore\Bundle\PersonalizationBundle\Targeting\VisitorInfoStorageInterface;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal
 */
class PimcoreTargetingDataCollector extends DataCollector implements ResetInterface
{
    public function __construct(
        private VisitorInfoStorageInterface $visitorInfoStorage,
        private DocumentResolver $documentResolver,
        private TargetingDataCollector $targetingDataCollector
    ) {
    }

    public function getName(): string
    {
        return 'pimcore_targeting';
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
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

    public function reset(): void
    {
        $this->data = [];
    }

    public function getVisitorInfo(): array|Data
    {
        return $this->data['visitor_info'];
    }

    public function getStorage(): array|Data
    {
        return $this->data['storage'];
    }

    public function getRules(): array|Data
    {
        return $this->data['rules'];
    }

    public function getTargetGroups(): array|Data
    {
        return $this->data['target_groups'];
    }

    public function getDocumentTargetGroup(): null|array|Data
    {
        return $this->data['document_target_group'];
    }

    public function getDocumentTargetGroups(): array|Data
    {
        return $this->data['document_target_groups'];
    }

    public function hasData(): bool
    {
        return !empty($this->data);
    }
}

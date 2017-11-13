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

use Pimcore\Model\Document;
use Pimcore\Model\Document\Page;
use Pimcore\Targeting\TargetingStorageInterface;

class DocumentTargetingHandler
{
    /**
     * @var TargetingStorageInterface
     */
    private $targetingStorage;

    public function __construct(TargetingStorageInterface $targetingStorage)
    {
        $this->targetingStorage = $targetingStorage;
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

        if (!$this->targetingStorage->hasVisitorInfo()) {
            return;
        }

        $visitorInfo = $this->targetingStorage->getVisitorInfo();

        $targetGroup = $visitorInfo->getMostRelevantTargetGroup();
        if (null === $targetGroup) {
            return;
        }

        $document->setUsePersona($targetGroup->getId());
    }
}

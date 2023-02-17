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

namespace Pimcore\Bundle\PersonalizationBundle\Event\Targeting;

use Pimcore\Bundle\PersonalizationBundle\Model\Tool\Targeting\TargetGroup;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Model\VisitorInfo;
use Pimcore\Model\Document;

class AssignDocumentTargetGroupEvent extends TargetingEvent
{
    private Document $document;

    private TargetGroup $targetGroup;

    public function __construct(VisitorInfo $visitorInfo, Document $document, TargetGroup $targetGroup)
    {
        parent::__construct($visitorInfo);

        $this->document = $document;
        $this->targetGroup = $targetGroup;
    }

    public function getDocument(): Document
    {
        return $this->document;
    }

    public function getTargetGroup(): TargetGroup
    {
        return $this->targetGroup;
    }
}

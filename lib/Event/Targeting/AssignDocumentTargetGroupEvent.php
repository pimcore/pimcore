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

namespace Pimcore\Event\Targeting;

use Pimcore\Model\Document;
use Pimcore\Model\Tool\Targeting\TargetGroup;
use Pimcore\Targeting\Model\VisitorInfo;

class AssignDocumentTargetGroupEvent extends TargetingEvent
{
    /**
     * @var Document
     */
    private $document;

    /**
     * @var TargetGroup
     */
    private $targetGroup;

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

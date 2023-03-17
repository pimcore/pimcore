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

namespace Pimcore\Bundle\AdminBundle\Event\Model;

use Pimcore\Bundle\AdminBundle\Event\Traits\ElementDeleteInfoEventTrait;
use Pimcore\Event\Model\DocumentEvent;

class DocumentDeleteInfoEvent extends DocumentEvent implements ElementDeleteInfoEventInterface
{
    use ElementDeleteInfoEventTrait;
}

@class_alias(DocumentDeleteInfoEvent::class, 'Pimcore\Event\Model\DocumentDeleteInfoEvent');

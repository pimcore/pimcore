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

namespace Pimcore\Event\Model\Document;

@trigger_error(sprintf('Class "%s" is deprecated since v6.8 and will be removed in Pimcore 10. Use "%s" instead.', TagNameEvent::class, EditableNameEvent::class), E_USER_DEPRECATED);

class_exists(EditableNameEvent::class);

if (false) {
    /**
     * @deprecated use \Pimcore\Event\Model\Document\EditableNameEvent instead.
     */
    class TagNameEvent extends EditableNameEvent
    {
    }
}

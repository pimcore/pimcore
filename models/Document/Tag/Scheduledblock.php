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
 * @category   Pimcore
 * @package    Document
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Tag;

use Pimcore\Model\Document\Editable\Scheduledblock as EditableScheduledblock;

@trigger_error(sprintf('Class "%s" is deprecated since v6.8 and will be removed in Pimcore 10. Use "%s" instead.', Scheduledblock::class, EditableScheduledblock::class), E_USER_DEPRECATED);

class_exists(EditableScheduledblock::class);

if (false) {
    /**
     * @deprecated use \Pimcore\Model\Document\Editable\Scheduledblock instead.
     */
    class Scheduledblock extends EditableScheduledblock
    {
    }
}

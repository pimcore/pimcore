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
 * @package    Element
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject;

@trigger_error(
    'Interface Pimcore\Model\DataObject\DirtyIndicatorInterface is deprecated since version 6.6.0 and will be removed in 7.0.0. ' .
    ' Use ' . \Pimcore\Model\Element\DirtyIndicatorInterface::class . ' interface instead.',
    E_USER_DEPRECATED
);

class_exists(\Pimcore\Model\Element\DirtyIndicatorInterface::class);

if (false) {
    /**
     * @deprecated use \Pimcore\Model\Element\DirtyIndicatorInterface instead.
     */
    interface DirtyIndicatorInterface extends \Pimcore\Model\Element\DirtyIndicatorInterface
    {
    }
}

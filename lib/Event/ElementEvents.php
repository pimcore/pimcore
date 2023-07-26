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

namespace Pimcore\Event;

final class ElementEvents
{
    /**
     * Allows you to modify whether a permission on an element is granted or not
     *
     * Subject: \Pimcore\Model\Element\AbstractElement
     * Arguments:
     *  - isAllowed | bool | the original "isAllowed" value as determined by pimcore. This can be modfied
     *  - permissionType | string | the permission that is checked
     *  - user | \Pimcore\Model\User | user the permission is checked for
     *
     * @Event("Pimcore\Event\Model\ElementEvent")
     *
     * @var string
     */
    const ELEMENT_PERMISSION_IS_ALLOWED = 'pimcore.element.permissions.isAllowed';
}

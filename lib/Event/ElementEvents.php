<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
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

    /**
     * Arguments:
     *  - elementId
     *   - elementType
     *
     * @Event("Pimcore\Event\Model\ElementEvent")
     *
     * @var string
     */
    const POST_ELEMENT_UNLOCK_PROPAGATE = 'pimcore.element.postUnlockPropagate';
}

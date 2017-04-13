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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Event;

final class ObjectCustomLayoutEvents
{
    /**
     * @Event("Pimcore\Event\Model\Object\CustomLayoutEvent")
     *
     * @var string
     */
    const PRE_ADD = 'pimcore.object.customLayout.preAdd';

    /**
     * @Event("Pimcore\Event\Model\Object\CustomLayoutEvent")
     *
     * @var string
     */
    const PRE_UPDATE = 'pimcore.object.customLayout.preUpdate';
}

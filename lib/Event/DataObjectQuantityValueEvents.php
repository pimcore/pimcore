<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Event;

final class DataObjectQuantityValueEvents
{
    /**
     * @Event("Pimcore\Event\Model\DataObject\QuantityValueUnitEvent")
     *
     * @var string
     */
    const UNIT_PRE_ADD = 'pimcore.dataobject.quantityvalue.unit.preAdd';

    /**
     * @Event("Pimcore\Event\Model\DataObject\QuantityValueUnitEvent")
     *
     * @var string
     */
    const UNIT_POST_ADD = 'pimcore.dataobject.quantityvalue.unit.postAdd';

    /**
     * @Event("Pimcore\Event\Model\DataObject\QuantityValueUnitEvent")
     *
     * @var string
     */
    const UNIT_PRE_UPDATE = 'pimcore.dataobject.quantityvalue.unit.preUpdate';

    /**
     * @Event("Pimcore\Event\Model\DataObject\QuantityValueUnitEvent")
     *
     * @var string
     */
    const UNIT_POST_UPDATE = 'pimcore.dataobject.quantityvalue.unit.postUpdate';

    /**
     * @Event("Pimcore\Event\Model\DataObject\QuantityValueUnitEvent")
     *
     * @var string
     */
    const UNIT_PRE_DELETE = 'pimcore.dataobject.quantityvalue.unit.preDelete';

    /**
     * @Event("Pimcore\Event\Model\DataObject\QuantityValueUnitEvent")
     *
     * @var string
     */
    const UNIT_POST_DELETE = 'pimcore.dataobject.quantityvalue.unit.postDelete';
}

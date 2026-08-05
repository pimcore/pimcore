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

final class DataObjectCustomLayoutEvents
{
    /**
     * @Event("Pimcore\Event\Model\DataObject\CustomLayoutEvent")
     *
     * @var string
     */
    const PRE_ADD = 'pimcore.dataobject.customLayout.preAdd';

    /**
     * @Event("Pimcore\Event\Model\DataObject\CustomLayoutEvent")
     *
     * @var string
     */
    const PRE_UPDATE = 'pimcore.dataobject.customLayout.preUpdate';
}

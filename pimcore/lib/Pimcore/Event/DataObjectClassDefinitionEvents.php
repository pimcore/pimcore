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

final class DataObjectClassDefinitionEvents
{
    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassDefinitionEvent")
     *
     * @var string
     */
    const PRE_ADD = 'pimcore.class.preAdd';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassDefinitionEvent")
     *
     * @var string
     */
    const POST_ADD = 'pimcore.class.postAdd';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassDefinitionEvent")
     *
     * @var string
     */
    const PRE_UPDATE = 'pimcore.class.preUpdate';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassDefinitionEvent")
     *
     * @var string
     */
    const POST_UPDATE = 'pimcore.class.postUpdate';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassDefinitionEvent")
     *
     * @var string
     */
    const PRE_DELETE = 'pimcore.class.preDelete';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassDefinitionEvent")
     *
     * @var string
     */
    const POST_DELETE = 'pimcore.class.postDelete';
}

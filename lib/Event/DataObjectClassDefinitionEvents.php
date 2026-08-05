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

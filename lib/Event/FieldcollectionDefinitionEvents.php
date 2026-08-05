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

final class FieldcollectionDefinitionEvents
{
    /**
     * @Event("Pimcore\Event\Model\DataObject\FieldcollectionDefinitionEvent")
     *
     * @var string
     */
    const PRE_ADD = 'pimcore.fieldcollection.preAdd';

    /**
     * @Event("Pimcore\Event\Model\DataObject\FieldcollectionDefinitionEvent")
     *
     * @var string
     */
    const POST_ADD = 'pimcore.fieldcollection.postAdd';

    /**
     * @Event("Pimcore\Event\Model\DataObject\FieldcollectionDefinitionEvent")
     *
     * @var string
     */
    const PRE_UPDATE = 'pimcore.fieldcollection.preUpdate';

    /**
     * @Event("Pimcore\Event\Model\DataObject\FieldcollectionDefinitionEvent")
     *
     * @var string
     */
    const POST_UPDATE = 'pimcore.fieldcollection.postUpdate';

    /**
     * @Event("Pimcore\Event\Model\DataObject\FieldcollectionDefinitionEvent")
     *
     * @var string
     */
    const PRE_DELETE = 'pimcore.fieldcollection.preDelete';

    /**
     * @Event("Pimcore\Event\Model\DataObject\FieldcollectionDefinitionEvent")
     *
     * @var string
     */
    const POST_DELETE = 'pimcore.fieldcollection.postDelete';
}

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

final class TagEvents
{
    /**
     * @Event("Pimcore\Event\Model\TagEvent")
     *
     * @var string
     */
    const PRE_ADD = 'pimcore.tag.preAdd';

    /**
     * @Event("Pimcore\Event\Model\TagEvent")
     *
     * @var string
     */
    const POST_ADD = 'pimcore.tag.postAdd';

    /**
     * @Event("Pimcore\Event\Model\TagEvent")
     *
     * @var string
     */
    const PRE_UPDATE = 'pimcore.tag.preUpdate';

    /**
     * @Event("Pimcore\Event\Model\TagEvent")
     *
     * @var string
     */
    const POST_UPDATE = 'pimcore.tag.postUpdate';

    /**
     * @Event("Pimcore\Event\Model\TagEvent")
     *
     * @var string
     */
    const PRE_DELETE = 'pimcore.tag.preDelete';

    /**
     * @Event("Pimcore\Event\Model\TagEvent")
     *
     * @var string
     */
    const POST_DELETE = 'pimcore.tag.postDelete';

    /**
     * Arguments:
     *  - elementType
     *  - elementId
     *
     * @Event("Pimcore\Event\Model\TagEvent")
     *
     * @var string
     */
    const PRE_ADD_TO_ELEMENT = 'pimcore.tag.preAddToElement';

    /**
     * Arguments:
     *  - elementType
     *  - elementId
     *
     * @Event("Pimcore\Event\Model\TagEvent")
     *
     * @var string
     */
    const POST_ADD_TO_ELEMENT = 'pimcore.tag.postAddToElement';

    /**
     * Arguments:
     *  - elementType
     *  - elementId
     *
     * @Event("Pimcore\Event\Model\TagEvent")
     *
     * @var string
     */
    const PRE_REMOVE_FROM_ELEMENT = 'pimcore.tag.preRemoveFromElement';

    /**
     * Arguments:
     *  - elementType
     *  - elementId
     *
     * @Event("Pimcore\Event\Model\TagEvent")
     *
     * @var string
     */
    const POST_REMOVE_FROM_ELEMENT = 'pimcore.tag.postRemoveFromElement';

    /**
     * Arguments:
     *  - tagIds
     *  - elementType
     *  - elementId
     *
     * @Event("Pimcore\Event\Model\TagEvent")
     *
     * @var string
     */
    const POST_BATCH_ASSIGN_TAGS_TO_ELEMENT = 'pimcore.tag.postBatchAssignTagsToElement';
}

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

final class DocumentEvents
{
    /**
     * @Event("Pimcore\Event\Model\DocumentEvent")
     *
     * @var string
     */
    const PRE_ADD = 'pimcore.document.preAdd';

    /**
     * @Event("Pimcore\Event\Model\DocumentEvent")
     *
     * @var string
     */
    const POST_ADD = 'pimcore.document.postAdd';

    /**
     * Arguments:
     *  - exception | exception object
     *
     * @Event("Pimcore\Event\Model\DocumentEvent")
     *
     * @var string
     */
    const POST_ADD_FAILURE = 'pimcore.document.postAddFailure';

    /**
     * Arguments:
     *  - saveVersionOnly | is set if method saveVersion() was called instead of save()
     *
     * @Event("Pimcore\Event\Model\DocumentEvent")
     *
     * @var string
     */
    const PRE_UPDATE = 'pimcore.document.preUpdate';

    /**
     * Arguments:
     *  - saveVersionOnly | is set if method saveVersion() was called instead of save()
     *  - oldPath | the old full path in case the path has changed
     *
     * @Event("Pimcore\Event\Model\DocumentEvent")
     *
     * @var string
     */
    const POST_UPDATE = 'pimcore.document.postUpdate';

    /**
     * Arguments:
     *  - saveVersionOnly | is set if method saveVersion() was called instead of save()
     *  - exception | exception object
     *
     * @Event("Pimcore\Event\Model\DocumentEvent")
     *
     * @var string
     */
    const POST_UPDATE_FAILURE = 'pimcore.document.postUpdateFailure';

    /**
     * @Event("Pimcore\Event\Model\DocumentDeleteInfoEvent")
     *
     * @var string
     */
    const DELETE_INFO = 'pimcore.document.deleteInfo';

    /**
     * @Event("Pimcore\Event\Model\DocumentEvent")
     *
     * @var string
     */
    const PRE_DELETE = 'pimcore.document.preDelete';

    /**
     * @Event("Pimcore\Event\Model\DocumentEvent")
     *
     * @var string
     */
    const POST_DELETE = 'pimcore.document.postDelete';

    /**
     * Arguments:
     *  - exception | exception object
     *
     * @Event("Pimcore\Event\Model\DocumentEvent")
     *
     * @var string
     */
    const POST_DELETE_FAILURE = 'pimcore.document.postDeleteFailure';

    /**
     * Arguments:
     *  - params | array | contains the values that were passed to getById() as the second parameter
     *
     * @Event("Pimcore\Event\Model\DocumentEvent")
     *
     * @var string
     */
    const POST_LOAD = 'pimcore.document.postLoad';

    /**
     * Arguments:
     *  - target_element | Pimcore\Model\Document | contains the target document used in copying process
     *
     * @Event("Pimcore\Event\Model\DocumentEvent")
     *
     * @var string
     */
    const PRE_COPY = 'pimcore.document.preCopy';

    /**
     * Arguments:
     *  - base_element | Pimcore\Model\Document | contains the base document used in copying process
     *
     * @Event("Pimcore\Event\Model\DocumentEvent")
     *
     * @var string
     */
    const POST_COPY = 'pimcore.document.postCopy';

    /**
     * The EDITABLE_NAME event is triggered when a document editable name is built.
     *
     * @Event("Pimcore\Event\Model\Document\EditableNameEvent")
     *
     */
    const EDITABLE_NAME = 'pimcore.document.editable.name';

    /**
     * The RENDERER_PRE_RENDER event is triggered before the DocumentRenderer renders a document
     *
     * @Event("Pimcore\Event\Model\DocumentEvent")
     *
     * @var string
     */
    const RENDERER_PRE_RENDER = 'pimcore.document.renderer.pre_render';

    /**
     * The RENDERER_POST_RENDER event is triggered after the DocumentRenderer rendered a document
     *
     * @Event("Pimcore\Event\Model\DocumentEvent")
     *
     * @var string
     */
    const RENDERER_POST_RENDER = 'pimcore.document.renderer.post_render';

    /**
     * The INCLUDERENDERER_PRE_RENDER event is triggered before the IncludeRenderer renders an include
     *
     * @Event("Pimcore\Event\Model\DocumentEvent")
     *
     * @var string
     */
    const INCLUDERENDERER_PRE_RENDER = 'pimcore.document.IncludeRenderer.pre_render';

    /**
     * Arguments:
     *  - element | \Pimcore\Mail | the pimcore mail instance
     *  - requestParams | contains the request parameters
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    const EDITABLE_RENDERLET_PRE_RENDER = 'pimcore.document.editable.renderlet.pre_render';

    /**
     *
     * @Event("Pimcore\Event\Model\DocumentEvent")
     *
     * @var string
     */
    const PAGE_POST_SAVE_ACTION = 'pimcore.document.page.post_save_action';

    /**
     *
     * @Event("Pimcore\Event\Model\DocumentEvent")
     *
     * @var string
     */
    const POST_MOVE_ACTION = 'pimcore.document.post_move_action';
}

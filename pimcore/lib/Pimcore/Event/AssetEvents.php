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

final class AssetEvents
{
    /**
     * @Event("Pimcore\Event\Model\AssetEvent")
     * @var string
     */
    const PRE_ADD = 'pimcore.asset.preAdd';

    /**
     * @Event("Pimcore\Event\Model\AssetEvent")
     * @var string
     */
    const POST_ADD = 'pimcore.asset.postAdd';

    /**
     * Arguments:
     *  - saveVersionOnly | is set if method saveVersion() was called instead of save()
     *
     * @Event("Pimcore\Event\Model\AssetEvent")
     * @var string
     */
    const PRE_UPDATE = 'pimcore.asset.preUpdate';

    /**
     * Arguments:
     *  - saveVersionOnly | is set if method saveVersion() was called instead of save()
     *
     * @Event("Pimcore\Event\Model\AssetEvent")
     * @var string
     */
    const POST_UPDATE = 'pimcore.asset.postUpdate';

    /**
     * @Event("Pimcore\Event\Model\AssetEvent")
     * @var string
     */
    const PRE_DELETE = 'pimcore.asset.preDelete';

    /**
     * @Event("Pimcore\Event\Model\AssetEvent")
     * @var string
     */
    const POST_DELETE = 'pimcore.asset.postDelete';

    /**
     * Arguments:
     *  - base_element | Pimcore\Model\Document | contains the base document used in copying process
     *
     * @Event("Pimcore\Event\Model\AssetEvent")
     * @var string
     */
    const POST_COPY = 'pimcore.asset.postCopy';

    /**
     * Fires after the thumbnail was created
     *
     * Arguments:
     *  - deferred | bool | Whether the thumbnail should be generated on demand or not
     *  - generated | bool | Whether a new thumbnail file was actually generated or not (from cache)
     *
     * @Event("Pimcore\Event\Model\GenericEvent")
     * @var string
     */
    const IMAGE_THUMBNAIL = 'pimcore.asset.image.thumbnail';

    /**
     * Fires after the image thumbnail was created
     *
     * Arguments:
     *  - deferred | bool | Whether the thumbnail should be generated on demand or not
     *  - generated | bool | Whether a new thumbnail file was actually generated or not (from cache)
     *
     * @Event("Pimcore\Event\Model\GenericEvent")
     * @var string
     */
    const VIDEO_IMAGE_THUMBNAIL = 'pimcore.asset.video.image-thumbnail';

    /**
     * Fires after the image thumbnail was created
     *
     * Arguments:
     *  - deferred | bool | Whether the thumbnail should be generated on demand or not
     *  - generated | bool | Whether a new thumbnail file was actually generated or not (from cache)
     *
     * @Event("Pimcore\Event\Model\GenericEvent")
     * @var string
     */
    const DOCUMENT_IMAGE_THUMBNAIL = 'pimcore.asset.document.image-thumbnail';
}

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

final class FrontendEvents
{
    /**
     * Allows to rewrite the frontend path of an image thumbnail
     * Overwrite the argument "frontendPath" to do so
     *
     * Subject: Pimcore\Model\Asset\Image\Thumbnail
     * Arguments:
     *  - filesystemPath | string | Absolute path of the thumbnail on the filesystem
     *  - frontendPath | string | Web-path, relative
     *
     * @Event("Pimcore\Event\Model\GenericEvent")
     * @var string
     */
    const ASSET_IMAGE_THUMBNAIL = "pimcore.frontend.path.asset.image.thumbnail";

    /**
     * Allows to rewrite the frontend path of an video image thumbnail
     * Overwrite the argument "frontendPath" to do so
     *
     * Subject: Pimcore\Model\Asset\Video\ImageThumbnail
     * Arguments:
     *  - filesystemPath | string | Absolute path of the thumbnail on the filesystem
     *  - frontendPath | string | Web-path, relative
     *
     * @Event("Pimcore\Event\Model\GenericEvent")
     * @var string
     */
    const ASSET_VIDEO_IMAGE_THUMBNAIL = "pimcore.frontend.path.asset.video.image-thumbnail";

    /**
     * Allows to rewrite the frontend path of an video thumbnail (mp4)
     * Overwrite the argument "frontendPath" to do so
     *
     * Subject: Pimcore\Model\Asset\Video
     * Arguments:
     *  - filesystemPath | string | Absolute path of the thumbnail on the filesystem
     *  - frontendPath | string | Web-path, relative
     *
     * @Event("Pimcore\Event\Model\GenericEvent")
     * @var string
     */
    const ASSET_VIDEO_THUMBNAIL = "pimcore.frontend.path.asset.video.thumbnail";

    /**
     * Allows to rewrite the frontend path of an video thumbnail (mp4)
     * Overwrite the argument "frontendPath" to do so
     *
     * Subject: 	Pimcore\Model\Asset\Document\ImageThumbnail
     * Arguments:
     *  - filesystemPath | string | Absolute path of the thumbnail on the filesystem
     *  - frontendPath | string | Web-path, relative
     *
     * @Event("Pimcore\Event\Model\GenericEvent")
     * @var string
     */
    const ASSET_DOCUMENT_IMAGE_THUMBNAIL = "pimcore.frontend.path.asset.document.image-thumbnail";

    /**
     * Allows to rewrite the frontend path of an asset (no matter which type)
     * Overwrite the argument "frontendPath" to do so
     *
     * Subject: 	Pimcore\Model\Asset
     * Arguments:
     *  - frontendPath | string | Web-path, relative
     *
     * @Event("Pimcore\Event\Model\GenericEvent")
     * @var string
     */
    const ASSET_PATH = "pimcore.frontend.path.asset";

    /**
     * Allows to rewrite the frontend path of a document (no matter which type)
     * Overwrite the argument "frontendPath" to do so
     *
     * Subject: 	Pimcore\Model\Document
     * Arguments:
     *  - frontendPath | string | Web-path, relative
     *
     * @Event("Pimcore\Event\Model\GenericEvent")
     * @var string
     */
    const DOCUMENT_PATH = "pimcore.frontend.path.document";

    /**
     * Allows to rewrite the frontend path of a static route
     * Overwrite the argument "frontendPath" to do so
     *
     * Subject: 	Pimcore\Model\Staticroute
     * Arguments:
     *  - frontendPath | string | Web-path, relative
     *  - params | array
     *  - reset | bool
     *  - encode | bool
     *
     * @Event("Pimcore\Event\Model\GenericEvent")
     * @var string
     */
    const STATICROUTE_PATH = "pimcore.frontend.path.staticroute";

    /**
     * Subject: 	\Pimcore\Bundle\PimcoreBundle\Templating\Helper\HeadLink
     * Arguments:
     *  - item | stdClass
     *
     * @Event("Pimcore\Event\Model\GenericEvent")
     * @var string
     */
    const VIEW_HELPER_HEAD_LINK = "pimcore.frontend.view.helper.head-link";

    /**
     * Subject: 	\Pimcore\Bundle\PimcoreBundle\Templating\Helper\HeadScript
     * Arguments:
     *  - item | stdClass
     *
     * @Event("Pimcore\Event\Model\GenericEvent")
     * @var string
     */
    const VIEW_HELPER_HEAD_SCRIPT = "pimcore.frontend.view.helper.head-script";
}

<?php

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
     * @Event("Pimcore\Event\Model\AssetEvent")
     * @var string
     */
    const PRE_UPDATE = 'pimcore.asset.preUpdate';

    /**
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
     * @Event("Pimcore\Event\Model\AssetEvent")
     * @var string
     */
    const POST_COPY = 'pimcore.asset.postCopy';

    /**
     * @Event("Pimcore\Event\Model\GenericEvent")
     * @var string
     */
    const IMAGE_THUMBNAIL = 'pimcore.asset.image.thumbnail';

    /**
     * @Event("Pimcore\Event\Model\GenericEvent")
     * @var string
     */
    const VIDEO_IMAGE_THUMBNAIL = 'pimcore.asset.video.image-thumbnail';

    /**
     * @Event("Pimcore\Event\Model\GenericEvent")
     * @var string
     */
    const DOCUMENT_IMAGE_THUMBNAIL = 'pimcore.asset.document.image-thumbnail';
}
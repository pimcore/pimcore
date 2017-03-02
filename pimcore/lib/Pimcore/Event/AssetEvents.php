<?php

namespace Pimcore\Event;

final class AssetEvents
{
    /**
     * @Event("Pimcore\Event\Element\AssetEvent")
     * @var string
     */
    const PRE_ADD = 'pimcore.asset.preAdd';

    /**
     * @Event("Pimcore\Event\Element\AssetEvent")
     * @var string
     */
    const POST_ADD = 'pimcore.asset.postAdd';

    /**
     * @Event("Pimcore\Event\Element\AssetEvent")
     * @var string
     */
    const PRE_UPDATE = 'pimcore.asset.preUpdate';

    /**
     * @Event("Pimcore\Event\Element\AssetEvent")
     * @var string
     */
    const POST_UPDATE = 'pimcore.asset.postUpdate';

    /**
     * @Event("Pimcore\Event\Element\AssetEvent")
     * @var string
     */
    const PRE_DELETE = 'pimcore.asset.preDelete';

    /**
     * @Event("Pimcore\Event\Element\AssetEvent")
     * @var string
     */
    const POST_DELETE = 'pimcore.asset.postDelete';

    /**
     * @Event("Pimcore\Event\Element\AssetEvent")
     * @var string
     */
    const POST_COPY = 'pimcore.asset.postCopy';

    /**
     * @Event("Pimcore\Event\Element\GenericEvent")
     * @var string
     */
    const IMAGE_THUMBNAIL = 'pimcore.asset.image.thumbnail';

    /**
     * @Event("Pimcore\Event\Element\GenericEvent")
     * @var string
     */
    const VIDEO_IMAGE_THUMBNAIL = 'pimcore.asset.video.image-thumbnail';

    /**
     * @Event("Pimcore\Event\Element\GenericEvent")
     * @var string
     */
    const DOCUMENT_IMAGE_THUMBNAIL = 'pimcore.asset.document.image-thumbnail';
}
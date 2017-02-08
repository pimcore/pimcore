<?php

namespace WebsiteDemoBundle\Document\Areabrick;

use Pimcore\Document\Area\AbstractTemplateAreabrick;

class ImageHotspotMarker extends AbstractTemplateAreabrick
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'image-hotspot-marker';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Image Hotspot & Marker';
    }
}

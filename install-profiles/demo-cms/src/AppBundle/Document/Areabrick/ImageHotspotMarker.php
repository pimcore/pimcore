<?php

namespace AppBundle\Document\Areabrick;

use Pimcore\Extension\Document\Areabrick\AbstractTemplateAreabrick;

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

<?php

namespace AppBundle\Document\Areabrick;

class ImageHotspotMarker extends AbstractAreabrick
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

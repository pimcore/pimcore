<?php

namespace AppBundle\Document\Areabrick;

class GalleryCarousel extends AbstractAreabrick
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'gallery-carousel';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Gallery (Carousel)';
    }
}

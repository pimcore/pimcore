<?php

namespace AppBundle\Document\Areabrick;

use Pimcore\Extension\Document\Areabrick\AbstractTemplateAreabrick;

class GalleryCarousel extends AbstractTemplateAreabrick
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

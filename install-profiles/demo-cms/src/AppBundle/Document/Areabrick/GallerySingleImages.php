<?php

namespace AppBundle\Document\Areabrick;

class GallerySingleImages extends AbstractAreabrick
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'gallery-single-images';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Gallery (Single)';
    }
}

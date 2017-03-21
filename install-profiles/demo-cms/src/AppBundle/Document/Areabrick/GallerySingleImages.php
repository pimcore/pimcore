<?php

namespace AppBundle\Document\Areabrick;

use Pimcore\Extension\Document\Areabrick\AbstractTemplateAreabrick;

class GallerySingleImages extends AbstractTemplateAreabrick
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

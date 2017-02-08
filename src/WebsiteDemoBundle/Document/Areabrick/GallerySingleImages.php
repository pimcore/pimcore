<?php

namespace WebsiteDemoBundle\Document\Areabrick;

use Pimcore\Document\Area\AbstractTemplateAreabrick;

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

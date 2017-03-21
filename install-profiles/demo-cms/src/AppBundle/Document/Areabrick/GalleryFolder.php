<?php

namespace AppBundle\Document\Areabrick;

use Pimcore\Extension\Document\Areabrick\AbstractTemplateAreabrick;

class GalleryFolder extends AbstractTemplateAreabrick
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'gallery-folder';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Gallery (Folder)';
    }
}

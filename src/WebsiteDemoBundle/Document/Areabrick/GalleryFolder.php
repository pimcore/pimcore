<?php

namespace WebsiteDemoBundle\Document\Areabrick;

use Pimcore\Document\Area\AbstractTemplateAreabrick;

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

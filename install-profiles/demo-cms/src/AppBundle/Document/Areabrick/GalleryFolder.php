<?php

namespace AppBundle\Document\Areabrick;

class GalleryFolder extends AbstractAreabrick
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

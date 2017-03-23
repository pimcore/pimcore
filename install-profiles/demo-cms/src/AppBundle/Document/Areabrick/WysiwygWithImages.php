<?php

namespace AppBundle\Document\Areabrick;

class WysiwygWithImages extends AbstractAreabrick
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'wysiwyg-with-images';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'WYSIWYG w. Images';
    }
}

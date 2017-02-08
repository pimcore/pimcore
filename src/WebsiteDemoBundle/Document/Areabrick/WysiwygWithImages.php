<?php

namespace WebsiteDemoBundle\Document\Areabrick;

use Pimcore\Document\Area\AbstractTemplateAreabrick;

class WysiwygWithImages extends AbstractTemplateAreabrick
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

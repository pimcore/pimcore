<?php

namespace WebsiteDemoBundle\Document\Area;

use Pimcore\Document\Area\AbstractTemplateAreabrick;

class Wysiwyg extends AbstractTemplateAreabrick
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'wysiwyg';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'WYSIWYG';
    }
}

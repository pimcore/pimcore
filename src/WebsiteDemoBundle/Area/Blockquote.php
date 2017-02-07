<?php

namespace WebsiteDemoBundle\Area;

use Pimcore\Document\Area\AbstractTemplateAreabrick;

class Blockquote extends AbstractTemplateAreabrick
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'blockquote';
    }
}

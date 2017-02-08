<?php

namespace WebsiteDemoBundle\Document\Area;

use Pimcore\Document\Area\AbstractTemplateAreabrick;

class Video extends AbstractTemplateAreabrick
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'video';
    }
}

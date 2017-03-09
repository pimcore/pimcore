<?php

namespace AppBundle\Document\Areabrick;

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

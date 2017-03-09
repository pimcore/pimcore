<?php

namespace AppBundle\Document\Areabrick;

use Pimcore\Document\Area\AbstractTemplateAreabrick;

class Image extends AbstractTemplateAreabrick
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'image';
    }
}

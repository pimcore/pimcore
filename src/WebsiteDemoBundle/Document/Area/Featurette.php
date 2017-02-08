<?php

namespace WebsiteDemoBundle\Document\Area;

use Pimcore\Document\Area\AbstractTemplateAreabrick;

class Featurette extends AbstractTemplateAreabrick
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'featurette';
    }
}

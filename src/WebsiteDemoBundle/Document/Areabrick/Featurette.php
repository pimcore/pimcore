<?php

namespace WebsiteDemoBundle\Document\Areabrick;

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

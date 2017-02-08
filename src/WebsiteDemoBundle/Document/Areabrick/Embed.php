<?php

namespace WebsiteDemoBundle\Document\Areabrick;

use Pimcore\Document\Area\AbstractTemplateAreabrick;

class Embed extends AbstractTemplateAreabrick
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'embed';
    }
}

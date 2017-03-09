<?php

namespace AppBundle\Document\Areabrick;

use Pimcore\Document\Area\AbstractTemplateAreabrick;

class IconTeaserRow extends AbstractTemplateAreabrick
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'icon-teaser-row';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Icon Teaser';
    }
}

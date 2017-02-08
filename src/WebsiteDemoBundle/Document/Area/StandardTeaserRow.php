<?php

namespace WebsiteDemoBundle\Document\Area;

use Pimcore\Document\Area\AbstractTemplateAreabrick;

class StandardTeaserRow extends AbstractTemplateAreabrick
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'standard-teaser-row';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Standard Teaser';
    }
}

<?php

namespace AppBundle\Document\Areabrick;

class StandardTeaserRow extends AbstractAreabrick
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

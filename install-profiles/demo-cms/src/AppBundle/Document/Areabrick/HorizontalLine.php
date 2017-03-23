<?php

namespace AppBundle\Document\Areabrick;

class HorizontalLine extends AbstractAreabrick
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'horizontal-line';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Horiz. Line';
    }
}

<?php

namespace AppBundle\Document\Areabrick;

use Pimcore\Extension\Document\Areabrick\AbstractTemplateAreabrick;

class HorizontalLine extends AbstractTemplateAreabrick
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

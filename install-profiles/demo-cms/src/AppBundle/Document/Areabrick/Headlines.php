<?php

namespace AppBundle\Document\Areabrick;

use Pimcore\Document\Area\AbstractTemplateAreabrick;

class Headlines extends AbstractTemplateAreabrick
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'headlines';
    }
}

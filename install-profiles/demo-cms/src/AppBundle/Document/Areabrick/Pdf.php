<?php

namespace AppBundle\Document\Areabrick;

use Pimcore\Extension\Document\Areabrick\AbstractTemplateAreabrick;

class Pdf extends AbstractTemplateAreabrick
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'pdf';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'PDF';
    }
}

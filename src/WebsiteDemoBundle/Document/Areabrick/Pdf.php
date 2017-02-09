<?php

namespace WebsiteDemoBundle\Document\Areabrick;

use Pimcore\Document\Area\AbstractTemplateAreabrick;

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

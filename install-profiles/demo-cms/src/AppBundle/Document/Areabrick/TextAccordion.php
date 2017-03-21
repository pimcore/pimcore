<?php

namespace AppBundle\Document\Areabrick;

use Pimcore\Extension\Document\Areabrick\AbstractTemplateAreabrick;

class TextAccordion extends AbstractTemplateAreabrick
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'text-accordion';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Text Accordion';
    }
}

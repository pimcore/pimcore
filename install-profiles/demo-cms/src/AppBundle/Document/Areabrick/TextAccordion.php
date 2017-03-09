<?php

namespace AppBundle\Document\Areabrick;

use Pimcore\Document\Area\AbstractTemplateAreabrick;

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

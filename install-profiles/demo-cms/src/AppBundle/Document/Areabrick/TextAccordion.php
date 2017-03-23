<?php

namespace AppBundle\Document\Areabrick;

class TextAccordion extends AbstractAreabrick
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

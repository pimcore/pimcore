<?php

namespace AppBundle\Document\Areabrick;

class Wysiwyg extends AbstractAreabrick
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'wysiwyg';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'WYSIWYG';
    }
}

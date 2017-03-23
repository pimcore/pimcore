<?php

namespace AppBundle\Document\Areabrick;

class Pdf extends AbstractAreabrick
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

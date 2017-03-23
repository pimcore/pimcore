<?php

namespace AppBundle\Document\Areabrick;

class TabbedSliderText extends AbstractAreabrick
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'tabbed-slider-text';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Slider (Tabs/Text)';
    }
}

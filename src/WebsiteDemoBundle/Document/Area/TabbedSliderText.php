<?php

namespace WebsiteDemoBundle\Document\Area;

use Pimcore\Document\Area\AbstractTemplateAreabrick;

class TabbedSliderText extends AbstractTemplateAreabrick
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

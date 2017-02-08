<?php

namespace WebsiteDemoBundle\Area;

use Pimcore\Document\Area\AbstractTemplateAreabrick;

class Blockquote extends AbstractTemplateAreabrick
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'blockquote';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplateSuffix()
    {
        // remove this method or set to TEMPLATE_SUFFIX_ZEND_VIEW to use the phtml template
        return static::TEMPLATE_SUFFIX_TWIG;
    }
}

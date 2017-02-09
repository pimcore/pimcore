<?php

namespace Pimcore\Document\Area;

use Pimcore\Model\Document\Tag\Area\Info;

abstract class AbstractAreabrick implements AreabrickInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return ucfirst($this->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function action(Info $info)
    {
        // noop - implement as needed
    }

    /**
     * {@inheritdoc}
     */
    public function postRenderAction(Info $info)
    {
        // noop - implement as needed
    }

    /**
     * {@inheritdoc}
     */
    public function getHtmlTagOpen(Info $info)
    {
        return '<div class="pimcore_area_' . $info->getId() . ' pimcore_area_content">';
    }

    /**
     * {@inheritdoc}
     */
    public function getHtmlTagClose(Info $info)
    {
        return '</div>';
    }
}

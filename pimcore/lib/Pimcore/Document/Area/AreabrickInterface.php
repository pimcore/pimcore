<?php

namespace Pimcore\Document\Area;

use Pimcore\Model\Document\Tag\Area\Info;

interface AreabrickInterface
{
    /**
     * Area ID - needs to be unique throughout the system.
     *
     * @return string
     */
    public function getId();

    /**
     * A descriptive name as shown in extension manager and edit mode.
     *
     * @return string
     */
    public function getName();

    /**
     * Area description as shown in extension manager.
     *
     * @return string
     */
    public function getDescription();

    /**
     * Get view template
     *
     * @return string|null
     */
    public function getViewTemplate();

    /**
     * Get edit template
     *
     * @return string|null
     */
    public function getEditTemplate();

    /**
     * Will be called before the view is rendered. Acts as extension point for custom area logic.
     *
     * @param Info $info
     */
    public function action(Info $info);

    /**
     * Will be called after rendering.
     *
     * @param Info $info
     */
    public function postRenderAction(Info $info);

    /**
     * Returns the brick HTML open tag.
     *
     * @param Info $info
     * @return string
     */
    public function getHtmlTagOpen(Info $info);

    /**
     * Returns the brick HTML close tag.
     *
     * @param Info $info
     * @return string
     */
    public function getHtmlTagClose(Info $info);
}

<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Extension\Document\Areabrick;

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
     * Area version as shown in extension manager.
     *
     * @return string
     */
    public function getVersion();

    /**
     * Icon as absolute path, e.g. /bundles/websitedemo/img/areas/foo/icon.png
     *
     * @return string|null
     */
    public function getIcon();

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

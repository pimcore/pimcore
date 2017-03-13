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

namespace Pimcore\API\Plugin;

interface PluginInterface
{

    /**
     * @return string $statusMessage
     */
    public static function install();

    /**
     * @return boolean $isInstalled
     */
    public static function isInstalled();

    /**
     * @return boolean $readyForInstall
     */
    public static function readyForInstall();

    /**
     * @return string $statusMessage
     */
    public static function uninstall();

    /**
     * @return string $jsClassName
     */
    public static function getJsClassName();

    /**
     * @return boolean $needsReloadAfterInstall
     */
    public static function needsReloadAfterInstall();

    /**
     * absolute path to the folder holding plugin translation files
     * @static
     * @abstract
     * @return string
     */
    public static function getTranslationFileDirectory();
}

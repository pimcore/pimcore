<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\API\Plugin;

interface PluginInterface  {

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


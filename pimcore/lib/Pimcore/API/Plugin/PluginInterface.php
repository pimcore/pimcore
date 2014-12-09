<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
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


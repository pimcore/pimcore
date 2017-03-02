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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\API\Plugin;

use Pimcore\API\AbstractAPI;
use Pimcore\Db;

class AbstractPlugin extends AbstractAPI
{

    /**
     * @var null
     */
    private $jsPaths;

    /**
     * @var null
     */
    private $cssPaths;

    /**
     *
     * @param string $language
     * @return string $languageFile for the specified language relative to plugin directory
     */
    public static function getTranslationFile($language)
    {
        return null;
    }

    /**
     * @param null $jsPaths
     * @param null $cssPaths
     */
    public function __construct($jsPaths = null, $cssPaths = null)
    {
        if (!empty($jsPaths)) {
            $this->jsPaths = $jsPaths;
        }
        if (!empty($cssPaths)) {
            $this->cssPaths = $cssPaths;
        }
    }

    /**
     * @return mixed|\Zend_Db_Adapter_Abstract
     */
    protected static function getDb()
    {
        $db = Db::get();

        return $db;
    }

    /**
     * @return null
     */
    public function getJsPaths()
    {
        return $this->jsPaths;
    }

    /**
     * @return null
     */
    public function getCssPaths()
    {
        return $this->cssPaths;
    }

    /**
     * @return string
     */
    public static function getJsClassName()
    {
        return "";
    }

    /**
     * @return bool
     */
    public static function needsReloadAfterInstall()
    {
        return false;
    }

    /**
     * @return boolean $readyForInstall
     */
    public static function readyForInstall()
    {
        return true;
    }

    /**
     * this method allows the plugin to show status messages in pimcore plugin settings
     *
     * @static
     * @return string
     */
    public static function getPluginState()
    {
        return "";
    }

    /**
     * absolute path to the folder holding plugin translation files
     * @static
     * @abstract
     * @return string
     */
    public static function getTranslationFileDirectory()
    {
        return null;
    }

    /**
     * @param $method
     * @param $args
     * @throws \Exception
     */
    public function __call($method, $args)
    {
        $legacyMethods = array_keys(self::$legacyMappings);
        $legacyMethods = array_map(function ($v) {
            return strtolower($v);
        }, $legacyMethods);

        if (in_array(strtolower($method), $legacyMethods)) {
            return;
        }

        throw new \Exception("Call to undefined method '" . $method . "' on Pimcore_API_Plugin_Abstract");
    }
}

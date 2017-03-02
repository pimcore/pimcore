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

use Pimcore\Tool;
use Pimcore\Logger;

class Broker
{
    /**
     * Array of instance of objects extending Pimcore_API_Plugin_Abstract
     *
     * @var array
     */
    protected $_plugins = [];

    /**
     * @return mixed|Broker
     * @throws \Exception
     * @deprecated Use pimcore.plugin_broker service
     */
    public static function getInstance()
    {
        $broker = \Pimcore::getKernel()->getContainer()->get('pimcore.plugin_broker');

        return $broker;
    }

    /**
     * @param AbstractPlugin $plugin
     * @param null $stackIndex
     * @return $this
     * @throws \Exception
     */
    public function registerPlugin(AbstractPlugin $plugin, $stackIndex = null)
    {
        if (false !== array_search($plugin, $this->_plugins, true)) {
            throw new \Exception('Plugin already registered');
        }

        //installed?
        if (!$plugin::isInstalled()) {
            if (is_object($plugin)) {
                $className = get_class($plugin);
                Logger::debug("Not registering plugin [ " . $className . " ] because it is not installed");
            } else {
                Logger::debug("Not registering plugin, it is not an object");
            }

            return $this;
        }


        $stackIndex = (int) $stackIndex;

        if ($stackIndex) {
            if (isset($this->_plugins[$stackIndex])) {
                throw new \Exception('Plugin with stackIndex "' . $stackIndex . '" already registered');
            }
            $this->_plugins[$stackIndex] = $plugin;
        } else {
            $stackIndex = count($this->_plugins);
            while (isset($this->_plugins[$stackIndex])) {
                ++$stackIndex;
            }
            $this->_plugins[$stackIndex] = $plugin;
        }

        ksort($this->_plugins);

        $plugin->init();

        return $this;
    }

    /**
     * @param $plugin
     * @return $this
     * @throws \Exception
     */
    public function unregisterPlugin($plugin)
    {
        if ($plugin instanceof AbstractPlugin) {
            // Given a plugin object, find it in the array
            $key = array_search($plugin, $this->_plugins, true);
            if (false === $key) {
                throw new \Exception('Plugin never registered.');
            }
            unset($this->_plugins[$key]);
        } elseif (is_string($plugin)) {
            // Given a plugin class, find all plugins of that class and unset them
            foreach ($this->_plugins as $key => $_plugin) {
                $type = get_class($_plugin);
                if ($plugin == $type) {
                    unset($this->_plugins[$key]);
                }
            }
        }

        return $this;
    }

    /**
     * Is a plugin of a particular class registered?
     *
     * @param  string $class
     * @return bool
     */
    public function hasPlugin($class)
    {
        foreach ($this->_plugins as $plugin) {
            $type = get_class($plugin);
            if ($class == $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $class
     * @return array|bool
     */
    public function getPlugin($class)
    {
        $found = [];
        foreach ($this->_plugins as $plugin) {
            $type = get_class($plugin);
            if ($class == $type) {
                $found[] = $plugin;
            }
        }

        switch (count($found)) {
            case 0:
                return false;
            case 1:
                return $found[0];
            default:
                return $found;
        }
    }

    /**
     * Retrieve all plugins
     *
     * @return array
     */
    public function getPlugins()
    {
        return $this->_plugins;
    }

    /**
     * Returns Plugins and Modules
     * @return array
     */
    public function getSystemComponents()
    {
        $plugins = (array)$this->getPlugins();

        return $plugins;
    }

    /**
     *
     * @param string $language
     * @return Array $translations
     */
    public function getTranslations($language)
    {
        $translations = [];
        foreach ($this->_plugins as $plugin) {
            try {
                $pluginLanguageFile = $plugin->getTranslationFile($language);
                if (!empty($pluginLanguageFile)) {
                    $languageFile = PIMCORE_PLUGINS_PATH . $pluginLanguageFile;

                    if (is_file($languageFile) and strtolower(substr($languageFile, -4, 4)) == ".csv") {
                        $handle = fopen($languageFile, "r");
                        while (($data = fgetcsv($handle, 0, ",")) !== false) {
                            if (!isset($data[1])) {
                                continue;
                            }
                            $pluginTranslations[$data[0]] = $data[1];
                        }
                        fclose($handle);

                        if (is_array($pluginTranslations)) {
                            $translations = array_merge($translations, $pluginTranslations);
                        }
                    }
                }
            } catch (\Exception $e) {
                Logger::error("Plugin " . get_class($plugin) . " threw Exception when trying to get translations");
            }
        }

        return $translations;
    }
}

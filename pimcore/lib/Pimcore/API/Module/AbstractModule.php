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

namespace Pimcore\API\Module;

use Pimcore\API\AbstractAPI;
use Pimcore\Model\Object;

class AbstractModule extends AbstractAPI
{

    /**
     * @var
     */
    protected $config;

    /**
     *
     */
    public function __construct()
    {
        $this->setConfig();
    }

    /**
     *
     */
    public function setConfig()
    {
        if (is_null($this->config)) {
            $reflector = new \ReflectionClass(get_class($this));
            $fn = $reflector->getFileName();
            $path = dirname(dirname(dirname($fn))) . '/module.xml';
            if (is_readable($path)) {
                $config = new \Zend_Config_Xml($path);
                $this->config = $config;
            }
        } else {
            $this->config = false;
        }
    }

    /**
     * @return mixed
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return array
     */
    public function getJsPaths()
    {
        if ($config = $this->getConfig()) {
            $config = $config->toArray();
            if ($config['module']['moduleJsPaths']) {
                return (array)$config['module']['moduleJsPaths']['path'];
            }
        }
        return array();
    }

    /**
     * @return array
     */
    public function getCssPaths()
    {
        if ($config = $this->getConfig()) {
            $config = $config->toArray();
            if ($config['module']['moduleCssPaths']) {
                return (array)$config['module']['moduleJsPaths']['path'];
            }
        }
        return array();
    }

    /**
     *
     * Hook called before a key/value key config was added
     *
     * @param Object\KeyValue\KeyConfig $config
     */
    public function preAddKeyValueKeyConfig(Object\KeyValue\KeyConfig $config)
    {
    }

    /**
     *
     * Hook called after a key/value key config was added
     *
     * @param Object\KeyValue\KeyConfig $config
     */
    public function postAddKeyValueKeyConfig(Object\KeyValue\KeyConfig $config)
    {
    }

    /**
     * Hook called before a key/value key config is deleted
     *
     * @param Object\KeyValue\KeyConfig $config
     */
    public function preDeleteKeyValueKeyConfig(Object\KeyValue\KeyConfig $config)
    {
    }

    /**
     * Hook called after a key/value key config is deleted
     *
     * @param Object\KeyValue\KeyConfig $config
     */
    public function postDeleteKeyValueKeyConfig(Object\KeyValue\KeyConfig $config)
    {
    }

    /**
     * Hook called before a key/value key config is updated
     *
     * @param Object\KeyValue\KeyConfig $config
     */
    public function preUpdateKeyValueKeyConfig(Object\KeyValue\KeyConfig $config)
    {
    }

    /**
     * Hook called after a key/value key config is updated
     *
     * @param Object\KeyValue\KeyConfig $config
     */
    public function postUpdateKeyValueKeyConfig(Object\KeyValue\KeyConfig $config)
    {
    }


    /**
     *
     * Hook called before a key/value group config was added
     *
     * @param Object\KeyValue\GroupConfig $config
     */
    public function preAddKeyValueGroupConfig(Object\KeyValue\GroupConfig $config)
    {
    }

    /**
     *
     * Hook called after a key/value group config was added
     *
     * @param Object\KeyValue\GroupConfig $config
     */
    public function postAddKeyValueGroupConfig(Object\KeyValue\GroupConfig $config)
    {
    }

    /**
     * Hook called before a key/value group config is deleted
     *
     * @param Object\KeyValue\GroupConfig $config
     */
    public function preDeleteKeyValueGroupConfig(Object\KeyValue\GroupConfig $config)
    {
    }

    /**
     * Hook called after a key/value group config is deleted
     *
     * @param Object\KeyValue\GroupConfig $config
     */
    public function postDeleteKeyValueGroupConfig(Object\KeyValue\GroupConfig $config)
    {
    }

    /**
     * Hook called before a key/value group config is updated
     *
     * @param Object\KeyValue\GroupConfig $config
     */
    public function preUpdateKeyValueGroupConfig(Object\KeyValue\GroupConfig $config)
    {
    }

    /**
     * Hook called after a key/value key config is updated
     *
     * @param Object\KeyValue\GroupConfig $config
     */
    public function postUpdateKeyValueGroupConfig(Object\KeyValue\GroupConfig $config)
    {
    }

    /**
     * Check if module is installed
     */

    public function isInstalled()
    {
        return true;
    }
}

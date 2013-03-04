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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_API_Module_Abstract extends Pimcore_API_Abstract {

    /**
     *
     * Hook called before a key/value key config was added
     *
     * @param Object_KeyValue_KeyConfig $config
     */
    public function preAddKeyValueKeyConfig(Object_KeyValue_KeyConfig $config)
    {

    }

    /**
     *
     * Hook called after a key/value key config was added
     *
     * @param Object_KeyValue_KeyConfig $config
     */
    public function postAddKeyValueKeyConfig(Object_KeyValue_KeyConfig $config)
    {

    }

    /**
     * Hook called before a key/value key config is deleted
     *
     * @param Object_KeyValue_KeyConfig $config
     */
    public function preDeleteKeyValueKeyConfig(Object_KeyValue_KeyConfig $config)
    {

    }

    /**
     * Hook called after a key/value key config is deleted
     *
     * @param Object_KeyValue_KeyConfig $config
     */
    public function postDeleteKeyValueKeyConfig(Object_KeyValue_KeyConfig $config)
    {

    }

    /**
     * Hook called before a key/value key config is updated
     *
     * @param Object_KeyValue_KeyConfig $config
     */
    public function preUpdateKeyValueKeyConfig(Object_KeyValue_KeyConfig $config)
    {

    }

    /**
     * Hook called after a key/value key config is updated
     *
     * @param Object_KeyValue_KeyConfig $config
     */
    public function postUpdateKeyValueKeyConfig(Object_KeyValue_KeyConfig $config)
    {

    }


    /**
     *
     * Hook called before a key/value group config was added
     *
     * @param Object_KeyValue_GroupConfig $config
     */
    public function preAddKeyValueGroupConfig(Object_KeyValue_GroupConfig $config)
    {

    }

    /**
     *
     * Hook called after a key/value group config was added
     *
     * @param Object_KeyValue_GroupConfig $config
     */
    public function postAddKeyValueGroupConfig(Object_KeyValue_GroupConfig $config)
    {

    }

    /**
     * Hook called before a key/value group config is deleted
     *
     * @param Object_KeyValue_GroupConfig $config
     */
    public function preDeleteKeyValueGroupConfig(Object_KeyValue_GroupConfig $config)
    {

    }

    /**
     * Hook called after a key/value group config is deleted
     *
     * @param Object_KeyValue_GroupConfig $config
     */
    public function postDeleteKeyValueGroupConfig(Object_KeyValue_GroupConfig $config)
    {

    }

    /**
     * Hook called before a key/value group config is updated
     *
     * @param Object_KeyValue_GroupConfig $config
     */
    public function preUpdateKeyValueGroupConfig(Object_KeyValue_GroupConfig $config)
    {

    }

    /**
     * Hook called after a key/value key config is updated
     *
     * @param Object_KeyValue_GroupConfig $config
     */
    public function postUpdateKeyValueGroupConfig(Object_KeyValue_GroupConfig $config)
    {

    }

}

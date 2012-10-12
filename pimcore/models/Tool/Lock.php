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
 * @category   Pimcore
 * @package    Document
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Tool_Lock extends Pimcore_Model_Abstract {

    /**
     * @var array
     */
    protected static $acquiredLocks = array();

    /**
     * @var Tool_Lock
     */
    protected static $instance;

    /**
     * @return Tool_Lock
     */
    protected static function getInstance () {
        if(!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param string $key
     */
    public static function acquire ($key) {
        $instance = self::getInstance();
        $instance->getResource()->acquire($key);

        self::$acquiredLocks[$key] = $key;
    }

    /**
     * @param string $key
     */
    public static function release ($key) {
        $instance = self::getInstance();
        $instance->getResource()->release($key);

        unset(self::$acquiredLocks[$key]);
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function lock ($key) {
        $instance = self::getInstance();
        return $instance->getResource()->lock($key);
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function isLocked ($key) {
        $instance = self::getInstance();
        return $instance->getResource()->isLocked($key);
    }

    /**
     *
     */
    public static function releaseAll() {
        $locks = self::$acquiredLocks;

        Logger::debug($locks);

        foreach($locks as $key) {
            self::release($key);
        }
    }
}

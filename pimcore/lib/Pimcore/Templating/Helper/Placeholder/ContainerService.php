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

/**
 * ----------------------------------------------------------------------------------
 * based on @author ZF1 Zend_View_Helper_Placeholder_Registry
 * ----------------------------------------------------------------------------------
 */

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Pimcore\Templating\Helper\Placeholder;

/**
 * Registry for placeholder containers
 */
class ContainerService
{
    /**
     * Placeholder containers
     *
     * @var array
     */
    protected $_items = [];

    /**
     * createContainer
     *
     * @param  string $key
     * @param  array $value
     *
     * @return Container
     */
    public function createContainer($key, array $value = [])
    {
        $key = (string) $key;

        $this->_items[$key] = new Container($value);

        return $this->_items[$key];
    }

    /**
     * Retrieve a placeholder container
     *
     * @param  string $key
     *
     * @return Container
     */
    public function getContainer($key)
    {
        $key = (string) $key;
        if (isset($this->_items[$key])) {
            return $this->_items[$key];
        }

        $container = $this->createContainer($key);

        return $container;
    }

    /**
     * Does a particular container exist?
     *
     * @param  string $key
     *
     * @return bool
     */
    public function containerExists($key)
    {
        $key = (string) $key;
        $return =  array_key_exists($key, $this->_items);

        return $return;
    }

    /**
     * Set the container for an item in the registry
     *
     * @param  string $key
     * @param  Container $container
     *
     * @return ContainerService
     */
    public function setContainer($key, Container $container)
    {
        $key = (string) $key;
        $this->_items[$key] = $container;

        return $this;
    }

    /**
     * Delete a container
     *
     * @param  string $key
     *
     * @return bool
     */
    public function deleteContainer($key)
    {
        $key = (string) $key;
        if (isset($this->_items[$key])) {
            unset($this->_items[$key]);

            return true;
        }

        return false;
    }
}

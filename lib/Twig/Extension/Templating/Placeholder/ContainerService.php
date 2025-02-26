<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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

namespace Pimcore\Twig\Extension\Templating\Placeholder;

use OutOfBoundsException;
use RuntimeException;

/**
 * Registry for placeholder containers
 *
 */
class ContainerService
{
    private int $currentIndex = 0;

    /**
     * Placeholder containers
     *
     */
    protected array $_items = [];

    public function __construct()
    {
        $this->_items[$this->currentIndex] = [];
    }

    public function pushIndex(): void
    {
        ++$this->currentIndex;

        if (isset($this->_items[$this->currentIndex])) {
            throw new RuntimeException(sprintf('Items at index %d already exist', $this->currentIndex));
        }

        $this->_items[$this->currentIndex] = [];
    }

    public function popIndex(): void
    {
        if (0 === $this->currentIndex) {
            throw new OutOfBoundsException('Current index is already at 0');
        }

        if (isset($this->_items[$this->currentIndex])) {
            unset($this->_items[$this->currentIndex]);
        }

        --$this->currentIndex;
    }

    /**
     * createContainer
     *
     *
     */
    public function createContainer(string $key, array $value = []): Container
    {
        return $this->_items[$this->currentIndex][$key] = new Container($value);
    }

    /**
     * Retrieve a placeholder container
     *
     *
     */
    public function getContainer(string $key): Container
    {
        return $this->_items[$this->currentIndex][$key] ?? $this->createContainer($key);
    }

    /**
     * Does a particular container exist?
     *
     *
     */
    public function containerExists(string $key): bool
    {
        return array_key_exists($key, $this->_items[$this->currentIndex]);
    }

    /**
     * Set the container for an item in the registry
     *
     *
     * @return $this
     */
    public function setContainer(string $key, Container $container): static
    {
        $this->_items[$this->currentIndex][$key] = $container;

        return $this;
    }

    /**
     * Delete a container
     *
     *
     */
    public function deleteContainer(string $key): bool
    {
        if (isset($this->_items[$this->currentIndex][$key])) {
            unset($this->_items[$this->currentIndex][$key]);

            return true;
        }

        return false;
    }
}

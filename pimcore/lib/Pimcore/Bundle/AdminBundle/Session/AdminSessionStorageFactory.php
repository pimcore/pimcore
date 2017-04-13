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

namespace Pimcore\Bundle\AdminBundle\Session;

use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\Proxy\AbstractProxy;

class AdminSessionStorageFactory
{
    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var AbstractProxy|NativeSessionHandler|\SessionHandlerInterface|null
     */
    protected $handler;

    /**
     * @var array
     */
    protected $defaultOptions = [
        'name'             => 'pimcore_admin_sid',
        'use_trans_sid'    => false,
        'use_only_cookies' => false,
        'cookie_httponly'  => true
    ];

    /**
     * @param AbstractProxy|NativeSessionHandler|\SessionHandlerInterface|null $handler
     * @param array $options
     */
    public function __construct($handler, array $options = [])
    {
        $this->handler = $handler;
        $this->options = array_merge($this->defaultOptions, $options);
    }

    /**
     * @return NativeSessionStorage
     */
    public function createStorage()
    {
        return new NativeSessionStorage($this->options, $this->handler);
    }

    /**
     * @param NativeSessionStorage $storage
     */
    public function initializeStorage(NativeSessionStorage $storage)
    {
        $storage->setOptions($this->options);
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function getOption($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
}

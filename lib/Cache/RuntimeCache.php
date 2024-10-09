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

namespace Pimcore\Cache;

use ArrayObject;
use Exception;
use Pimcore;

class RuntimeCache extends ArrayObject
{
    private const SERVICE_ID = __CLASS__;

    protected static ?RuntimeCache $tempInstance = null;

    protected static ?RuntimeCache $instance = null;

    private static bool $disabled = false;

    /**
     * Retrieves the default registry instance.
     *
     */
    public static function getInstance(): self
    {
        if (self::$instance) {
            return self::$instance;
        }

        if (Pimcore::hasContainer()) {
            $container = Pimcore::getContainer();

            /** @var self $instance */
            $instance = null;
            if ($container->initialized(self::SERVICE_ID)) {
                $instance = $container->get(self::SERVICE_ID);
            } else {
                $instance = new self;
                $container->set(self::SERVICE_ID, $instance);
            }

            self::$instance = $instance;

            if (self::$tempInstance) {
                // copy values from static temp. instance to the service instance
                foreach (self::$tempInstance as $key => $value) {
                    $instance->offsetSet($key, $value);
                }

                self::$tempInstance = null;
            }

            return $instance;
        }

        // create a temp. instance
        // this is necessary because the runtime cache is sometimes in use before the actual service container
        // is initialized
        if (!self::$tempInstance) {
            self::$tempInstance = new self;
        }

        return self::$tempInstance;
    }

    /**
     * disables the caching for the current process, this is useful for importers, ...
     * There are no new objects will be cached after that
     */
    public static function disable(): void
    {
        self::$disabled = true;
    }

    /**
     * see @ self::disable()
     * just enabled the caching in the current process
     */
    public static function enable(): void
    {
        self::$disabled = false;
    }

    public static function isEnabled(): bool
    {
        return !self::$disabled;
    }

    /**
     * getter method, basically same as offsetGet().
     *
     * This method can be called from an object of type \Pimcore\Cache\Runtime, or it
     * can be called statically.  In the latter case, it uses the default
     * static instance stored in the class.
     *
     * @param string $index - get the value associated with $index
     *
     * @throws Exception if no entry is registered for $index.
     */
    public static function get(string $index): mixed
    {
        $instance = self::getInstance();

        if (!$instance->offsetExists($index)) {
            throw new Exception("No entry is registered for key '$index'");
        }

        return $instance->offsetGet($index);
    }

    /**
     * setter method, basically same as offsetSet().
     *
     * This method can be called from an object of type \Pimcore\Cache\Runtime, or it
     * can be called statically.  In the latter case, it uses the default
     * static instance stored in the class.
     *
     * @param string $index The location in the ArrayObject in which to store
     *   the value.
     * @param mixed $value The object to store in the ArrayObject.
     *
     */
    public static function set(string $index, mixed $value): void
    {
        $instance = self::getInstance();
        $instance->offsetSet($index, $value);
    }

    /**
     * Returns TRUE if the $index is a named value in the registry,
     * or FALSE if $index was not found in the registry.
     *
     *
     */
    public static function isRegistered(string $index): bool
    {
        $instance = self::getInstance();

        return $instance->offsetExists($index);
    }

    /**
     * Constructs a parent ArrayObject with default
     * ARRAY_AS_PROPS to allow access as an object
     *
     * @param array $array data array
     * @param int $flags ArrayObject flags
     */
    public function __construct($array = [], int $flags = parent::ARRAY_AS_PROPS)
    {
        parent::__construct($array, $flags);
    }

    public function offsetSet($index, $value): void
    {
        // check if caching is disabled for this process
        if (self::$disabled) {
            return;
        }

        parent::offsetSet($index, $value);
    }

    /**
     * Alias of self::set() to be compatible with Pimcore\Cache
     *
     */
    public static function save(mixed $data, string $id): void
    {
        self::set($id, $data);
    }

    /**
     * Alias of self::get() to be compatible with Pimcore\Cache
     *
     *
     */
    public static function load(string $id): mixed
    {
        return self::get($id);
    }

    public static function clear(array $keepItems = []): void
    {
        self::$instance = null;
        $newInstance = new self();
        $oldInstance = self::getInstance();

        foreach ($keepItems as $key) {
            if ($oldInstance->offsetExists($key)) {
                $newInstance->offsetSet($key, $oldInstance->offsetGet($key));
            }
        }

        Pimcore::getContainer()->set(self::SERVICE_ID, $newInstance);
        self::$instance = $newInstance;
    }
}

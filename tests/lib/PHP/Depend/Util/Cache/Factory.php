<?php
/**
 * This file is part of PHP_Depend.
 *
 * PHP Version 5
 *
 * Copyright (c) 2008-2012, Manuel Pichler <mapi@pdepend.org>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Manuel Pichler nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   QualityAssurance
 * @package    PHP_Depend
 * @subpackage Util_Cache
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    SVN: $Id$
 * @link       http://pdepend.org/
 * @since      0.10.0
 */

/**
 * Factory that encapsulates the creation of a concrete cache instance.
 *
 * @category   QualityAssurance
 * @package    PHP_Depend
 * @subpackage Util_Cache
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 1.1.0
 * @link       http://pdepend.org/
 * @since      0.10.0
 */
class PHP_Depend_Util_Cache_Factory
{
    /**
     * The system configuration.
     *
     * @var PHP_Depend_Util_Configuration
     */
    protected $configuration = null;

    /**
     * Singleton property that holds existing cache instances.
     *
     * @var PHP_Depend_Util_Cache_Driver[]
     */
    protected $caches = array();

    /**
     * Constructs a new cache factory instance for the given configuration.
     *
     * @param PHP_Depend_Util_Configuration $configuration The system configuration.
     */
    public function __construct(PHP_Depend_Util_Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Creates a new instance or returns an existing cache for the given cache
     * identifier.
     *
     * @param string $cacheKey The name/identifier for the cache instance.
     *
     * @return PHP_Depend_Util_Cache_Driver
     */
    public function create($cacheKey = null)
    {
        if (false === isset($this->caches[$cacheKey])) {
            $this->caches[$cacheKey] = $this->createCache($cacheKey);
        }
        return $this->caches[$cacheKey];
    }

    /**
     * Creates a cache instance based on the supplied configuration.
     *
     * @param string $cacheKey The name/identifier for the cache instance.
     *
     * @return PHP_Depend_Util_Cache_Driver
     * @throws InvalidArgumentException If the configured cache driver is unknown.
     */
    protected function createCache($cacheKey)
    {
        switch ($this->configuration->cache->driver) {

        case 'file':
            return $this->createFileCache(
                $this->configuration->cache->location,
                $cacheKey
            );

        case 'memory':
            return $this->createMemoryCache();
        }
        throw new InvalidArgumentException(
            "Unknown cache driver '{$this->configuration->cache->driver}' given."
        );
    }

    /**
     * Creates a new file system based cache instance.
     *
     * @param string $location Cache root directory.
     * @param string $cacheKey The name/identifier for the cache instance.
     *
     * @return PHP_Depend_Util_Cache_Driver_File
     */
    protected function createFileCache($location, $cacheKey)
    {
        return new PHP_Depend_Util_Cache_Driver_File($location, $cacheKey);
    }

    /**
     * Creates an in memory cache instance.
     *
     * @return PHP_Depend_Util_Cache_Driver_Memory
     */
    protected function createMemoryCache()
    {
        return new PHP_Depend_Util_Cache_Driver_Memory();
    }
}

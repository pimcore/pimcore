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
 * @subpackage Metrics
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    SVN: $Id$
 * @link       http://pdepend.org/
 * @since      1.0.0
 */

/**
 * This abstract class provides an analyzer that provides the basic infrastructure
 * for caching.
 *
 * @category   QualityAssurance
 * @package    PHP_Depend
 * @subpackage Metrics
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 1.1.0
 * @link       http://pdepend.org/
 * @since      1.0.0
 */
abstract class PHP_Depend_Metrics_AbstractCachingAnalyzer
       extends PHP_Depend_Metrics_AbstractAnalyzer
    implements PHP_Depend_Metrics_CacheAware
{
    /**
     * Collected node metrics
     *
     * @var array
     */
    protected $metrics = null;

    /**
     * Metrics restored from the cache. This property is only used temporary.
     *
     * @var array
     */
    private $metricsCached = array();

    /**
     * Injected cache driver.
     *
     * @var PHP_Depend_Util_Cache_Driver
     */
    private $cache;

    /**
     * Setter method for the system wide used cache.
     *
     * @param PHP_Depend_Util_Cache_Driver $cache Used cache object.
     *
     * @return void
     */
    public function setCache(PHP_Depend_Util_Cache_Driver $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Tries to restore the metrics for a cached node. If this method has
     * restored the metrics it will return <b>TRUE</b>, otherwise the return
     * value will be <b>FALSE</b>.
     *
     * @param PHP_Depend_Code_NodeI $node The context node instance.
     *
     * @return boolean
     */
    protected function restoreFromCache(PHP_Depend_Code_NodeI $node)
    {
        $uuid = $node->getUuid();
        if ($node->isCached() && isset($this->metricsCached[$uuid])) {
            $this->metrics[$uuid] = $this->metricsCached[$uuid];
            return true;
        }
        return false;
    }

    /**
     * Initializes the previously calculated metrics from the cache.
     *
     * @return void
     */
    protected function loadCache()
    {
        $this->metricsCached = (array) $this->cache
            ->type('metrics')
            ->restore(get_class($this));
    }

    /**
     * Unloads the metrics cache and stores the current set of metrics in the
     * cache.
     *
     * @return void
     */
    protected function unloadCache()
    {
        $this->cache
            ->type('metrics')
            ->store(get_class($this), $this->metrics);

        $this->metricsCached = array();
    }
}

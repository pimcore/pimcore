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

namespace Pimcore\Templating\Helper;

use Pimcore\Cache as CacheManager;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Tool;
use Symfony\Component\Templating\Helper\Helper;

/**
 * @deprecated
 */
class Cache extends Helper
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var bool[]
     */
    protected $captureEnabled = [];

    /**
     * @var bool
     */
    protected $force = false;

    /**
     * @var int
     */
    protected $lifetime;

    /**
     * @var EditmodeResolver
     */
    protected $editmodeResolver;

    public function __construct(EditmodeResolver $editmodeResolver)
    {
        $this->editmodeResolver = $editmodeResolver;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'cache';
    }

    /**
     * @param string $name
     * @param int|null $lifetime
     * @param bool $force
     *
     * @return mixed
     */
    public function __invoke($name, $lifetime = null, $force = false)
    {
        $this->key = 'pimcore_viewcache_' . $name;
        $this->force = $force;

        if (Tool\Frontend::hasWebpSupport()) {
            $this->key .= 'webp';
        }

        if (!$lifetime) {
            $lifetime = null;
        }

        $this->lifetime = $lifetime;

        return $this;
    }

    /**
     * @return bool
     */
    public function start()
    {
        if (\Pimcore\Tool::isFrontendRequestByAdmin() && !$this->force) {
            return false;
        }

        if ($content = CacheManager::load($this->key)) {
            $this->outputContent($content, $this->key, true);

            return true;
        }

        $this->captureEnabled[$this->key] = true;
        ob_start();

        return false;
    }

    /**
     *  @return void
     */
    public function end()
    {
        if ($this->captureEnabled[$this->key]) {
            $this->captureEnabled[$this->key] = false;

            $tags = ['in_template'];
            if (!$this->lifetime) {
                $tags[] = 'output';
            }

            $content = ob_get_clean();
            $this->saveContentToCache($content, $this->key, $tags);
            $this->outputContent($content, $this->key, false);
        }
    }

    /**
     *  @return void
     */
    public function stop()
    {
        $this->end();
    }

    /**
     * Output the content.
     *
     * @param string $content the content, either rendered or retrieved directly from the cache.
     * @param string $key the cache key
     * @param bool $isLoadedFromCache true if the content origins from the cache and hasn't been created "live".
     */
    protected function outputContent($content, string $key, bool $isLoadedFromCache)
    {
        echo $content;
    }

    /**
     * Save the (rendered) content to to cache. May be overriden to add custom markup / code, or specific tags, etc.
     *
     * @param string $content
     * @param string $key
     * @param array $tags
     */
    protected function saveContentToCache($content, string $key, array $tags)
    {
        CacheManager::save($content, $key, $tags, $this->lifetime, 996, true);
    }
}

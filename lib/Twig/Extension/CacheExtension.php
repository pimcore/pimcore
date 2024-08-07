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

namespace Pimcore\Twig\Extension;

use Pimcore\Cache as CacheManager;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @internal
 *
 * @deprecated
 */
class CacheExtension extends AbstractExtension
{
    protected string $key;

    /**
     * @var bool[]
     */
    protected array $captureEnabled = [];

    protected bool $force = false;

    protected ?int $lifetime;

    protected EditmodeResolver $editmodeResolver;

    public function __construct(EditmodeResolver $editmodeResolver)
    {
        $this->editmodeResolver = $editmodeResolver;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('pimcore_cache', [$this, 'init'], ['is_safe' => ['html']]),
        ];
    }

    /**
     *
     * @return $this
     */
    public function init(string $name, int $lifetime = null, bool $force = false): static
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '11.4',
            '"pimcore_cache" twig extension is deprecated. Use the "pimcorecache" tag instead.'
        );

        $this->key = 'pimcore_viewcache_' . $name;
        $this->force = $force;

        if (!$lifetime) {
            $lifetime = null;
        }

        $this->lifetime = $lifetime;

        return $this;
    }

    public function start(): bool
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

    public function end(): void
    {
        if ($this->captureEnabled[$this->key] ?? false) {
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

    public function stop(): void
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
    protected function outputContent(string $content, string $key, bool $isLoadedFromCache): void
    {
        echo $content;
    }

    /**
     * Save the (rendered) content to to cache. May be overriden to add custom markup / code, or specific tags, etc.
     *
     */
    protected function saveContentToCache(string $content, string $key, array $tags): void
    {
        CacheManager::save($content, $key, $tags, $this->lifetime, 996, true);
    }
}

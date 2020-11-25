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

namespace Pimcore\Twig\Extension;

use Pimcore\Cache as CacheManager;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Tool;
use Pimcore\ViewCache;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CacheExtension extends AbstractExtension
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

    /** @var ViewCache */
    protected $viewCacheHelper;

    public function __construct(EditmodeResolver $editmodeResolver, ViewCache $viewCacheHelper)
    {
        $this->editmodeResolver = $editmodeResolver;
        $this->viewCacheHelper = $viewCacheHelper;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('pimcore_cache', [$this, 'init'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param string $name
     * @param int|null $lifetime
     * @param bool $force
     *
     * @return mixed
     */
    public function init($name, $lifetime = null, $force = false)
    {
        $this->key = $name;
        $this->force = $force;

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

        if ($content = $this->viewCacheHelper->loadFromViewCache($this->key)) {
            $this->viewCacheHelper->outputContent($content, $this->key, true);

            return true;
        }

        $this->viewCacheHelper->enableCapture($this->key);
        ob_start();

        return false;
    }

    /**
     *  @return void
     */
    public function end()
    {
        if ($this->viewCacheHelper->isCaptureEnabled($this->key)) {
            $this->viewCacheHelper->disableCapture($this->key);

            $tags = ['in_template'];
            if (!$this->lifetime) {
                $tags[] = 'output';
            }

            $content = ob_get_clean();
            $this->viewCacheHelper->saveToViewCache($content, $this->key, $tags, $this->lifetime);
            $this->viewCacheHelper->outputContent($content, $this->key, false);
        }
    }

    /**
     *  @return void
     */
    public function stop()
    {
        $this->end();
    }
}

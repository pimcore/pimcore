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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\PimcoreBundle\Templating\Helper;

use Pimcore\Bundle\PimcoreBundle\Service\Request\EditmodeResolver;
use Pimcore\Cache as CacheManager;
use Symfony\Component\Templating\Helper\Helper;

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
     * @param $name
     * @param null $lifetime
     * @param bool $force
     * @return mixed
     */
    public function __invoke($name, $lifetime = null, $force = false)
    {
        $this->key = "pimcore_viewcache_" . $name;
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
        if (\Pimcore\Tool::isFrontentRequestByAdmin() && !$this->force) {
            return false;
        }

        if ($content = CacheManager::load($this->key)) {
            echo $content;

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

            $tags = ["in_template"];
            if (!$this->lifetime) {
                $tags[] = "output";
            }

            $content = ob_get_clean();
            CacheManager::save($content, $this->key, $tags, $this->lifetime, 996, true);
            echo $content;
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
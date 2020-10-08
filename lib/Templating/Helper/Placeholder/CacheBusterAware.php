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

namespace Pimcore\Templating\Helper\Placeholder;

/**
 * Class CacheBusterAware
 *
 * adds cache buster functionality to placeholder helper
 *
 * @deprecated
 */
abstract class CacheBusterAware extends AbstractHelper
{
    /**
     * @var bool
     */
    protected $cacheBuster = true;

    /**
     * prepares entries with cache buster prefix
     */
    abstract protected function prepareEntries();

    /**
     * @return bool
     */
    public function isCacheBuster()
    {
        return $this->cacheBuster;
    }

    /**
     * @param bool $cacheBuster
     */
    public function setCacheBuster($cacheBuster)
    {
        $this->cacheBuster = $cacheBuster;
    }
}

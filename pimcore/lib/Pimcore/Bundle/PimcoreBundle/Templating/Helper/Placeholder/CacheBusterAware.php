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


namespace Pimcore\Bundle\PimcoreBundle\Templating\Helper\Placeholder;

/**
 * Class CacheBusterAware
 *
 * adds cache buster functionality to placeholder helper
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
    public function prepareEntries()
    {
        foreach ($this as &$item) {
            if ($this->isCacheBuster()) {
                // adds the automatic cache buster functionality
                if (isset($item->href)) {
                    $realFile = PIMCORE_DOCUMENT_ROOT . $item->href;
                    if (file_exists($realFile)) {
                        $item->href = "/cache-buster-" . filemtime($realFile) . $item->href;
                    }
                }
            }

            \Pimcore::getEventManager()->trigger($this->getEventManagerKey(), $this, [
                "item" => $item
            ]);
        }
    }

    /**
     * @return boolean
     */
    public function isCacheBuster()
    {
        return $this->cacheBuster;
    }

    /**
     * @param boolean $cacheBuster
     */
    public function setCacheBuster($cacheBuster)
    {
        $this->cacheBuster = $cacheBuster;
    }

    /**
     * key for event that is triggered on every item in prepareEntries()
     *
     * @return string
     */
    abstract protected function getEventManagerKey();

}

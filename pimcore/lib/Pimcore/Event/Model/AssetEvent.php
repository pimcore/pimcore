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

namespace Pimcore\Event\Model;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Pimcore\Model\Asset;
use Symfony\Component\EventDispatcher\Event;

class AssetEvent extends Event implements ElementEventInterface
{
    use ArgumentsAwareTrait;

    /**
     * @var Asset
     */
    protected $asset;

    /**
     * DocumentEvent constructor.
     *
     * @param Asset $asset
     * @param array $arguments
     */
    public function __construct(Asset $asset, array $arguments = [])
    {
        $this->asset = $asset;
        $this->arguments = $arguments;
    }

    /**
     * @return Asset
     */
    public function getAsset()
    {
        return $this->asset;
    }

    /**
     * @param Asset $asset
     */
    public function setAsset($asset)
    {
        $this->asset = $asset;
    }

    /**
     * @return Asset
     */
    public function getElement()
    {
        return $this->getAsset();
    }
}

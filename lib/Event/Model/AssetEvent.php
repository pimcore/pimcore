<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Event\Model;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Pimcore\Model\Asset;
use Symfony\Contracts\EventDispatcher\Event;

class AssetEvent extends Event implements ElementEventInterface
{
    use ArgumentsAwareTrait;

    protected Asset $asset;

    /**
     * AssetEvent constructor.
     *
     * @param array $arguments additional parameters (e.g. "versionNote" for the version note)
     */
    public function __construct(Asset $asset, array $arguments = [])
    {
        $this->asset = $asset;
        $this->arguments = $arguments;
    }

    public function getAsset(): Asset
    {
        return $this->asset;
    }

    public function setAsset(Asset $asset): void
    {
        $this->asset = $asset;
    }

    public function getElement(): Asset
    {
        return $this->getAsset();
    }
}

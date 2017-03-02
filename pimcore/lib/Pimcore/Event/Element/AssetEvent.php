<?php

namespace Pimcore\Event\Element;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Pimcore\Model\Asset;
use Symfony\Component\EventDispatcher\Event;

class AssetEvent extends Event {

    use ArgumentsAwareTrait;

    /**
     * @var Asset
     */
    protected $asset;

    /**
     * DocumentEvent constructor.
     * @param Asset $asset
     * @param array $arguments
     */
    function __construct(Asset $asset, array $arguments = [])
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
}
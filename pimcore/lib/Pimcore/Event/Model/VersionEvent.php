<?php

namespace Pimcore\Event\Model;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Pimcore\Model\Version;
use Symfony\Component\EventDispatcher\Event;

class VersionEvent extends Event {

    use ArgumentsAwareTrait;

    /**
     * @var Version
     */
    protected $version;

    /**
     * DocumentEvent constructor.
     * @param Version $version
     */
    function __construct(Version $version)
    {
        $this->version = $version;
    }

    /**
     * @return Version
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param Version $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }
}

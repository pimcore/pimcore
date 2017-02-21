<?php


namespace Pimcore\Event\Admin;

use Symfony\Component\EventDispatcher\Event;

class UnauthenticatedRequestWhitelistEvent extends Event
{
    /**
     * @var array
     */
    protected $whitelist;

    /**
     * @param array $whitelist
     */
    public function __construct(array $whitelist)
    {
        $this->whitelist = $whitelist;
    }

    /**
     * @return array
     */
    public function getWhitelist()
    {
        return $this->whitelist;
    }

    /**
     * @param array $whitelist
     * @return $this
     */
    public function setWhitelist(array $whitelist)
    {
        $this->whitelist = $whitelist;
    }
}

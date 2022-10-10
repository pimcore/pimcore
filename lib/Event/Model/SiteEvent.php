<?php

namespace Pimcore\Event\Model;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Pimcore\Model\Site;
use Symfony\Contracts\EventDispatcher\Event;

class SiteEvent extends Event
{
    use ArgumentsAwareTrait;

    protected Site $site;

    public function __construct(Site $site, array $arguments = [])
    {
        $this->site = $site;
        $this->arguments = $arguments;
    }

    public function getSite(): Site
    {
        return $this->site;
    }

    public function setSite(Site $site): void
    {
        $this->site = $site;
    }
}

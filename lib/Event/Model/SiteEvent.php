<?php

namespace Pimcore\Event\Model;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Site;
use Symfony\Contracts\EventDispatcher\Event;

class SiteEvent extends Event implements ElementEventInterface
{
    use ArgumentsAwareTrait;

    /** @var Site */
    protected $site;

    /**
     * @param Site $site
     * @param array $arguments
     */
    public function __construct(Site $site, array $arguments = [])
    {
        $this->site = $site;
        $this->arguments = $arguments;
    }

    /**
     * @return Site
     */
    public function getSite(): Site
    {
        return $this->site;
    }

    /**
     * @param Site $site
     */
    public function setSite(Site $site): void
    {
        $this->site = $site;
    }

    /**
     * @return ElementInterface|Site
     */
    public function getElement()
    {
        return $this->getSite();
    }
}

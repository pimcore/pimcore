<?php

namespace Pimcore\Bundle\PimcoreBundle\EventListener;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Integrates areablock/block handling with \Pimcore\Cache\Runtime
 *
 * TODO can this be removed later? who is in charge of handling block state?
 */
class AreablockStateListener implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var array
     */
    protected $parentBlockCurrent = [];

    /**
     * @var array
     */
    protected $parentBlockNumeration = [];

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST  => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse'
        ];
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($request->get('disableBlockClearing')) {
            return;
        }

        // this is for $this->action() in templates when they are inside a block element
        try {
            $this->parentBlockCurrent = [];
            if (\Pimcore\Cache\Runtime::isRegistered('pimcore_tag_block_current')) {
                $this->parentBlockCurrent = \Pimcore\Cache\Runtime::get('pimcore_tag_block_current');
            }

            $this->parentBlockNumeration = [];
            if (\Pimcore\Cache\Runtime::isRegistered('pimcore_tag_block_numeration')) {
                $this->parentBlockNumeration = \Pimcore\Cache\Runtime::get('pimcore_tag_block_numeration');
            }

            \Pimcore\Cache\Runtime::set('pimcore_tag_block_current', []);
            \Pimcore\Cache\Runtime::set('pimcore_tag_block_numeration', []);
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($request->get('disableBlockClearing')) {
            return;
        }

        // restore parent block data
        if (!empty($this->parentBlockCurrent)) {
            \Pimcore\Cache\Runtime::set('pimcore_tag_block_current', $this->parentBlockCurrent);
            \Pimcore\Cache\Runtime::set('pimcore_tag_block_numeration', $this->parentBlockNumeration);
        }
    }
}

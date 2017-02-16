<?php

namespace Pimcore\Bundle\PimcoreBundle\EventListener;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Integrates areablock/block handling with Zend_Registry
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
            if (\Zend_Registry::isRegistered('pimcore_tag_block_current')) {
                $this->parentBlockCurrent = \Zend_Registry::get('pimcore_tag_block_current');
            }

            $this->parentBlockNumeration = [];
            if (\Zend_Registry::isRegistered('pimcore_tag_block_numeration')) {
                $this->parentBlockNumeration = \Zend_Registry::get('pimcore_tag_block_numeration');
            }

            \Zend_Registry::set('pimcore_tag_block_current', []);
            \Zend_Registry::set('pimcore_tag_block_numeration', []);
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
            \Zend_Registry::set('pimcore_tag_block_current', $this->parentBlockCurrent);
            \Zend_Registry::set('pimcore_tag_block_numeration', $this->parentBlockNumeration);
        }
    }
}

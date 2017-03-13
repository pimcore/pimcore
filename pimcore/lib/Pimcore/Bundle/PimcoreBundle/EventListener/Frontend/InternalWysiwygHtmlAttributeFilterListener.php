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

namespace Pimcore\Bundle\PimcoreBundle\EventListener\Frontend;

use Pimcore\Bundle\PimcoreBundle\EventListener\AbstractResponseInjectionListener;
use Pimcore\Bundle\PimcoreBundle\EventListener\Traits\ResponseInjectionTrait;
use Pimcore\Bundle\PimcoreBundle\Service\Request\PimcoreContextResolver;
use Pimcore\Tool;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class InternalWysiwygHtmlAttributeFilterListener extends AbstractFrontendListener
{
    use ResponseInjectionTrait;

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @return bool
     */
    public function disable()
    {
        $this->enabled = false;
        return true;
    }

    /**
     * @return bool
     */
    public function enable()
    {
        $this->enabled = true;
        return true;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!$event->isMasterRequest()) {
            return;
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if (!Tool::useFrontendOutputFilters()) {
            return;
        }

        $response = $event->getResponse();
        if(!$this->isHtmlResponse($response)) {
            return;
        }

        $content = $response->getContent();
        $content = preg_replace("/ pimcore_(id|type|disable_thumbnail)=\\\"([0-9a-z]+)\\\"/", "", $content);

        $response->setContent($content);
    }
}

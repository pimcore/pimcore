<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\CoreBundle\EventListener\Frontend;

use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Http\Request\Resolver\TemplateResolver;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * If a contentTemplate attribute was set on the request (done by router when building a document route), extract the
 * value and set it on the Template annotation. This handles custom template files being configured on documents.
 *
 * @internal
 */
class ContentTemplateListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    /**
     * @param TemplateResolver $templateResolver
     */
    public function __construct(protected TemplateResolver $templateResolver)
    {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['onKernelView', 16],
        ];
    }

    /**
     * If there's a contentTemplate attribute set on the request, it was read from the document template setting from
     * the router or from the sub-action renderer and takes precedence over the auto-resolved and manually configured
     * template.
     *
     * @param ViewEvent $event
     */
    public function onKernelView(ViewEvent $event)
    {
        $request = $event->getRequest();

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        $template = $request->attributes->get('_template');

        // no @Template present -> nothing to do
        if (null === $template || !($template instanceof Template)) {
            return;
        }

        $resolvedTemplate = $this->templateResolver->getTemplate($request);
        if (null === $resolvedTemplate) {
            // no contentTemplate on the request -> nothing to do
            return;
        }

        $template->setTemplate($resolvedTemplate);
    }
}

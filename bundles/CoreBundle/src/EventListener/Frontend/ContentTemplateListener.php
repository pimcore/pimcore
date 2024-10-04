<?php

declare(strict_types=1);

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

use Exception;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Http\Request\Resolver\TemplateResolver;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

/**
 * If a contentTemplate attribute was set on the request (done by router when building a document route), extract the
 * value and set it on the Template annotation. This handles custom template files being configured on documents.
 *
 * @internal
 */
class ContentTemplateListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    public function __construct(protected TemplateResolver $templateResolver, protected Environment $twig)
    {
    }

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
     */
    public function onKernelView(ViewEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        $attribute = $event->controllerArgumentsEvent?->getAttributes()[Template::class][0] ?? null;
        $resolvedTemplate = $this->templateResolver->getTemplate($request);
        if (null === $resolvedTemplate) {
            // no contentTemplate on the request -> nothing to do
            return;
        }

        $parameters = $this->resolveParameters($event, $attribute?->vars ?? []);
        $status = 200;

        if (interface_exists('Symfony\\Component\\Form\\FormInterface')) {
            foreach ($parameters as $k => $v) {
                if (!$v instanceof \Symfony\Component\Form\FormInterface) {
                    continue;
                }
                if ($v->isSubmitted() && !$v->isValid()) {
                    $status = 422;
                }
                $parameters[$k] = $v->createView();
            }
        }

        $event->setResponse(($attribute instanceof Template && $attribute->stream)
            ? new StreamedResponse(fn () => $this->twig->display($resolvedTemplate, $parameters), $status)
            : new Response($this->twig->render($resolvedTemplate, $parameters), $status)
        );
    }

    private function resolveParameters(ViewEvent $event, array $vars): array
    {
        $controllerArguments = $event->controllerArgumentsEvent?->getNamedArguments() ?? [];
        $controllerResults = is_array($event->getControllerResult()) ? $event->getControllerResult() : [];

        $mergedArray = array_merge(array_keys($controllerArguments), array_keys($controllerResults), array_keys($vars));
        $duplicateKeys = array_unique(array_diff_assoc($mergedArray, array_unique($mergedArray)));

        if ($duplicateKeys) {
            throw new Exception('Duplicate keys found: '.implode(', ', array_values($duplicateKeys)).'. Please use unique names for your controller arguments, controller results and template variables.');
        }

        return array_merge($controllerArguments, $controllerResults, $vars);
    }
}

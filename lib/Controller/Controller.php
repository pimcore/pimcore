<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Templating\EngineInterface;

abstract class Controller extends AbstractController
{
    protected function render(string $view, array $parameters = [], ?Response $response = null): Response
    {
        $templatingEngine = $this->container->get('pimcore.templating');
        if ($templatingEngine->isDelegate()) {
            $content = $templatingEngine->render($view, $parameters);
            if (null === $response) {
                $response = new Response();
            }

            $response->setContent($content);

            return $response;
        }

        return parent::render($view, $parameters, $response);
    }

    protected function stream(string $view, array $parameters = [], ?StreamedResponse $response = null): StreamedResponse
    {
        $templatingEngine = $this->container->get('pimcore.templating');
        if ($templatingEngine->isDelegate()) {
            $callback = function () use ($templatingEngine, $view, $parameters) {
                $templatingEngine->stream($view, $parameters);
            };

            if (null === $response) {
                return new StreamedResponse($callback);
            }

            $response->setCallback($callback);

            return $response;
        }

        return parent::stream($view, $parameters, $response);
    }

    protected function renderView(string $view, array $parameters = []): string
    {
        $templatingEngine = $this->container->get('pimcore.templating');
        if ($templatingEngine->isDelegate()) {
            return $templatingEngine->render($view, $parameters);
        }

        return parent::renderView($view, $parameters);
    }

    /**
     * @return string[]
     */
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        // @deprecated since 12.3, will be removed in 13.0. Use Twig\Environment instead.
        $services['pimcore.templating'] = '?'.EngineInterface::class;

        return $services;
    }
}

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Templating\EngineInterface;

abstract class Controller extends AbstractController
{
    /**
     * {@inheritdoc}
     *
     */
    protected function render(string $view, array $parameters = [], Response $response = null): Response
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

    /**
     * {@inheritdoc}
     *
     */
    protected function stream(string $view, array $parameters = [], StreamedResponse $response = null): StreamedResponse
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

    /**
     * {@inheritdoc}
     *
     */
    protected function renderView(string $view, array $parameters = []): string
    {
        $templatingEngine = $this->container->get('pimcore.templating');
        if ($templatingEngine->isDelegate()) {
            return $templatingEngine->render($view, $parameters);
        }

        return parent::renderView($view, $parameters);
    }

    /**
     * {@inheritdoc}
     *
     */
    public static function getSubscribedServices()
    {
        $services = parent::getSubscribedServices();
        $services['pimcore.templating'] = '?'.EngineInterface::class;

        return $services;
    }
}

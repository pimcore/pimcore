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

namespace Pimcore\Templating;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\DelegatingEngine as BaseDelegatingEngine;
use Symfony\Component\Templating\EngineInterface;
use Twig\Environment;

/**
 * @internal
 */
class TwigDefaultDelegatingEngine extends BaseDelegatingEngine
{
    protected Environment $twig;

    protected bool $delegate = false;

    /**
     * @param Environment $twig
     * @param EngineInterface[] $engines
     */
    public function __construct(Environment $twig, array $engines = [])
    {
        $this->twig = $twig;

        parent::__construct($engines);
    }

    /**
     * {@inheritdoc}
     */
    public function exists($name): bool
    {
        if (!$this->delegate) {
            return $this->twig->getLoader()->exists($name);
        } else {
            return parent::exists($name);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function render($name, array $parameters = []): string
    {
        if (!$this->delegate) {
            return $this->twig->render($name, $parameters);
        } else {
            return parent::render($name, $parameters);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports($name): bool
    {
        if (!$this->delegate) {
            return true;
        } else {
            return parent::supports($name);
        }
    }

    public function setDelegate(bool $delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * @return bool $delegate
     */
    public function isDelegate(): bool
    {
        return $this->delegate;
    }

    public function getTwigEnvironment(): Environment
    {
        return $this->twig;
    }

    /**
     * @param string $view
     * @param array $parameters
     * @param Response|null $response
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function renderResponse(string $view, array $parameters = [], Response $response = null): Response
    {
        if (null === $response) {
            $response = new Response();
        }

        $response->setContent($this->render($view, $parameters));

        return $response;
    }
}

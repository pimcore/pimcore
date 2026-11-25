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

namespace Pimcore\Templating;

use Exception;
use Pimcore\Config;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\DelegatingEngine as BaseDelegatingEngine;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Templating\TemplateReferenceInterface;
use Twig\Environment;
use Twig\Extension\SandboxExtension;

/**
 * @internal
 *
 * @deprecated since 12.3, will be removed in 13.0. Will be replaced with direct Twig injection.
 */
class TwigDefaultDelegatingEngine extends BaseDelegatingEngine
{
    protected bool $delegate = false;

    /**
     * Number of sandboxed renders in progress. A sandboxed template can start another sandboxed render,
     * so the sandbox may only be disabled again once the outermost render has finished.
     */
    private int $sandboxDepth = 0;

    /**
     * @param EngineInterface[] $engines
     */
    public function __construct(protected Environment $twig, protected Config $config, array $engines = [])
    {
        parent::__construct($engines);
    }

    public function exists(string|TemplateReferenceInterface $name): bool
    {
        if (!$this->delegate) {
            return $this->twig->getLoader()->exists($name);
        } else {
            return parent::exists($name);
        }
    }

    /**
     *
     *
     * @throws Exception
     */
    public function render(string|TemplateReferenceInterface $name, array $parameters = []): string
    {
        if (!$this->delegate) {
            return $this->twig->render($name, $parameters);
        } else {
            return parent::render($name, $parameters);
        }
    }

    public function supports(string|TemplateReferenceInterface $name): bool
    {
        if (!$this->delegate) {
            return true;
        } else {
            return parent::supports($name);
        }
    }

    public function setDelegate(bool $delegate): void
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

    public function getTwigEnvironment(bool $sandboxed = false): Environment
    {
        if ($sandboxed) {
            /** @var SandboxExtension $sandboxExtension */
            $sandboxExtension = $this->twig->getExtension(SandboxExtension::class);
            $sandboxExtension->enableSandbox();
            $this->sandboxDepth++;
        }

        return $this->twig;
    }

    /**
     * Ends one sandboxed render started with getTwigEnvironment(true). The sandbox stays enabled while an
     * outer sandboxed render is still in progress.
     */
    public function disableSandboxExtensionFromTwigEnvironment(): void
    {
        if ($this->sandboxDepth > 0) {
            $this->sandboxDepth--;
        }

        if ($this->sandboxDepth > 0) {
            return;
        }

        /** @var SandboxExtension $sandboxExtension */
        $sandboxExtension = $this->twig->getExtension(SandboxExtension::class);
        $sandboxExtension->disableSandbox();
    }

    /**
     *
     *
     * @throws Exception
     */
    public function renderResponse(string $view, array $parameters = [], ?Response $response = null): Response
    {
        if (null === $response) {
            $response = new Response();
        }

        $response->setContent($this->render($view, $parameters));

        return $response;
    }
}

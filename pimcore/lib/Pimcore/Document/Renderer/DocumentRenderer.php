<?php

declare(strict_types=1);

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

namespace Pimcore\Document\Renderer;

use Pimcore\Model\Document;
use Pimcore\Templating\Renderer\ActionRenderer;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface;

class DocumentRenderer implements DocumentRendererInterface
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ActionRenderer
     */
    private $actionRenderer;

    /**
     * @var FragmentRendererInterface
     */
    private $fragmentRenderer;

    /**
     * @param RequestStack $requestStack
     * @param ActionRenderer $actionRenderer
     * @param FragmentRendererInterface $fragmentRenderer
     */
    public function __construct(
        RequestStack $requestStack,
        ActionRenderer $actionRenderer,
        FragmentRendererInterface $fragmentRenderer
    ) {
        $this->requestStack     = $requestStack;
        $this->actionRenderer   = $actionRenderer;
        $this->fragmentRenderer = $fragmentRenderer;
    }

    /**
     * @inheritdoc
     */
    public function render(Document\PageSnippet $document, array $attributes = [], array $query = [], array $options = []): string
    {
        $uri      = $this->actionRenderer->createDocumentReference($document, $attributes, $query);
        $response = $this->fragmentRenderer->render($uri, $this->requestStack->getCurrentRequest(), $options);

        return $response->getContent();
    }
}

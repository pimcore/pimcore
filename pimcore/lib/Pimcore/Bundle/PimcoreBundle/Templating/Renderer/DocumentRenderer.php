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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\PimcoreBundle\Templating\Renderer;


use Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine;
use Pimcore\Model\Document;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface;

class DocumentRenderer {

    /**
     * @var ActionRenderer
     */
    protected $actionRenderer;

    /**
     * @var FragmentRendererInterface
     */
    protected $fragmentRenderer;

    /**
     * DocumentRenderer constructor.
     * @param ActionRenderer $actionRenderer
     * @param FragmentRendererInterface $fragmentRenderer
     */
    public function __construct(ActionRenderer $actionRenderer, FragmentRendererInterface $fragmentRenderer)
    {
        $this->actionRenderer = $actionRenderer;
        $this->fragmentRenderer = $fragmentRenderer;
    }

    /**
     * renders document and returns rendered result as string
     *
     * @param Document $document
     * @param array $params
     * @param bool $useLayout
     * @return string
     */
    public function render(Document $document, $params = [], $useLayout = false) {

        //TODO consider useLayout == false

        if ($document && $document instanceof Document\PageSnippet) {
            $params = $this->actionRenderer->addDocumentParams($document, $params);
        }

        $uri = $this->actionRenderer->createDocumentReference($document, $params);

        $content = $this->fragmentRenderer->render($uri, new Request($params));

        return $content->getContent();
    }

}
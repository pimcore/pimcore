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

namespace Pimcore\Controller;

use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Http\Request\Resolver\ResponseHeaderResolver;
use Pimcore\Model\Document;
use Pimcore\Templating\Renderer\EditableRenderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @property Document\PageSnippet $document
 * @property bool $editmode
 */
abstract class FrontendController extends Controller
{
    /**
     * @var ResponseHeaderResolver
     */
    protected $responseResolver;
    /**
     * @var RequestStack
     */
    protected $requestStack;
    /**
     * @var DocumentResolver
     */
    protected $documentResolver;
    /**
     * @var TranslatorInterface
     */
    protected $translator;
    /**
     * @var EditmodeResolver
     */
    protected $editmodeResolver;
    /**
     * @var EditableRenderer
     */
    protected $editableRenderer;

    /**
     * document and editmode as properties and proxy them to request attributes through
     * their resolvers.
     *
     * {@inheritdoc}
     */
    public function __get($name)
    {
        if ('document' === $name) {
            return $this->documentResolver->getDocument();
        }

        if ('editmode' === $name) {
            return $this->editmodeResolver->isEditmode();
        }

        throw new \RuntimeException(sprintf('Trying to read undefined property "%s"', $name));
    }

    /**
     * {@inheritdoc}
     */
    public function __set($name, $value)
    {
        $requestAttributes = ['document', 'editmode'];
        if (in_array($name, $requestAttributes)) {
            throw new \RuntimeException(sprintf(
                'Property "%s" is a request attribute and can\'t be set on the controller instance',
                $name
            ));
        }

        throw new \RuntimeException(sprintf('Trying to set unknown property "%s"', $name));
    }

    /**
     * @required
     * @param ResponseHeaderResolver $responseResolver
     */
    public function setResponseResolver(ResponseHeaderResolver $responseResolver)
    {
        $this->responseResolver = $responseResolver;
    }

    /**
     * @required
     * @param RequestStack $requestStack
     */
    public function setRequestStack(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @required
     * @param DocumentResolver $documentResolver
     */
    public function setDocumentResolver(DocumentResolver $documentResolver)
    {
        $this->documentResolver = $documentResolver;
    }

    /**
     * @required
     * @param EditmodeResolver $editmodeResolver
     */
    public function setEditmodeResolver(EditmodeResolver $editmodeResolver)
    {
        $this->editmodeResolver = $editmodeResolver;
    }

    /**
     * @required
     * @param EditableRenderer $editableRenderer
     */
    public function setEditableRenderer(EditableRenderer $editableRenderer)
    {
        $this->editableRenderer = $editableRenderer;
    }

    /**
     * @required
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }


    /**
     * We don't have a response object at this point, but we can add headers here which will be
     * set by the ResponseHeaderListener which reads and adds this headers in the kernel.response event.
     *
     * @param string $key
     * @param array|string $values
     * @param bool $replace
     * @param Request|null $request
     */
    protected function addResponseHeader(string $key, $values, bool $replace = false, Request $request = null)
    {
        if (null === $request) {
            $request = $this->requestStack->getCurrentRequest();
        }

        $this->responseResolver->addResponseHeader($request, $key, $values, $replace);
    }

    /**
     * Loads a document editable
     *
     * e.g. `$this->getDocumentEditable('input', 'foobar')`
     *
     * @param string $type
     * @param string $inputName
     * @param array $options
     * @param Document\PageSnippet|null $document
     *
     * @return Document\Editable\EditableInterface
     * @throws \Exception
     */
    public function getDocumentEditable($type, $inputName, array $options = [], Document\PageSnippet $document = null)
    {
        if (null === $document) {
            $document = $this->document;
        }

        return $this->editableRenderer->getEditable($document, $type, $inputName, $options);
    }

    /**
     * @param string $view
     * @param array $parameters
     * @param Response|null $response
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderTemplate($view, array $parameters = [], Response $response = null)
    {
        return $this->render($view, $parameters, $response);
    }
}

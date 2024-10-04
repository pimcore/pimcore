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

namespace Pimcore\Controller;

use Exception;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Http\Request\Resolver\ResponseHeaderResolver;
use Pimcore\Model\Document;
use Pimcore\Templating\Renderer\EditableRenderer;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @property Document|null $document
 * @property bool $editmode
 */
abstract class FrontendController extends Controller
{
    /**
     * @return string[]
     */
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services[EditmodeResolver::class] = '?'.EditmodeResolver::class;
        $services[DocumentResolver::class] = '?'.DocumentResolver::class;
        $services[ResponseHeaderResolver::class] = '?'.ResponseHeaderResolver::class;
        $services[EditableRenderer::class] = '?'.EditableRenderer::class;

        return $services;
    }

    /**
     * document and editmode as properties and proxy them to request attributes through
     * their resolvers.
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        if ('document' === $name) {
            return $this->container->get(DocumentResolver::class)->getDocument();
        }

        if ('editmode' === $name) {
            return $this->container->get(EditmodeResolver::class)->isEditmode();
        }

        throw new RuntimeException(sprintf('Trying to read undefined property "%s"', $name));
    }

    public function __set(string $name, mixed $value): void
    {
        $requestAttributes = ['document', 'editmode'];
        if (in_array($name, $requestAttributes)) {
            throw new RuntimeException(sprintf(
                'Property "%s" is a request attribute and can\'t be set on the controller instance',
                $name
            ));
        }

        throw new RuntimeException(sprintf('Trying to set unknown property "%s"', $name));
    }

    /**
     * We don't have a response object at this point, but we can add headers here which will be
     * set by the ResponseHeaderListener which reads and adds this headers in the kernel.response event.
     */
    protected function addResponseHeader(string $key, array|string $values, bool $replace = false, Request $request = null): void
    {
        if (null === $request) {
            $request = $this->container->get('request_stack')->getCurrentRequest();
        }

        $this->container->get(ResponseHeaderResolver::class)->addResponseHeader($request, $key, $values, $replace);
    }

    /**
     * Loads a document editable
     *
     * e.g. `$this->getDocumentEditable('input', 'foobar')`
     *
     * @throws Exception
     */
    public function getDocumentEditable(string $type, string $inputName, array $options = [], Document\PageSnippet $document = null): Document\Editable\EditableInterface
    {
        if (null === $document) {
            $document = $this->document;
            if (!$document instanceof Document\PageSnippet) {
                throw new Exception('FrontendController::getDocumentEditable() needs a Document\PageSnippet instance');
            }
        }

        return $this->container->get(EditableRenderer::class)->getEditable($document, $type, $inputName, $options);
    }

    protected function renderTemplate(string $view, array $parameters = [], Response $response = null): Response
    {
        return $this->render($view, $parameters, $response);
    }
}

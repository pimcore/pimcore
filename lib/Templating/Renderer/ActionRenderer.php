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

namespace Pimcore\Templating\Renderer;

use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Model\Document;
use Symfony\Bridge\Twig\Extension\HttpKernelRuntime;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\HttpKernel\Controller\ControllerReference;

/**
 * @internal
 */
class ActionRenderer
{
    /**
     * @var HttpKernelRuntime
     */
    protected $httpKernelRuntime;

    /**
     * @param HttpKernelRuntime $httpKernelRuntime
     */
    public function __construct(HttpKernelRuntime $httpKernelRuntime)
    {
        $this->httpKernelRuntime = $httpKernelRuntime;
    }

    /**
     * Render an URI
     *
     * @param mixed $uri     A URI
     * @param array  $options An array of options
     *
     * @return string
     *
     * @see HttpKernelRuntime::renderFragment()
     */
    public function render($uri, array $options = [])
    {
        if ($uri instanceof Document\PageSnippet) {
            $uri = $this->createDocumentReference($uri, [], $options);
        }

        return $this->httpKernelRuntime->renderFragment($uri, $options);
    }

    /**
     * Create a document controller reference
     *
     * @param Document\PageSnippet $document
     * @param array $attributes
     * @param array $query
     *
     * @return ControllerReference
     */
    public function createDocumentReference(Document\PageSnippet $document, array $attributes = [], array $query = [])
    {
        $attributes = $this->addDocumentAttributes($document, $attributes);

        return new ControllerReference($document->getController(), $attributes, $query);
    }

    /**
     * Add document params to params array
     *
     * @param Document\PageSnippet $document
     * @param array $attributes
     * @param string $context
     *
     * @return array
     */
    public function addDocumentAttributes(Document\PageSnippet $document, array $attributes = [], string $context = PimcoreContextResolver::CONTEXT_DEFAULT)
    {
        if (null !== $context) {
            // document needs to be rendered with default context as the context guesser can't resolve the
            // context from a fragment route
            $attributes[PimcoreContextResolver::ATTRIBUTE_PIMCORE_CONTEXT] = $context;
        }

        // The CMF dynamic router sets the 2 attributes contentDocument and contentTemplate to set
        // a route's document and template. Those attributes are later used by controller listeners to
        // determine what to render. By injecting those attributes into the sub-request we can rely on
        // the same rendering logic as in the routed request.
        $attributes[DynamicRouter::CONTENT_KEY] = $document;

        if ($document->getTemplate()) {
            $attributes[DynamicRouter::CONTENT_TEMPLATE] = $document->getTemplate();
        }

        if ($language = $document->getProperty('language')) {
            $attributes['_locale'] = $language;
        }

        return $attributes;
    }
}

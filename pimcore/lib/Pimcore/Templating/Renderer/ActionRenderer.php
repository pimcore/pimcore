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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Templating\Renderer;

use Pimcore\Controller\Config\ConfigNormalizer;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Model\Document;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\ActionsHelper;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\HttpKernel\Controller\ControllerReference;

class ActionRenderer
{
    /**
     * @var ActionsHelper
     */
    protected $actionsHelper;

    /**
     * @var ConfigNormalizer
     */
    protected $configNormalizer;

    /**
     * @param ActionsHelper $actionsHelper
     * @param ConfigNormalizer $configNormalizer
     */
    public function __construct(ActionsHelper $actionsHelper, ConfigNormalizer $configNormalizer)
    {
        $this->actionsHelper    = $actionsHelper;
        $this->configNormalizer = $configNormalizer;
    }

    /**
     * Render an URI
     *
     * @param string $uri     A URI
     * @param array  $options An array of options
     *
     * @return string
     *
     * @see ActionsHelper::render()
     */
    public function render($uri, array $options = [])
    {
        if ($uri instanceof Document\PageSnippet) {
            $uri = $this->createDocumentReference($uri);
        }

        return $this->actionsHelper->render($uri, $options);
    }

    /**
     * Create a controller reference
     *
     * @param $bundle
     * @param $controller
     * @param $action
     * @param array $attributes
     * @param array $query
     *
     * @return ControllerReference
     */
    public function createControllerReference($bundle, $controller, $action, array $attributes = [], array $query = [])
    {
        $controller = $this->configNormalizer->formatControllerReference(
            $bundle,
            $controller,
            $action
        );

        return $this->actionsHelper->controller($controller, $attributes, $query);
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

        return $this->createControllerReference(
            $document->getModule(),
            $document->getController(),
            $document->getAction(),
            $attributes,
            $query
        );
    }

    /**
     * Add document params to params array
     *
     * @param Document\PageSnippet $document
     * @param array $attributes
     * @param string|null $context
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
            $template                                    = $this->configNormalizer->normalizeTemplateName($document->getTemplate());
            $attributes[DynamicRouter::CONTENT_TEMPLATE] = $template;
        }

        if ($language = $document->getProperty('language')) {
            $attributes['_locale'] = $language;
        }

        return $attributes;
    }
}

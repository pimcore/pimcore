<?php

namespace Pimcore\Service;

use Pimcore\Bundle\PimcoreBundle\Service\MvcConfigNormalizer;
use Pimcore\Model\Document;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\ActionsHelper;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\HttpKernel\Controller\ControllerReference;

class RenderService
{
    /**
     * @var ActionsHelper
     */
    protected $actionsHelper;

    /**
     * @var MvcConfigNormalizer
     */
    protected $configNormalizer;

    /**
     * @param ActionsHelper $actionsHelper
     * @param MvcConfigNormalizer $configNormalizer
     */
    public function __construct(ActionsHelper $actionsHelper, MvcConfigNormalizer $configNormalizer)
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
     * @param array $params
     * @param array $query
     * @return ControllerReference
     */
    public function createControllerReference($bundle, $controller, $action, array $params = [], array $query = [])
    {
        $controller = $this->configNormalizer->formatController(
            $bundle,
            $controller,
            $action
        );

        return $this->actionsHelper->controller($controller, $params, $query);
    }

    /**
     * Create a document controller reference
     *
     * @param Document\PageSnippet $document
     * @param array $params
     * @param array $query
     * @return ControllerReference
     */
    public function createDocumentReference(Document\PageSnippet $document, array $params = [], array $query = [])
    {
        $params = $this->addDocumentParams($document, $params);

        return $this->createControllerReference(
            $document->getModule(),
            $document->getController(),
            $document->getAction(),
            $params,
            $query
        );
    }

    /**
     * Add document params to params array
     *
     * @param Document\PageSnippet $document
     * @param array $params
     * @return array
     */
    public function addDocumentParams(Document\PageSnippet $document, array $params = [])
    {
        // The CMF dynamic router sets the 2 attributes contentDocument and contentTemplate to set
        // a route's document and template. Those attributes are later used by controller listeners to
        // determine what to render. By injecting those attributes into the sub-request we can rely on
        // the same rendering logic as in the routed request.
        $params[DynamicRouter::CONTENT_KEY] = $document;

        if ($document->getTemplate()) {
            $template = $this->configNormalizer->normalizeTemplate($document->getTemplate());
            $params[DynamicRouter::CONTENT_TEMPLATE] = $template;
        }

        return $params;
    }
}

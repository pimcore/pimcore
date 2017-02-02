<?php

namespace Pimcore\Bundle\PimcoreZendBundle\Templating;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\Loader\LoaderInterface;
use Symfony\Component\Templating\Storage\Storage;
use Symfony\Component\Templating\TemplateNameParserInterface;
use Symfony\Component\Templating\TemplateReferenceInterface;
use Zend\View\Model\ModelInterface;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\RendererInterface;

class ZendEngine implements EngineInterface
{
    /**
     * @var RendererInterface
     */
    protected $renderer;

    /**
     * @var TemplateNameParserInterface
     */
    protected $parser;

    /**
     * @var LoaderInterface
     */
    protected $loader;

    /**
     * @var Storage[]
     */
    protected $cache = [];

    /**
     * @param RendererInterface $renderer
     * @param TemplateNameParserInterface $parser
     * @param LoaderInterface $loader
     */
    public function __construct(RendererInterface $renderer, TemplateNameParserInterface $parser, LoaderInterface $loader)
    {
        $this->renderer = $renderer;
        $this->parser   = $parser;
        $this->loader   = $loader;
    }

    /**
     * Renders a view and returns a Response.
     *
     * @param string $view The view name
     * @param array $parameters An array of parameters to pass to the view
     * @param Response $response A Response instance
     *
     * @return Response A Response instance
     *
     * @throws \RuntimeException if the template cannot be rendered
     */
    public function renderResponse($view, array $parameters = array(), Response $response = null)
    {
        if (null === $response) {
            $response = new Response();
        }

        $response->setContent($this->render($view, $parameters));

        return $response;
    }

    /**
     * Renders a template.
     *
     * @param string|TemplateReferenceInterface $name A template name or a TemplateReferenceInterface instance
     * @param array $parameters An array of parameters to pass to the template
     *
     * @return string The evaluated template as a string
     *
     * @throws \RuntimeException if the template cannot be rendered
     */
    public function render($name, array $parameters = [])
    {
        /** @var ModelInterface $view */
        $view = null;

        if (isset($parameters['_view'])) {
            $view = $this->buildModel($parameters['_view']);
            unset($parameters['_view']);
        } else {
            $view = new ViewModel();
            $view->setTemplate($name);
        }

        $view->setVariables($parameters);

        if (isset($parameters['_layout'])) {
            $layout = $this->buildModel($parameters['_layout']);
            unset($parameters['_layout']);

            $layout->addChild($view, 'content');
            $view = $layout;
        }

        $result = $this->renderModel($view);

        return $result;
    }

    /**
     * @param string|ModelInterface $name
     * @return ModelInterface
     */
    protected function buildModel($name)
    {
        if ($name instanceof ModelInterface) {
            return $name;
        }

        $model = new ViewModel();
        $model->setTemplate($name);

        return $model;
    }

    /**
     * @param ViewModel $model
     * @return string
     * @throws \Exception
     */
    protected function renderModel(ViewModel $model)
    {
        foreach ($model as $child) {
            if ($child->terminate()) {
                throw new \Exception('Cannot render; encountered a child marked terminal');
            }

            $capture = $child->captureTo();
            if (empty($capture)) {
                continue;
            }

            $result = $this->renderModel($child);

            if ($child->isAppend()) {
                $oldResult = $model->{$capture};
                $model->setVariable($capture, $oldResult . $result);
                continue;
            }

            $model->setVariable($capture, $result);
        }

        return $this->renderer->render($model);
    }

    /**
     * Returns true if the template exists.
     *
     * @param string|TemplateReferenceInterface $name A template name or a TemplateReferenceInterface instance
     *
     * @return bool true if the template exists, false otherwise
     *
     * @throws \RuntimeException if the engine cannot handle the template name
     */
    public function exists($name)
    {
        try {
            $this->load($name);
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if this class is able to render the given template.
     *
     * @param string|TemplateReferenceInterface $name A template name or a TemplateReferenceInterface instance
     *
     * @return bool true if this class supports the given template, false otherwise
     */
    public function supports($name)
    {
        $template = $this->parser->parse($name);

        return 'zend' === $template->get('engine');
    }

    /**
     * Loads the given template.
     *
     * @param string|TemplateReferenceInterface $name A template name or a TemplateReferenceInterface instance
     *
     * @return Storage A Storage instance
     *
     * @throws \InvalidArgumentException if the template cannot be found
     */
    protected function load($name)
    {
        $template = $this->parser->parse($name);

        $key = $template->getLogicalName();
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $storage = $this->loader->load($template);

        if (false === $storage) {
            throw new \InvalidArgumentException(sprintf('The template "%s" does not exist.', $template));
        }

        return $this->cache[$key] = $storage;
    }
}

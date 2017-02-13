<?php

namespace Pimcore\Bundle\PimcoreBundle\Templating;

use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModel;
use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModelInterface;
use Pimcore\Bundle\PimcoreBundle\Templating\Renderer\TagRenderer;
use Pimcore\Model\Document\PageSnippet;
use Symfony\Bundle\FrameworkBundle\Templating\PhpEngine as BasePhpEngine;
use Symfony\Component\Templating\Storage\Storage;

/**
 * Symfony PHP engine with pimcore additions:
 *
 *  - property access - $this->variable and $this->helper()
 *  - tag integration
 */
class PhpEngine extends BasePhpEngine
{
    /**
     * @var TagRenderer
     */
    protected $tagRenderer;

    /**
     * @var ViewModelInterface[]
     */
    protected $viewModels = [];

    /**
     * @param TagRenderer $tagRenderer
     */
    public function setTagRenderer(TagRenderer $tagRenderer)
    {
        $this->tagRenderer = $tagRenderer;
    }

    /**
     * In addition to the core method, this keeps parameters in a ViewModel instance which is accessible from
     * view helpers and via $this->$variable.
     *
     * {@inheritdoc}
     */
    protected function evaluate(Storage $template, array $parameters = array())
    {
        // create view model push it onto the model stack
        $this->viewModels[] = new ViewModel($parameters);

        // render the template
        $result = parent::evaluate($template, $parameters);

        // remove current view model from stack and destroy it
        $viewModel = array_pop($this->viewModels);
        unset($viewModel);

        return $result;
    }

    /**
     * Renders template with current parameters
     *
     * @param $name
     * @param array $parameters
     * @return string
     */
    public function template($name, array $parameters = [])
    {
        if ($viewModel = $this->getViewModel()) {
            // attach current variables
            $parameters = array_replace($viewModel->getParameters()->all(), $parameters);
        }

        return $this->render($name, $parameters);
    }

    /**
     * Get the current view model
     *
     * @return ViewModelInterface
     */
    public function getViewModel()
    {
        $count = count($this->viewModels);
        if ($count > 0) {
            return $this->viewModels[$count - 1];
        }
    }

    /**
     * Get a view model parameter
     *
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    public function getViewParameter($name, $default = null)
    {
        $viewModel = $this->getViewModel();

        if (null !== $viewModel) {
            return $viewModel->getParameters()->get($name, $default);
        }

        return $default;
    }

    /**
     * Get all params passed to current view
     *
     * @return array
     */
    public function getAllParams()
    {
        $viewModel = $this->getViewModel();
        if ($viewModel) {
            return $viewModel->getParameters()->all();
        }

        return [];
    }

    /**
     * Magic getter reads variable from ViewModel
     *
     * @inheritDoc
     */
    public function __get($name)
    {
        return $this->getViewParameter($name);
    }

    /**
     * @inheritDoc
     */
    public function __call($method, $arguments)
    {
        $document = null;
        if ($this->getViewParameter('document') instanceof PageSnippet) {
            $document = $this->document;
        }

        if (null !== $this->tagRenderer && $this->tagRenderer->tagExists($method)) {
            if (!isset($arguments[0])) {
                throw new \Exception('You have to set a name for the called tag (editable): ' . $method);
            }

            // set default if there is no editable configuration provided
            if (!isset($arguments[1])) {
                $arguments[1] = [];
            }

            if (null === $document) {
                throw new \RuntimeException(sprintf('Trying to render the tag "%s", but no document was found', $method));
            }

            return $this->tagRenderer->render($document, $method, $arguments[0], $arguments[1]);
        }

        if (null !== $document) {
            // call method on the current document if it exists
            if (method_exists($document, $method)) {
                return call_user_func_array([$document, $method], $arguments);
            }
        }

        // try to call view helper
        $helper = $this->get($method);
        if (is_callable($helper)) {
            return call_user_func_array($helper, $arguments);
        }

        return $helper;
    }
}

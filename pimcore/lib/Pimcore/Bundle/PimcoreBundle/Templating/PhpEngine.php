<?php

namespace Pimcore\Bundle\PimcoreBundle\Templating;

use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModel;
use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModelInterface;
use Symfony\Bundle\FrameworkBundle\Templating\PhpEngine as BasePhpEngine;
use Symfony\Component\Templating\Storage\Storage;

/**
 * Symfony PHP engine with pimcore additions (property access, tag integration).
 */
class PhpEngine extends BasePhpEngine
{
    /**
     * @var ViewModelInterface[]
     */
    private $viewModels = [];

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
        // TODO: implement view helper delegation

        return '<code>__call: ' . $method . '</code>';
    }
}

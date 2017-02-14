<?php

namespace Pimcore\Bundle\PimcoreBundle\Templating;

use Pimcore\Bundle\PimcoreBundle\Templating\HelperBroker\HelperBrokerInterface;
use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModel;
use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModelInterface;
use Symfony\Bundle\FrameworkBundle\Templating\PhpEngine as BasePhpEngine;
use Symfony\Component\Templating\Helper\HelperInterface;
use Symfony\Component\Templating\Storage\Storage;

/**
 * Symfony PHP engine with pimcore additions:
 *
 *  - property access - $this->variable and $this->helper()
 *  - helper brokers integrate other view helpers (ZF) on __call
 *  - tag integration
 */
class PhpEngine extends BasePhpEngine
{
    /**
     * @var HelperBrokerInterface[]
     */
    protected $helperBrokers = [];

    /**
     * @var ViewModelInterface[]
     */
    protected $viewModels = [];

    /**
     * @param HelperBrokerInterface $helperBroker
     */
    public function addHelperBroker(HelperBrokerInterface $helperBroker)
    {
        $this->helperBrokers[] = $helperBroker;
    }

    /**
     * @inheritDoc
     */
    public function get($name)
    {
        $helper = parent::get($name);
        if ($helper instanceof TemplatingAwareHelperInterface) {
            $helper->setEngine($this);
        }

        return $helper;
    }

    /**
     * In addition to the core method, this keeps parameters in a ViewModel instance which is accessible from
     * view helpers and via $this->$variable.
     *
     * {@inheritdoc}
     */
    protected function evaluate(Storage $template, array $parameters = array())
    {
        // create view model and push it onto the model stack
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
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $viewModel = $this->getViewModel();
        if ($viewModel) {
            $viewModel->getParameters()->set($name, $value);
        } else {
            throw new \RuntimeException(sprintf('Can\'t set variable %s as there is no active view model', $name));
        }
    }

    /**
     * @inheritDoc
     */
    public function __call($method, $arguments)
    {
        // try to run helper from helper broker (native helper, document tag, zend view, ...)
        foreach ($this->helperBrokers as $helperBroker) {
            if ($helperBroker->supports($this, $method)) {
                return $helperBroker->helper($this, $method, $arguments);
            }
        }

        throw new \InvalidArgumentException('Call to undefined method ' . $method);
    }
}

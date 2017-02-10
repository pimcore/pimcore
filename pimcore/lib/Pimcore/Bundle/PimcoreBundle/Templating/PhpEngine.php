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
     * @var array
     */
    private $currentIndexes = [];

    /**
     * In addition to the core method, this keeps parameters in a ViewModel instance which is accessible from
     * view helpers and via $this->$variable.
     *
     * This keeps a an internal counter of templates rendered (template type identified by $this->current) to make sure
     * the correct ViewModel instance is used when looking up vars via __get()
     *
     * {@inheritdoc}
     */
    protected function evaluate(Storage $template, array $parameters = array())
    {
        $currentKey    = $this->current;
        $currentIndex  = 0;
        $previousIndex = null;

        if (isset($this->currentIndexes[$currentKey])) {
            $previousIndex = $currentIndex = $this->currentIndexes[$currentKey];
            $currentIndex++;
        }

        $this->currentIndexes[$currentKey] = $currentIndex;

        // create view model and register it for the current key and index
        $this->viewModels[$currentKey . '-' . $currentIndex] = new ViewModel($parameters);

        // render the template
        $result = parent::evaluate($template, $parameters);

        // clean up
        unset($this->viewModels[$currentKey . '-' . $currentIndex]);

        // set index to previous value
        if (null !== $previousIndex) {
            $this->currentIndexes[$currentKey] = $previousIndex;
        } else {
            unset($this->currentIndexes[$currentKey]);
        }

        return $result;
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

    /**
     * Get the current view model
     *
     * @return ViewModelInterface
     */
    public function getViewModel()
    {
        $index    = isset($this->currentIndexes[$this->current]) ? $this->currentIndexes[$this->current] : 0;
        $modelKey = $this->current . '-' . $index;

        if (isset($this->viewModels[$modelKey])) {
            return $this->viewModels[$modelKey];
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
}

<?php

namespace Pimcore\Bundle\PimcoreBundle\Controller\Traits;

use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModel;
use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModelInterface;

trait ViewAwareTrait
{
    /**
     * @var ViewModelInterface|ViewModel
     */
    protected $view;

    /**
     * @param ViewModelInterface $view
     */
    public function setView(ViewModelInterface $view)
    {
        $this->view = $view;
    }

    /**
     * @return ViewModelInterface
     */
    public function getView()
    {
        return $this->view;
    }
}

<?php

namespace Pimcore\Bundle\PimcoreBundle\Controller;

use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModelInterface;

interface ViewAwareInterface
{
    /**
     * @param ViewModelInterface $view
     */
    public function setView(ViewModelInterface $view);

    /**
     * @return ViewModelInterface
     */
    public function getView();
}

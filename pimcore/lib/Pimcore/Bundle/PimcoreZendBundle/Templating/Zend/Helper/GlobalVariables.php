<?php

namespace Pimcore\Bundle\PimcoreZendBundle\Templating\Zend\Helper;

use Symfony\Bundle\FrameworkBundle\Templating\GlobalVariables as GlobalVariablesContainer;
use Zend\View\Helper\AbstractHelper;

class GlobalVariables extends AbstractHelper
{
    /**
     * @var GlobalVariablesContainer
     */
    protected $globalVariables;

    /**
     * @param GlobalVariablesContainer $globalVariables
     */
    public function __construct(GlobalVariablesContainer $globalVariables)
    {
        $this->globalVariables = $globalVariables;
    }

    /**
     * @param null $method
     * @return mixed|GlobalVariablesContainer
     */
    public function __invoke($method = null)
    {
        if (null !== $method) {
            $accessor = 'get' . ucfirst($method);
            return call_user_func_array([$this->globalVariables, $accessor], func_get_args());
        }

        return $this->globalVariables;
    }
}

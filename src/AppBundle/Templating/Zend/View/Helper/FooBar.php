<?php

namespace AppBundle\Templating\Zend\View\Helper;

use Zend\View\Helper\AbstractHelper;

class FooBar extends AbstractHelper
{
    /**
     * @return string
     */
    public function __invoke()
    {
        return 'fooBar: ' . time();
    }
}

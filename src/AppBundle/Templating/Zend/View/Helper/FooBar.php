<?php

namespace AppBundle\Templating\Zend\View\Helper;

use Zend\View\Helper\AbstractHelper;

class FooBar extends AbstractHelper
{
    protected $count = 0;

    /**
     * @return string
     */
    public function __invoke()
    {
        return 'fooBar: ' . $this->count++;
    }
}

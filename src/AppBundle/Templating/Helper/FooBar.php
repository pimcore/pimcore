<?php

namespace AppBundle\Templating\Helper;

use Symfony\Component\Templating\Helper\Helper;

class FooBar extends Helper
{
    /**
     * @var int
     */
    protected $count = 0;

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'fooBar';
    }

    /**
     * @inheritDoc
     */
    public function __invoke()
    {
        return 'fooBar: ' . $this->count++;
    }
}

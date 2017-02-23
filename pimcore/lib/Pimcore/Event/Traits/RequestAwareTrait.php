<?php

namespace Pimcore\Event\Traits;

use Symfony\Component\HttpFoundation\Request;

trait RequestAwareTrait
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}

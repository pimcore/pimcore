<?php

namespace PimcoreBundle\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class EditmodeResolver
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @return Request
     */
    protected function getRequest()
    {
        if (!$this->requestStack->getCurrentRequest()) {
            throw new \LogicException('A Request must be available.');
        }

        return $this->requestStack->getCurrentRequest();
    }

    /**
     * @return bool
     */
    public function isEditmode(Request $request = null)
    {
        if (null === $request) {
            $request = $this->getRequest();
        }

        // TODO editmode is only available for logged in users
        if ($request->get('pimcore_editmode')) {
            return true;
        }

        return false;
    }
}

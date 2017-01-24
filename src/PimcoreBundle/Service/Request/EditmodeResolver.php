<?php

namespace PimcoreBundle\Service\Request;

use Symfony\Component\HttpFoundation\Request;

class EditmodeResolver extends AbstractRequestResolver
{
    /**
     * @param Request $request
     * @return bool
     */
    public function isEditmode(Request $request = null)
    {
        if (null === $request) {
            $request = $this->getCurrentRequest();
        }

        // TODO editmode is only available for logged in users
        if ($request->get('pimcore_editmode')) {
            return true;
        }

        return false;
    }
}

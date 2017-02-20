<?php

namespace Pimcore\Bundle\PimcoreBundle\Service\Request;

use Pimcore\Tool\Authentication;
use Symfony\Component\HttpFoundation\Request;

class EditmodeResolver extends AbstractRequestResolver
{
    const ATTRIBUTE_EDITMODE = '_editmode';

    /**
     * @param Request $request
     * @return bool
     */
    public function isEditmode(Request $request = null)
    {
        // editmode is only allowed for logged in users
        $user = Authentication::authenticateSession();
        if (!$user) {
            return false;
        }

        if (null === $request) {
            $request = $this->getCurrentRequest();
        }

        // try to ready attribute from request - this allows sub-requests to define their
        // own editmode state
        if ($request->attributes->has(static::ATTRIBUTE_EDITMODE)) {
            return $request->attributes->get(static::ATTRIBUTE_EDITMODE);
        }

        // read editmode from request params
        if ($request->query->get('pimcore_editmode')) {
            return true;
        }

        return false;
    }
}

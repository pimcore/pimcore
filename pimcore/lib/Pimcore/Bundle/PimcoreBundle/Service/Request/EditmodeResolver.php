<?php

namespace Pimcore\Bundle\PimcoreBundle\Service\Request;

use Symfony\Component\HttpFoundation\Request;

class EditmodeResolver extends AbstractRequestResolver
{
    const ATTRIUTE_EDITMODE = '_editmode';

    /**
     * @param Request $request
     * @return bool
     */
    public function isEditmode(Request $request = null)
    {
        if (null === $request) {
            $request = $this->getCurrentRequest();
        }

        if ($request->attributes->has(static::ATTRIUTE_EDITMODE)) {
            return $request->attributes->get(static::ATTRIUTE_EDITMODE);
        }

        // TODO editmode is only available for logged in users
        if ($request->get('pimcore_editmode')) {
            return true;
        }

        return false;
    }
}

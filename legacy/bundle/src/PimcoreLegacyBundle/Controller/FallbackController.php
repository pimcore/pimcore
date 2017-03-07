<?php

namespace PimcoreLegacyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class FallbackController extends Controller
{
    public function fallbackAction(Request $request)
    {
        $legacyKernel = $this->get('pimcore.legacy_kernel');

        return $legacyKernel->handle($request);
    }
}

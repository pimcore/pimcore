<?php

namespace Pimcore\Bundle\PimcoreBundle\Controller;

use Pimcore\Bundle\PimcoreBundle\Controller\Traits\DocumentAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

abstract class FrontendController extends Controller implements DocumentAwareInterface
{
    use DocumentAwareTrait;
}

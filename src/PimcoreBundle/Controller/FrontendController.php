<?php

namespace PimcoreBundle\Controller;

use PimcoreBundle\Controller\Traits\DocumentAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

abstract class FrontendController extends Controller implements DocumentAwareInterface
{
    use DocumentAwareTrait;
}

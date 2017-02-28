<?php

use Pimcore\Bundle\PimcoreBundle\HttpKernel\Config\SystemConfigParamResource;

/** @var \Symfony\Component\DependencyInjection\ContainerBuilder $container */
$resource = new SystemConfigParamResource($container);
$resource->setParameters();

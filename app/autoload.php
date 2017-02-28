<?php

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Composer\Autoload\ClassLoader;

/** @var ClassLoader $loader */
$loader = require __DIR__.'/../vendor/autoload.php';

AnnotationRegistry::registerLoader([$loader, 'loadClass']);

// ignore apiDoc params (see http://apidocjs.com/) as we use apiDoc in webservice
// controllers. TODO: use @ApiDoc (NelmioApiDocBundle) instead?
$apiDocAnnotations = [
    'api',
    'apiDefine',
    'apiDeprecated',
    'apiDescription',
    'apiError',
    'apiErrorExample',
    'apiExample',
    'apiGroup',
    'apiHeader',
    'apiHeaderExample',
    'apiIgnore',
    'apiName',
    'apiParam',
    'apiParamExample',
    'apiPermission',
    'apiSampleRequest',
    'apiSuccess',
    'apiSuccessExample',
    'apiUse',
    'apiVersion',
];

foreach ($apiDocAnnotations as $apiDocAnnotation) {
    AnnotationReader::addGlobalIgnoredName($apiDocAnnotation);
}

return $loader;

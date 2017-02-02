<?php

namespace Pimcore\Bundle\PimcoreBundle\Service\Document;

use Pimcore\Controller\Router\Route\Frontend;
use Pimcore\Model\Document;
use Symfony\Component\HttpFoundation\Request;

class DocumentService
{
    /**
     * @param $id
     * @return Document
     */
    public function getById($id)
    {
        return Document::getById($id);
    }

    /**
     * Get the nearest document by path. Used to match nearest document for a static route.
     *
     * @param string|Request $path
     * @param bool $ignoreHardlinks
     * @param array $types
     * @return null|Document
     */
    public function getNearestDocumentByPath($path, $ignoreHardlinks = false, array $types = [])
    {
        if ($path instanceof Request) {
            $path = urldecode($path->getPathInfo());
        }

        // HACK HACK use the pimcore route for testing - refactor method from ZF1 route
        $reflector = new \ReflectionClass(Frontend::class);

        $method = $reflector->getMethod('getNearestDocumentByPath');
        $method->setAccessible(true);

        $nearestDocument = $method->invoke(new Frontend(), $path, $ignoreHardlinks, $types);

        return $nearestDocument;
    }
}

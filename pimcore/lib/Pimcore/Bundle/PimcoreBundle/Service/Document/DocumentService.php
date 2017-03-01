<?php

namespace Pimcore\Bundle\PimcoreBundle\Service\Document;

use Pimcore\Controller\Router\Route\Frontend;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
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
}

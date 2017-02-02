<?php

namespace Pimcore\Bundle\PimcoreBundle\Service\Document;

use Pimcore\Model\Document;

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

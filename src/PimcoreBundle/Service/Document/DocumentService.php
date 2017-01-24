<?php

namespace PimcoreBundle\Service\Document;

use Pimcore\Model\Document;

class DocumentService
{
    /**
     * @param $id
     * @return Document
     */
    public function get($id)
    {
        return Document::getById($id);
    }
}

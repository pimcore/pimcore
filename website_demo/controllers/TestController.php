<?php

use Website\Controller\Action;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Object;
use Pimcore\Model;

class TestController extends Action
{

    public function testAction() {
    }

    public function thumbnailAction() {
        $a = Asset::getById(22);
        $a->clearThumbnails(true);
        $t = $a->getThumbnail("content");

        header("Content-Type: image/jpeg");

        while (@ob_end_flush()) ;
        flush();


        readfile($t->getFileSystemPath());
        exit;
    }
}

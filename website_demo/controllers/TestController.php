<?php

class TestController extends Website_Controller_Action {

    public function testAction () {


    }

    public function thumbnailAction () {

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

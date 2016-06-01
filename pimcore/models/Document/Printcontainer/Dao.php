<?php

namespace Pimcore\Model\Document\Printcontainer;

use \Pimcore\Model\Document;


class Dao extends Document\PrintAbstract\Dao {

    public function getLastedChildMofidicationDate() {
        $path = $this->model->getFullPath();
        return $this->db->fetchOne("SELECT modificationDate FROM documents WHERE path LIKE ? ORDER BY modificationDate DESC LIMIT 0,1", array($path . "%"));
    }
}

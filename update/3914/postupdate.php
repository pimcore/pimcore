<?php

$db = \Pimcore\Db::get();

$db->query("
    ALTER TABLE documents_printpage
    ADD contentMasterDocumentId INT(11) AFTER lastGenerateMessage;
");

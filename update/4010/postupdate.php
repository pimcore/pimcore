<?php

$db = \Pimcore\Db::get();

$db->query("ALTER TABLE users ADD websiteTranslationLanguagesEdit LONGTEXT, ADD websiteTranslationLanguagesView LONGTEXT;");
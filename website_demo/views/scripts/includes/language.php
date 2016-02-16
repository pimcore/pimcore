<div class="languages">
    <div class="dropdown">
        <button class="btn btn-default btn-xs dropdown-toggle" type="button" id="languageSelector" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
            <?= $this->translate("Language") ?>
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu" aria-labelledby="languageSelector">
            <?php
                // this is an auto-generated language switcher, of course you can create your own
                $service = new \Pimcore\Model\Document\Service;
                $translations = $service->getTranslations($this->document);
                $links = [];
                foreach(\Pimcore\Tool::getValidLanguages() as $language) {
                    $target = "/" . $language;
                    if(isset($translations[$language])) {
                        $localizedDocument = \Pimcore\Model\Document::getById($translations[$language]);
                        if($localizedDocument) {
                            $target = $localizedDocument->getFullPath();
                        }
                    }

                    $links[$target] = \Zend_Locale::getTranslation($language, "language");
                }
            ?>
            <?php foreach($links as $link => $text) { ?>
                <li><a href="<?= $link ?>"><?= $text ?></a></li>
            <?php } ?>
        </ul>
    </div>
</div>

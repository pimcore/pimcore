/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */
 
 /* THIS FILE IS GENERATED DYNAMICALLY BECAUSE OF DYNAMIC CSS CLASSES IN THE ADMIN */
 

<?php // custom views ?>

<?php if (is_array($this->customviews)) { ?>
    <?php foreach ($this->customviews as $cv) { ?>
    
    <?php if ($cv["icon"]) { ?>
    .pimcore_object_customviews_icon_<?php echo $cv["id"]; ?> {
        background: url(<?php echo $cv["icon"]; ?>) left center no-repeat !important;
    }
    <?php } ?> 
    
    <?php } ?>
<?php } ?>


<?php // language icons ?>

<?php
    $languages = \Pimcore\Tool::getValidLanguages();
?>

<?php foreach ($languages as $language) {
        $iconFile = \Pimcore\Tool::getLanguageFlagFile($language);
        $iconFile = preg_replace("@^" . preg_quote(PIMCORE_DOCUMENT_ROOT, "@") . "@", "", $iconFile);
    ?>
    /* tab icon for localized fields [ <?= $language ?> ] */
    .pimcore_icon_language_<?= strtolower($language) ?> {
        background: url(<?= $iconFile ?>) left center no-repeat;
    }

    /* grid column header icon in translations [ <?= $language ?> ] */
    .x-grid3-hd-translation_column_<?= strtolower($language) ?> {
        background: url(<?= $iconFile ?>) no-repeat 3px 3px ! important;
        padding-left:22px !important;
    }

<?php } ?>
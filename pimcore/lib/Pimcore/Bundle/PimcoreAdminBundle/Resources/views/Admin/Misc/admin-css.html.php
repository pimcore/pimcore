/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

 /* THIS FILE IS GENERATED DYNAMICALLY BECAUSE OF DYNAMIC CSS CLASSES IN THE ADMIN */


<?php // custom views ?>

<?php if (is_array($this->customviews)) { ?>
    <?php foreach ($this->customviews as $cv) { ?>

    <?php if ($cv["icon"]) {
            $treetype = $cv["treetype"] ? $cv["treetype"] : "object";
            ?>
    .pimcore_<?= $treetype ?>_customview_icon_<?= $cv["id"]; ?> {
        background: url(<?= $cv["icon"]; ?>) left center no-repeat !important;
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
        $iconFile = preg_replace("@^" . preg_quote(PIMCORE_PROJECT_ROOT, "@") . "@", "", $iconFile);
    ?>

    /* tab icon for localized fields [ <?= $language ?> ] */
    .pimcore_icon_language_<?= strtolower($language) ?> {
        background: url(<?= $iconFile ?>) center center/contain no-repeat;
    }

    /* grid column header icon in translations [ <?= $language ?> ] */
    .x-column-header_<?= strtolower($language) ?> {
        background: url(<?= $iconFile ?>) no-repeat left center/contain !important;
        padding-left:22px !important;
    }

<?php } ?>

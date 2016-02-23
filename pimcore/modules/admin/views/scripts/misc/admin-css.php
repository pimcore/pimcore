/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

 /* THIS FILE IS GENERATED DYNAMICALLY BECAUSE OF DYNAMIC CSS CLASSES IN THE ADMIN */


<?php // custom views ?>

<?php if (is_array($this->customviews)) { ?>
    <?php foreach ($this->customviews as $cv) { ?>

    <?php if ($cv["icon"]) { ?>
    .pimcore_object_customviews_icon_<?= $cv["id"]; ?> {
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
        $iconFile = preg_replace("@^" . preg_quote(PIMCORE_DOCUMENT_ROOT, "@") . "@", "", $iconFile);
    ?>


    <?php if (!\Pimcore\Tool\Admin::isExtJS6()) { ?>
        /* tab icon for localized fields [ <?= $language ?> ] */
        .pimcore_icon_language_<?= strtolower($language) ?> {
            background: url(<?= $iconFile ?>) left center/16px 16px no-repeat;
        }

        /* grid column header icon in translations [ <?= $language ?> ] */
        .x-grid3-hd-translation_column_<?= strtolower($language) ?> {
            background: url(<?= $iconFile ?>) no-repeat 3px 3px/16px 16px !important;
            padding-left:22px !important;
        }
    <?php } else { ?>
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

<?php } ?>

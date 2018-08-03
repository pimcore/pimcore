<?php
/**
 * @var \Pimcore\Model\Document\Page $element
 */

$previewImage = null;
if ($element instanceof \Pimcore\Model\Document\Page && \Pimcore\Config::getSystemConfig()->documents->generatepreview) {
    $thumbnailFileHdpi = $element->getPreviewImageFilesystemPath(true);
    if (file_exists($thumbnailFileHdpi)) {
        $previewImage = $this->path('pimcore_admin_page_display_preview_image', ['id' => $element->getId(), 'hdpi' => true]);
    }

}

?>

<?php if($previewImage) {?>
    <div class="full-preview">
        <img src="<?= $previewImage ?>" onload="this.parentNode.className += ' complete';">
        <?= $this->render('PimcoreAdminBundle:SearchAdmin/Search/Quicksearch:info-table.html.php', ['element' => $element]) ?>
    </div>
<?php } else { ?>
    <div class="mega-icon <?= $this->iconCls ?>"></div>
    <?= $this->render('PimcoreAdminBundle:SearchAdmin/Search/Quicksearch:info-table.html.php', ['element' => $element]) ?>
<?php } ?>


<?php
    /**
     * @var \Pimcore\Model\Asset\Image|\Pimcore\Model\Asset\Document|\Pimcore\Model\Asset\Video $element
     */
    $this->get("translate")->setDomain("admin");

    $previewImage = null;
    try {
        $suffix = '&hdpi=true';
        if ($element instanceof \Pimcore\Model\Asset\Image) {
            $previewImage = '/admin/asset/get-image-thumbnail?id=' . $element->getId() . '&treepreview=true' . $suffix;
        } elseif ($element instanceof \Pimcore\Model\Asset\Video && \Pimcore\Video::isAvailable()) {
            $previewImage = '/admin/asset/get-video-thumbnail?id=' . $element->getId() . '&treepreview=true'. $suffix;
        } elseif ($element instanceof \Pimcore\Model\Asset\Document && \Pimcore\Document::isAvailable()) {
            $previewImage = '/admin/asset/get-document-thumbnail?id=' . $element->getId() . '&treepreview=true' . $suffix;
        }
    } catch (\Exception $e) {

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


<?php
    /**
     * @var \Pimcore\Model\Asset\Image|\Pimcore\Model\Asset\Document|\Pimcore\Model\Asset\Video $element
     */
    $element = $this->element;
    $this->get("translate")->setDomain("admin");

    $previewImage = null;
    try {
        if ($element instanceof \Pimcore\Model\Asset\Image) {
             $previewImage =$this->generateUrl('pimcore_admin_asset_getimagethumbnail', [
                'id' => $element->getId(),
                'treepreview' => true,
                'hdpi' => true,
            ]);
        }

        elseif ($element instanceof \Pimcore\Model\Asset\Video && \Pimcore\Video::isAvailable()) {
            $previewImage = $this->generateUrl('pimcore_admin_asset_getvideothumbnail', [
                'id' => $element->getId(),
                'treepreview' => true,
                'hdpi' => true,
            ]);
        }

        if ($element instanceof \Pimcore\Model\Asset\Document && \Pimcore\Document::isAvailable()) {
            $previewImage = $this->generateUrl('pimcore_admin_asset_getdocumentthumbnail', [
                'id' => $element->getId(),
                'treepreview' => true,
                'hdpi' => true,
            ]);
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


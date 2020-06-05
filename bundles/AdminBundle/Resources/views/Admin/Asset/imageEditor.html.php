<?php
/** @var \Pimcore\Templating\PhpEngine $view */
?>
<!DOCTYPE html>
<html dir="ltr" lang="en-US">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <base href="/bundles/pimcoreadmin/js/lib/minipaint/" />
    <script src="/bundles/pimcoreadmin/js/lib/minipaint/dist/bundle.js"></script>
</head>
<body>
<div class="wrapper">

    <div class="submenu">
        <div class="block attributes" id="action_attributes"></div>
        <div class="clear"></div>
    </div>

    <div class="sidebar_left" id="tools_container"></div>

    <div class="main_wrapper" id="main_wrapper">
        <div class="canvas_wrapper" id="canvas_wrapper">
            <div id="mouse"></div>
            <div class="transparent-grid" id="canvas_minipaint_background"></div>
            <canvas id="canvas_minipaint">
                <div class="trn error">
                    Your browser does not support canvas or JavaScript is not enabled.
                </div>
            </canvas>
        </div>
    </div>

    <div class="sidebar_right">
        <div class="preview block">
            <h2 class="trn toggle" data-target="toggle_preview">Preview</h2>
            <div id="toggle_preview"></div>
        </div>

        <div class="colors block">
            <h2 class="trn toggle" data-target="toggle_colors">Colors</h2>
            <input
                title="Click to change color"
                type="color"
                class="color_area"
                id="main_color"
                value="#0000ff"	/>
            <div class="content" id="toggle_colors"></div>
        </div>

        <div class="block" id="info_base">
            <h2 class="trn toggle toggle-full" data-target="toggle_info">Information</h2>
            <div class="content" id="toggle_info"></div>
        </div>

        <div class="details block" id="details_base">
            <h2 class="trn toggle toggle-full" data-target="toggle_details">Layer details</h2>
            <div class="content" id="toggle_details"></div>
        </div>

        <div class="layers block">
            <h2 class="trn">Layers</h2>
            <div class="content" id="layers_base"></div>
        </div>
    </div>
</div>
<div class="mobile_menu">
    <button class="right_mobile_menu" id="mobile_menu_button" type="button"></button>
</div>
<div class="ddsmoothmenu" id="main_menu"></div>
<div class="hidden" id="tmp"></div>
<div id="popup"></div>

<?php
    $imageFileExtension = \Pimcore\File::getFileExtension($this->asset->getFilename());
    $imageUrl = $view->router()->path('pimcore_admin_asset_getasset', ['id' => $this->asset->getId()]);
    if(!in_array($imageFileExtension, ['png', 'jpg', 'jpeg'])) {
        $imageUrl = $view->router()->path('pimcore_admin_asset_getimagethumbnail', [
            'id' => $this->asset->getId(),
            'format' => 'png'
        ]);
    }

?>

<img style="visibility: hidden" id='image' src='<?= $imageUrl ?>'/>
<script>
    window.addEventListener('load', function (e) {
        var image = document.getElementById('image');
        window.Layers.insert({
            name: "<?= $this->asset->getFilename() ?>",
            type: 'image',
            data: image,
            width: image.naturalWidth || image.width,
            height: image.naturalHeight || image.height,
            width_original: image.naturalWidth || image.width,
            height_original: image.naturalHeight || image.height,
        });

        document.getElementById('save_button').addEventListener('click', function () {

            var tempCanvas = document.createElement("canvas");
            var tempCtx = tempCanvas.getContext("2d");
            var dim = window.Layers.get_dimensions();
            tempCanvas.width = dim.width;
            tempCanvas.height = dim.height;
            Layers.convert_layers_to_canvas(tempCtx);
            var dataUri = tempCanvas.toDataURL('image/<?= ($imageFileExtension == "png") ? "png" : "jpeg" ?>');

            parent.Ext.Ajax.request({
                url: "<?=$view->router()->path('pimcore_admin_asset_imageeditorsave', ['id' => $this->asset->getId()])?>",
                method: 'PUT',
                params: {
                    dataUri: dataUri
                }
            });

            return false;
        });
    }, false);
</script>

</body>
</html>

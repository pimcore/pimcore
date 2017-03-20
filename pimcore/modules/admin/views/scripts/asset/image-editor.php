<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style type="text/css">
        html {
            height: 100%;
            overflow: hidden;
        }

        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body>

<script type="text/javascript" src="https://dme0ih8comzn4.cloudfront.net/imaging/v3/editor.js"></script>
<script type="text/javascript" src="/pimcore/static6/js/lib/jquery.min.js"></script>

<div id="editor"></div>

<img style="visibility: hidden" id='image' src='/admin/asset/get-image-thumbnail/id/<?= $this->asset->getId() ?>/width/1000/height/1000/contain/true/image.png'/>

<script type='text/javascript'>
    var featherEditor = new Aviary.Feather({
        apiKey: '9d80aadbc8cb41e98bd3f373efc85187',
        theme: 'light',
        tools: 'all',
        appendTo: 'editor',
        language: "<?= $this->language ?>",
        enableCORS: true,
        noCloseButton: true,
        fileFormat: "<?= (\Pimcore\File::getFileExtension($this->asset->getFilename()) == "png") ? "png" : "jpg" ?>",
        jpgQuality: 90,
        onSave: function(imageID, newURL) {
            $.ajax({
                url: "/admin/asset/image-editor-save/id/<?= $this->asset->getId() ?>",
                method: "GET",
                data: { url : newURL },
                dataType: "json"
            });
        },
        onError: function(errorObj) {
            alert(errorObj.message);
        }
    });

    window.setTimeout(function () {
        featherEditor.launch({
            image: "image"
        });
    }, 2000);
</script>

</body>
</html>

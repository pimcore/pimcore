<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    <style type="text/css">

        /* hide from ie on mac \*/
        html {
            height: 100%;
            overflow: hidden;
        }
        /* end hide */

        body {
            height: 100%;
            margin: 0;
            padding: 0;
            background: #000;
        }

        #videoContainer {
            text-align: center;
            position: absolute;
            top:50%;
            margin-top: -200px;
            width: 100%;
        }

        video {

        }

    </style>

</head>

<body>


<?php
    $thumbnail = "";
    if(\Pimcore\Video::isAvailable()) {
        $thumbnail = "/admin/asset/get-video-thumbnail/id/" . $this->asset->getId() . "/treepreview/true";
    }
?>

<div id="videoContainer">
    <video id="video" controls="controls" height="400" poster="<?= $thumbnail ?>">
        <source src="<?= $this->thumbnail["formats"]["mp4"] ?>" type="video/mp4" />
    </video>
</div>


</body>
</html>
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


<div id="videoContainer">
    <video id="video" autoplay="autoplay" controls="controls" height="400">
        <source src="<?= $this->thumbnail["formats"]["mp4"] ?>" type="video/mp4" />
        <source src="<?= $this->thumbnail["formats"]["webm"] ?>" type="video/webm" />
    </video>
</div>


</body>
</html>
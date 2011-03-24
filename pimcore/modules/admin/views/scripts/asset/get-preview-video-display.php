<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    <style type="text/css">

        /* hide from ie on mac \*/
        html {
            height: 100%;
            overflow: hidden;
        }

        #flashcontent {
            height: 100%;
        }

        /* end hide */

        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

    </style>


    <script type="text/javascript" src="/pimcore/static/js/lib/swfobject/swfobject.js"></script>

</head>

<body>

<?php $video = $this->asset->getCustomSetting("youtube"); ?>


<script type="text/javascript">

    var ytplayer = null;
    var interval = null;

    function onYouTubePlayerReady(playerId) {

        try {
            ytplayer = document.getElementById("myytplayer");
            ytplayer.playVideo();

            interval = window.setTimeout(function () {
                try {
                var duration = ytplayer.getDuration();
                    if (duration < 1) {
                        location.reload();
                    }
                } catch (e) {
                    console.log("video is not converted yet");
                }
            }, 10000);
        } catch (e) {
            console.log(e);
        }
    }

</script>

<div id="ytapiplayer"></div>

<script type="text/javascript">

    var params = { allowScriptAccess: "always", wmode: "transparent" };
    var atts = { id: "myytplayer" };
    swfobject.embedSWF("http://www.youtube.com/v/<?php echo $video["id"] ?>?enablejsapi=1&playerapiid=ytplayer&rel=0&hd=1", "ytapiplayer", "100%", "100%", "8", null, null, params, atts);

</script>


</body>
</html>
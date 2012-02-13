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

</head>

<body>

<div id="flashcontent"></div>

<script type="text/javascript" src="/pimcore/static/js/lib/flowplayer/flowplayer.min.js"></script><script type="text/javascript" src="/pimcore/static/js/lib/array_merge.js"></script><script type="text/javascript" src="/pimcore/static/js/lib/array_merge_recursive.js"></script><div id="pimcore_video_myVideocontentblock2"><div id="video_4efc79ca37515"></div></div>
    <script type="text/javascript">
        var player = flowplayer("flashcontent", {
            src: "/pimcore/static/js/lib/flowplayer/flowplayer.swf",
            width: "100%",
            height: "100%",
            wmode: "transparent"
        },{
            "clip": {
                "autoPlay":false,
                scaling: "orig",
                "url": <?php echo Zend_Json::encode($this->thumbnail["formats"]["mp4"]); ?>
            },
            "plugins": {
                "controls": {
                    "autoHide": {
                        "enabled": false
                    }
                }
            }
        });
    </script>
</div>


</body>
</html>
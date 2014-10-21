<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link rel="stylesheet" type="text/css" href="/pimcore/static/css/admin.css"/>

    <style type="text/css">

        /* hide from ie on mac \*/
        html {
            height: 100%;
            overflow: hidden;
        }

        #wrapper {
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

<table id="wrapper" width="100%" height="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td class="error" align="center" valign="center">
            <?php if ($this->thumbnail && $this->thumbnail["status"] == "inprogress") { ?>
                <?php $progress = \Pimcore\Model\Asset\Video\Thumbnail\Processor::getProgress($this->thumbnail["processId"]) ?>

                <style type="text/css">
                    .pimcore_tag_video_progress {
                        position:relative;
                        background:#555 url(<?php echo $this->asset->getImageThumbnail(array("width" => 640)); ?>) no-repeat center center;
                        font-family:Arial,Verdana,sans-serif;
                        color:#fff;
                        text-shadow: 0 0 3px #000, 0 0 5px #000, 0 0 1px #000;
                    }
                    .pimcore_tag_video_progress_status {
                        font-size:16px;
                        color:#555;
                        font-family:Arial,Verdana,sans-serif;
                        line-height:66px;
                        background:#fff url(/pimcore/static/img/video-loading.gif) center center no-repeat;
                        width:66px;
                        height:66px;
                        padding:20px;
                        border:1px solid #555;
                        text-align:center;
                        box-shadow: 2px 2px 5px #333;
                        border-radius:20px;
                        top: <?php echo ((380-106)/2); ?>px;
                        left: <?php echo ((640-106)/2); ?>px;
                        position:absolute;
                        opacity: 0.8;
                        text-shadow: none;
                    }
                </style>
                <div class="pimcore_tag_video_progress" style="width:640px; height:380px;">

                    <br />
                    <?php echo $this->translate("video_preview_in_progress"); ?>
                    <br />
                    <?php echo $this->translate("please_wait"); ?>

                    <div class="pimcore_tag_video_progress_status"><?php echo number_format($progress,0) ?>%</div>
                </div>


                <script type="text/javascript">
                    window.setTimeout(function () {
                        location.reload();
                    }, 5000);
                </script>
            <?php } else if (!\Pimcore\Video::isAvailable()) { ?>
                <?php echo $this->translate("preview_not_available"); ?>
                <br />
                <?php echo $this->translate("php_cli_binary_and_or_ffmpeg_binary_setting_is_missing"); ?>
            <?php } else { ?>
                <?php echo $this->translate("preview_not_available"); ?>
                <br />
                Error unknown, please check the debug.log
            <?php } ?>
        </td>
    </tr>
</table>


</body>
</html>
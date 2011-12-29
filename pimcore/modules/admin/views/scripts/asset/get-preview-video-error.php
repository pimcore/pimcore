<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
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
                <?php echo $this->translate("video_preview_in_progress"); ?>
                <br />
                <b><?php echo $this->translate("status"); ?> : <?= number_format(Asset_Video_Thumbnail_Processor::getProgress($this->thumbnail["processId"]),2) ?>%</b>
                <br />
                <br />
                <?php echo $this->translate("please_wait"); ?>
                <script type="text/javascript">
                    window.setTimeout(function () {
                        location.reload();
                    }, 2000);
                </script>
            <?php } else if (!Pimcore_Video::isAvailable()) { ?>
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
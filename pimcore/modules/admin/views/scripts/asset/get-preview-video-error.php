<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link rel="stylesheet" type="text/css" href="/pimcore/static/css/admin.css"/>


</head>

<body>

<table id="wrapper" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td class="error">
        <?php echo $this->translate("no_preview_available");  ?><br/>
        <?php if ($this->configError) { ?>
        <?php echo $this->translate("youtube_missing_config"); ?><br/>
        <?php } ?>
        </td>
    </tr>
</table>


</body>
</html>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html>
<head>
<title><?php echo _("Nag is not properly configured") ?></title>
<style type="text/css">
<!--
body {
        font-family: Geneva,Arial,Helvetica,sans-serif;
        font-size: 9pt;
        background-color: #222244;
        color: black;
}

.smallheader {
        color: #ccccee;
        background-color: #444466;
        font-family: Geneva,Arial,Helvetica,sans-serif;
        font-size: 9pt;
}

.light {
        color: white;
        font-family: Geneva,Arial,Helvetica,sans-serif;
        font-size: 9pt;
}

.header {
        color: #ccccee;
        background-color: #444466;
        font-family: Verdana,Helvetica,sans-serif;
        font-weight: bold;
        font-size: 13pt;
}
-->
</style>
</head>
<body>
<table border="0" align="center" width="500" cellpadding="2" cellspacing="4">
<tr><td colspan="2" class="header"><b><?php echo _("Some of Nag's configuration files are missing:") ?></b></td></tr>

<?php if (!@is_readable('./config/conf.php')): ?>
<tr>
  <td align="right" class="smallheader"><b>conf.php</b></td>
  <td class="light"><?php echo _("This is the main Nag configuration file. It contains options for all Nag scripts.") ?></td>
</tr>
<?php endif; ?>

<?php if (!@is_readable('./config/prefs.php')): ?>
<tr>
  <td align="right" class="smallheader"><b>prefs.php</b></td>
  <td class="light"><?php echo _("This file contains preferences for Nag.") ?></td>
</tr>
<?php endif; ?>

<?php if (!@is_readable('./config/html.php')): ?>
<tr>
  <td align="right" class="smallheader"><b>html.php</b></td>
  <td class="light"><?php echo gettext("This file controls the stylesheet that is used to set colors and fonts in addition to or overriding Horde defaults.") ?></td>
</tr>
<?php endif; ?>

</table>
</body>
</html>

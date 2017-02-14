<?php
$icons = scandir(dirname(dirname(__FILE__)).'/img/icon/');
$iconPath = dirname(dirname($_SERVER['SCRIPT_NAME'])) . '/img/icon/';

$pimcoreIconClasses = [];

//get pimcore css classes for icons
$handle = @fopen(dirname(dirname(__FILE__)) . '/css/icons.css', "r");
$lastIconClass;
if ($handle) {
    while (($row = fgets($handle)) !== false) {
        if (preg_match("@(\.pimcore_icon_[a-z_]+)@", $row, $match)) {
            $lastIconClass = $match[1];
        }

        if (preg_match("@background:\s*url\((.*?)\)@", $row, $match)) {
            $pimcoreIconClasses[$match[1]][] = $lastIconClass;
            $lastIconClass = $match[1];
        }
    }
    fclose($handle);
}

$iconsGrouped = [];
/**
 * @param $icon
 * @param $iconCss
 * @param $pimcoreIconClasses
 * @return array
 */
function getIconData($icon, $iconCss, $pimcoreIconClasses)
{
    $data = [];
    $data['name'] = str_replace('.png', '', $icon);
    $data['path'] = dirname(dirname($_SERVER['SCRIPT_NAME'])) . '/img/icon/'.$icon;
    $data['id'] = str_replace('.', '', $icon);
    $data['iconClass'] = implode(', ', $pimcoreIconClasses[$data['path']]);

    return $data;
}

foreach ($icons as $icon) {
    if ($icon != '.' && $icon != '..') {
        $name = str_replace('.png', '', $icon);

        if (strpos($icon, '_') === false) {
            $iconsGrouped[$name][] = getIconData($icon, $iconCss, $pimcoreIconClasses);
        } else {
            $tmp = explode('_', $icon, 2);
            $iconsGrouped[$tmp[0]][] = getIconData($icon, $iconCss, $pimcoreIconClasses);
        }
    }
}
?><!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Pimcore:: Icon list</title>
    <style type="text/css">
        table {
            font-size: 11px;
            font-family: Arial,Helvetica,sans-serif;
            border-collapse: collapse;
        }
        table td, table th{
            border: 1px solid #333333;
            padding:  5px;
        }
        table th {
            text-align: left;
            background-color: #eeeeee;
            font-size: 12px;
        }

        table .group{
            font-weight: bold;
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
<?php
foreach ($icons as $icon) {
    if ($icon == '.' || $icon == '..') {
        continue;
    }
    ?>
    <a href="#<?=str_replace('.', '', $icon)?>"><img src="<?php echo $iconPath . $icon;
    ?>" title="<?php echo $icon;
    ?>" alt="<?php echo $icon;
    ?>"/></a>
<?php

} ?>
<br/><br/>
<table>
    <tr>
        <th>Icon</th>
        <th>Name</th>
        <th>Path</th>
        <th>Pimcore CSS class</th>
    </tr>
    <?php foreach ($iconsGrouped as $group => $icons) {
    ?>
        <tr class="group">
            <td colspan="4"><?=ucfirst($group)?></td>
        </tr>
        <?php foreach ($icons as $icon) {
        ?>
            <tr>
                <td width="100"><img src="<?=$icon['path']?>" title="<?=$icon['path']?>" als="<?=$icon['path']?>" id="<?=$icon['id']?>"/></td>
                <td><?=$icon['name']?></td>
                <td><?=$icon['path']?></td>
                <td><?=$icon['iconClass']?></td>
            </tr>
        <?php

    }
    ?>

    <?php

}?>


</table>
</body>
</html>


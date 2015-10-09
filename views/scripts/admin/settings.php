<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Pimcore OnlineShop :: Settings</title>
</head>
<body>
	<h3>Properties for OnlineShop-Plugin</h3>

	<form action="" method="post">
		<table>
			<tr>
				<th><div class="inputlabel">Online-Shop-Configfile:</div></th>
				<td><input style="width:500px" class="inputfield" type="text" name="onlineshop_config_file" value="<?=$this->onlineshop_config_file ?>" /></td>
            </tr>
		</table>
		<div class="submitbutton"><input type="submit" value="save"/></div>
	</form>	
</body>
</html>
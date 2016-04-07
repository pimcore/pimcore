<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
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
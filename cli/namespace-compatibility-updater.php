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


$workingDirectory = getcwd();
chdir(__DIR__);
include_once("../../../../../config/startup_cli.php");
chdir($workingDirectory);

\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Legacy\LegacyClassMappingTool::createNamespaceCompatibilityFile();

//\OnlineShop\LegacyClassMappingTool::generateMarkdownTable();

die("done.\n\n");
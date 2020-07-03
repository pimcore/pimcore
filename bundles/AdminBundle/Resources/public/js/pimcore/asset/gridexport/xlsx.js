/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.asset.gridexport.xlsx");
pimcore.asset.gridexport.xlsx = Class.create(pimcore.element.gridexport.abstract, {
    name: "xlsx",
    text: t("export_xlsx"),
    warningText: t('asset_export_warning'),

    getDownloadUrl: function(fileHandle) {
         return Routing.generate('pimcore_admin_asset_assethelper_downloadxlsxfile', {fileHandle: fileHandle});
    }
});

pimcore.globalmanager.get("pimcore.asset.gridexport").push(new pimcore.asset.gridexport.xlsx());

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

pimcore.registerNS("pimcore.gridexport.csv");
pimcore.gridexport.csv = Class.create(pimcore.gridexport.abstract, {
    name: "csv",
    text: t("export_csv"),
    downloadUrl: "/admin/object-helper/download-csv-file"
});

pimcore.globalmanager.get("pimcore.gridexport").push(new pimcore.gridexport.csv())
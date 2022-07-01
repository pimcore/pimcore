/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

pimcore.registerNS("pimcore.events.x");

pimcore.events.uninstall = "pimcore.uninstall";
pimcore.events.pimcoreReady = "pimcore.ready";

pimcore.events.preOpenAsset = "pimcore.asset.preOpen";
pimcore.events.postOpenAsset = "pimcore.asset.postOpen";
pimcore.events.preSaveAsset = "pimcore.asset.preSave";
pimcore.events.postSaveAsset = "pimcore.asset.postSave";
pimcore.events.preDeleteAsset = "pimcore.asset.preDelete";
pimcore.events.postDeleteAsset = "pimcore.asset.postDelete";


pimcore.events.preOpenDocument = "pimcore.document.preOpen";
pimcore.events.postOpenDocument = "pimcore.document.postOpen";
pimcore.events.preSaveDocument = "pimcore.document.preSave";
pimcore.events.postSaveDocument = "pimcore.document.postSave";
pimcore.events.preDeleteDocument = "pimcore.document.preDelete";
pimcore.events.postDeleteDocument = "pimcore.document.postDelete";
pimcore.events.postAddDocumentTree = "pimcore.documentTree.postAdd";

pimcore.events.preOpenObject = "pimcore.object.preOpen";
pimcore.events.postOpenObject = "pimcore.object.postOpen";
pimcore.events.preSaveObject = "pimcore.object.preSave";
pimcore.events.postSaveObject = "pimcore.object.postSave";
pimcore.events.preDeleteObject = "pimcore.object.preDelete";
pimcore.events.postDeleteObject = "pimcore.object.postDelete";
pimcore.events.postAddObjectTree = "pimcore.objectTree.postAdd";

pimcore.events.preCreateMenuOption = "pimcore.menuOption.preCreate";
pimcore.events.preCreateAssetMetadataEditor = "pimcore.assetMetadataEditor.preCreate";
pimcore.events.prepareAssetMetadataGridConfigurator = "pimcore.gridConfigurator.prepareAssetMetadata";
pimcore.events.prepareAssetTreeContextMenu = "pimcore.assetTreeContextMenu.prepare";
pimcore.events.prepareObjectTreeContextMenu = "pimcore.objectTreeContextMenu.prepare";
pimcore.events.prepareDocumentTreeContextMenu = "pimcore.documentTreeContextMenu.prepare";
pimcore.events.prepareClassLayoutContextMenu = "pimcore.classLayoutContextMenu.prepare";
pimcore.events.prepareOnRowContextmenu = "pimcore.onRowContextMenu.prepare";

pimcore.events.prepareOnObjectTreeNodeClick = "pimcore.objectTreeNode.onClick";
pimcore.events.preGetObjectFolder = "pimcore.objectFolder.preGet";
pimcore.events.preCreateObjectGrid = "pimcore.objectGrid.preCreate";
pimcore.events.postOpenReport = "pimcore.report.postOpen";

pimcore.events.preEditTranslations = "pimcore.translations.preEdit";
pimcore.events.prepareDocumentTypesGrid = "pimcore.documentTypesGrid.prepare";

//TODO: delete in Pimcore11
pimcore.events.eventMappings = {
    "uninstall": pimcore.events.uninstall,
    "pimcoreReady": pimcore.events.pimcoreReady,

    "preOpenAsset": pimcore.events.preOpenAsset,
    "postOpenAsset": pimcore.events.postOpenAsset,
    "preSaveAsset": pimcore.events.preSaveAsset,
    "postSaveAsset": pimcore.events.postSaveAsset,
    "preDeleteAsset": pimcore.events.preDeleteAsset,
    "postDeleteAsset": pimcore.events.postDeleteAsset,

    "preOpenDocument": pimcore.events.preOpenDocument,
    "postOpenDocument": pimcore.events.postOpenDocument,
    "preSaveDocument": pimcore.events.preSaveDocument,
    "postSaveDocument": pimcore.events.postSaveDocument,
    "preDeleteDocument": pimcore.events.preDeleteDocument,
    "postDeleteDocument": pimcore.events.postDeleteDocument,
    "postAddDocumentTree": pimcore.events.postAddDocumentTree,

    "preOpenObject": pimcore.events.preOpenObject,
    "postOpenObject": pimcore.events.postOpenObject,
    "preSaveObject": pimcore.events.preSaveObject,
    "postSaveObject": pimcore.events.postSaveObject,
    "preDeleteObject": pimcore.events.preDeleteObject,
    "postDeleteObject": pimcore.events.postDeleteObject,
    "postAddObjectTree": pimcore.events.postAddObjectTree,

    "preCreateMenuOption": pimcore.events.preCreateMenuOption,
    "preCreateAssetMetadataEditor": pimcore.events.preCreateAssetMetadataEditor,
    "prepareAssetMetadataGridConfigurator": pimcore.events.prepareAssetMetadataGridConfigurator,
    "prepareAssetTreeContextMenu": pimcore.events.prepareAssetTreeContextMenu,
    "prepareObjectTreeContextMenu": pimcore.events.prepareObjectTreeContextMenu,
    "prepareDocumentTreeContextMenu": pimcore.events.prepareDocumentTreeContextMenu,
    "prepareClassLayoutContextMenu": pimcore.events.prepareClassLayoutContextMenu,
    "prepareOnRowContextmenu": pimcore.events.prepareOnRowContextmenu,

    "prepareOnObjectTreeNodeClick": pimcore.events.prepareOnObjectTreeNodeClick,
    "preGetObjectFolder": pimcore.events.preGetObjectFolder,
    "preCreateObjectGrid": pimcore.events.preCreateObjectGrid,
    "postOpenReport": pimcore.events.postOpenReport,

    "preEditTranslations": pimcore.events.preEditTranslations,
    "prepareDocumentTypesGrid": pimcore.events.prepareDocumentTypesGrid
};

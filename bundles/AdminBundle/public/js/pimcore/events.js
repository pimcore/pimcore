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

 /**
  * is called when the corresponding plugin is uninstalled via Pimcore backend UI
  */
 pimcore.events.uninstall = "pimcore.uninstall";
 
 /**
  * Pimcore backend UI is loaded
  * viewport is passed as parameter
  */
 pimcore.events.pimcoreReady = "pimcore.ready";
 
 /**
  * before asset is opened
  * asset and type are passed as parameters
  */
 pimcore.events.preOpenAsset = "pimcore.asset.preOpen";
 
 /**
  * after asset is opened
  * asset and type are passed as parameters
  */
 pimcore.events.postOpenAsset = "pimcore.asset.postOpen";
 
 /**
  * before asset is saved
  * asset id and task is passed as parameter
  */
 pimcore.events.preSaveAsset = "pimcore.asset.preSave";
 
 /**
  * after asset is saved
  * asset id is passed as parameter
  */
 pimcore.events.postSaveAsset = "pimcore.asset.postSave";
 
 /**
  * before asset is deleted
  * asset id is passed as parameter
  */
 pimcore.events.preDeleteAsset = "pimcore.asset.preDelete";
 
 /**
  * after asset is deleted
  * asset id is passed as parameter
  */
 pimcore.events.postDeleteAsset = "pimcore.asset.postDelete";
 
 /**
  * before document is opened
  * document and type are passed as parameters
  */
 pimcore.events.preOpenDocument = "pimcore.document.preOpen";
 
 /**
  * after document is opened
  * document and type are passed as parameters
  */
 pimcore.events.postOpenDocument = "pimcore.document.postOpen";
 
 /**
  * before document is saved
  * document, type, task and onlySaveVersion are passed as parameters
  */
 pimcore.events.preSaveDocument = "pimcore.document.preSave";
 
 /**
  * after document is saved
  * document, type, task and onlySaveVersion are passed as parameters
  */
 pimcore.events.postSaveDocument = "pimcore.document.postSave";
 
 /**
  * before document is deleted
  * document id is passed as parameter
  */
 pimcore.events.preDeleteDocument = "pimcore.document.preDelete";
 
 /**
  * after document is deleted
  * document id is passed as parameter
  */
 pimcore.events.postDeleteDocument = "pimcore.document.postDelete";
 
 /**
  * after the document is successfully created in the tree
  * document id is passed as parameter
  */
 pimcore.events.postAddDocumentTree = "pimcore.documentTree.postAdd";
 
 /**
  * before object is opened
  * object and type are passed as parameters
  */
 pimcore.events.preOpenObject = "pimcore.object.preOpen";
 
 /**
  * after object is opened
  * object and type are passed as parameters
  */
 pimcore.events.postOpenObject = "pimcore.object.postOpen";
 
 /**
  * before object is saved
  * object and type are passed as parameters
  */
 pimcore.events.preSaveObject = "pimcore.object.preSave";
 
 /**
  * after object is saved
  * object is passed as parameter
  */
 pimcore.events.postSaveObject = "pimcore.object.postSave";
 
 /**
  * before object is deleted
  * object id is passed as parameter
  */
 pimcore.events.preDeleteObject = "pimcore.object.preDelete";
 
 /**
  * after object is deleted
  * object id is passed as parameter
  */
 pimcore.events.postDeleteObject = "pimcore.object.postDelete";
 
 /**
  * after the object is successfully created in the tree
  * object id is passed as parameter
  */
 pimcore.events.postAddObjectTree = "pimcore.objectTree.postAdd";
 
 /**
  * called before navigation menu is created
  */
 pimcore.events.preCreateMenuOption = "pimcore.menuOption.preCreate";
 
 /**
  * @internal
  *
  * fired when asset metadata editor tab is created
  * editor and eventParams are passed as parameter
  */
 pimcore.events.preCreateAssetMetadataEditor = "pimcore.assetMetadataEditor.preCreate";
 
 /**
  * before opening the grid config dialog
  * url returning the metadata definitions is passed as parameter
  */
 pimcore.events.prepareAssetMetadataGridConfigurator = "pimcore.gridConfigurator.assetMetadata.prepare";
 
 /**
  * before context menu is opened
  * menu, tree and asset are passed as parameters
  */
 pimcore.events.prepareAssetTreeContextMenu = "pimcore.assetTreeContextMenu.prepare";
 
 /**
  * before context menu is opened
  * menu, tree and object are passed as parameters
  */
 pimcore.events.prepareObjectTreeContextMenu = "pimcore.objectTreeContextMenu.prepare";
 
 /**
  * before context menu is opened
  * menu, tree and document are passed as parameters
  */
 pimcore.events.prepareDocumentTreeContextMenu = "pimcore.documentTreeContextMenu.prepare";
 
 /**
  * before context menu is opened
  * allowedTypes array and source is passed as parameters
  */
 pimcore.events.prepareClassLayoutContextMenu = "pimcore.classLayoutContextMenu.prepare";
 
 /**
  * before context menu is opened on object folder
  * menu, grid and selectedRows are passed as parameters
  */
 pimcore.events.prepareOnRowContextmenu = "pimcore.onRowContextMenu.prepare";
 
 /**
  * before the data object is opened, after a tree node has been clicked
  * node item is passed as parameter
  */
 pimcore.events.prepareOnObjectTreeNodeClick = "pimcore.objectTreeNode.onClick";
 
 /**
  * before the data object grid folder configuration is loaded from the server.
  * request configuration is passed as parameter
  */
 pimcore.events.preGetObjectFolder = "pimcore.objectFolder.preGet";
 
 /**
  * before the data object grid items are loaded from the server
  * request configuration are passed as parameter
  */
 pimcore.events.preCreateObjectGrid = "pimcore.objectGrid.preCreate";
 
 /**
  * fired when a report has been opened
  * report grid panel gets passed as parameters
  */
 pimcore.events.postOpenReport = "pimcore.report.postOpen";
 
 /**
  *  before translations is edited
  *  translation and domain are passed as parameters
  */
 pimcore.events.preEditTranslations = "pimcore.translations.preEdit";
 
 /**
  * before document types grid loaded
  * grid and object are passed as parameters
  */
 pimcore.events.prepareDocumentTypesGrid = "pimcore.documentTypesGrid.prepare";
 
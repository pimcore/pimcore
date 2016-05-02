/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

/*global localStorage */
pimcore.registerNS("pimcore.elementservice.x");

pimcore.elementservice.deleteElement = function (options) {
    var elementType = options.elementType;
    var url = "/admin/"  + elementType + "/delete-info/";
    // check for dependencies
    Ext.Ajax.request({
        url: url,
        params: {id: options.id},
        success: pimcore.elementservice.deleteElementCheckDependencyComplete.bind(window, options)
    });
};

pimcore.elementservice.deleteElementCheckDependencyComplete = function (options, response) {

    try {
        var res = Ext.decode(response.responseText);
        var message = res.batchDelete ? t('delete_message_batch') : t('delete_message');
        if (res.hasDependencies) {
            message += "<br />" + t('delete_message_dependencies');
        }

        if(res["childs"] > 100) {
            message += "<br /><br /><b>" + t("too_many_children_for_recyclebin") + "</b>";
        }

        var deleteMethod = "delete" + ucfirst(options.elementType) + "FromServer";

        Ext.MessageBox.show({
            title:t('delete'),
            msg: message,
            buttons: Ext.Msg.OKCANCEL ,
            icon: Ext.MessageBox.INFO ,
            fn: pimcore.elementservice.deleteElementFromServer.bind(window, res, options)
        });
    }
    catch (e) {
        console.log(e);
    }
};

pimcore.elementservice.deleteElementFromServer = function (r, options, button) {

    if (button == "ok" && r.deletejobs) {
        var successHandler = options["success"];
        var elementType = options.elementType;
        var id = options.id;

        var treeNames = ["layout_" + elementType + "_tree"]
        if (pimcore.settings.customviews.length > 0) {
            for (var cvs = 0; cvs < pimcore.settings.customviews.length; cvs++) {
                var cv = pimcore.settings.customviews[cvs];
                if (!cv.treetype || cv.treetype == elementType) {
                    treeNames.push("layout_" + elementType + "_tree_" + cv.id);
                }
            }
        }

        var affectedNodes = [];

        for (index = 0; index < treeNames.length; index++) {
            var treeName = treeNames[index];
            var tree = pimcore.globalmanager.get(treeName);
            if (!tree) {
                continue;
            }
            tree = tree.tree;
            var view = tree.getView();
            var store = tree.getStore();
            var node = store.getNodeById(id);
            pimcore.helpers.addTreeNodeLoadingIndicator(elementType, id);

            if (node) {
                var nodeEl = Ext.fly(view.getNodeByRecord(node));
                nodeEl.addCls("pimcore_delete");
                affectedNodes.push(node);
            }
        }

        if (pimcore.globalmanager.exists(elementType + "_" + id)) {
            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.remove(elementType + "_" + id);
        }

        if(r.deletejobs.length > 2) {
            this.deleteProgressBar = new Ext.ProgressBar({
                text: t('initializing')
            });

            this.deleteWindow = new Ext.Window({
                title: t("delete"),
                layout:'fit',
                width:500,
                bodyStyle: "padding: 10px;",
                closable:false,
                plain: true,
                modal: true,
                items: [this.deleteProgressBar]
            });

            this.deleteWindow.show();
        }
        
        var pj = new pimcore.tool.paralleljobs({
            success: function (id, successHandler) {

                for (index = 0; index < affectedNodes.length; index++) {
                    var node = affectedNodes[index];
                    var tree = node.getOwnerTree();
                    try {

                        var view = tree.getView();
                        var nodeEl = Ext.fly(view.getNodeByRecord(node));

                        if (nodeEl) {
                            nodeEl.removeCls("pimcore_delete");
                        }
                        //Ext.get(this.getUI().getIconEl()).dom.setAttribute("class", this.originalClass);
                        pimcore.helpers.removeTreeNodeLoadingIndicator(elementType, id);

                        if (node) {
                            node.remove();
                        }
                    } catch (e) {
                        console.log(e);
                        pimcore.helpers.showNotification(t("error"), t("error_deleting_" + elementType), "error");
                        if (node) {
                            tree.getStore().load({
                                node: node.parentNode
                            });
                        }
                    }
                }

                if(this.deleteWindow) {
                    this.deleteWindow.close();
                }

                this.deleteProgressBar = null;
                this.deleteWindow = null;

                if(typeof successHandler == "function") {
                    successHandler();
                }
            }.bind(this, id, successHandler),
            update: function (currentStep, steps, percent) {
                if(this.deleteProgressBar) {
                    var status = currentStep / steps;
                    this.deleteProgressBar.updateProgress(status, percent + "%");
                }
            }.bind(this),
            failure: function (id, message) {
                this.deleteWindow.close();

                pimcore.helpers.showNotification(t("error"), t("error_deleting_" + elementType), "error", t(message));
                for (index = 0; index < affectedNodes.length; index++) {
                    try {
                        var node = affectedNodes[i];
                        if (node) {
                            tree.getStore().load({
                                node: node.parentNode
                            });
                        }
                    } catch (e) {
                        console.log(e);
                    }
                }
            }.bind(this, id),
            jobs: r.deletejobs
        });
    }
};

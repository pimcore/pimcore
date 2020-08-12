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

pimcore.registerNS("pimcore.element.tag.assignment");
pimcore.element.tag.assignment = Class.create({

    initialize: function(element, elementType) {
        this.element = element;
        this.elementType = elementType;
    },

    getLayout: function () {

        if (this.layout == null) {

            var gridStore = Ext.create("Ext.data.Store", {
                proxy: {
                    type: 'ajax',
                    url: Routing.generate('pimcore_admin_tags_loadtagsforelement'),
                    extraParams: {
                        assignmentCId: this.element.id,
                        assignmentCType: this.elementType
                    },
                    reader: {
                        type: 'json'
                    }
                },
                fields: [
                    {name: 'id'},
                    {name: 'path'}
                ]
            });

            var tree = new pimcore.element.tag.tree();
            tree.setAllowAdd(true);
            tree.setAllowDelete(false);
            tree.setAllowRename(true);
            tree.setAllowDnD(false);
            tree.setShowSelection(true);
            tree.setAssignmentElement(this.element.id, this.elementType);
            tree.setCheckChangeCallback(function(gridStore, node, checked) {
                var record = {id: node.id, path: node.data.path};
                if(checked) {
                    gridStore.add(record);

                    Ext.Ajax.request({
                        url: Routing.generate('pimcore_admin_tags_addtagtoelement'),
                        method: 'PUT',
                        params: {
                            assignmentElementId: this.element.id,
                            assignmentElementType: this.elementType,
                            tagId: node.id
                        }
                    });

                } else {
                    gridStore.removeAt(gridStore.findExact('id', node.id));
                    this.removeTagFromElement(node.id);
                }
                gridStore.sort('path', 'ASC');

            }.bind(this, gridStore));

            this.grid = Ext.create('Ext.grid.Panel', {
                title: t('assigned_tags'),
                region: 'west',
                width: 460,
                trackMouseOver: true,
                store: gridStore,
                columnLines: true,
                stripeRows: true,
                columns: {
                    items: [
                        {
                            text: t("name"),
                            dataIndex: 'path',
                            sortable: true,
                            width: 400
                        },
                        {
                            xtype: 'actioncolumn',
                            menuText: t('delete'),
                            width: 40,
                            items: [{
                                tooltip: t('delete'),
                                icon: "/bundles/pimcoreadmin/img/flat-color-icons/delete.svg",
                                handler: function (tree, grid, rowIndex) {
                                    var record = grid.getStore().getAt(rowIndex);

                                    grid.getStore().removeAt(rowIndex);
                                    var node = tree.getStore().findNode('id', record.id);
                                    if(node) {
                                        node.set('checked', false);
                                    }
                                    this.removeTagFromElement(record.id);
                                }.bind(this, tree.getLayout())
                            }]
                        }
                    ]
                },
                buttons: [{
                    text: t("apply_tags"),
                    iconCls: "pimcore_icon_apply",
                    handler: this.prepareBatchUpdate.bind(this, false)
                },{
                    text: t("remove_and_apply_tags"),
                    iconCls: "pimcore_icon_apply",
                    handler: this.prepareBatchUpdate.bind(this, true)
                }]

            });

            var treePanel = Ext.create("Ext.Panel", {
                items: [tree.getLayout()],
                layout: "border",
                region: 'center'
            });

            this.layout = Ext.create("Ext.Panel", {
                tabConfig: {
                    tooltip: t('tags')
                },
                region: "center",
                iconCls: "pimcore_material_icon_tags pimcore_material_icon",
                layout: 'border',
                items: [this.grid, treePanel],
                listeners: {
                    activate: function () {
                        gridStore.load();
                    }
                }
            });
        }

        return this.layout;
    },

    removeTagFromElement: function(tagId) {
        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_tags_removetagfromelement'),
            method: 'DELETE',
            params: {
                assignmentElementId: this.element.id,
                assignmentElementType: this.elementType,
                tagId: tagId
            }
        });
    },


    prepareBatchUpdate: function(removeAndApply) {
        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_tags_getbatchassignmentjobs'),
            params: {
                elementId: this.element.id,
                elementType: this.elementType
            },
            success: function(response) {
                var responseJson = Ext.decode(response.responseText);

                if(responseJson.totalCount == 0) {
                    Ext.MessageBox.alert(t("error"), t("no_children_found"));
                } else {
                    // get selected elements
                    var jobs = [];

                    var assignedTags = [];
                    this.grid.getStore().each(function(record) {
                        assignedTags.push(record.id);
                    });

                    var params = {
                        elementId: this.element.id,
                        elementType: this.elementType,
                        removeAndApply: removeAndApply,
                        assignedTags: Ext.encode(assignedTags)
                    };

                    for (var i=0; i<responseJson.idLists.length; i++) {
                        jobs.push({
                            url: Routing.generate('pimcore_admin_tags_dobatchassignment'),
                            method: 'PUT',
                            params: array_merge(params, {
                                childrenIds: Ext.encode(responseJson.idLists[i])
                            })
                        });
                    }

                    if(jobs.length) {
                        this.progressBar = new Ext.ProgressBar({
                            text: t('initializing')
                        });

                        this.progressBarWin = new Ext.Window({
                            title: t("batch_assignment"),
                            layout:'fit',
                            width:200,
                            bodyStyle: "padding: 10px;",
                            closable:false,
                            plain: true,
                            items: [this.progressBar],
                            listeners: pimcore.helpers.getProgressWindowListeners()
                        });

                        this.progressBarWin.show();

                        var pj = new pimcore.tool.paralleljobs({
                            success: function () {

                                if(this.progressBarWin) {
                                    this.progressBarWin.close();
                                }

                                this.progressBar = null;
                                this.progressBarWin = null;

                                if(typeof callback == "function") {
                                    callback();
                                }
                            }.bind(this),
                            update: function (currentStep, steps, percent) {
                                if(this.progressBar) {
                                    var status = currentStep / steps;
                                    this.progressBar.updateProgress(status, percent + "%");
                                }
                            }.bind(this),
                            failure: function (message) {
                                this.progressBarWin.close();
                                pimcore.helpers.showNotification(t("error"), "", "error", t(message));
                            }.bind(this),
                            jobs: [jobs]
                        });
                    } else {
                        Ext.MessageBox.alert(t("error"), t("batch_assignment_error"));
                    }
                }
            }.bind(this)
        });
    }


});

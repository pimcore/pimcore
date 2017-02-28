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
                    url: '/admin/tags/load-tags-for-element',
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
                        url: "/admin/tags/add-tag-to-element",
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
                trackMouseOver: true,
                store: gridStore,
                region: 'center',
                columnLines: true,
                stripeRows: true,
                columns: {
                    items: [
                        {
                            header: t("name"),
                            dataIndex: 'path',
                            sortable: true,
                            width: 400
                        },
                        {
                            xtype: 'actioncolumn',
                            width: 40,
                            items: [{
                                tooltip: t('delete'),
                                icon: "/pimcore/static6/img/flat-color-icons/delete.svg",
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
                }

            });

            var treePanel = Ext.create("Ext.Panel", {
                title: t('element_tag_tree'),
                items: [tree.getLayout()],
                layout: "border",
                region: 'center'
            });

            var gridPanel = Ext.create("Ext.Panel", {
                title: t('assigned_tags'),
                items: [this.grid],
                layout: "border",
                region: 'west',
                width: 460,
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

            this.layout = Ext.create("Ext.Panel", {
                tabConfig: {
                    tooltip: t('tags')
                },
                region: "center",
                iconCls: "pimcore_icon_element_tags",
                layout: 'border',
                items: [gridPanel, treePanel],
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
            url: "/admin/tags/remove-tag-from-element",
            params: {
                assignmentElementId: this.element.id,
                assignmentElementType: this.elementType,
                tagId: tagId
            }
        });
    },


    prepareBatchUpdate: function(removeAndApply) {
        Ext.Ajax.request({
            url: "/admin/tags/get-batch-assignment-jobs",
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
                            url: "/admin/tags/do-batch-assignment",
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
                            width:500,
                            bodyStyle: "padding: 10px;",
                            closable:false,
                            plain: true,
                            modal: true,
                            items: [this.progressBar]
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
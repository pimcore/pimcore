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

pimcore.registerNS("pimcore.element.tag.tree");
pimcore.element.tag.tree = Class.create({

    allowDnD: true,
    allowAdd: true,
    allowRename: true,
    allowDelete: true,

    showSelection: false,
    assignmentCId: null,
    assignmentCType: null,
    checkChangeCallback: function() {

    },

    initialize: function() {

    },

    setAllowDnD: function(allowDnD) {
        this.allowDnD = allowDnD;
    },
    setAllowAdd: function(allowAdd) {
        this.allowAdd = allowAdd;
    },
    setAllowRename: function(allowRename) {
        this.allowRename = allowRename;
    },
    setAllowDelete: function(allowDelete) {
        this.allowDelete = allowDelete;
    },

    setShowSelection: function(showSelection) {
        this.showSelection = showSelection;
    },

    setAssignmentElement: function(id, type) {
        this.assignmentCId = id;
        this.assignmentCType = type;
    },

    setCheckChangeCallback: function(callback) {
        this.checkChangeCallback = callback;
    },

    getLayout: function () {
        if (!this.tree) {

            var store = Ext.create('Ext.data.TreeStore', {
                proxy: {
                    type: 'ajax',
                    url: '/admin/tags/tree-get-children-by-id',
                        extraParams: {
                            showSelection: this.showSelection,
                            assignmentCId: this.assignmentCId,
                            assignmentCType: this.assignmentCType
                        }
                },
                listeners: {
                    load: function(store, records, successful, operation, node) {
                        //necessary in order to show new tree items after they are added
                        if(node) {
                            node.expand();
                        }
                    }
                }
            });

            var user = pimcore.globalmanager.get("user");
            var treePlugins = null;
            if(this.allowDnD && user.isAllowed("tags_config")) {
                treePlugins = {
                    ptype: 'treeviewdragdrop',
                    ddGroup: "tags",
                    appendOnly: true
                };
            }

            this.tree = Ext.create('Ext.tree.Panel', {
                store: store,
                forceLayout: true,
                region: "center",
                autoScroll: true,
                animate: false,
                viewConfig: {
                    plugins: treePlugins,
                    listeners: {
                        drop: function(node, data, overModel, dropPosition, eOpts) {
                            overModel.set('expandable', true);

                            Ext.Ajax.request({
                                url: "/admin/tags/update",
                                params: {
                                    id: data.records[0].id,
                                    parentId: overModel.id
                                }
                            });
                        },
                        ontreenodeover: function (targetNode, position, dragData, e, eOpts ) {
                            var node = dragData.records[0];
                            return node.getOwnerTree() == targetNode.getOwnerTree();
                        }
                    }
                },
                bufferedRenderer: true,
                containerScroll: true,
                root: {
                    id: '0',
                    text: t('element_tag_all_tags'),
                    iconCls: 'pimcore_icon_folder',
                    expanded: true
                },
                rootVisible: true,
                listeners: {
                    itemcontextmenu: this.onTreeNodeContextmenu.bind(this),
                    checkchange: this.checkChangeCallback,
                    beforeitemappend: function (thisNode, newChildNode, index, eOpts) {
                        newChildNode.data.qtip = t('id') +  ": " + newChildNode.data.id;
                    }
                }

            });
        }

        return this.tree;
    },

    onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts ) {
        e.stopEvent();

        var user = pimcore.globalmanager.get("user");

        var menu = new Ext.menu.Menu();
        var hasEntries = false;

        if(this.allowAdd && user.isAllowed("tags_config")) {
            hasEntries = true;
            menu.add(new Ext.menu.Item({
                text: t('add_tag'),
                iconCls: "pimcore_icon_add",
                handler: function(tree, record) {
                    Ext.MessageBox.prompt(t('add_tag'), t('enter_the_name_of_the_new_tag'), this.addTagComplete.bind(this, tree, record), null, null, "");
                }.bind(this, tree, record)
            }));
        }

        if(this.allowDelete && user.isAllowed("tags_config")) {
            hasEntries = true;
            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: function(tree, record) {
                    Ext.Msg.confirm(t('delete'), t('delete_message'), function(btn){
                        if (btn == 'yes'){
                            Ext.Ajax.request({
                                url: "/admin/tags/delete",
                                params: {
                                    id: record.data.id
                                },
                                success: function() {
                                    record.remove();
                                }.bind(this, tree, record)
                            });
                        }
                    });
                }.bind(this, tree, record)
            }));
        }

        if(this.allowRename && user.isAllowed("tags_config")) {
            hasEntries = true;
            menu.add(new Ext.menu.Item({
                text: t('rename'),
                iconCls: "pimcore_icon_key pimcore_icon_overlay_go",
                handler: function(tree, record) {
                    Ext.MessageBox.prompt(t('rename_tag'), t('enter_new_name_of_the_tag'), function(tree, record, button, value) {
                        if (button == "ok" && value.length > 2) {
                            Ext.Ajax.request({
                                url: "/admin/tags/update",
                                params: {
                                    id: record.id,
                                    text: value
                                },
                                success: function(record, value) {
                                    record.set('text', value);
                                    tree.getStore().reload();
                                }.bind(this, record, value)
                            });
                        } else if (button == "cancel") {
                            return;
                        }
                        else {
                            Ext.Msg.alert(t('rename_tag'), t('invalid_tag_name'));
                        }

                    }.bind(this, tree, record), null, null, record.get('text'));
                }.bind(this, tree, record)
            }));
        }

        if(hasEntries) {
            menu.showAt(e.pageX, e.pageY);
        }

    },

    addTagComplete: function (tree, record, button, value, object) {
        if (button == "ok" && value.length > 2) {
            Ext.Ajax.request({
                url: "/admin/tags/add",
                params: {
                    parentId: record.data.id,
                    text: value
                },
                success: function(tree, record) {
                    record.set('leaf', false);
                    record.set('expandable', true);
                    tree.getStore().reload({
                        node: record
                    });
                }.bind(this, tree, record)
            });
        }
        else if (button == "cancel") {
            return;
        }
        else {
            Ext.Msg.alert(t('add_tag'), t('invalid_tag_name'));
        }
    },

    getCheckedTagIds: function() {
        var store = this.tree.getStore();
        var checkedTagIds = [];
        store.each(function(node) {
            if(node.data.checked) {
                checkedTagIds.push(node.id);
            }
        });

        return checkedTagIds;
    }

});
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
    checkChangeCallback: function () {

    },

    initialize: function () {

    },

    setAllowDnD: function (allowDnD) {
        this.allowDnD = allowDnD;
    },
    setAllowAdd: function (allowAdd) {
        this.allowAdd = allowAdd;
    },
    setAllowRename: function (allowRename) {
        this.allowRename = allowRename;
    },
    setAllowDelete: function (allowDelete) {
        this.allowDelete = allowDelete;
    },

    setShowSelection: function (showSelection) {
        this.showSelection = showSelection;
    },

    setAssignmentElement: function (id, type) {
        this.assignmentCId = id;
        this.assignmentCType = type;
    },

    setCheckChangeCallback: function (callback) {
        this.checkChangeCallback = callback;
    },

    setFilterFieldWidth: function (size) {
        if (this.filterField) {
            this.filterField.width = size;
        }
    },

    getLayout: function () {
        if (!this.tree) {

            var store = Ext.create('Ext.data.TreeStore', {
                autoLoad: false,
                proxy: {
                    type: 'ajax',
                    url: Routing.generate('pimcore_admin_tags_treegetchildrenbyid'),
                    extraParams: {
                        showSelection: this.showSelection,
                        assignmentCId: this.assignmentCId,
                        assignmentCType: this.assignmentCType
                    }
                },
                listeners: {
                    load: function (store, records, successful, operation, node) {
                        //necessary in order to show new tree items after they are added
                        if (node) {
                            node.expand();
                        }
                    }
                }
            });

            var user = pimcore.globalmanager.get("user");
            var treePlugins = null;
            if (this.allowDnD && user.isAllowed("tags_configuration")) {
                treePlugins = {
                    ptype: 'treeviewdragdrop',
                    ddGroup: "tags",
                    appendOnly: false
                };
            }

            this.filterField = new Ext.form.field.Text(
                {
                    hideLabel: true,
                    enableKeyEvents: true,
                    listeners: {
                        "keyup": function (field, key) {
                             if (key.getKey() == key.ENTER) {
                                this.tagFilter();
                             }
                        }.bind(this)
                    }
                }
            );

            this.filterButton = new Ext.Button({
                iconCls: "pimcore_icon_search",
                text: t("filter"),
                handler: this.tagFilter.bind(this)
            });

            this.tree = Ext.create('Ext.tree.Panel', {
                store: store,
                forceLayout: true,
                region: "center",
                autoScroll: true,
                animate: false,
                tbar: [this.filterField, this.filterButton],
                viewConfig: {
                    plugins: treePlugins,
                    listeners: {
                        drop: function (node, data, overModel, dropPosition, eOpts) {
                            overModel.set('expandable', true);

                            Ext.Ajax.request({
                                url: Routing.generate('pimcore_admin_tags_update'),
                                method: 'PUT',
                                params: {
                                    id: data.records[0].id,
                                    parentId: overModel.id
                                }
                            });
                            this.tagFilter();
                        }.bind(this),
                        ontreenodeover: function (targetNode, position, dragData, e, eOpts) {
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
                    expanded: true,
                    loaded:true
                },
                rootVisible: true,
                listeners: {
                    itemcontextmenu: this.onTreeNodeContextmenu.bind(this),
                    checkchange: this.checkChangeCallback,
                    beforeitemappend: function (thisNode, newChildNode, index, eOpts) {
                        newChildNode.data.qtip = t('id') + ": " + newChildNode.data.id;
                    }
                }

            });

            this.tree.on("render", function () {
                this.getStore().load();
            });
        }

        return this.tree;
    },

    tagFilter: function() {
        this.tree.getStore().clearFilter();
        var currentFilterValue = this.filterField.getValue().toLowerCase();

        this.tree.getStore().load({
            params: {
                filter : currentFilterValue
            },
            callback: this.updateTagFilter.bind(this)
        });
    },

    updateTagFilter: function () {
        this.tree.getStore().clearFilter();
        var currentFilterValue = this.filterField.getValue().toLowerCase();

        if(currentFilterValue) {
            this.tree.getStore().filterBy(function (item) {
                if (item.data.text.toLowerCase().indexOf(currentFilterValue) !== -1) {
                    return true;
                }

                if (!item.data.leaf) {
                    if (item.data.root) {
                        return true;
                    }

                    var childNodes = item.childNodes;
                    var hide = true;
                    if (childNodes) {
                        var i;
                        for (i = 0; i < childNodes.length; i++) {
                            var childNode = childNodes[i];
                            if (childNode.get("visible")) {
                                hide = false;
                                break;
                            }
                        }
                    }

                    return !hide;
                }
            }.bind(this));

            var rootNode = this.tree.getRootNode();
            rootNode.set('text', currentFilterValue ? t('element_tag_filtered_tags') : t('element_tag_all_tags'));
            var storeCount = this.tree.getStore().data.items.length;
            if (currentFilterValue && storeCount > 1) {
                rootNode.expand(true);
            }
        }
    },

    onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts) {
        e.stopEvent();

        var user = pimcore.globalmanager.get("user");

        var menu = new Ext.menu.Menu();
        var hasEntries = false;

        if (this.allowAdd && user.isAllowed("tags_configuration")) {
            hasEntries = true;
            menu.add(new Ext.menu.Item({
                text: t('add'),
                iconCls: "pimcore_icon_add",
                handler: function (tree, record) {
                    Ext.MessageBox.prompt(' ', t('enter_the_name_of_the_new_item'), this.addTagComplete.bind(this, tree, record), null, null, "");
                }.bind(this, tree, record)
            }));
        }

        if (this.allowDelete && user.isAllowed("tags_configuration") && index !== 0) {
            hasEntries = true;
            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: function (tree, record) {
                    Ext.Msg.confirm(t('delete'), t('delete_message'), function (btn) {
                        if (btn == 'yes') {
                            Ext.Ajax.request({
                                url: Routing.generate('pimcore_admin_tags_delete'),
                                method: 'DELETE',
                                params: {
                                    id: record.data.id
                                },
                                success: function () {
                                    record.remove();
                                }.bind(this, tree, record)
                            });
                        }
                    });
                }.bind(this, tree, record)
            }));
        }

        if (this.allowRename && user.isAllowed("tags_configuration") && index !== 0) {
            hasEntries = true;
            menu.add(new Ext.menu.Item({
                text: t('rename'),
                iconCls: "pimcore_icon_key pimcore_icon_overlay_go",
                handler: function (tree, record) {
                    Ext.MessageBox.prompt(t('rename_tag'), t('enter_new_name_of_the_tag'), function (tree, record, button, value) {
                        value = strip_tags(trim(value));
                        if (button == "ok" && value.length > 0) {
                            if (this.isKeyInSameLevel(record.parentNode, value, record)) {
                                return;
                            }

                            Ext.Ajax.request({
                                url: Routing.generate('pimcore_admin_tags_update'),
                                method: 'PUT',
                                params: {
                                    id: record.id,
                                    text: value
                                },
                                success: function (record, value) {
                                    record.set('text', value);
                                    var currentFilterValue = this.filterField.getValue().toLowerCase();
                                    if (currentFilterValue) {
                                        this.tagFilter();
                                        return;
                                    }
                                }.bind(this, record, value)
                            });
                        } else if (button == "cancel") {
                            return;
                        }
                        else {
                            Ext.Msg.alert(t('rename'), t('invalid_name'));
                        }

                    }.bind(this, tree, record), null, null, record.get('text'));
                }.bind(this, tree, record)
            }));
        }

        if (hasEntries) {
            menu.showAt(e.pageX, e.pageY);
        }

    },

    addTagComplete: function (tree, record, button, value, object) {
        value = strip_tags(trim(value));
        if (button == "ok" && value.length > 0) {
            if (this.isKeyInSameLevel(record, value, record)) {
               return;
            }

            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_tags_add'),
                method: 'POST',
                params: {
                    parentId: record.data.id,
                    text: value
                },
                success: function (tree, record, response) {
                    res = Ext.decode(response.responseText);
                    if (!res.success) {
                        pimcore.helpers.showNotification(t("error"), t("error"), "error", t(res.message));
                    }

                    var currentFilterValue = this.filterField.getValue().toLowerCase();
                    if (currentFilterValue) {
                        this.tagFilter();
                        return;
                    }
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
            Ext.Msg.alert(' ', t('invalid_name'));
        }
    },

    getCheckedTagIds: function () {
        var store = this.tree.getStore();
        var checkedTagIds = [];
        store.each(function (node) {
            if (node.data.checked) {
                checkedTagIds.push(node.id);
            }
        });

        return checkedTagIds;
    },

    isKeyInSameLevel: function (parentNode, key, record) {
        var parentChilds = parentNode.childNodes;
        for (var i = 0; i < parentChilds.length; i++) {
            if (parentChilds[i].data.text == key && parentChilds[i] !== record) {
                Ext.MessageBox.alert(t('error'),
                    t('name_already_in_use'));
                return true;
            }
        }
    }

});

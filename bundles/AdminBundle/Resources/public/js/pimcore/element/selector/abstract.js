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

pimcore.registerNS("pimcore.element.selector.abstract");
pimcore.element.selector.abstract = Class.create({


    initialize: function (parent) {
        this.parent = parent;

        this.initStore();

        if(this.parent.multiselect) {
            this.searchPanel = new Ext.Panel({
                layout: "border",
                items: [this.getForm(), this.getSelectionPanel(), this.getResultPanel()]
            });
        } else {
            this.searchPanel = new Ext.Panel({
                layout: "border",
                items: [this.getForm(), this.getResultPanel()]
            });
        }

        var user = pimcore.globalmanager.get("user");
        if(user.isAllowed("tags_search")) {
            this.searchPanel.add(this.getTagsPanel());
        }


        this.parent.setSearch(this.searchPanel);
    },

    addToSelection: function (data) {

        // check for dublicates
        var existingItem = this.selectionStore.find("id", data.id);

        if(existingItem < 0) {
            this.selectionStore.add(data);
        }
    },

    getTagsPanel: function() {

        if(!this.tagsPanel) {

            var considerAllChildTags = Ext.create("Ext.form.Checkbox", {
                style: "margin-bottom: 0; margin-left: 5px",
                fieldStyle: "margin-top: 0",
                cls: "tag-tree-topbar",
                boxLabel: t("consider_child_tags"),
                listeners: {
                    change: function (field, checked) {
                        var proxy = this.store.getProxy();
                        proxy.setExtraParam("considerChildTags", checked);
                        this.search();
                    }.bind(this)
                }
            });


            var tree = new pimcore.element.tag.tree();
            tree.setAllowAdd(false);
            tree.setAllowDelete(false);
            tree.setAllowDnD(false);
            tree.setAllowRename(false);
            tree.setShowSelection(true);
            tree.setCheckChangeCallback(function(tree) {
                var tagIds = tree.getCheckedTagIds();
                var proxy = this.store.getProxy();
                proxy.setExtraParam("tagIds[]", tagIds);
                this.search();
            }.bind(this, tree));

            this.tagsPanel = Ext.create("Ext.Panel", {
                region: "west",
                width: 300,
                collapsedCls: "tag-tree-toolbar-collapsed",
                collapsible: true,
                collapsed: true,
                autoScroll: true,
                items: [tree.getLayout()],
                title: t('filter_tags'),
                tbar: [considerAllChildTags],
                iconCls: "pimcore_icon_element_tags"
            });
        }

        return this.tagsPanel;
    },

    getData: function () {
        if(this.parent.multiselect) {
            this.tmpData = [];

            if(this.selectionStore.getCount() > 0) {
                this.selectionStore.each(function (rec) {
                    this.tmpData.push(rec.data);
                }.bind(this));

                return this.tmpData;
            } else {
                // is the store is empty and a item is selected take this
                var selected = this.getGrid().getSelectionModel().getSelected();
                if(selected) {
                    this.tmpData.push(selected.data);
                }
            }

            return this.tmpData;
        } else {
            var selected = this.getGrid().getSelectionModel().getSelected();
            if(selected) {
                return selected.getAt(0).data;
            }
            return null;
        }
    },

    getPagingToolbar: function() {
        var pagingToolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store);
        return pagingToolbar;
    },

    onRowContextmenu: function (grid, record, tr, rowIndex, e, eOpts ) {

        var menu = new Ext.menu.Menu();
        var data = grid.getStore().getAt(rowIndex);

        menu.add(new Ext.menu.Item({
            text: t('add'),
            iconCls: "pimcore_icon_add",
            handler: function (data) {
                var selModel = grid.getSelectionModel();
                var selectedRows = selModel.getSelection();
                for (var i = 0; i < selectedRows.length; i++) {
                    this.addToSelection(selectedRows[i].data);
                }

            }.bind(this, data)
        }));

        e.stopEvent();
        menu.showAt(e.getXY());
    },

    getGridSelModel: function() {
        return Ext.create('Ext.selection.RowModel', {mode: (this.parent.multiselect ? "MULTI" : "SINGLE")});
    },

    updateTabTitle: function(term) {
        if(this.parent.tabPanel) {
            this.parent.tabPanel.setTitle(t('search') + ': <i>' + term + '</i>');
        }
    }
});

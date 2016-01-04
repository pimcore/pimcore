/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
                    url: '/admin/tags/load-tags-for-element/',
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

            gridStore.load();

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
                                icon: "/pimcore/static6/img/icon/cross.png",
                                handler: function (tree, grid, rowIndex) {
                                    var record = grid.getStore().getAt(rowIndex);

                                    grid.getStore().removeAt(rowIndex);
                                    var node = tree.getStore().findRecord('id', record.id);
                                    node.set('checked', false);
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
                width: 460
            });

            this.layout = Ext.create("Ext.Panel", {
                title: t("tags"),
                region: "center",
                iconCls: "pimcore_icon_element_tags",
                layout: 'border',
                items: [gridPanel, treePanel]

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
    }

});
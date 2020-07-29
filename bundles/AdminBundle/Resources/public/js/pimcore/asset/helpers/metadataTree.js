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

pimcore.registerNS("pimcore.asset.helpers.metadataTree");
pimcore.asset.helpers.metadataTree = Class.create({

    showFieldName: false,

    initialize: function (showFieldName) {
        if (showFieldName) {
            this.showFieldName = showFieldName;
        }
    },

    updateFilter: function (tree, filterField) {

        tree.getStore().clearFilter();
        var currentFilterValue = filterField.getValue().toLowerCase();

        tree.getStore().filterBy(function (item) {
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

        var rootNode = tree.getRootNode()
        rootNode.set('text', currentFilterValue ? t('element_tag_filtered_tags') : t('element_tag_all_tags'));
        rootNode.expand(true);
    },

    getMetaTree: function (url, classId, objectId) {

        var filterField = new Ext.form.field.Text(
            {
                width: 230,
                hideLabel: true,
                enableKeyEvents: true
            }
        );

        var filterButton = new Ext.button.Button({
            iconCls: "pimcore_icon_search"
        });

        var headerConfig = {
            title: t('class_attributes'),
            items: [
                filterField,
                filterButton
            ]
        };

        var tree = new Ext.tree.TreePanel({
            title: t('asset_metadata'),
            iconCls: 'pimcore_icon_gridconfig_class_attributes',
            tbar: headerConfig,
            region: "center",
            autoScroll: true,
            rootVisible: false,
            bufferedRenderer: false,
            animate: false,
            width: 300,
            root: {
                id: "0",
                root: true,
                text: t("base"),
                allowDrag: false,
                leaf: true,
                isTarget: true
            },
            viewConfig: {
                plugins: {
                    ptype: 'treeviewdragdrop',
                    enableDrag: true,
                    enableDrop: false,
                    ddGroup: "columnconfigelement"
                }
            }
        });

        Ext.Ajax.request({
            url: url,
            params: {
                id: classId,
                oid: objectId
            },
            success: this.initLayoutFields.bind(this, tree)
        });

        filterField.on("keyup", this.updateFilter.bind(this, tree, filterField));
        filterButton.on("click", this.updateFilter.bind(this, tree, filterField));

        return tree;
    },

    initLayoutFields: function (tree, response) {
        var data = Ext.decode(response.responseText);

        var keys = Object.keys(data);
        for (var i = 0; i < keys.length; i++) {
            if (data[keys[i]]) {
                if (data[keys[i]].childs) {

                    var text = t(data[keys[i]].nodeLabel);

                    var baseNode = {
                        type: "layout",
                        allowDrag: false,
                        iconCls: "pimcore_icon_" + data[keys[i]].nodeType,
                        text: text
                    };

                    baseNode = tree.getRootNode().appendChild(baseNode);
                    for (var j = 0; j < data[keys[i]].childs.length; j++) {
                        baseNode.appendChild(this.recursiveAddNode(data[keys[i]].childs[j], baseNode));
                    }
                    if (data[keys[i]].nodeType != "system") {
                        baseNode.expand();
                    }
                }
            }
        }
    },

    recursiveAddNode: function (con, scope) {

        var fn = null;
        var newNode = null;

        fn = this.addDataChild.bind(scope, con.fieldtype, con, this.showFieldName);

        newNode = fn();

        if (con.childs) {
            for (var i = 0; i < con.childs.length; i++) {
                this.recursiveAddNode(con.childs[i], newNode);
            }
        }

        return newNode;
    },

    addLayoutChild: function (type, initData) {

        var nodeLabel = type;

        if (initData) {
            if (initData.title) {
                nodeLabel = initData.title;
            } else if (initData.name) {
                nodeLabel = initData.name;
            }
        }

        var newNode = {
            type: "layout",
            expanded: true,
            expandable: initData.childs.length,
            allowDrag: false,
            iconCls: "pimcore_icon_" + type,
            text: t(nodeLabel),
            copyText: t(nodeLabel)
        };

        newNode = this.appendChild(newNode);

        this.expand();

        return newNode;
    },

    addDataChild: function (type, initData, showFieldname) {

        if (!initData.invisible) {
            var isLeaf = true;
            var draggable = true;

            var key = initData.name;

            var text = initData.title;


            var subType = initData.subtype;

            if(subType) {
                text += " (" + subType + ")";
            }

            var copyText = initData.copyTitle ? initData.copyTitle : text;

            var newNode = {
                text: text,
                key: key,
                type: "data",
                layout: initData,
                leaf: isLeaf,
                allowDrag: draggable,
                dataType: type,
                iconCls: "pimcore_icon_" + type,
                expanded: true,
                copyText: copyText
            };

            newNode = this.appendChild(newNode);

            if (this.rendered) {
                this.expand();
            }

            return newNode;
        }

    }

});

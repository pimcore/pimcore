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

pimcore.registerNS("pimcore.object.helpers.classTree");
pimcore.object.helpers.classTree = Class.create({

    showFieldName: false,

    initialize: function (showFieldName) {
        if(showFieldName) {
            this.showFieldName = showFieldName;
        }
    },


    getClassTree: function(url, classId, objectId) {

        var tree = new Ext.tree.TreePanel({
            title: t('class_definitions'),
            region: "center",
            //ddGroup: "columnconfigelement",
            autoScroll: true,
            rootVisible: false,
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

        return tree;
    },

    initLayoutFields: function (tree, response) {
        var data = Ext.decode(response.responseText);

        var keys = Object.keys(data);
        for(var i = 0; i < keys.length; i++) {
            if (data[keys[i]]) {
                if (data[keys[i]].childs) {
                    var attributePrefix = "";
                    var text = t(data[keys[i]].nodeLabel);
                    if(data[keys[i]].nodeType == "objectbricks") {
                        text = ts(data[keys[i]].nodeLabel) + " " + t("columns");
                        attributePrefix = data[keys[i]].nodeLabel;
                    }
                    var baseNode = {
                        type: "layout",
                        allowDrag: false,
                        iconCls: "pimcore_icon_" + data[keys[i]].nodeType,
                        text: text
                    };

                    baseNode = tree.getRootNode().appendChild(baseNode);
                    for (var j = 0; j < data[keys[i]].childs.length; j++) {
                        baseNode.appendChild(this.recursiveAddNode(data[keys[i]].childs[j], baseNode, attributePrefix));
                    }
                    if(data[keys[i]].nodeType == "object") {
                        baseNode.expand();
                    } else {
                        // baseNode.collapse();
                    }
                }
            }
        }
    },

    recursiveAddNode: function (con, scope, attributePrefix) {

        var fn = null;
        var newNode = null;

        if (con.datatype == "layout") {
            fn = this.addLayoutChild.bind(scope, con.fieldtype, con);
        }
        else if (con.datatype == "data") {
            fn = this.addDataChild.bind(scope, con.fieldtype, con, attributePrefix, this.showFieldName);
        }

        newNode = fn();

        if (con.childs) {
            for (var i = 0; i < con.childs.length; i++) {
                this.recursiveAddNode(con.childs[i], newNode, attributePrefix);
            }
        }

        return newNode;
    },

    addLayoutChild: function (type, initData) {

        var nodeLabel = t(type);

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
            text: nodeLabel
        };

        newNode = this.appendChild(newNode);

        this.expand();

        return newNode;
    },

    addDataChild: function (type, initData, attributePrefix, showFieldname) {

        if(type != "objectbricks" && !initData.invisible) {
            var isLeaf = true;
            var draggable = true;

            // localizedfields can be a drop target
            if(type == "localizedfields") {
                isLeaf = false;
                draggable = false;
            }

            var key = initData.name;
            if(attributePrefix) {
                key = attributePrefix + "~" + key;
            }

            var text = ts(initData.title);
            if(showFieldname) {
                text = text + " (" + key.replace("~", ".") + ")";
            }
            var newNode = {
                text: text,
                key: key,
                type: "data",
                layout: initData,
                leaf: isLeaf,
                allowDrag: draggable,
                dataType: type,
                iconCls: "pimcore_icon_" + type,
                expanded: true
            };

            newNode = this.appendChild(newNode);

            if(this.rendered) {
                this.expand();
            }

            return newNode;
        } else {
            return null;
        }

    }

});

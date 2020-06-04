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

pimcore.registerNS("pimcore.object.fieldlookup.helper");
pimcore.object.fieldlookup.helper = Class.create(pimcore.object.helpers.classTree, {

    showFieldName: false,
    filter: null,

    initLayoutFields: function (tree, response) {
        var data = Ext.decode(response.responseText);

        var keys = Object.keys(data);
        for(let i = 0; i < keys.length; i++) {
            if (data[keys[i]]) {
                let dataEntry = data[keys[i]];
                if (dataEntry.childs) {
                    var attributePrefix = "";
                    var text = t(dataEntry.nodeLabel);
                    var nodeType = dataEntry.nodeType;
                    if(nodeType == "objectbricks") {
                        text = t(dataEntry.nodeLabel) + " " + t("columns");
                        attributePrefix = dataEntry.nodeLabel;
                    }

                    var dataContext = {};
                    if (in_array(nodeType, ["objectbricks", "fieldcollections"])) {
                        dataContext.containerType = nodeType;
                        dataContext.containerKey = keys[i];
                        if (nodeType == "objectbricks") {
                            dataContext.brickField = dataEntry.brickField;
                        }
                    }

                    var baseNode = {
                        type: "layout",
                        iconCls: "pimcore_icon_" + dataEntry.nodeType,
                        text: text,
                        originalText: text
                    };

                    baseNode = tree.getRootNode().appendChild(baseNode);
                    for (let j = 0; j < dataEntry.childs.length; j++) {
                        let childData = this.recursiveAddNode(dataEntry.childs[j], baseNode, attributePrefix, this, dataContext);
                        if (childData) {
                            baseNode.appendChild(childData);
                        }
                    }
                    if(dataEntry.nodeType == "object") {
                        baseNode.expand();
                    } else {
                        baseNode.collapse();
                    }
                }
            }
        }
    },


    addDataChild: function (type, initData, attributePrefix, showFieldname, ctx, dataContext) {

        if(type != "objectbricks" && !initData.invisible) {
            var isLeaf = true;

            if(type == "localizedfields") {
                isLeaf = false;
            }

            var key = initData.name;
            if(attributePrefix) {
                key = attributePrefix + "~" + key;
            }

            var text = t(initData.title) + " (" + key.replace("~", ".") + ")";

            var newNode = {
                originalText: text,
                text: text,
                key: key,
                type: "data",
                layout: initData,
                leaf: isLeaf,
                dataType: type,
                iconCls: "pimcore_icon_" + type,
                qtip: ctx.object.data.data[key],
                dataContext: dataContext,
                fieldtype: initData.fieldtype
            };

            newNode = this.appendChild(newNode);

            if (newNode.parentNode.data && newNode.parentNode.data.layout
                && newNode.parentNode.data.layout.fieldtype == "localizedfields"
                && newNode.parentNode.data.layout.fieldtype == "fieldcollections"
                && newNode.parentNode.data.layout.fieldtype == "objectbricks"

                ) {
                newNode.disabled = true;
            }

            this.expand();
            return newNode;
        } else {
            return null;
        }

    },

    updateFilter: function (tree, filterField) {

        tree.getStore().clearFilter();
        var currentFilterValue = filterField.getValue().toLowerCase();

        tree.getStore().filterBy(function (item) {
            if (item.data.type === "data") {
                var text = t(item.data.originalText);
                let fieldtype = item.data.fieldtype;

                if (fieldtype == "localizedfields"
                    || fieldtype == "fieldcollections"
                    || fieldtype == "objectbricks") {
                    return true;
                }

                if (currentFilterValue) {
                    var textLower = text.toLowerCase();
                    var idx = textLower.indexOf(currentFilterValue);
                    if (idx == -1) {
                        return null;
                    }
                    var pre = text.substring(0, idx);
                    var post = text.substring(idx + currentFilterValue.length);
                    var match = text.substring(idx, idx + currentFilterValue.length);
                    text = pre + "<strong style=\"color: #008040\"> " + match + "</strong>" + post;
                    item.set("text", text);
                } else {
                    item.set("text", item.data.originalText)
                }
            } else {
                item.set("text", item.data.originalText)
            }

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


    collapse: function(node) {
        if (node && node.childNodes.length > 0) {
            var childNodes = node.childNodes;
            for (var i = 0; i < childNodes.length; i++) {
                var child = node.childNodes[i];
                if (child.data.type == "data") {
                    return false;
                }
                if (!this.collapse(child)) {
                    return false;
                }
            }
        }
        return true;
    },

    recursiveAddNode: function (con, scope, attributePrefix, ctx, dataContext) {

        dataContext = dataContext || {};

        try {
            var fn = null;
            var newNode = null;

            if (con.fieldtype == "localizedfields") {
                if (dataContext.containerType) {
                    dataContext.subContainerType = con.fieldtype;
                } else {
                    dataContext.containerType = con.fieldtype;
                }
            }

            if (con.datatype == "layout") {
                fn = this.addLayoutChild.bind(scope, con.fieldtype, con, dataContext);
            }
            else if (con.datatype == "data") {
                fn = this.addDataChild.bind(scope, con.fieldtype, con, attributePrefix, this.showFieldName, ctx, dataContext);
                if (!fn) {
                    return null;
                }
            }

            newNode = fn();

            if (con.childs) {
                for (var i = 0; i < con.childs.length; i++) {
                    this.recursiveAddNode(con.childs[i], newNode, attributePrefix, ctx, dataContext);
                }
            }

            if (con.datatype == "layout" || con.fieldtype == "localizedfields"
                || con.fieldtype == "fieldcollections"
                || con.fieldtype == "objectbricks") {
                if (newNode && this.collapse(newNode)) {
                    newNode.collapse();
                }
            }

            return newNode;
        } catch ( e) {
            console.log(e);
        }
    }
});

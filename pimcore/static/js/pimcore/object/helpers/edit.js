/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */


/**
 * NOTE: This helper-methods are added to the classes pimcore.object.edit, pimcore.object.fieldcollection, pimcore.object.tags.localizedfields
 */

pimcore.registerNS("pimcore.object.helpers.edit");
pimcore.object.helpers.edit = {

    getRecursiveLayout: function (l) {

        var panelListenerConfig = {};

        var xTypeLayoutMapping = {
            accordion: {
                xtype: "panel",
                layout: "accordion",
                forceLayout: true,
                hideMode: "offsets",
                listeners: panelListenerConfig
            },
            fieldset: {
                xtype: "fieldset",
                autoScroll: true,
                forceLayout: true,
                hideMode: "offsets",
                listeners: panelListenerConfig
            },
            panel: {
                xtype: "panel",
                padding: 10,
                autoScroll: true,
                forceLayout: true,
                monitorResize: true,
                layout: "pimcoreform",
                hideMode: "offsets",
                listeners: panelListenerConfig
            },
            region: {
                xtype: "panel",
                layout: "border",
                forceLayout: true,
                hideMode: "offsets",
                listeners: panelListenerConfig
            },
            tabpanel: {
                xtype: "tabpanel",
                activeTab: 0,
                deferredRender: true,
                forceLayout: true,
                hideMode: "offsets",
                enableTabScroll: true,
                listeners: {
                    afterrender: function (panel) {
                        window.setTimeout(function () {
                            try {
                                var parentEl = panel.body.findParent(".x-tab-panel");
                                if(parentEl) {
                                    panel.setWidth(Ext.get(parentEl).getWidth()-50);
                                    //panel.getEl().applyStyles("position:relative;");
                                    panel.ownerCt.doLayout();
                                }

                            } catch (e) {
                                console.log(e);
                            }
                        }, 2000);
                    }
                }
            },
            button: {
                xtype: "button"
            },
            text: {
                xtype: "panel",
                padding: 10,
                autoScroll: true,
                forceLayout: true,
                monitorResize: true,
                listeners: panelListenerConfig
            }
        };

        var validKeys = ["xtype","title","layout","items","region","width","height","name","text","html","handler","labelWidth","collapsible","collapsed","bodyStyle"];

        var tmpItems;

        // translate title
        if(typeof l.title != "undefined") {
            l.title = ts(l.title);
        }

        if (l.datatype == "layout") {
            if (l.childs && typeof l.childs == "object") {
                if (l.childs.length > 0) {
                    l.items = [];
                    for (var i = 0; i < l.childs.length; i++) {
                        tmpItems = this.getRecursiveLayout(l.childs[i]);
                        if (tmpItems) {
                            l.items.push(tmpItems);
                        }
                    }
                }
            }

            var configKeys = Object.keys(l);
            var newConfig = {};
            var currentKey;
            for (var u = 0; u < configKeys.length; u++) {
                currentKey = configKeys[u];
                if (in_array(configKeys[u], validKeys)) {

                    // handlers must be eval()
                    if(configKeys[u] == "handler") {
                        l[configKeys[u]] = eval(l[configKeys[u]]);
                    }

                    if(l[configKeys[u]]) {
                    //if (typeof l[configKeys[u]] != "undefined") {
                        if(configKeys[u] == "html"){
                            newConfig[configKeys[u]] = ts(l[configKeys[u]]);
                        } else {
                            newConfig[configKeys[u]] = l[configKeys[u]];
                        }
                    }
                }
            }

            newConfig = Object.extend(newConfig, xTypeLayoutMapping[l.fieldtype]);
            newConfig.forceLayout = true;

            if (newConfig.items) {
                if (newConfig.items.length < 1) {
                    delete newConfig.items;
                }
            }

            var tmpLayoutId;
            // generate id for layout cmp
            if (newConfig.name) {
                tmpLayoutId = Ext.id();

                newConfig.id = tmpLayoutId;
                newConfig.cls = "objectlayout_element_"+newConfig.name;
            }

            return newConfig;
        }
        else if (l.datatype == "data") {

            // if invisible return false
            if (l.invisible) {
                return false;
            }

            if (pimcore.object.tags[l.fieldtype]) {
                var dLayout;
                var data;
                var metaData;

                try {
                    if (typeof this.getDataForField(l.name) != "function") {
                        data = this.getDataForField(l.name);
                    }
                } catch (e) {
                    data = null;
                    console.log(e);
                }

                try {
                    if (typeof this.getMetaDataForField(l.name) != "function") {
                        metaData = this.getMetaDataForField(l.name);
                    }
                } catch (e) {
                    metaData = null;
                    console.log(e);
                }

                // add asterisk to mandatory field
                l.titleOriginal = l.title;
                if(l.mandatory) {
                    l.title += ' <span style="color:red;">*</span>';
                }

                var field = new pimcore.object.tags[l.fieldtype](data, l);

                field.setObject(this.object);
                field.setName(l.name);
                field.setTitle(l.titleOriginal);
                field.setInitialData(data);

                this.addToDataFields(field, l.name);

                // WYSIWYG is a frame that must be masked
                if (l.fieldtype == "wysiwyg") {
                    this.addFieldsToMask(field);
                }

                if (l.noteditable) {
                    dLayout = field.getLayoutShow();
                }
                else {
                    dLayout = field.getLayoutEdit();
                }

                // set title back to original (necessary for localized fields because they use the same config several times, for each language )
                l.title = l.titleOriginal;


                try {
                    dLayout.on("render", function (metaData) {
                        if(metaData && metaData.inherited) {
                            this.markInherited(metaData);
                        }
                    }.bind(field, metaData));
                }
                catch (e) {
                    console.log(l.name + " event render not supported (tag type: " + l.fieldtype + ")");
                    console.log(e);
                }

                // set styling
                if (l.style || l.tooltip) {
                    try {
                        dLayout.on("render", function (field) {

                            try {
                                var el = this.getEl();
                                if(!el.hasClass("object_field")) {
                                    el = el.parent(".object_field");
                                }
                            } catch (e) {
                                console.log(e);
                                return;
                            }

                            // if element does not exist, abort
                            if(!el) {
                                console.log(field.name + " style and tooltip aborted, nor matching element found");
                                return;
                            }

                            // apply custom css styles
                            if(field.style) {
                                try {
                                    el.applyStyles(field.style);
                                } catch (e) {
                                    console.log(e);
                                }
                            }


                            // apply tooltips
                            if(field.tooltip) {
                                try {
                                    new Ext.ToolTip({
                                        target: el,
                                        title: field.title,
                                        html: nl2br(ts(field.tooltip)),
                                        trackMouse:true,
                                        showDelay: 200
                                    });
                                } catch (e) {
                                    console.log(e);
                                }
                            }
                        }.bind(dLayout, l));
                    }
                    catch (e) {
                        console.log(l.name + " event render not supported (tag type: " + l.fieldtype + ")");
                        console.log(e);
                    }
                }

                return dLayout;
            }
        }

        return false;
    }
};

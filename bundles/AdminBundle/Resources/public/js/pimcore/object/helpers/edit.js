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


/**
 * NOTE: This helper-methods are added to the classes pimcore.object.edit, pimcore.object.fieldcollection,
 * pimcore.object.tags.localizedfields
 */

pimcore.registerNS("pimcore.object.helpers.edit");
pimcore.object.helpers.edit = {

    getRecursiveLayout: function (l, noteditable, context, skipLayoutChildren, onlyLayoutChildren, dataProvider, disableLazyRendering) {
        if (typeof context === "undefined") {
            context = {};
        }

        if (typeof dataProvider === "undefined") {
            dataProvider = this;
        }

        context.objectId = this.object.id;

        if (this.object.data.currentLayoutId) {
            context.layoutId = this.object.data.currentLayoutId;
        }

        var panelListenerConfig = {};

        var tabpanelCorrection = function (panel) {
            window.setTimeout(function () {
                try {
                    if(typeof panel["pimcoreLayoutCorrected"] == "undefined") {
                        var parentEl = panel.body.findParent(".x-tab-panel");
                        if(parentEl && Ext.get(parentEl).getWidth()) {
                            panel.setWidth(Ext.get(parentEl).getWidth()-50);
                            //panel.getEl().applyStyles("position:relative;");
                            panel.ownerCt.updateLayout();

                            panel["pimcoreLayoutCorrected"] = true;
                        }
                    }
                } catch (e) {
                    console.log(e);
                }
            }, 2000);
        };

        var xTypeLayoutMapping = {
            accordion: {
                xtype: "panel",
                layout: "accordion",
                forceLayout: true,
                hideMode: "offsets",
                padding: 0,
                bodyStyle: "padding: 0",
                listeners: panelListenerConfig
            },
            fieldset: {
                xtype: "fieldset",
                autoScroll: true,
                forceLayout: true,
                hideMode: "offsets",
                listeners: panelListenerConfig
            },
            fieldcontainer: {
                xtype: "fieldcontainer",
                autoScroll: true,
                forceLayout: true,
                hideMode: "offsets",
                listeners: panelListenerConfig
            },
            panel: {
                xtype: "panel",
                autoScroll: true,
                forceLayout: true,
                monitorResize: true,
                bodyStyle: "padding: 10px",
                border: false,
                defaults: {
                    width: "auto"
                },
                hideMode: "offsets",
                listeners: panelListenerConfig
            },
            region: {
                xtype: "panel",
                layout: "border",
                forceLayout: true,
                padding: 0,
                hideMode: "offsets",
                listeners: panelListenerConfig
            },
            tabpanel: {
                xtype: "tabpanel",
                activeTab: 0,
                monitorResize: true,
                deferredRender: true,
                border: false,
                bodyStyle: "padding: 10px",
                forceLayout: true,
                hideMode: "offsets",
                enableTabScroll: true,
                listeners: {
                    afterrender:tabpanelCorrection,
                    tabchange: tabpanelCorrection
                }
            },
            button: {
                xtype: "button",
                style: "margin-bottom: 10px;"
            },
            text: {
                xtype: "panel",
                style: "margin-bottom: 10px;",
                autoScroll: true,
                forceLayout: true,
                monitorResize: true,
                listeners: panelListenerConfig
            }
        };

        var validKeys = ["xtype","title","layout","icon","items","region","width","height","name","text","html","handler",
            "labelWidth", "fieldLabel", "collapsible","collapsed","bodyStyle","listeners", "border", "tabPosition"];

        var tmpItems;

        // translate title
        if(typeof l.title != "undefined") {
            l.title = t(l.title);
        }

        if (l.datatype == "layout") {
            if (skipLayoutChildren !== true && l.childs && typeof l.childs == "object") {
                if (l.childs.length > 0) {
                    l.items = [];
                    for (var i = 0; i < l.childs.length; i++) {

                        var childConfig = l.childs[i];
                        if (typeof childConfig.labelWidth == "undefined" && l.labelWidth != "undefined") {
                            childConfig.labelWidth = l.labelWidth;
                        }

                        if (typeof childConfig.fieldLabel == "undefined" && l.fieldLabel != "undefined") {
                            childConfig.fieldLabel = l.fieldLabel;
                        }


                        if(l.fieldtype =='tabpanel' && !disableLazyRendering) {
                            tmpItems = this.getRecursiveLayout(childConfig, noteditable, context, true, false, dataProvider, disableLazyRendering);
                            if (tmpItems) {
                                if (!tmpItems['listeners']) {
                                    tmpItems['listeners'] = {};
                                }
                                tmpItems['listeners']['afterrender'] = function (childConfig, panel) {
                                    if (!panel.__tabpanel_initialized) {
                                        var children = this.getRecursiveLayout(childConfig, noteditable, context, false, true, dataProvider, disableLazyRendering);
                                        panel.add(children);
                                        panel.updateLayout();

                                        if (panel.setActiveTab) {
                                            var activeTab = panel.items.items[0];
                                            if (activeTab) {
                                                activeTab.updateLayout();
                                                panel.setActiveTab(activeTab);
                                            }
                                        }

                                        panel.__tabpanel_initialized = true;


                                    }
                                }.bind(this, childConfig);
                            }
                        } else {
                            tmpItems = this.getRecursiveLayout(childConfig, noteditable, context, false, false, dataProvider, disableLazyRendering);
                        }

                        if (tmpItems) {
                            l.items.push(tmpItems);
                        }
                    }
                }
            }

            if(onlyLayoutChildren === true) {
                return l.items;
            }

            var configKeys = Object.keys(l);
            var newConfig = {};
            var currentKey;
            for (var u = 0; u < configKeys.length; u++) {
                currentKey = configKeys[u];
                if (in_array(configKeys[u], validKeys)) {

                    // handlers must be eval()
                    if(configKeys[u] == "handler") {
                        try {
                            if(l[configKeys[u]] && Ext.isString(l[configKeys[u]]) && l[configKeys[u]].length > 10) {
                                l[configKeys[u]] = eval(l[configKeys[u]]).bind(this);
                            }
                        } catch (e) {
                            console.error('Unable to eval() given handler function of layout compontent: ' + l.name + " of type: " + l.fieldtype);
                            console.log(e);
                        }
                    }

                    if(l[configKeys[u]]) {
                        //if (typeof l[configKeys[u]] != "undefined") {
                        if(configKeys[u] == "html"){
                            newConfig[configKeys[u]] = l["renderingClass"] ? l[configKeys[u]] : t(l[configKeys[u]]);
                        } else {
                            newConfig[configKeys[u]] = l[configKeys[u]];
                        }
                    }
                }
            }

            if (pimcore.object.layout[l.fieldtype] !== undefined) {
                var layout = new pimcore.object.layout[l.fieldtype](l, context);
                newConfig = layout.getLayout();
            } else {
                newConfig = Object.assign(xTypeLayoutMapping[l.fieldtype] || {}, newConfig);
                if (typeof newConfig.labelWidth != "undefined") {
                    newConfig = Ext.applyIf(newConfig, {
                        defaults: {}
                    });
                    newConfig.defaults.labelWidth = newConfig.labelWidth;

                }

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
                    newConfig.cls = "objectlayout_element_" + newConfig.name + " objectlayout_element_" + l.fieldtype;
                }
            }


            return newConfig;
        }
        else if (l.datatype == "data") {

            if (l.invisible) {
                return false;
            }

            if (pimcore.object.tags[l.fieldtype]) {
                var dLayout;
                var data;
                var metaData;

                try {
                    if (typeof dataProvider.getDataForField(l) != "function") {
                        data = dataProvider.getDataForField(l);
                    }
                } catch (e) {
                    data = null;
                    console.log(e);
                }

                try {
                    if (typeof dataProvider.getMetaDataForField(l) != "function") {
                        metaData = dataProvider.getMetaDataForField(l);
                    }
                } catch (e2) {
                    metaData = null;
                    console.log(e2);
                }

                // add asterisk to mandatory field
                l.titleOriginal = l.title;
                if(l.mandatory) {
                    l.title += ' <span style="color:red;">*</span>';
                }
                if(l.tooltip) {
                    l.title += ' <span class="pimcore_object_label_icon pimcore_icon_gray_info"></span>';
                }

                var field = new pimcore.object.tags[l.fieldtype](data, l);

                let applyDefaults = false;
                if (context && context['applyDefaults']) {
                    applyDefaults = true;
                }
                field.setObject(this.object);
                field.updateContext(context);

                field.setName(l.name);
                field.setTitle(l.titleOriginal);

                if (applyDefaults && typeof field["applyDefaultValue"] !== "undefined") {
                    field.applyDefaultValue();
                }
                field.setInitialData(data);


                if (typeof field["finishSetup"] !== "undefined") {
                    field.finishSetup();
                }


                if (typeof l.labelWidth != "undefined") {
                    field.labelWidth = l.labelWidth;
                }

                dataProvider.addToDataFields(field, l.name);

                if (l.noteditable || noteditable) {
                    dLayout = field.getLayoutShow();
                }
                else {
                    dLayout = field.getLayoutEdit();
                }

                // set title back to original (necessary for localized fields because they use the same config several
                // times, for each language )
                l.title = l.titleOriginal;


                try {
                    dLayout.on("afterrender", function (metaData) {
                        if(metaData && metaData.inherited) {
                            this.markInherited(metaData);
                        }
                    }.bind(field, metaData));
                }
                catch (e3) {
                    console.log(l.name + " event render not supported (tag type: " + l.fieldtype + ")");
                    console.log(e3);
                }

                if(pimcore.currentuser.admin) {
                    // add real field name as title attribute on element
                    try {
                        dLayout.on("render", function (l) {
                            if(Ext.isFunction(this['getEl'])) {
                                var el = this.getEl();
                                if (!el.hasCls("object_field")) {
                                    el = el.parent(".object_field");
                                }
                                if (el) {
                                    var label = el.down('label span');
                                    if(label) {
                                        label.dom.setAttribute('title', l.name);
                                    }
                                }
                            }
                        }.bind(dLayout, l));
                    } catch (e) {
                        console.log(e);
                        // noting to do
                    }
                }

                // set styling
                if (l.style || l.tooltip) {
                    try {
                        dLayout.on("render", function (field) {

                            try {
                                var el = this.getEl();
                                if(!el.hasCls("object_field")) {
                                    el = el.parent(".object_field");
                                }
                            } catch (e4) {
                                console.log(e4);
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
                                } catch (e5) {
                                    console.log(e5);
                                }
                            }

                            // apply tooltips
                            if(field.tooltip) {
                                try {
                                    var tooltipHtml = field.tooltip;

                                    // classification-store tooltips are already translated
                                    if (context.type != "classificationstore") {
                                        tooltipHtml = t(tooltipHtml);
                                    }

                                    new Ext.ToolTip({
                                        target: el,
                                        html: nl2br(tooltipHtml),
                                        trackMouse:true,
                                        showDelay: 200,
                                        dismissDelay: 0
                                    });
                                } catch (e6) {
                                    console.log(e6);
                                }
                            }
                        }.bind(dLayout, l));
                    }
                    catch (e7) {
                        console.log(l.name + " event render not supported (tag type: " + l.fieldtype + ")");
                        console.log(e7);
                    }
                }

                return dLayout;
            }
        }

        return false;
    }
};

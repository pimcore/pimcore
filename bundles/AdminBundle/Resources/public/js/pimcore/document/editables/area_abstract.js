/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.document.area_abstract");
pimcore.document.area_abstract = Class.create(pimcore.document.editable, {
    dialogBoxes: {},

    openEditableDialogBox: function (element, dialogBoxDiv) {
        let id = dialogBoxDiv.dataset.dialogId;
        let jsonConfig = document.getElementById('dialogBoxConfig-' + id).innerHTML;
        var config = JSON.parse(jsonConfig);

        var editablesInBox = this.getEditablesInDialogBox(id);
        let items = this.buildEditableDialogLayout(config["items"], editablesInBox, 1);

        if(!this.dialogBoxes[id]) {
            this.dialogBoxes[id] = new Ext.Window({
                closeAction: 'hide',
                width: Math.min(config["width"], Ext.getBody().getViewSize().width),
                height: Math.min(config["height"], Ext.getBody().getViewSize().height),
                items: items,
                bodyStyle: 'padding: 10px',
                scrollable: 'y',
                cls: 'pimcore_areablock_dialogBox',
                listeners: {
                    afterrender: function (win, eOpts) {
                        // render editables in window
                        // we need a bit of a timeout, since it seems the layout (especially when using tabs) isn't
                        // completely done in terms of the right dimensions, which has bad effects on the size
                        // of editables where the size matters, e.g. the image editable
                        window.setTimeout(function () {
                            Object.keys(editablesInBox).forEach(function (editableName) {
                                if (typeof editablesInBox[editableName]["renderInDialogBox"] === "function") {
                                    editablesInBox[editableName].renderInDialogBox();
                                } else {
                                    editablesInBox[editableName].render();
                                }
                            });
                        }, 200);
                    }
                },
                buttons: ['->', {
                    text: t("close"),
                    listeners: {
                        "click": function () {
                            this.dialogBoxes[id].close();
                            if(config["reloadOnClose"]) {
                                this.reloadDocument();
                            }
                        }.bind(this)
                    },
                    iconCls: "pimcore_icon_save"
                }]
            })
        }

        this.dialogBoxes[id].show();
    },

    getEditablesInDialogBox: function (id) {
        let editablesInDialogBox = {};
        Object.values(editableManager.getEditables()).forEach(editable => {
            if(editable.getInDialogBox() === id) {
                editablesInDialogBox[editable.getRealName()] = editable;
            }
        });

        return editablesInDialogBox;
    },

    buildEditableDialogLayout: function (config, editablesInBox, level) {
        var nextLevel = level+1;
        if(Array.isArray(config)) {
            var items = [];
            config.forEach(function (itemConfig) {
                let item = this.buildEditableDialogLayout(itemConfig, editablesInBox, nextLevel);
                if(item) {
                    items.push(item);
                }
            }.bind(this));

            if(level === 1) {
                return {
                    xtype: 'container',
                    items: items
                };
            }

            return items;
        } else if(editablesInBox[config['name']]) {
            let templateId = 'template__' + editablesInBox[config['name']].getId();
            var templateEl = document.getElementById(templateId);
            if(templateEl) {
                if(typeof editablesInBox[config['name']]['renderInDialogBox'] === "function") {
                    if (editablesInBox[config['name']]['config']) {
                        editablesInBox[config['name']]['config']['label'] = config['label'] ?? config['name'];
                    }
                    return {
                        xtype: 'container',
                        html: templateEl.innerHTML
                    };
                } else {
                    return {
                        xtype: 'fieldset',
                        title: config['label'] ?? config['name'],
                        html: templateEl.innerHTML
                    };
                }
            }
        } else if(config['items']) {
            let container = {
                xtype: config['type'],
                bodyStyle: 'padding: 10px',
                deferredRender: false,
                manageHeight: false,
                items: this.buildEditableDialogLayout(config['items'], editablesInBox, nextLevel)
            };
            let allowedConfigElements = [
                'layout',
                'flex',
                'defaults',
                'title'
            ];
            for (let i in allowedConfigElements) {
                let cfgElement = allowedConfigElements[i];
                if(config[cfgElement]) {
                    container[cfgElement] = config[cfgElement];
                }
            }

            return container;
        }
    },

});
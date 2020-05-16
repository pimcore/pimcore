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

pimcore.registerNS("pimcore.asset.metadata.tags.manyToOneRelation");
pimcore.asset.metadata.tags.manyToOneRelation = Class.create(pimcore.asset.metadata.tags.abstract, {

    type: "manyToOneRelation",
    dataChanged: false,
    dataObjectFolderAllowed: false,

    initialize: function (data, fieldConfig) {

        this.data = null;

        if (data) {
            this.data = data;
        }
        this.fieldConfig = fieldConfig;
    },

    getGridColumnConfig: function (field) {
        return {
            text: field.label,
            width: this.getColumnWidth(field, 300),
            sortable: false,
            dataIndex: field.key,
            getEditor: this.getWindowCellEditor.bind(this, field),
            renderer: this.getRenderer(field)
        };
    },


    getLayoutEdit: function () {

        var href = {
            name: this.fieldConfig.name
        };

        var labelWidth = this.fieldConfig.labelWidth ? this.fieldConfig.labelWidth : 100;

        if (this.data) {
            href.value = this.data;
        }

        if (this.fieldConfig.width) {
            href.width = this.fieldConfig.width;
        } else {
            href.width = 350;
        }

        href.enableKeyEvents = true;
        href.editable = false;
        href.fieldCls = "pimcore_droptarget_input";
        this.component = new Ext.form.TextField(href);

        this.component.on("render", function (el) {

            // add drop zone
            new Ext.dd.DropZone(el.getEl(), {
                reference: this,
                ddGroup: "element",
                getTargetFromEvent: function (e) {
                    return this.reference.component.getEl();
                },

                onNodeOver: function (target, dd, e, data) {
                    if (data.records.length === 1 && this.dndAllowed(data.records[0].data)) {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    }
                }.bind(this),

                onNodeDrop: this.onNodeDrop.bind(this)
            });

        }.bind(this));


        this.composite = Ext.create('Ext.form.FieldContainer', {
            fieldLabel: this.fieldConfig.title,
            labelWidth: labelWidth,
            layout: 'hbox',
            items: this.component,
            componentCls: "object_field",
            border: false,
            style: {
                padding: 0
            }
        });

        return this.composite;
    },

    getLayoutShow: function () {

        var href = {
            fieldLabel: this.fieldConfig.title,
            name: this.fieldConfig.name,
            cls: "object_field",
            labelWidth: this.fieldConfig.labelWidth ? this.fieldConfig.labelWidth : 100
        };

        if (this.data) {
            href.value = this.data;
        }

        if (this.fieldConfig.width) {
            href.width = this.fieldConfig.width;
        } else {
            href.width = 300;
        }
        href.width = href.labelWidth + href.width;
        href.disabled = true;

        this.component = new Ext.form.TextField(href);

        this.composite = Ext.create('Ext.form.FieldContainer', {
            layout: 'hbox',
            items: [this.component, {
                xtype: "button",
                iconCls: "pimcore_icon_open",
                handler: this.openElement.bind(this)
            }],
            componentCls: "object_field",
            border: false,
            style: {
                padding: 0
            }
        });

        return this.composite;

    },

    onNodeDrop: function (target, dd, e, data) {

        if(!pimcore.helpers.dragAndDropValidateSingleItem(data)) {
            return false;
        }

        data = data.records[0].data;

        if (this.dndAllowed(data)) {
            this.data = data.path;

            this.component.removeCls("strikeThrough");
            if (data.published === false) {
                this.component.addCls("strikeThrough");
            }
            this.component.setValue(data.path);

            return true;
        } else {
            return false;
        }
    },

    empty: function () {
        this.data = {};
        this.dataChanged = true;
        this.component.setValue("");
    },

    getValue: function () {
        return this.data;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    dndAllowed: function (data) {

        var elementType = data.elementType;
        var isAllowed = false;
        if (elementType == this.fieldConfig.subtype) {
            isAllowed = true;
        }
        return isAllowed;
    },

    getCellEditValue: function () {
        return this.getValue();
    },

    isDirty:function () {
        if (this.component) {
            if (!this.component.rendered) {
                return false;
            } else {
                return this.dataChanged;
            }
        }
    },

    updatePredefinedGridRow: function(grid, row, data) {

        var dd = new Ext.dd.DropZone(row, {
            ddGroup: "element",

            getTargetFromEvent: function(e) {
                return this.getEl();
            },

            onNodeOver: function(dataRow, target, dd, e, data) {
                if (data.records.length == 1) {
                    var record = data.records[0];
                    var data = record.data;

                    if (dataRow.type == data.elementType) {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    }
                }
                return Ext.dd.DropZone.prototype.dropNotAllowed;
            }.bind(this, data),

            onNodeDrop : function(grid, recordid, target, dd, e, data) {
                if (pimcore.helpers.dragAndDropValidateSingleItem(data)) {
                    var rec = grid.getStore().getById(recordid);

                    var record = data.records[0];
                    var data = record.data;

                    if (data.elementType != rec.get("type")) {
                        return false;
                    }

                    rec.set("data", data.path);
                    rec.set("all", {
                        data: {
                            id: data.id,
                            type: data.type
                        }
                    });

                    return true;
                }
                return false;
            }.bind(this, grid, data.id)
        });
    },

    getGridCellRenderer: function(value, metaData, record, rowIndex, colIndex, store) {
        if (value) {
            value =  nl2br(value);
        } else {
            value =  "";
        }

        return '<div class="pimcore_property_droptarget">' + value + '</div>';
    },

    getOpenActionItem: function() {
        return {
            tooltip: t('open'),
            icon: "/bundles/pimcoreadmin/img/flat-color-icons/open_file.svg",
            handler: function (grid, rowIndex) {
                var pData = grid.getStore().getAt(rowIndex).data;
                if (pData.data) {
                    pimcore.helpers.openElement(pData.data, pData.type);
                }
            }.bind(this),
        };
    },

    getGridOpenActionVisibilityStyle: function() {
        return "";
    },


    handleGridOpenAction:function (grid, rowIndex) {
        var pData = grid.getStore().getAt(rowIndex).data;
        if (pData.data) {
            pimcore.helpers.openElement(pData.data, pData.type);
        }
    }

});

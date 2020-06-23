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

pimcore.registerNS("pimcore.asset.metadata.tags.abstract");
pimcore.asset.metadata.tags.abstract = Class.create({

    asset:null,
    name:null,
    title:"",
    initialData:null,

    setAsset:function (asset) {
        this.asset = asset;
    },

    getAsset:function () {
        return this.asset;
    },

    setName:function (name) {
        this.name = name;
        this.context.fieldname = name;
    },

    getName:function () {
        return this.name;
    },

    setTitle:function (title) {
        this.title = title;
    },

    getTitle:function () {
        return this.title;
    },

    setInitialData:function (initialData) {
        this.initialData = initialData;
    },

    getInitialData:function () {
        return this.initialData;
    },

    getGridColumnEditor:function (field) {
        return null;
    },

    getRenderer: function(field) {
        return function (key, value, metaData, record) {
            return Ext.util.Format.htmlEncode(value);
        }.bind(this, field.key);
    },

    getGridColumnConfig:function (field) {
        return {
            text: field.label,
            sortable:false,
            width: this.getColumnWidth(field, 200),
            dataIndex:field.key,
            renderer: this.getRenderer(field),
            filter: 'string',
            editor:this.getGridColumnEditor(field)
        };
    },

    getGridColumnFilter:function (field) {
        return null;
    },

    applyGridEvents: function(grid, field) {
        //nothing to do here, but maybe in sub types
    },

    getContext: function() {
        this.createDefaultContext();
        return this.context;
    },

    updateContext: function(context) {
        this.createDefaultContext();
        Ext.apply(this.context, context);
    },

    createDefaultContext: function() {
        if (typeof this.context === "undefined") {
            this.context = {
                containerType: "object",
                fieldname: null
            };
        }
    },

    getWindowCellEditor: function ( field, record) {
        return new pimcore.element.helpers.gridCellEditor({
            fieldInfo: field,
            elementType: "assetmetadata"
        });
    },

    isRendered:function () {
        if (this.component) {
            return this.component.rendered;
        }

        throw "it seems that the field -" + this.getName()
        + "- does not implement the isRendered() method and doesn't contain this.component";
    },

    getColumnWidth: function(field, defaultValue) {
        if (field.width) {
            return field.width;
        } else if(field.layout && field.layout.width) {
            return field.layout.width;
        } else {
            return defaultValue;
        }
    },

    getGridCellEditor: function(gridtype, record) {
        return null;
    },

    updatePredefinedGridRow: function(grid, row, data) {
        // nothing to do
    },

    getGridCellRenderer: function(value, metaData, record, rowIndex, colIndex, store) {
        return Ext.util.Format.htmlEncode(value);
    },

    handleGridCellClick: function(grid, cell, rowIndex, cellIndex, e) {
        // nothing to do
    },

    marshal: function(value) {
        return value;
    },

    unmarshal: function(value) {
        return value;
    },

    getGridOpenActionVisibilityStyle: function() {
        return "pimcore_hidden";
    },

    handleGridOpenAction:function (grid, rowIndex) {

    }
});

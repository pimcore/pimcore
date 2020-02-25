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

pimcore.registerNS("pimcore.object.tags.abstract");
pimcore.object.tags.abstract = Class.create({

    object:null,
    name:null,
    title:"",
    initialData:null,

    setObject:function (object) {
        this.object = object;
    },

    getObject:function () {
        return this.object;
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

    applyPermissionStyle: function (key, value, metaData, record) {
        var metadata = record.data.metadata;

        if (metadata && metadata.permission !== undefined) {
            // evaluate permissions
            if (metadata.permission[key] !== undefined) {
                if (metadata.permission[key].noView) {
                    metaData.tdCls += " grid_value_noview";
                }

                if (metadata.permission[key].noEdit) {
                    metaData.tdCls += " grid_value_noedit";
                }
            }
        }
    },

    getGridColumnConfig:function (field) {
        var renderer = function (key, value, metaData, record) {
            this.applyPermissionStyle(key, value, metaData, record);

            try {
                if (record.data.inheritedFields && record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                    metaData.tdCls += " grid_value_inherited";
                }
            } catch (e) {
                console.log(e);
            }

            return Ext.util.Format.htmlEncode(value);

        }.bind(this, field.key);

        return {text: t(field.label), sortable:true, dataIndex:field.key, renderer:renderer,
            editor:this.getGridColumnEditor(field)};
    },

    getGridColumnFilter:function (field) {
        return null;
    },

    applyGridEvents: function(grid, field) {
        //nothing to do here, but maybe in sub types
    },

    getEl:function () {
        if (this.component) {
            return this.component.getEl();
        }

        throw "the component `" + this.getName()
        + "´ doesn't implement the method getEl() and is not standard-compliant!";
    },

    unmarkInherited:function () {
        var el = this.getEl();
        if (el) {
            el.removeCls("object_value_inherited");
            this.removeInheritanceSourceButton();
        }

    },

    markInherited:function (metaData) {

        var el = this.getEl();
        if (el) {
            el.addCls("object_value_inherited");
        }
        this.addInheritanceSourceButton(metaData);
    },


    getWrappingEl:function () {
        var el = this.getEl();
        try {
            if (el && !el.hasCls("object_field")) {
                el = el.parent(".object_field");
            }
        } catch (e) {
            console.log(e);
            return;
        }

        return el;
    },

    addInheritanceSourceButton:function (metaData) {

        var el = this.getWrappingEl();
        if (el && el.getFirstChild()) {
            el = el.getFirstChild();
            el.setStyle({position:"relative"});
            el.insertHtml("afterBegin", '<div class="pimcore_open_inheritance_source"></div>');
            var button = Ext.get(el.query(".pimcore_open_inheritance_source")[0]);
            if (button) {
                button.addListener("click", function (metaData) {

                    var myName = this.getName();
                    var myObject = this.getObject();

                    if (!metaData && myObject.data.metaData && myObject.data.metaData[myName]) {
                        metaData = myObject.data.metaData[myName];
                    }

                    if (metaData) {
                        pimcore.helpers.openObject(metaData.objectid, "object");
                    }

                }.bind(this, metaData));
            }
        }
    },

    removeInheritanceSourceButton:function () {
        var el = this.getWrappingEl();
        if (el) {
            var button = Ext.get(el.query(".pimcore_open_inheritance_source")[0]);
            if (button) {
                button.remove();
            }
        }
    },

    isMandatory:function () {
        return this.fieldConfig.mandatory;
    },

    isRendered:function () {
        if (this.component) {
            return this.component.rendered;
        }

        throw "it seems that the field -" + this.getName()
        + "- does not implement the isRendered() method and doesn't contain this.component";
    },


    dataIsNotInherited: function() {
        // by default the data cannot be inherited if the field is dirty. Composite fields (object bricks,
        // localized fields must implement their own logic)
        return this.isDirty();
    },

    isDirty:function () {
        var dirty = false;
        if (this.component && typeof this.component.isDirty == "function") {

            if (!this.component.rendered) {
                return false;
            } else {
                dirty = this.component.isDirty();

                // once a field is dirty it should be always dirty (not an ExtJS behavior)
                if (this.component["__pimcore_dirty"]) {
                    dirty = true;
                }
                if (dirty) {
                    this.component["__pimcore_dirty"] = true;
                }

                return dirty;
            }
        }

        throw "isDirty() is not implemented";
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
            elementType: "object"
        });
    },

    fullPathRenderCheck :function (value, metaData, record) {
        if(record.data.published === false) {
            metaData.tdStyle = 'text-decoration: line-through;color: #777;';
        }
        return value;
    }

});

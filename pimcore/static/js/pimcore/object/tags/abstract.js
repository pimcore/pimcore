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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
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

        if (metadata.permission !== undefined) {
            // evaluate permissions
            if (metadata.permission[key] !== undefined) {
                if (metadata.permission[key].noView) {
                    metaData.css += " grid_value_noview";
                }

                if (metadata.permission[key].noEdit) {
                    metaData.css += " grid_value_noedit";
                }
            }

        }

    },

    getGridColumnConfig:function (field) {
        var renderer = function (key, value, metaData, record) {
            this.applyPermissionStyle(key, value, metaData, record);

            if (record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                metaData.css += " grid_value_inherited";
            }
            return value;

        }.bind(this, field.key);

        return {header:ts(field.label), sortable:true, dataIndex:field.key, renderer:renderer,
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
            el.removeClass("object_value_inherited");
            this.removeInheritanceSourceButton();
        }

    },

    markInherited:function (metaData) {

        var el = this.getEl();
        if (el) {
            el.addClass("object_value_inherited");
        }
        this.addInheritanceSourceButton(metaData);
    },


    getWrappingEl:function () {
        var el = this.getEl();
        try {
            if (el && !el.hasClass("object_field")) {
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
        if (el) {
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

    isInvalidMandatory:function () {

        if (!this.isRendered() && this.getInitialData().length > 0) {
            return false;
        } else if (!this.isRendered()) {
            return true;
        }

        if (this.getValue().length < 1) {
            return true;
        }
        return false;
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
    }
});
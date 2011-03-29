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

pimcore.registerNS("pimcore.object.tags.abstract");
pimcore.object.tags.abstract = Class.create({

    object: null,
    myName: null, 

    setObject: function (object) {
        this.object = object;
    },

    getObject: function () {
        return this.object;
    },

    setName: function (name) {
        this.myName = name;
    },

    getName: function () {
        return this.myName;
    },

    markMandatory: function () {
        var el = this.getEl();
        if (el) {
            el.addClass("object_mendatory_error");
        }
    },

    unmarkMandatory: function () {
        var el = this.getEl();
        if (el) {
            el.removeClass("object_mendatory_error");
        }
    },

    getEl: function () {
        if (this.layout) {
            return this.layout.getEl()
        }
        if(this.grid) {
            return this.grid.getEl();
        }
        if(this.panel) {
            return this.panel.getEl();
        }
    },

    unmarkInherited: function () {
        var el = this.getEl();
        el.removeClass("object_value_inherited");

        this.removeInheritanceSourceButton();
    },

    markInherited: function () {

        var el = this.getEl();
        if (el) {
            el.addClass("object_value_inherited");
        }
        this.addInheritanceSourceButton();
    },

    getWrappingEl: function () {
        var el = this.getEl();
        try {
            if(!el.hasClass("object_field")) {
                el = el.parent(".object_field");
            }
        } catch (e) {
            console.log(e);
            return;
        }

        return el;
    },

    addInheritanceSourceButton: function () {

        var el = this.getWrappingEl();
        if(el) {
            el.setStyle({position: "relative"});
            el.insertHtml("afterBegin", '<div class="pimcore_open_inheritance_source"></div>');
            var button = Ext.get(el.query(".pimcore_open_inheritance_source")[0]);
            if(button) {
                button.addListener("click", function () {

                    var myName = this.getName();
                    var myObject = this.getObject();
                    var metaData = null;
                    if(myObject.data.metaData && myObject.data.metaData[myName]) {
                        metaData = myObject.data.metaData[myName];
                        pimcore.helpers.openObject(metaData.objectid, "object");
                    }

                }.bind(this));
            }
        }
    },

    removeInheritanceSourceButton: function () {
        var el = this.getWrappingEl();
        if(el) {
            var button = Ext.get(el.query(".pimcore_open_inheritance_source")[0]);
            if(button) {
                button.remove();
            }
        }
    },

    isInvalidMandatory: function () {
        if (this.getValue().length < 1) {
            return true;
        }
        return false;
    },

    isDirty: function () {
        if(this.layout && typeof this.layout.isDirty == "function") {
            return this.layout.isDirty();
        }

        throw "isDirty() is not implemented";
    }
});
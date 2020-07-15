/**
 * @class Ext.app.domain.View
 */
Ext.define('Ext.app.domain.View', {
    extend: 'Ext.app.EventDomain',

    requires: ['Ext.Widget'],

    isInstance: true,

    constructor: function(controller) {
        this.callParent([controller]);
        this.controller = controller;
        this.monitoredClasses = [Ext.Widget];
    },

    match: function(target, selector, controller) {
        var out = false;

        if (selector === '#') {
            out = controller === target.getController();
        }
        else {
            out = target.is(selector);
        }

        return out;
    },

    destroy: function() {
        this.controller = null;

        this.callParent();
    }
});

/**
 * @class Ext.list.Tree
 */
Ext.define('Ext.overrides.list.Tree', {
    override: 'Ext.list.Tree',

    canMeasure: true,

    constructor: function(config) {
        this.callParent([config]);

        // Track size so that we can track the expanded size
        // for use by the floated state of items when in micro mode.
        // Browsers where this event is not supported, fall back to a width
        // of 200px for floated tree items.
        if (!Ext.isIE8) {
            this.element.on('resize', 'onElResize', this);
        }
    },

    beforeLayout: function() {
        this.syncIconSize();
    },

    onElResize: function(el, details) {
        if (!this.getMicro() && this.canMeasure) {
            this.expandedWidth = details.width;
        }
    },

    privates: {
        defaultListWidth: 200,
        expandedWidth: null
    }
});

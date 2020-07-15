/**
 * A basic component that changes background color, with considerations for opacity
 * support (checkered background image and IE8 support).
 */
Ext.define('Ext.ux.colorpick.ColorPreview', {
    extend: 'Ext.Component',
    alias: 'widget.colorpickercolorpreview',

    requires: [
        'Ext.util.Format'
    ],

    cls: Ext.baseCSSPrefix + 'colorpreview',

    getTemplate: function() {
        return [
            {
                reference: 'filterElement',
                cls: Ext.baseCSSPrefix + 'colorpreview-filter-el'
            },
            {
                reference: 'btnElement',
                cls: Ext.baseCSSPrefix + 'colorpreview-btn-el',
                tag: 'a'
            }
        ];
    },

    onRender: function() {
        var me = this;

        me.callParent(arguments);
        me.mon(me.btnElement, 'click', me.onClick, me);
    },

    onClick: function(e) {
        e.preventDefault();
        this.fireEvent('click', this, this.color);
    },

    // Called via databinding - update background color whenever ViewModel changes
    setColor: function(color) {
        this.color = color;

        this.applyBgStyle(color);
    },

    applyBgStyle: function(color) {
        var me = this,
            colorUtils = Ext.ux.colorpick.ColorUtils,
            el = me.filterElement,
            rgba;

        rgba = colorUtils.getRGBAString(color);

        el.applyStyles({
            background: rgba
        });
    }
});

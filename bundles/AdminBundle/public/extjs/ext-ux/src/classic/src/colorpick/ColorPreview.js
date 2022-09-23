/**
 * A basic component that changes background color, with considerations for opacity
 * support (checkered background image and IE8 support).
 */
Ext.define('Ext.ux.colorpick.ColorPreview', {
    extend: 'Ext.Component',
    alias: 'widget.colorpickercolorpreview',

    requires: [
        'Ext.util.Format',
        'Ext.XTemplate'
    ],

    // hack to solve issue with IE, when applying a filter the click listener is not being fired.
    style: 'position: relative',

    /* eslint-disable max-len */
    html: '<div class="' + Ext.baseCSSPrefix + 'colorpreview-filter" style="height:100%; width:100%; position: absolute;"></div>' +
          '<a class="btn" style="height:100%; width:100%; position: absolute;"></a>',
    /* eslint-enable max-len */
    // eo hack

    cls: Ext.baseCSSPrefix + 'colorpreview',

    height: 256,

    onRender: function() {
        var me = this;

        me.callParent(arguments);

        me.mon(me.el.down('.btn'), 'click', me.onClick, me);
    },

    onClick: function() {
        this.fireEvent('click', this, this.color);
    },

    // Called via databinding - update background color whenever ViewModel changes
    setColor: function(color) {
        var me = this,
            el = me.getEl();

        // Too early in rendering cycle; skip
        if (!el) {
            return;
        }

        me.color = color;

        me.applyBgStyle(color);
    },

    bgStyleTpl: Ext.create(
        'Ext.XTemplate',
        Ext.isIE && Ext.ieVersion < 10
            // eslint-disable-next-line max-len
            ? 'filter: progid:DXImageTransform.Microsoft.gradient(GradientType=0, startColorstr=\'#{hexAlpha}{hex}\', endColorstr=\'#{hexAlpha}{hex}\');' /* IE6-9 */
            : 'background: {rgba};'
    ),

    applyBgStyle: function(color) {
        var me = this,
            colorUtils = Ext.ux.colorpick.ColorUtils,
            filterSelector = '.' + Ext.baseCSSPrefix + 'colorpreview-filter',
            el = me.getEl().down(filterSelector),
            hex, alpha, rgba, bgStyle;

        hex = colorUtils.rgb2hex(color.r, color.g, color.b);
        alpha = Ext.util.Format.hex(Math.floor(color.a * 255), 2);
        rgba = colorUtils.getRGBAString(color);
        bgStyle = this.bgStyleTpl.apply({ hex: hex, hexAlpha: alpha, rgba: rgba });

        el.applyStyles(bgStyle);
    }
});

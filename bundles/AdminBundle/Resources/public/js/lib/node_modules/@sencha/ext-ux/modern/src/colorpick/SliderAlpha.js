/**
 * Used for "Alpha" slider.
 * @private
 */
Ext.define('Ext.ux.colorpick.SliderAlpha', {
    extend: 'Ext.ux.colorpick.Slider',
    alias: 'widget.colorpickerslideralpha',
    cls: Ext.baseCSSPrefix + 'colorpicker-alpha',

    requires: [
        'Ext.XTemplate'
    ],

    gradientStyleTpl: Ext.create('Ext.XTemplate', // eslint-disable-next-line max-len
                                 'background: -moz-linear-gradient(top, rgba({r}, {g}, {b}, 1) 0%, rgba({r}, {g}, {b}, 0) 100%);' + /* FF3.6+ */ // eslint-disable-next-line max-len
            'background: -webkit-linear-gradient(top,rgba({r}, {g}, {b}, 1) 0%, rgba({r}, {g}, {b}, 0) 100%);' + /* Chrome10+,Safari5.1+ */ // eslint-disable-next-line max-len
            'background: -o-linear-gradient(top, rgba({r}, {g}, {b}, 1) 0%, rgba({r}, {g}, {b}, 0) 100%);' + /* Opera 11.10+ */ // eslint-disable-next-line max-len
            'background: -ms-linear-gradient(top, rgba({r}, {g}, {b}, 1) 0%, rgba({r}, {g}, {b}, 0) 100%);' + /* IE10+ */ // eslint-disable-next-line max-len
            'background: linear-gradient(to bottom, rgba({r}, {g}, {b}, 1) 0%, rgba({r}, {g}, {b}, 0) 100%);'     /* W3C */
    ),

    // Called via data binding whenever selectedColor.a changes; param is 0-100
    setAlpha: function(value) {
        var me = this,
            container = me.getDragContainer(),
            dragHandle = me.getDragHandle(),
            containerEl = container.bodyElement,
            containerHeight = containerEl.getHeight(),
            el, top;

        value = Math.max(value, 0);
        value = Math.min(value, 100);

        // User actively dragging? Skip event
        if (dragHandle.isDragging) {
            return;
        }

        // y-axis of slider with value 0-1 translates to reverse of "value"
        top = containerHeight * (1 - (value / 100));

        // Position dragger
        el = dragHandle.element;
        el.setStyle({
            top: top + 'px'
        });
    },

    // Called via data binding whenever selectedColor.h changes; hue param is 0-1
    setColor: function(color) {
        var me = this,
            container = me.getDragContainer(),
            hex, el;

        // set default value if selected color is set to null
        color = color === null ? { r: 0, g: 0, b: 0, h: 1, s: 1, v: 1, a: "1" } : color;

        // Determine HEX for new hue and set as background based on template
        hex = Ext.ux.colorpick.ColorUtils.rgb2hex(color.r, color.g, color.b);

        el = container.bodyElement;
        el.applyStyles(me.gradientStyleTpl.apply({ hex: hex, r: color.r, g: color.g, b: color.b }));
    }
});

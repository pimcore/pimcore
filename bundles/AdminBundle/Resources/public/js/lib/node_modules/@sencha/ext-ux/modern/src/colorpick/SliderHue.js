/**
 * Used for "Hue" slider.
 * @private
 */
Ext.define('Ext.ux.colorpick.SliderHue', {
    extend: 'Ext.ux.colorpick.Slider',
    alias: 'widget.colorpickersliderhue',
    cls: Ext.baseCSSPrefix + 'colorpicker-hue',

    afterRender: function() {
        var me = this,
            src = me.gradientUrl,
            el = me.el;

        me.callParent();

        if (!src) {
            // We do this trick to allow the Sass to calculate resource image path for
            // our package and pick up the proper image URL here.
            src = el.getStyle('background-image');
            src = src.substring(4, src.length - 1);  // strip off outer "url(...)"

            // In IE8 this path will have quotes around it
            if (src.indexOf('"') === 0) {
                src = src.substring(1, src.length - 1);
            }

            // Then remember it on our prototype for any subsequent instances.
            Ext.ux.colorpick.SliderHue.prototype.gradientUrl = src;
        }

        // Now clear that style because it will conflict with the background-color
        el.setStyle('background-image', 'none');

        // Create the image with the background PNG

        el = me.getDragContainer().el;
        el.createChild({
            tag: 'img',
            cls: Ext.baseCSSPrefix + 'colorpicker-hue-gradient',
            src: src
        });
    },

    // Called via data binding whenever selectedColor.h changes; hue param is 0-1
    setHue: function(hue) {

        var me = this,
            container = me.getDragContainer(),
            dragHandle = me.getDragHandle(),
            containerEl = container.bodyElement,
            containerHeight = containerEl.getHeight(),
            top, yRatio;

        hue = hue > 1 ? hue / 360 : hue;

        // User actively dragging? Skip event
        if (dragHandle.isDragging) {
            return;
        }

        // y-axis of slider with value 0-1 translates to reverse of "saturation"
        yRatio = 1 - hue;
        top = containerHeight * yRatio;

        // Position dragger
        dragHandle.element.setStyle({
            top: top + 'px'
        });
    }
});

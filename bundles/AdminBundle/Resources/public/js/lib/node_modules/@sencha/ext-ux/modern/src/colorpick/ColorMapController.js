/**
 * @private
 */
Ext.define('Ext.ux.colorpick.ColorMapController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.colorpickercolormapcontroller',

    requires: [
        'Ext.ux.colorpick.ColorUtils'
    ],

    init: function() {
        var me = this,
            colorMap = me.getView();

        // event handlers
        me.mon(colorMap.bodyElement, {
            mousedown: me.onMouseDown,
            mouseup: me.onMouseUp,
            mousemove: me.onMouseMove,
            scope: me
        });
    },

    // Fires when handle is dragged; propagates "handledrag" event on the ColorMap
    // with parameters "percentX" and "percentY", both 0-1, representing the handle
    // position on the color map, relative to the container
    onHandleDrag: function(componentDragger, e) {
        var me = this,
            container = me.getView(), // the Color Map
            dragHandle = container.down('#dragHandle').element,
            x = dragHandle.getX() - container.element.getX(),
            y = dragHandle.getY() - container.element.getY(),
            containerEl = container.bodyElement,
            containerWidth = containerEl.getWidth(),
            containerHeight = containerEl.getHeight(),
            xRatio = x / containerWidth,
            yRatio = y / containerHeight;

        // Adjust x/y ratios for dragger always being 1 pixel from the edge on the right
        if (xRatio > 0.99) {
            xRatio = 1;
        }

        if (yRatio > 0.99) {
            yRatio = 1;
        }

        // Adjust x/y ratios for dragger always being 0 pixel from the edge on the left
        if (xRatio < 0) {
            xRatio = 0;
        }

        if (yRatio < 0) {
            yRatio = 0;
        }

        container.fireEvent('handledrag', xRatio, yRatio);
    },

    // Whenever we mousedown over the colormap area
    onMouseDown: function(e) {
        var me = this;

        me.onMapClick(e);
        me.onHandleDrag();
        me.isDragging = true;
    },

    onMouseUp: function(e) {
        var me = this;

        me.onMapClick(e);
        me.onHandleDrag();
        me.isDragging = false;
    },

    onMouseMove: function(e) {
        var me = this;

        if (me.isDragging) {
            me.onMapClick(e);
            me.onHandleDrag();
        }
    },

    // Whenever the map is clicked (but not the drag handle) we need to position
    // the drag handle to the point of click
    onMapClick: function(e) {
        var me = this,
            container = me.getView(), // the Color Map
            dragHandle = container.down('#dragHandle'),
            cXY = container.element.getXY(),
            eXY = e.getXY(),
            left, top;

        left = eXY[0] - cXY[0];
        top = eXY[1] - cXY[1];

        dragHandle.element.setStyle({
            left: left + 'px',
            top: top + 'px'
        });

        e.preventDefault();
        me.onHandleDrag();
    },

    // Whenever the underlying binding data is changed we need to 
    // update position of the dragger.
    onColorBindingChanged: function(selectedColor) {
        var me = this,
            vm = me.getViewModel(),
            rgba = vm.get('selectedColor'),
            hsv,
            container = me.getView(), // the Color Map
            dragHandle = container.down('#dragHandle'),
            containerEl = container.bodyElement,
            containerWidth = containerEl.getWidth(),
            containerHeight = containerEl.getHeight(),
            xRatio,
            yRatio,
            left,
            top;

        // set default value if selected color is set to null
        rgba = rgba === null ? { r: 0, g: 0, b: 0, h: 1, s: 1, v: 1, a: "1" } : rgba;

        // Color map selection really only depends on saturation and value of the color
        hsv = Ext.ux.colorpick.ColorUtils.rgb2hsv(rgba.r, rgba.g, rgba.b);

        // x-axis of color map with value 0-1 translates to saturation
        xRatio = hsv.s;
        left = containerWidth * xRatio;

        // y-axis of color map with value 0-1 translates to reverse of "value"
        yRatio = 1 - hsv.v;
        top = containerHeight * yRatio;

        // Position dragger
        dragHandle.element.setStyle({
            left: left + 'px',
            top: top + 'px'
        });
    },

    // Whenever only Hue changes we can update the 
    // background color of the color map
    // Param "hue" has value of 0-1
    onHueBindingChanged: function(hue) {
        var me = this,
            fullColorRGB,
            hex;

        fullColorRGB = Ext.ux.colorpick.ColorUtils.hsv2rgb(hue, 1, 1);
        hex = Ext.ux.colorpick.ColorUtils.rgb2hex(fullColorRGB.r, fullColorRGB.g, fullColorRGB.b);
        me.getView().element.applyStyles({ 'background-color': '#' + hex });
    }
});

/**
 * @private
 */
Ext.define('Ext.ux.colorpick.SliderController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.colorpick-slidercontroller',

    getDragHandle: function() {
        return this.view.lookupReference('dragHandle');
    },

    getDragContainer: function() {
        return this.view.lookupReference('dragHandleContainer');
    },

    // Fires when handle is dragged; fires "handledrag" event on the slider
    // with parameter  "percentY" 0-1, representing the handle position on the slider
    // relative to the height
    onHandleDrag: function(e) {
        var me = this,
            view = me.getView(),
            container = me.getDragContainer(),
            dragHandle = me.getDragHandle(),
            containerEl = container.bodyElement,
            top = containerEl.getY(),
            y = e.getY() - containerEl.getY(),
            containerHeight = containerEl.getHeight(),
            yRatio = y / containerHeight;

        if (y >= 0 && y < containerHeight) {
            dragHandle.element.setY(y + top);
        }
        else {
            return;
        }

        // Adjust y ratio for dragger always being 1 pixel from the edge on the bottom
        if (yRatio > 0.99) {
            yRatio = 1;
        }

        e.preventDefault();
        view.fireEvent('handledrag', yRatio);
        dragHandle.el.repaint();
    },

    // Whenever we mousedown over the slider area
    onMouseDown: function(e) {
        var me = this,
            dragHandle = me.getDragHandle();

        // position drag handle accordingly
        dragHandle.isDragging = true;
        me.onHandleDrag(e);
    },

    onMouseMove: function(e) {
        var me = this,
            dragHandle = me.getDragHandle();

        if (dragHandle.isDragging) {
            me.onHandleDrag(e);
        }
    },

    onMouseUp: function(e) {
        var me = this,
            dragHandle = me.getDragHandle();

        if (dragHandle.isDragging) {
            me.onHandleDrag(e);
        }

        dragHandle.isDragging = false;
    }
});

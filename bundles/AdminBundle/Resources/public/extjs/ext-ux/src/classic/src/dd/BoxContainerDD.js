/**
 * A DragDrop implementation specialized for use with BoxReorderer.
 */
Ext.define('Ext.ux.dd.BoxContainerDD', {
    extend: 'Ext.dd.DD',

    /**
     * @method alignElWithMouse
     * @member Ext.dd.DD
     * @inheritdoc
     */
    alignElWithMouse: function(el, iPageX, iPageY) {
        var me = this,
            oCoord = me.getTargetCoord(iPageX, iPageY),
            x = oCoord.x,
            y = oCoord.y,
            fly = el.dom ? el : Ext.fly(el, '_dd'),
            aCoord, newLeft, newTop;

        if (!me.deltaSetXY) {
            aCoord = [
                Math.max(0, x),
                Math.max(0, y)
            ];
            fly.setXY(aCoord);
            newLeft = me.getLocalX(fly);
            newTop = fly.getLocalY();
            me.deltaSetXY = [newLeft - x, newTop - y];
        }
        else {
            me.setLocalXY(
                fly,
                Math.max(0, x + me.deltaSetXY[0]),
                Math.max(0, y + me.deltaSetXY[1])
            );
        }

        me.cachePosition(x, y);
        me.autoScroll(x, y, el.offsetHeight, el.offsetWidth);

        return oCoord;
    }
});

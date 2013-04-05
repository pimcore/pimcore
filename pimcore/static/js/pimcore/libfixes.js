
// fixes for composite field => getFieldValues() doesn't work
// read more here http://www.sencha.com/forum/showthread.php?99021&mode=linear
Ext.override(Ext.form.CompositeField, {
    bubble : Ext.Container.prototype.bubble,
    cascade : Ext.Container.prototype.cascade,
    findById : Ext.Container.prototype.findById,
    findByType : Ext.Container.prototype.findByType,
    find : Ext.Container.prototype.find,
    findBy : Ext.Container.prototype.findBy,
    get : Ext.Container.prototype.get
});


var _initComponent = Ext.form.CompositeField.prototype.initComponent;
Ext.override(Ext.form.CompositeField, {
    initComponent: function(){
        _initComponent.apply(this, arguments);
        this.innerCt.onwerCt = this;
    },
    bubble : Ext.Container.prototype.bubble,
    cascade : Ext.Container.prototype.cascade,
    findById : Ext.Container.prototype.findById,
    findByType : Ext.Container.prototype.findByType,
    find : Ext.Container.prototype.find,
    findBy : Ext.Container.prototype.findBy,
    get : Ext.Container.prototype.get,
    setValue : undefined
});


// fixes problem with Ext.Slider in IE9
// http://www.sencha.com/forum/showthread.php?141254-Ext.Slider-not-working-properly-in-IE9
Ext.override(Ext.dd.DragTracker, {
    onMouseMove: function (e, target) {
        if (this.active && Ext.isIE && !Ext.isIE9 && !e.browserEvent.button) {
            e.preventDefault();
            this.onMouseUp(e);
            return;
        }
        e.preventDefault();
        var xy = e.getXY(), s = this.startXY;
        this.lastXY = xy;
        if (!this.active) {
            if (Math.abs(s[0] - xy[0]) > this.tolerance || Math.abs(s[1] - xy[1]) > this.tolerance) {
                this.triggerStart(e);
            } else {
                return;
            }
        }
        this.fireEvent('mousemove', this, e);
        this.onDrag(e);
        this.fireEvent('drag', this, e);
    }
});


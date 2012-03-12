
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


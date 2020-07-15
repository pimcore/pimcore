/**
 * @class Ext.sparkline.Shape
 * @private
 */
Ext.define('Ext.sparkline.Shape', {
    constructor: function(target, id, type, args) {
        var me = this;

        me.target = target;
        me.id = id;
        me.type = type;
        me.args = args;
    },
    append: function() {
        this.target.appendShape(this);

        return this;
    }
});

/**
 * This class represents an infix (binary) operator.
 * @private
 */
Ext.define('Ext.parse.symbol.Infix', {
    extend: 'Ext.parse.Symbol',

    arity: 'binary',
    isBinary: true,

    defaultProperty: 'priority',

    led: function(left) {
        var me = this;

        me.lhs = left;
        me.rhs = me.parser.parseExpression(me.priority);
        // the next line is here in case this symbol already exists in the symbols table
        // and this function overrides that symbol
        me.arity = 'binary';
        me.isBinary = true;

        return me;
    }
});

/**
 * This class represents a prefix (unary) operator.
 * @private
 */
Ext.define('Ext.parse.symbol.Prefix', {
    extend: 'Ext.parse.Symbol',

    arity: 'unary',
    isUnary: true,

    priority: 70,

    nud: function() {
        var me = this;

        me.operand = me.parser.parseExpression(me.priority);
        // the next line is here in case this symbol already exists in the symbols table
        // and this function overrides that symbol
        me.arity = 'unary';
        me.isUnary = true;

        return me;
    }
});

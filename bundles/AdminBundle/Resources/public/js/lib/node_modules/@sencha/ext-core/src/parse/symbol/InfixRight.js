/**
 * This class represents an right-associative, infix (binary) operator.
 * @private
 */
Ext.define('Ext.parse.symbol.InfixRight', {
    extend: 'Ext.parse.symbol.Infix',

    led: function(left) {
        var me = this;

        me.lhs = left;
        me.rhs = me.parser.parseExpression(me.priority - 1);
        // the next line is here in case this symbol already exists in the symbols table
        // and this function overrides that symbol
        me.arity = 'binary';
        me.isBinary = true;

        return me;
    }
});

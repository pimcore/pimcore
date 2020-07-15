/**
 * This class implements the parenthesis operator.
 * @private
 */
Ext.define('Ext.parse.symbol.Paren', {
    extend: 'Ext.parse.Symbol',

    arity: 'binary',
    isBinary: true,

    priority: 80,

    led: function(left) {
        // Handles function call operator
        var me = this,
            args = [],
            parser = me.parser,
            id = left.id,
            type = left.arity;

        if (id !== '.' && id !== '[') {
            if ((type !== "unary" || id !== "function") &&
                 type !== "ident" && id !== "(" &&
                 id !== "&&" && id !== "||" && id !== "?") {
                parser.syntaxError(left.at, "Expected a variable name.");
            }
        }

        me.arity = 'invoke';
        me.isInvoke = true;
        me.operand = left;
        me.args = args;

        while (parser.token.id !== ')') {
            if (args.length) {
                parser.advance(',');
            }

            args.push(parser.parseExpression());
        }

        parser.advance(')');

        return me;
    },

    nud: function() {
        // Handles parenthesized expressions
        var parser = this.parser,
            ret = parser.parseExpression();

        parser.advance(")");

        return ret;
    }
});

/**
 * This class represents a constant in the parser.
 * @private
 */
Ext.define('Ext.parse.symbol.Constant', {
    extend: 'Ext.parse.Symbol',

    arity: 'literal',
    isLiteral: true,

    defaultProperty: 'value',

    constructor: function(id, config) {
        this.callParent([ id, config ]);

        this._value = this.value;
    },

    nud: function() {
        var me = this;

        // The value property gets smashed by the parser so restore it.
        me.value = me._value;
        // the next line is here in case this symbol already exists in the symbols table
        // and this function overrides that symbol
        me.arity = 'literal';
        me.isLiteral = true;

        return me;
    }
});

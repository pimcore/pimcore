/**
 * This class represents a symbol in the parser.
 * @private
 */
Ext.define('Ext.parse.Symbol', {
    priority: 0,

    /**
     * This property holds the name of the property to update when a config provided is
     * not an object (just a value).
     * @property {String} defaultProperty
     */

    constructor: function(id, config) {
        var me = this,
            defaultProperty = me.defaultProperty;

        if (config && typeof config === 'object') {
            Ext.apply(me, config);
        }
        else if (config !== undefined && defaultProperty) {
            me[defaultProperty] = config;
        }

        me.id = id;
    },

    //<debug>
    dump: function() {
        var me = this,
            ret = {
                at: me.at,
                arity: me.arity
            },
            i;

        if ('value' in me) {
            ret.value = me.value;
        }

        if (me.lhs) {
            ret.lhs = me.lhs.dump();
            ret.rhs = me.rhs.dump();
        }

        if (me.operand) {
            ret.operand = me.operand.dump();
        }

        if (me.args) {
            ret.args = [];

            for (i = 0; i < me.args.length; ++i) {
                ret.args.push(me.args[i].dump());
            }
        }

        return ret;
    },
    //</debug>

    /**
     * This abstract method is implemented by operators that follow their operand (like
     * a binary operator). When the symbol is encountered in an expression this method
     * is called. The name "led" stands for "left denotation".
     *
     * @param {Ext.parse.Symbol} left
     */
    led: function() {
        this.parser.syntaxError(this.at, 'Missing operator');
    },

    /**
     * This abstract method is implemented by operators that precede their operand (like
     * a unary operator). When the symbol is encountered in an expression this method
     * is called. The name "nud" stands for "null denotation".
     */
    nud: function() {
        this.parser.syntaxError(this.at, 'Undefined');
    },

    /**
     * This method updates this symbol given an additional config object. This is used
     * when a symbol is placed in multiple categories (such `infix` and `prefix`). The
     * `priority` is the most likely value to update, but also a `led` or `nud` method
     * may be provided to complete the symbol.
     *
     * @param {Object} config
     */
    update: function(config) {
        if (config && typeof config === 'object') {
            // eslint-disable-next-line vars-on-top
            var me = this,
                priority = config.priority,
                led = config.led,
                nud = config.nud;

            if (me.priority <= priority) {
                me.priority = priority;
            }

            if (led) {
                me.led = led;
            }

            if (nud) {
                me.nud = nud;
            }
        }
    }
});

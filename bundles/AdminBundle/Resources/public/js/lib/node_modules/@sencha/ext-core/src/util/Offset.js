/**
 * @private
 */
Ext.define('Ext.util.Offset', {

    /* Begin Definitions */

    statics: {
        fromObject: function(obj) {
            if (obj instanceof this) {
                return obj;
            }

            if (typeof obj === 'number') {
                return new this(obj, obj);
            }

            if (obj.length) {
                return new this(obj[0], obj[1]);
            }

            return new this(obj.x, obj.y);
        }
    },

    /* End Definitions */

    constructor: function(x, y) {
        this.x = (x != null && !isNaN(x)) ? x : 0;
        this.y = (y != null && !isNaN(y)) ? y : 0;

        return this;
    },

    copy: function() {
        return new Ext.util.Offset(this.x, this.y);
    },

    copyFrom: function(p) {
        this.x = p.x;
        this.y = p.y;
    },

    toString: function() {
        return "Offset[" + this.x + "," + this.y + "]";
    },

    equals: function(offset) {
        //<debug>
        if (!(offset instanceof this.statics())) {
            Ext.raise('Offset must be an instance of Ext.util.Offset');
        }
        //</debug>

        return (this.x === offset.x && this.y === offset.y);
    },

    add: function(offset) {
        //<debug>
        if (!(offset instanceof this.statics())) {
            Ext.raise('Offset must be an instance of Ext.util.Offset');
        }
        //</debug>

        this.x += offset.x;
        this.y += offset.y;
    },

    round: function(to) {
        var factor;

        if (!isNaN(to)) {
            factor = Math.pow(10, to);

            this.x = Math.round(this.x * factor) / factor;
            this.y = Math.round(this.y * factor) / factor;
        }
        else {
            this.x = Math.round(this.x);
            this.y = Math.round(this.y);
        }
    },

    isZero: function() {
        return this.x === 0 && this.y === 0;
    }
});

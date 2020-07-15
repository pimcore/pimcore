/**
 * @private
 */
Ext.define('Ext.ProgressBase', {
    mixinId: 'progressbase',

    config: {
        /**
         * @cfg {Number} [value=0]
         * A floating point value between 0 and 1 (e.g., .5)
         */
        value: 0,

        /**
         * @cfg {String/Ext.XTemplate} [textTpl]
         * A template used to create this ProgressBar's background text given two values:
         *
         * - `value` - The raw progress value between 0 and 1
         * - `percent` - The value as a percentage between 0 and 100
         */
        textTpl: null
    },

    applyTextTpl: function(textTpl) {
        if (!textTpl.isTemplate) {
            textTpl = new Ext.XTemplate(textTpl);
        }

        return textTpl;
    },

    applyValue: function(value) {
        return value || 0;
    }
});

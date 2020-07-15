/**
 * Validates that the passed value is not `null` or `undefined`.
 * @since 6.5.0
 */
Ext.define('Ext.data.validator.NotNull', {
    extend: 'Ext.data.validator.Presence',
    alias: 'data.validator.notnull',

    type: 'notnull',

    allowEmpty: true
});

/**
 * Data reader class to create an Array of {@link Ext.data.Model} objects from an Array.
 * Each element of that Array represents a row of data fields. The
 * fields are pulled into a Record object using as a subscript, the `mapping` property
 * of the field definition if it exists, or the field's ordinal position in the definition.
 *
 * ##Example code:
 *
 *      Employee = Ext.define('Employee', {
 *          extend: 'Ext.data.Model',
 *          fields: [
 *              'id',
 *              // "mapping" only needed if an "id" field is present which
 *              // precludes using the ordinal position as the index.
 *              { name: 'name', mapping: 1 },
 *              { name: 'occupation', mapping: 2 }
 *          ]
 *      });
 *
 *       var myReader = new Ext.data.reader.Array({
 *            model: 'Employee'
 *       }, Employee);
 *
 * This would consume an Array like this:
 *
 *      [ [1, 'Bill', 'Gardener'], [2, 'Ben', 'Horticulturalist'] ]
 *
 */
Ext.define('Ext.data.reader.Array', {
    extend: 'Ext.data.reader.Json',
    alternateClassName: 'Ext.data.ArrayReader',
    alias: 'reader.array',

    // For Array Reader, methods in the base which use these properties must not see the defaults
    config: {

        /**
         * @cfg totalProperty
         * @inheritdoc
         */
        totalProperty: undefined,

        /**
         * @cfg successProperty
         * @inheritdoc
         */
        successProperty: undefined

        /**
         * @cfg {Boolean} preserveRawData
         * @hide
         */
    },

    /**
     * @method constructor
     * @constructor
     * Create a new ArrayReader
     * @param {Object} meta Metadata configuration options.
     */

    createFieldAccessor: function(field) {
        // In the absence of a mapping property, use the original ordinal position
        // at which the Model inserted the field into its collection.
        var oldMap = field.mapping,
            index = field.hasMapping() ? oldMap : field.ordinal,
            result;

        // Temporarily overwrite the mapping and use the superclass method.
        field.mapping = index;
        result = this.callParent(arguments);
        field.mapping = oldMap;

        return result;
    },

    getModelData: function(raw) {
        // Can't preserve raw data here
        return {};
    }
});

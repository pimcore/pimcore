/**
 * **This class is never created directly. It should be constructed through associations
 * in `Ext.data.Model`.**
 *
 * Associations enable you to express relationships between different {@link Ext.data.Model Models}.
 * Consider an ecommerce system where Users can place Orders - there is a one to many relationship
 * between these Models, one user can have many orders (including 0 orders). Here is what a sample
 * implementation of this association could look like. This example will be referred to in the
 * following sections.
 *
 *     Ext.define('User', {
 *         extend: 'Ext.data.Model',
 *         fields: [{
 *             name: 'id',
 *             type: 'int'
 *         }, 'name']
 *     });
 *
 *     Ext.define('Order', {
 *         extend: 'Ext.data.Model',
 *         fields: [{
 *             name: 'id',
 *             type: 'int'
 *         }, {
 *             name: 'userId',
 *             type: 'int',
 *             reference: 'User'
 *         }]
 *     });
 *
 * # Association Types
 *
 * Assocations can describe relationships in 3 ways:
 *
 * ## Many To One
 *
 * A single entity (`A`) has a relationship with many (`B`) entities. An example of this is
 * an ecommerce system `User` can have many `Order` entities.
 *
 * This can be defined using `Ext.data.schema.ManyToOne` for keyed associations, or 
 * `Ext.data.schema.HasMany` for keyless associations.
 * 
 * ## One To One
 *
 * A less common form of Many To One, a single entity (`A`) has a relationship with at most 1 entity
 * (`B`). This is often used when partitioning data. For example a `User` may have a single
 * `UserInfo` object that stores extra metadata about the user.
 *
 * This can be defined using `Ext.data.schema.OneToOne` for keyed associations, or 
 * `Ext.data.schema.HasOne` for keyless associations.
 * 
 * ## Many To Many
 *
 * An entity (`A`) may have a have a relationship with many (`B`) entities. That (`B`) entity may
 * also have a relationship with many `A` entities. For example a single `Student` can have many
 * `Subject` entities and a single `Subject` can have many `Student` entities.
 *
 * This can be defined using `Ext.data.schema.ManyToMany`. Many To Many relationships are read-only
 * unless used with a `Ext.data.Session`.
 *     
 *
 * # Keyed vs Keyless Associations
 *
 * Associations can be declared in 2 ways, which are outlined below.
 *
 * ## Keyed associations
 *
 * A keyed association relies on a field in the model matching the id of another model. Membership
 * is driven by the key. This is the type of relationship that is typically used in a relational
 * database. This is declared using the ||reference|| configuration on a model field. An example
 * of this can be seen  above for `User/Order`.
 *
 * # Keyless associations
 *
 * A keyless association relies on data hierarchy to determine membership. Items are members because
 * they are contained by another entity. This type of relationship is common with NoSQL databases.
 * formats. A simple example definition using `User/Order`:
 *
 *     Ext.define('User', {
 *         extend: 'Ext.data.Model',
 *         fields: [{
 *             name: 'id',
 *             type: 'int'
 *         }, 'name'],
 *         hasMany: 'Order'
 *     });
 *
 *     Ext.define('Order', {
 *         extend: 'Ext.data.Model',
 *         fields: [{
 *             name: 'id',
 *             type: 'int'
 *         }]
 *     });
 *
 * # Advantages of Associations
 * 
 * Assocations make it easier to work with Models that share a connection. Some of the main
 * functionality includes:
 *
 * ## Generated Accessors/Setters
 *
 * Associated models will automatically generate named methods that allow for accessing the
 * associated data. The names for these are created using a {@link Ext.data.schema.Schema Schema},
 * to provide a consistent and predictable naming structure.
 *
 * Using the example code above, there will be 3 generated methods:
 * + `User` will have an `orders()` function that returns a `Ext.data.Store` of`Orders`. 
 * + `Order` will have a `getUser` method which will return a `User` Model.
 * + `Order` will have a `setUser` method that will accept a `User` model or a key value.
 *
 * ## Nested Loading
 *
 * Nested loading is the ability to load hierarchical associated data from a remote source within
 * a single request. In the following example, each `User` in the `users` store has an `orders`
 * store. Each `orders` store is populated with `Order` models read from the request. Each `Order`
 * model also has a reference back to the appropriate `User`.
 *
 *     // Sample JSON data returned by /Users
 *     [{
 *         "id": 1,
 *         "name": "User Foo",
 *         "orders": [{
 *             "id": 101,
 *             "userId": 1
 *         }, {
 *             "id": 102,
 *             "userId": 1
 *         }, {
 *             "id": 103,
 *             "userId": 1
 *         }]
 *     }, {
 *         "id": 2,
 *         "name": "User Bar",
 *         "orders": [{
 *             "id": 201,
 *             "userId": 2
 *         }, {
 *             "id": 202,
 *             "userId": 2
 *         }]
 *     }]
 *
 *     // Application code
 *     var users = new Ext.data.Store({
 *         model: 'User',
 *         proxy: {
 *             type: 'ajax',
 *             url: '/Users'
 *         }
 *     });
 *     users.load(function() {
 *         var user1 = users.first(),
 *             user2 = users.last(),
 *             orders1 = user1.orders(),
 *             orders2 = user2.orders();
 *
 *         // 3 orders, same reference back to user1
 *         console.log(orders1.getCount(), orders1.first().getUser() === user1);
 *         // 2 orders, same reference back to user2
 *         console.log(orders2.getCount(), orders2.first().getUser() === user2);
 *     });
 *
 * ## Binding
 *
 * Data binding using {@link Ext.app.ViewModel ViewModels} have functionality to be able
 * to recognize associated data as part of a bind statement. For example:
 * + `{user.orders}` binds to the orders store for a user.
 * + `{order.user.name}` binds to the name of the user taken from the order.
 * 
 *
 * # Association Concepts
 *
 * ## Roles
 *
 * The role is used to determine generated names for an association. By default, the role is
 * generated from either the field name (in a keyed association) or the model name. This naming
 * follows a pattern defined by the `Ext.data.schema.Namer`. To change a specific instance,
 * an explicit role can be specified:
 *
 *     Ext.define('Thread', {
 *         extend: 'Ext.data.Model',
 *         fields: ['id', 'title']
 *     });
 *
 *     Ext.define('Post', {
 *         extend: 'Ext.data.Model',
 *         fields: ['id', 'content', {
 *             name: 'threadId',
 *             reference: {
 *                 type: 'Thread',
 *                 role: 'discussion',
 *                 inverse: 'comments'
 *                 
 *             }
 *         }]
 *     });
 *
 * In the above example, the `Thread` will be decorated with a `comments` method that returns
 * the store. The `Post` will be decorated with `getDiscussion/setDiscussion` methods.
 *
 * ## Generated Methods
 *
 * Associations generate methods to allow reading and manipulation on associated data. 
 * 
 * On records that have a "to many" relationship, a single methods that returns a `Ext.data.Store`
 * is created.  See {@link #storeGetter}. On records that have a "to one" relationship, 2 methods
 * are generated, a {@link #recordGetter getter} and a {@link #recordSetter setter}.
 *
 * ## Reflexive
 *
 * Associations are reflexive. By declaring one "side" of the relationship, the other is
 * automatically setup. In the example below, there is no code in the `Thread` entity regarding
 * the association, however by virtue of the declaration in post, `Thread` is decorated with the
 * appropriate infrastructure to participate in the association.
 *
 *     Ext.define('Thread', {
 *         extend: 'Ext.data.Model',
 *         fields: ['id', 'title']
 *     });
 *
 *     Ext.define('Post', {
 *         extend: 'Ext.data.Model',
 *         fields: ['id', 'content', {
 *             name: 'threadId',
 *             reference: 'Thread'
 *         }]
 *     });
 *
 * ## Naming
 *
 * Referring to model names in associations depends on their {@link Ext.data.Model#entityName}. See
 * the "Relative Naming" section in the `Ext.data.schema.Schema` documentation.
 */
Ext.define('Ext.data.schema.Association', {
    requires: [
        'Ext.data.schema.Role'
    ],

    isOneToOne: false,
    isManyToOne: false,
    isManyToMany: false,

    /**
     * @cfg {String} associationKey
     * The name of the property in the data to read the association from. Defaults to the
     * name of the associated model.
     */

    /**
     * @method storeGetter
     * **This is not a real method, it is placeholder documentation for a generated method on
     * a `Ext.data.Model`.**
     *
     * Gets a store configured with the model of the "many" record.
     * @param {Object/Function} [options] The options for the getter, or a callback function
     * to execute. If specified as a function, it will act as the `callback` option.
     *
     * @param {Boolean} [options.reload] `true` to force the store to reload from the server.
     *
     * @param {Object} [options.scope] The `this` reference for the callback.
     * Defaults to the record.
     * 
     * @param {Function} [options.success] A function to execute when the store loads successfully.
     * If the store has already loaded, this will be called immediately and the `Operation` will be
     * `null`. The success is passed the following parameters:
     * @param {Ext.data.Store} [options.success.store] The store.
     * @param {Ext.data.operation.Operation} [options.success.operation] The operation. `null`
     * if no load occurred.
     *
     * @param {Function} [options.failure] A function to execute when the store load fails.
     * If the store has already loaded, this will not be called.
     * The failure is passed the following parameters:
     * @param {Ext.data.Store} [options.failure.store] The store.
     * @param {Ext.data.operation.Operation} [options.failure.operation] The operation
     * 
     * @param {Function} [options.callback] A function to execute when the store loads, whether
     * it is successful or failed. If the store has already loaded, this will be called immediately
     * and the `Operation` will be `null`. The callback is passed the following parameters:
     * @param {Ext.data.Store} [options.callback.store] The store.
     * @param {Ext.data.operation.Operation} [options.callback.operation] The operation. `null`
     * if no load occurred.
     * @param {Boolean} [options.callback.success] `true` if the load was successful. If already
     * loaded this will always be true.
     *
     * @param {Object} [scope] The `this` reference for the callback. Defaults to the record.
     *
     * @return {Ext.data.Store} The store.
     */

    /**
     * @method recordGetter
     * **This is not a real method, it is placeholder documentation for a generated method on
     * a `Ext.data.Model`.**
     *
     * Gets a model of the "one" type.
     * @param {Object/Function} [options] The options for the getter, or a callback function
     * to execute. If specified as a function, it will act as the `callback` option.
     *
     * @param {Boolean} [options.reload] `true` to force the record to reload from the server.
     *
     * @param {Object} [options.scope] The `this` reference for the callback.
     * Defaults to the record.
     * 
     * @param {Function} [options.success] A function to execute when the record loads successfully.
     * If the record has already loaded, this will be called immediately and the `Operation` will be
     * `null`. The success is passed the following parameters:
     * @param {Ext.data.Model} [options.success.record] The record.
     * @param {Ext.data.operation.Operation} [options.success.operation] The operation. `null`
     * if no load occurred.
     *
     * @param {Function} [options.failure] A function to execute when the record load fails.
     * If the record has already loaded, this will not be called.
     * The failure is passed the following parameters:
     * @param {Ext.data.Model} [options.failure.record] The record.
     * @param {Ext.data.operation.Operation} [options.failure.operation] The operation
     * 
     * @param {Function} [options.callback] A function to execute when the record loads, whether
     * it is successful or failed. If the record has already loaded, this will be called immediately
     * and the `Operation` will be `null`. The callback is passed the following parameters:
     * @param {Ext.data.Model} [options.callback.record] The record.
     * @param {Ext.data.operation.Operation} [options.callback.operation] The operation. `null`
     * if no load occurred.
     * @param {Boolean} [options.callback.success] `true` if the load was successful. If already
     * loaded this will always be true.
     * 
     * @param {Object} [scope] The `this` reference for the callback. Defaults to the record.
     * @return {Ext.data.Model} The record. `null` if the reference has been previously specified
     * as empty.
     */

    /**
     * @method recordSetter **This is not a real method, it is placeholder documentation
     * for a generated method on a `Ext.data.Model`.**
     *
     * Sets a model of the "one" type.
     * @param {Ext.data.Model/Object} value The value to set. This can be a model instance,
     * a key value (if a keyed association) or `null` to clear the value.
     *
     * @param {Object/Function} [options] Options to handle callback. If specified as
     * a function, it will act as the `callback` option. If specified as an object, the params
     * are the same as {@link Ext.data.Model#save}. If  options is specified,
     * {@link Ext.data.Model#save} will be called on this record.
     */

    /**
     * @cfg {String} name
     * The name of this association.
     */

    /**
     * @property {Object} owner
     * Points at either `left` or `right` objects if one is the owning party in this
     * association or is `null` if there is no owner.
     * @readonly
     */
    owner: null,

    /**
     * @property {Ext.Class} definedBy
     * @readonly
     */

    /**
     * @property {Ext.data.field.Field} field
     * @readonly
     */
    field: null,

    /**
     * @property {Ext.data.schema.Schema} schema
     * @readonly
     */

    /**
     * @property {Boolean} nullable
     * @readonly
     */

    /**
     * @property {Ext.data.schema.Role} left
     * @readonly
     */

    /**
     * @property {Ext.data.schema.Role} right
     * @readonly
     */

    constructor: function(config) {
        var me = this,
            left, right;

        Ext.apply(me, config);

        me.left = left = new me.Left(me, me.left);
        me.right = right = new me.Right(me, me.right);

        left.inverse = right;
        right.inverse = left;
    },

    hasField: function() {
        return !!this.field;
    },

    getFieldName: function() {
        var field = this.field;

        return field ? field.name : '';
    }
});

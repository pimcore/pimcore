/**
 * This class manages arbitrary data and its relationship to data models. Instances of
 * `ViewModel` are associated with some `Component` and then used by their child items
 * for the purposes of Data Binding.
 * 
 * # Binding
 * 
 * The most commonly used aspect of a `ViewModel` is the `bind` method. This method takes
 * a "bind descriptor" (see below) and a callback to call when the data indicated by the
 * bind descriptor either becomes available or changes.
 *
 * The `bind` method, based on the bind descriptor given, will return different types of
 * "binding" objects. These objects maintain the connection between the requested data and
 * the callback. Bindings ultimately derive from `{@link Ext.app.bind.BaseBinding}`
 * which provides several methods to help manage the binding.
 *
 * Perhaps the most important method is `destroy`. When the binding is no longer needed
 * it is important to remember to `destroy` it. Leaking bindings can cause performance
 * problems or worse when callbacks are called at unexpected times.
 *
 * The types of bindings produced by `bind` are:
 *
 *   * `{@link Ext.app.bind.Binding}`
 *   * `{@link Ext.app.bind.Multi}`
 *   * `{@link Ext.app.bind.TemplateBinding}`
 *
 * ## Bind Descriptors
 * 
 * A "bind descriptor" is a value (a String, an Object or an array of these) that describe
 * the desired data. Any piece of data in the `ViewModel` can be described by a bind
 * descriptor.
 * 
 * ### Textual Bind Descriptors
 * 
 * The simplest and most common form of bind descriptors are strings that look like an
 * `Ext.Template` containing text and tokens surrounded by "{}" with dot notation inside
 * to traverse objects and their properties.
 * 
 * For example:
 * 
 *   * `'Hello {user.name}!'`
 *   * `'You have selected "{selectedItem.text}".'`
 *   * `'{!isDisabled}'`
 *   * `'{a > b ? "Bigger" : "Smaller"}'`
 *   * `'{user.groups}'`
 *
 * All except the last are `{@link Ext.app.bind.TemplateBinding template bindings}`
 * which use the familiar `Ext.Template` syntax with some slight differences. For more on
 * templates see `{@link Ext.app.bind.Template}`.
 *
 * The last descriptor is called a "direct bind descriptor". This special form of
 * bind maps one-to-one to some piece of data in the `ViewModel` and is managed by the
 * `{@link Ext.app.bind.Binding}` class.
 *
 * #### Two-Way Descriptors
 *
 * A direct bind descriptor may be able to write back a value to the `ViewModel` as well
 * as retrieve one. When this is the case, they are said to be "two-way". For example:
 *
 *      var binding = viewModel.bind('{s}', function(s) { console.log('s=' + s); });
 *
 *      binding.setValue('abc');
 *
 * Direct use of `ViewModel` in this way is not commonly needed because `Ext.Component`
 * automates this process. For example, a `textfield` component understands when it is
 * given a "two-way" binding and automatically synchronizes its value bidirectionally using
 * the above technique. For example:
 *
 *      Ext.widget({
 *          items: [{
 *              xtype: 'textfield',
 *              bind: '{s}'  // a two-way / direct bind descriptor
 *          }]
 *      });
 *
 * ### Object and Array Descriptors / Multi-Bind
 *
 * With two exceptions (see below) an Object is interpreted as a "shape" to produce by
 * treating each of its properties as individual bind descriptors. An object of the same
 * shape is passed as the value of the bind except that each property is populated with
 * the appropriate value. Of course, this definition is recursive, so these properties
 * may also be objects.
 *
 * For example:
 *
 *      viewModel.bind({
 *              x: '{x}',
 *              foo: {
 *                  bar: 'Hello {foo.bar}'
 *              }
 *          },
 *          function (obj) {
 *              //  obj = {
 *              //      x: 42,
 *              //      foo: {
 *              //          bar: 'Hello foobar'
 *              //      }
 *              //  }
 *          });
 *
 * Arrays are handled in the same way. Each element of the array is considered a bind
 * descriptor (recursively) and the value produced for the binding is an array with each
 * element set to the bound property.
 *
 * ### Bind Options
 *
 * One exception to the "object is a multi-bind" rule is when that object contains a
 * `bindTo` property. When an object contains a `bindTo` property the object is understood
 * to contain bind options and the value of `bindTo` is considered the actual bind
 * descriptor.
 *
 * For example:
 *
 *      viewModel.bind({
 *              bindTo: '{x}',
 *              single: true
 *          },
 *          function (x) {
 *              console.log('x: ' + x); // only called once
 *          });
 *
 * The available bind options depend on the type of binding, but since all bindings
 * derive from `{@link Ext.app.bind.BaseBinding}` its options are always applicable.
 * For a list of the other types of bindings, see above.
 *
 * #### Deep Binding
 *
 * When a direct bind is made and the bound property is an object, by default the binding
 * callback is only called when that reference changes. This is the most efficient way to
 * understand a bind of this type, but sometimes you may need to be notified if any of the
 * properties of that object change.
 *
 * To do this, we create a "deep bind":
 *
 *      viewModel.bind({
 *              bindTo: '{someObject}',
 *              deep: true
 *          },
 *          function (someObject) {
 *              // called when reference changes or *any* property changes
 *          });
 *
 * #### Binding Timings
 *
 * The `ViewModel` has a {@link #scheduler} attached that is used to coordinate the firing of
 * bindings.
 * It serves 2 main purposes:
 * - To coordinate dependencies between bindings. This means bindings will be fired in an order
 * such that the any dependencies for a binding are fired before the binding itself.
 * - To batch binding firings. The scheduler runs on a short timer, so the following code will
 * only trigger a single binding (the last), the changes in between will never be triggered.
 *
 * Example:
 *
 *     viewModel.bind('{val}', function(v) {
 *         console.log(v);
 *     });
 *     viewModel.set('val', 1);
 *     viewModel.set('val', 2);
 *     viewModel.set('val', 3);
 *     viewModel.set('val', 4);
 *
 * The `ViewModel` can be forced to process by calling `{@link #notify}`, which will force the
 * scheduler to run immediately in the current state.
 * 
 *     viewModel.bind('{val}', function(v) {
 *         console.log(v);
 *     });
 *     viewModel.set('val', 1);
 *     viewModel.notify();
 *     viewModel.set('val', 2);
 *     viewModel.notify();
 *     viewModel.set('val', 3);
 *     viewModel.notify();
 *     viewModel.set('val', 4);
 *     viewModel.notify();
 *  
 *
 * #### Models, Stores and Associations
 *
 * A {@link Ext.data.Session Session} manages model instances and their associations.
 * The `ViewModel` may be used with or without a `Session`. When a `Session` is attached, the
 * `ViewModel` will always consult the `Session` to ask about records and stores. The `Session`
 * ensures that only a single instance of each model Type/Id combination is created. This is 
 * important when tracking changes in models so that we always have the same reference.
 *
 * A `ViewModel` provides functionality to easily consume the built in data package types
 * {@link Ext.data.Model} and {@link Ext.data.Store}, as well as their associations.
 *
 * ### Model Links
 *
 * A model can be described declaratively using {@link #links}. In the example code below,
 * We ask the `ViewModel` to construct a record of type `User` with `id: 17`. The model will be
 * loaded from the server and the bindings will trigger once the load has completed. Similarly,
 * we could also attach a model instance to the `ViewModel` data directly.
 *
 *     Ext.define('MyApp.model.User', {
 *         extend: 'Ext.data.Model',
 *         fields: ['name']
 *     });
 *     
 *     var rec = new MyApp.model.User({
 *         id: 12,
 *         name: 'Foo'
 *     });
 *     
 *     var viewModel = new Ext.app.ViewModel({
 *         links: {
 *             theUser: {
 *                 type: 'User',
 *                 id: 17
 *             }
 *         },
 *         data: {
 *             otherUser: rec
 *         }
 *     });
 *     viewModel.bind('{theUser.name}', function(v) {
 *         console.log(v);
 *     });
 *     viewModel.bind('{otherUser.name}', function(v) {
 *         console.log(v);
 *     });
 *
 * ### Model Fields
 *
 * Bindings have the functionality to inspect the parent values and resolve the underlying
 * value dynamically. This behavior allows model fields to be interrogated as part of a binding.
 *
 *     Ext.define('MyApp.model.User', {
 *         extend: 'Ext.data.Model',
 *         fields: ['name', 'age']
 *     });
 *
 *     var viewModel = new Ext.app.ViewModel({
 *         links: {
 *             theUser: {
 *                 type: 'User',
 *                 id: 22
 *             }
 *         }
 *     });
 *
 *     // Server responds with:
 *     {
 *         "id": 22,
 *         "name": "Foo",
 *         "age": 100
 *     }
 *
 *     viewModel.bind('Hello {name}, you are {age} years old', function(v) {
 *         console.log(v);
 *     });
 *
 * ### Record Properties
 *
 * It is possible to bind to the certain state properties of a record. The available options are:
 * - `{@link Ext.data.Model#property-dirty dirty}`
 * - `{@link Ext.data.Model#property-phantom phantom}`
 * - `{@link Ext.data.Model#method-isValid valid}`
 *
 * Example usage:
 *
 *     Ext.define('MyApp.model.User', {
 *         extend: 'Ext.data.Model',
 *         fields: [{
 *             name: 'name',
 *             validators: 'presence'
 *         }, {
 *             name: 'age',
 *             validators: {
 *                type: 'range',
 *                 min: 0
 *              }
 *         }]
 *     });
 *
 *     var rec = new MyApp.model.User();
 *
 *     var viewModel = new Ext.app.ViewModel({
 *         data: {
 *             theUser: rec
 *         }
 *     });
 *
 *     viewModel.bind({
 *         dirty: '{theUser.dirty}',
 *         phantom: '{theUser.phantom}',
 *         valid: '{theUser.valid}'
 *     }, function(v) {
 *         console.log(v.dirty, v.valid);
 *     });
 *
 *     rec.set('name', 'Foo');
 *     viewModel.notify(); // dirty, not valid
 *     rec.set('age', 20);
 *     viewModel.notify(); // dirty, valid
 *     rec.reject();
 *     viewModel.notify(); // not dirty, not valid
 *
 * ### Advanced Record Binding
 *
 * For accessing other record information that is not exposed by the binding API, formulas
 * can be used to achieve more advanced operations:
 *
 *     Ext.define('MyApp.model.User', {
 *         extend: 'Ext.data.Model',
 *         fields: ['name', 'age']
 *     });
 *
 *     var rec = new MyApp.model.User();
 *
 *     var viewModel = new Ext.app.ViewModel({
 *         formulas: {
 *             isNameModified: {
 *                 bind: {
 *                     bindTo: '{theUser}',
 *                     deep: true
 *                 },
 *                 get: function(rec) {
 *                     return rec.isModified('name');
 *                 }
 *             }
 *         },
 *         data: {
 *             theUser: rec
 *         }
 *     });
 *
 *     viewModel.bind('{isNameModified}', function(modified) {
 *         console.log(modified);
 *     });
 *     rec.set('name', 'Foo');
 *
 * ### Associations
 *
 * In the same way as fields, the bindings can also traverse associations in a bind statement.
 * The `ViewModel` will handle the asynchronous loading of data and only present the value once
 * the full path has been loaded. For more information on associations see
 * {@link Ext.data.schema.OneToOne OneToOne} and {@link Ext.data.schema.ManyToOne ManyToOne}
 * associations.
 *
 *     Ext.define('User', {
 *         extend: 'Ext.data.Model',
 *         fields: ['name']
 *     });
 *
 *     Ext.define('Order', {
 *         extend: 'Ext.data.Model',
 *         fields: ['date', {
 *             name: 'userId',
 *             reference: 'User'
 *         }]
 *     });
 *
 *     Ext.define('OrderItem', {
 *         extend: 'Ext.data.Model',
 *         fields: ['price', 'qty', {
 *             name: 'orderId',
 *             reference: 'Order'
 *         }]
 *     });
 *
 *     var viewModel = new Ext.app.ViewModel({
 *         links: {
 *             orderItem: {
 *                 type: 'OrderItem',
 *                 id: 13
 *             }
 *         }
 *     });
 *     // The viewmodel will handle both ways of loading the data:
 *     // a) If the data is loaded inline in a nested fashion it will
 *     //    not make requests for extra data
 *     // b) Only loading a single model at a time. So the Order will be loaded once
 *     //    the OrderItem returns. The User will be loaded once the Order loads.
 *     viewModel.bind('{orderItem.order.user.name}', function(name) {
 *         console.log(name);
 *     });
 *
 * ### Stores
 *
 * Stores can be created as part of the `ViewModel` definition. The definitions are processed
 * like bindings which allows for very powerful dynamic functionality.
 *
 * It is important to ensure that you name viewModel's data keys uniquely. If data is not named  
 * uniquely, binds and formulas may receive information from an unintended data source.  
 * This applies to keys in the viewModel's data block, stores, and links configs.
 *
 *     var viewModel = new Ext.app.ViewModel({
 *         stores: {
 *             users: {
 *                 model: 'User',
 *                 autoLoad: true,
 *                 filters: [{
 *                     property: 'createdDate',
 *                     value: '{createdFilter}',
 *                     operator: '>'
 *                 }]
 *             }
 *         }
 *     });
 *     // Later on in our code, we set the date so that the store is created.
 *     viewModel.set('createdFilter', Ext.Date.subtract(new Date(), Ext.Date.DAY, 7));
 *
 * See {@link #stores} for more detail.
 *
 * ### Store Properties
 *
 * It is possible to bind to the certain state properties of the store. The available options are:
 * - `{@link Ext.data.Store#method-getCount count}`
 * - `{@link Ext.data.Store#method-first}`
 * - `{@link Ext.data.Store#method-last}`
 * - `{@link Ext.data.Store#method-hasPendingLoad loading}`
 * - `{@link Ext.data.Store#method-getTotalCount totalCount}`
 *
 * Example:
 *
 *     Ext.define('MyApp.model.User', {
 *         extend: 'Ext.data.Model',
 *         fields: ['name']
 *     });
 *
 *     var viewModel = new Ext.app.ViewModel({
 *         stores: {
 *             users: {
 *                 model: 'MyApp.model.User',
 *                 data: [{
 *                     name: 'Foo'
 *                 }, {
 *                     name: 'Bar'
 *                 }]
 *             }
 *         }
 *     });
 *
 *     viewModel.bind('{users.first}', function(first) {
 *         console.log(first ? first.get('name') : 'Nobody');
 *     });
 *
 *     var timer = Ext.interval(function() {
 *         var store = viewModel.getStore('users');
 *         if (store.getCount()) {
 *             store.removeAt(0);
 *         } else {
 *             Ext.uninterval(timer);
 *         }
 *     }, 100);
 *
 * ### Advanced Store Binding
 *
 * For accessing other store information that is not exposed by the binding API, formulas
 * can be used to achieve more advanced operations:
 *
 *     Ext.define('MyApp.model.User', {
 *         extend: 'Ext.data.Model',
 *         fields: ['name', 'score']
 *     });
 *
 *     var viewModel = new Ext.app.ViewModel({
 *         stores: {
 *             users: {
 *                 model: 'MyApp.model.User',
 *                 data: [{
 *                     name: 'Foo',
 *                     score: 100
 *                 }, {
 *                     name: 'Bar',
 *                     score: 350
 *                 }]
 *             }
 *         },
 *         formulas: {
 *             totalScore: {
 *                 bind: {
 *                     bindTo: '{users}',
 *                     deep: true
 *                 },
 *                 get: function(store) {
 *                     return store.sum('score');
 *                 }
 *             }
 *         }
 *     });
 *
 *     viewModel.bind('{totalScore}', function(score) {
 *         console.log(score);
 *     });
 *
 *     viewModel.notify();
 *     viewModel.getStore('users').removeAll();
 *
 * #### Formulas
 *
 * Formulas allow for calculated `ViewModel` data values. The dependencies for these formulas
 * are automatically determined so that the formula will not be processed until the required
 * data is present.
 *
 *     var viewModel = new Ext.app.ViewModel({
 *         formulas: {
 *             fullName: function(get) {
 *                 return get('firstName') + ' ' + get('lastName');
 *             }
 *         },
 *         data: {firstName: 'John', lastName: 'Smith'}
 *     });
 *
 *     viewModel.bind('{fullName}', function(v) {
 *         console.log(v);
 *     });
 *
 * See {@link #formulas} for more detail.
 *
 * #### Inheriting Data With Nesting
 *
 * ViewModels can have a {@link #parent} which allows values to be consumed from
 * a shared base. These values that are available from the {@link #parent} are not copied,
 * rather they are "inherited" in a similar fashion to a javascript closure scope chain. 
 * This is demonstrated in the example below:
 *
 *     var parent = new Ext.app.ViewModel({
 *         data: {
 *             foo: 3
 *         }
 *     });
 *     var child = new Ext.app.ViewModel({
 *         parent: parent
 *     });
 *
 * This is analogous to the following javascript closure:
 *
 *     var foo = 3;
 *     Ext.Ajax.request({
 *         success: function() {
 *             // foo is available here
 *         }
 *     });
 *
 * ### Climbing/Inheriting
 *
 * In line with the above, the default behaviour when setting the value of a child ViewModel
 * (either) through {@link #set} or {@link Ext.app.bind.Binding#method-setValue} is to climb to
 * where the value  is "owned" and set the value there:
 *
 *     var parent = new Ext.app.ViewModel({
 *         data: {
 *             foo: 3
 *         }
 *     });
 *     var child = new Ext.app.ViewModel({
 *         parent: parent
 *     });
 *     
 *     child.set('foo', 100); // Climbs to set the value on parent
 *     console.log(parent.get('foo')); // 100
 *     parent.set('foo', 200);
 *     console.log(child.get('foo')); // 200, inherited from the parent
 *
 * Any subsequent sets are also inherited in the same fashion. The inheriting/climbing behavior 
 * occurs for any arbitrary depth, climbing/inherting can owned by a parent at any level above.
 *
 *     function log() {
 *         console.log([a, b, c, d, e].map(function(vm) {
 *             return vm.get('foo');
 *         }));
 *     }
 *
 *     var a = new Ext.app.ViewModel({data: {foo: 3}}),
 *         b = new Ext.app.ViewModel({parent: a}),
 *         c = new Ext.app.ViewModel({parent: b}),
 *         d = new Ext.app.ViewModel({parent: c}),
 *         e = new Ext.app.ViewModel({parent: d});
 *
 *     log(); // [3, 3, 3, 3, 3]
 *
 *     e.set('foo', 100);
 *     log(); // [100, 100, 100, 100, 100]
 *
 * This same climbing behavior applies when setting a value on a binding. The climbing begins from
 * the ViewModel where the binding was attached:
 *
 *     function log() {
 *         console.log([a, b, c].map(function(vm) {
 *             return vm.get('foo');
 *         }));
 *     }
 *
 *     var a = new Ext.app.ViewModel({data: {foo: 3}}),
 *         b = new Ext.app.ViewModel({parent: a}),
 *         c = new Ext.app.ViewModel({parent: b});
 *
 *     var bind = c.bind('{foo}', function() {});
 *
 *     bind.setValue(100);
 *     log(); // [100, 100, 100]
 *
 * The exception to this rule is when there is nothing above to climb to. If a value is set and
 * there is no parent above to hold it, then the value is set where it was called:
 *
 *     function log() {
 *         console.log([a, b, c].map(function(vm) {
 *             return vm.get('foo');
 *         }));
 *     }
 *
 *     var a = new Ext.app.ViewModel(),
 *         b = new Ext.app.ViewModel({parent: a}),
 *         c = new Ext.app.ViewModel({parent: b});
 *
 *     c.set('foo', 3);
 *     log(); // [null, null, 3]
 *
 *     b.set('foo', 2);
 *     log(); // [null, 2, 3]
 *
 *     a.set('foo', 1);
 *     log(); // [1, 2, 3]
 *
 * These values are called local values, which are discussed below.
 *
 * ### Local Values
 *
 * If the child ViewModel is declared with top level data that also exists in the parent, then that
 * child is considered to own that local value, so no value is inherited from the parent, nor does
 * the climbing behaviour occur.
 *
 *     var parent = new Ext.app.ViewModel({
 *         data: {
 *             foo: 3
 *         }
 *     });
 *     var child = new Ext.app.ViewModel({
 *         parent: parent,
 *         data: {
 *             foo: 5
 *         }
 *     });
 *
 *     console.log(parent.get('foo'), child.get('foo')); // 3, 5
 *     child.set('foo', 100);
 *     console.log(parent.get('foo'), child.get('foo')); // 3, 100
 *     parent.set('foo', 200);
 *     console.log(parent.get('foo'), child.get('foo')); // 200, 100
 *
 * The inheriting/climbing behavior is limited to local values:
 *
 *     function log() {
 *         console.log([a, b, c, d, e].map(function(vm) {
 *             return vm.get('foo');
 *         }));
 *     }
 *
 *     var a = new Ext.app.ViewModel({data: {foo: 1}}),
 *         b = new Ext.app.ViewModel({parent: a}),
 *         c = new Ext.app.ViewModel({parent: b, data: {foo: 2}}),
 *         d = new Ext.app.ViewModel({parent: c}),
 *         e = new Ext.app.ViewModel({parent: d, data: {foo: 3}});
 *
 *     log(); // [1, 1, 2, 2, 3]
 *
 *     e.set('foo', 100);
 *     log(); // [1, 1, 2, 2, 100]
 *
 *     d.set('foo', 200);
 *     log(); // [1, 1, 200, 200, 100]
 *
 *     c.set('foo', 201);
 *     log(); // [1, 1, 201, 201, 100]
 *
 *     b.set('foo', 300);
 *     log(); // [300, 300, 201, 201, 100]
 *
 *     a.set('foo', 301);
 *     log(); // [301, 301, 201, 201, 100]
 *
 * ### Attaching/Clearing Local Values Dynamically
 *
 * To bypass the climbing behaviour and push a value into a particular point
 * in the hierarchy, the {@link #setData} method should be used. Once a local value
 * is set, it will be used as such in the future.
 *
 *     function log() {
 *         console.log([a, b, c, d, e].map(function(vm) {
 *             return vm.get('foo');
 *         }));
 *     }
 *
 *     var a = new Ext.app.ViewModel({data: {foo: 3}}),
 *         b = new Ext.app.ViewModel({parent: a}),
 *         c = new Ext.app.ViewModel({parent: b}),
 *         d = new Ext.app.ViewModel({parent: c}),
 *         e = new Ext.app.ViewModel({parent: d});
 *
 *     log(); // [3, 3, 3, 3, 3]
 *
 *     c.setData({
 *         foo: 100
 *     });
 *
 *     log(); // [3, 3, 100, 100, 100]
 *
 *     d.set('foo', 200); // Climbs to new local value
 *     log(); // [3, 3, 200, 200, 200]
 *
 * Similarly, data can be cleared from being a local value by setting the value to undefined:
 *
 *     function log() {
 *         console.log([a, b, c, d].map(function(vm) {
 *             return vm.get('foo');
 *         }));
 *     }
 *
 *     var a = new Ext.app.ViewModel({data: {foo: 3}}),
 *         b = new Ext.app.ViewModel({parent: a}),
 *         c = new Ext.app.ViewModel({parent: b, data: {foo: 100}}),
 *         d = new Ext.app.ViewModel({parent: c});
 *
 *     log(); // [3, 3, 100, 100]
 *
 *     c.setData({
 *         foo: undefined
 *     });
 *     log([3, 3, 3, 3]);
 *
 */
Ext.define('Ext.app.ViewModel', {
    mixins: [
        'Ext.mixin.Factoryable',
        'Ext.mixin.Identifiable'
    ],

    requires: [
        'Ext.util.Scheduler',
        'Ext.data.Session',
        'Ext.app.bind.RootStub',
        'Ext.app.bind.LinkStub',
        'Ext.app.bind.Multi',
        'Ext.app.bind.Formula',
        'Ext.app.bind.TemplateBinding',
        // TODO: this is an injected dependency in onStoreBind, need to define so 
        // cmd can detect it
        'Ext.data.ChainedStore'
    ],

    alias: 'viewmodel.default', // also configures Factoryable

    isViewModel: true,

    factoryConfig: {
        name: 'viewModel'
    },

    collectTimeout: 100,

    expressionRe: /^(?:\{(?:(\d+)|([a-z_][\w.]*))\})$/i,

    statics: {
        /**
         * Escape bind strings so they are treated as literals.
         * 
         * @param {Object/String} value The value to escape. If the value is
         * an object, any strings will be recursively escaped.
         * @return {Object/String} The escaped value. Matches the type of the
         * passed value.
         *
         * @since 6.5.2
         * @private
         */
        escape: function(value) {
            var ret = value,
                key;

            if (typeof value === 'string') {
                ret = '~~' + value;
            }
            else if (value && value.constructor === Object) {
                ret = {};

                for (key in value) {
                    ret[key] = this.escape(value[key]);
                }
            }

            return ret;
        }
    },

    $configStrict: false, // allow "formulas" to be specified on derived class body
    config: {
        /**
         * @cfg {Object} data
         * This object holds the arbitrary data that populates the `ViewModel` and is
         * then available for binding.
         * @since 5.0.0
         */
        data: true,

        /**
         * @cfg {Object} formulas
         * An object that defines named values whose value is managed by function calls.
         * The names of the properties of this object are assigned as values in the
         * ViewModel.
         *
         * For example:
         *
         *      formulas: {
         *          xy: function (get) { return get('x') * get('y'); }
         *      }
         *
         * For more details about defining a formula, see `{@link Ext.app.bind.Formula}`.
         * @since 5.0.0
         */
        formulas: {
            $value: null,
            merge: function(newValue, currentValue, target, mixinClass) {
                return this.mergeNew(newValue, currentValue, target, mixinClass);
            }
        },

        /**
         * @cfg {Object} links
         * Links provide a way to assign a simple name to a more complex bind. The primary
         * use for this is to assign names to records in the data model.
         *
         *      links: {
         *          theUser: {
         *              type: 'User',
         *              id: 12
         *          }
         *      }
         *
         * It is also possible to force a new phantom record to be created by not specifying an
         * id but passing `create: true` as part of the descriptor. This is often useful when
         * creating a new record for a child session.
         *
         *     links: {
         *         newUser: {
         *             type: 'User',
         *             create: true
         *         }
         *     } 
         *
         * `create` can also be an object containing initial data for the record.
         *
         *     links: {
         *         newUser: {
         *             type: 'User',
         *             create: {
         *                 firstName: 'John',
         *                 lastName: 'Smith'
         *             }
         *         }
         *     } 
         *
         * While that is the typical use, the value of each property in `links` may also be
         * a bind descriptor (see `{@link #method-bind}` for the various forms of bind
         * descriptors).
         * @since 5.0.0
         */
        links: null,

        /**
         * @cfg {Ext.app.ViewModel} parent
         * The parent `ViewModel` of this `ViewModel`. Once set, this cannot be changed.
         * @readonly
         * @since 5.0.0
         */
        parent: null,

        /**
         * @cfg {Ext.app.bind.RootStub} root
         * A reference to the root "stub" (an object that manages bindings).
         * @private
         * @since 5.0.0
         */
        root: true,

        /**
         * @cfg {Ext.util.Scheduler} scheduler
         * The scheduler used to schedule and manage the delivery of notifications for
         * all connections to this `ViewModel` and any other attached to it. The normal
         * process to initialize the `scheduler` is to get the scheduler used by the
         * `parent` or `session` and failing either of those, create one.
         * @readonly
         * @private
         * @since 5.0.0
         */
        scheduler: null,

        /**
         * @cfg {String/Ext.data.schema.Schema} schema
         * The schema to use for getting information about entities.
         */
        schema: 'default',

        /**
         * @cfg {Ext.data.Session} session
         * The session used to manage the data model (records and stores).
         * @since 5.0.0
         */
        session: null,

        // @cmd-auto-dependency {isKeyedObject: true, aliasPrefix: "store.", defaultType: "store"}
        /**
         * @cfg {Object} stores
         * A declaration of `Ext.data.Store` configurations that are first processed as
         * binds to produce an effective store configuration.
         *
         * A simple store definition. We can reference this in our bind statements using the
         * `{users}` as we would with other data values.
         *
         *     new Ext.app.ViewModel({
         *         stores: {
         *             users: {
         *                 model: 'User',
         *                 autoLoad: true
         *             }
         *         }
         *     });
         *
         * This store definition contains a dynamic binding. The store will not be created until
         * the initial value for groupId is set. Once that occurs, the store is created with the
         * appropriate filter configuration. Subsequently, once we change the group value, the old
         * filter will be overwritten with the new value.
         *
         *     var viewModel = new Ext.app.ViewModel({
         *         stores: {
         *             users: {
         *                 model: 'User',
         *                 filters: [{
         *                     property: 'groupId',
         *                     value: '{groupId}'
         *                 }]
         *             }
         *         }
         *     });
         *     viewModel.set('groupId', 1); // This will trigger the store creation with the filter.
         *     viewModel.set('groupId', 2); // The filter value will be changed.
         *
         * This store uses {@link Ext.data.ChainedStore store chaining} to create a store backed by
         * the data in another store. By specifying a string as the store, it will bind our creation
         * and backing to the other store. This functionality is especially useful when wanting to
         * display a different "view" of a store, for example a different sort order or different
         * filters.
         *
         *     var viewModel = new Ext.app.ViewModel({
         *         stores: {
         *             allUsers: {
         *                 model: 'User',
         *                 autoLoad: true
         *             },
         *             children: {
         *                 source: '{allUsers}',
         *                 filters: [{
         *                     property: 'age',
         *                     value: 18,
         *                     operator: '<'
         *                 }]
         *             }
         *         }
         *     });
         *
         * @since 5.0.0
         */
        stores: null,

        /**
         * @cfg {Ext.container.Container} view
         * The Container that owns this `ViewModel` instance.
         * @since 5.0.0
         */
        view: null
    },

    constructor: function(config) {
        // Used to track non-stub bindings
        this.bindings = {};
        /*
         *  me.data = {
         *      foo: {
         *      },
         *          
         *      selectedUser: {
         *          name: null
         *      },
         *  }
         *
         *  me.root = new Ext.app.bind.RootStub({
         *      children: {
         *          foo: new Ext.app.bind.Stub(),
         *          selectedUser: new Ext.app.bind.LinkStub({
         *              binding: session.bind(...),
         *              children: {
         *                  name: : new Ext.app.bind.Stub()
         *              }
         *          }),
         *      }
         *  })
         */

        this.initConfig(config);
    },

    destroy: function() {
        var me = this,
            scheduler = me._scheduler,
            stores = me.storeInfo,
            parent = me.getParent(),
            task = me.collectTask,
            children = me.children,
            bindings = me.bindings,
            key, store, autoDestroy, storeBinding;

        me.destroying = true;

        if (task) {
            task.cancel();
            me.collectTask = null;
        }

        // When used with components, they are destroyed bottom up
        // so this scenario is only likely to happen in the case where
        // we're using the VM without any component attachment, in which case
        // we need to clean up here.
        if (children) {
            for (key in children) {
                children[key].destroy();
            }
        }

        if (stores) {
            for (key in stores) {
                store = stores[key];

                // Cache this property in case store is destroyed;
                // Properties are cleared on destroy
                storeBinding = store.$binding;
                autoDestroy = store.autoDestroy;

                if (autoDestroy || (!store.$wasInstance && autoDestroy !== false)) {
                    store.destroy();
                }

                Ext.destroy(storeBinding);
            }
        }

        if (parent) {
            parent.unregisterChild(me);
        }

        me.getRoot().destroy();

        for (key in bindings) {
            bindings[key].destroy();
        }

        if (scheduler && scheduler.$owner === me) {
            scheduler.$owner = null;
            scheduler.destroy();
        }

        me.children = me.storeInfo = me._session = me._view = me._scheduler =
                      me.bindings = me._root = me._parent = me.formulaFn = me.$formulaData = null;

        // This just makes it hard to ask "was destroy() called?":
        // me.destroying = false; // removed in 7.0

        me.callParent();
    },

    /**
     * This method requests that data in this `ViewModel` be delivered to the specified
     * `callback`. The data desired is given in a "bind descriptor" which is the first
     * argument.
     *
     * A simple call might look like this:
     *
     *     var binding = vm.bind('{foo}', this.onFoo, this);
     * 
     *     binding.destroy();  // when done with the binding
     *
     * Options for the binding can be provided in the last argument:
     *
     *     var binding = vm.bind('{foo}', this.onFoo, this, {
     *         deep: true
     *     });
     * 
     * Alternatively, bind options can be combined with the bind descriptor using only
     * the first argument:
     *
     *     var binding = vm.bind({
     *         bindTo: '{foo}',  // the presence of bindTo identifies this form
     *         deep: true
     *     }, this.onFoo, this);
     * 
     * See the class documentation for more details on Bind Descriptors and options.
     *
     * @param {String/Object/Array} descriptor The bind descriptor. See class description
     * for details.
     * @param {Function} callback The function to call with the value of the bound property.
     * @param {Object} [scope] The scope (`this` pointer) for the `callback`.
     * @param {Object} [options] Additional options to configure the
     * {@link Ext.app.bind.Binding binding}. If this parameter is provided, the `bindTo` form
     * of combining options and bind descriptor is not recognized.
     * @return {Ext.app.bind.BaseBinding/Ext.app.bind.Binding} The binding.
     */
    bind: function(descriptor, callback, scope, options) {
        var me = this,
            track = true,
            binding;

        scope = scope || me;

        if (!options && descriptor.bindTo !== undefined && !Ext.isString(descriptor)) {
            options = descriptor;
            descriptor = options.bindTo;
        }

        if (!Ext.isString(descriptor)) {
            binding = new Ext.app.bind.Multi(descriptor, me, callback, scope, options);
        }
        else if (me.expressionRe.test(descriptor)) {
            // If we have '{foo}' alone it is a literal
            descriptor = descriptor.substring(1, descriptor.length - 1);
            binding = me.bindExpression(descriptor, callback, scope, options);
            track = false;
        }
        else {
            binding = new Ext.app.bind.TemplateBinding(descriptor, me, callback, scope, options);
        }

        if (track) {
            me.bindings[binding.id] = binding;
        }

        return binding;
    },

    /**
     * Gets the session attached to this (or a parent) ViewModel. See the {@link #session}
     * configuration.
     * @return {Ext.data.Session} The session. `null` if no session exists.
     */
    getSession: function() {
        var me = this,
            session = me._session,
            parent;

        if (!session && (parent = me.getParent())) {
            me.setSession(session = parent.getSession());
        }

        return session || null;
    },

    /**
     * Gets a store configured via the {@link #stores} configuration.
     * @param {String} key The name of the store.
     * @return {Ext.data.Store} The store. `null` if no store exists.
     */
    getStore: function(key) {
        var storeInfo = this.storeInfo,
            store;

        if (storeInfo) {
            store = storeInfo[key];
        }

        return store || null;
    },

    /**
     * @method getStores
     * @hide
     */

    /**
     * Create a link to a reference. See the {@link #links} configuration.
     * @param {String} key The name for the link.
     * @param {Object} reference The reference descriptor.
     */
    linkTo: function(key, reference) {
        var me = this,
            stub, create, id, modelType, linkStub, rec;

        //<debug>
        if (key.indexOf('.') > -1) {
            Ext.raise('Links can only be at the top-level: "' + key + '"');
        }
        //</debug>

        if (reference.isModel) {
            reference = {
                type: reference.entityName,
                id: reference.id
            };
        }

        // reference is backwards compat, type is preferred.
        modelType = reference.type || reference.reference;
        create = reference.create;

        if (modelType) {
            // It's a record
            id = reference.id;

            //<debug>
            if (!reference.create && Ext.isEmpty(id)) {
                Ext.raise('No id specified. To create a phantom model, specify "create: true" ' +
                          'as part of the reference.');
            }
            //</debug>

            if (create) {
                id = undefined;
            }

            rec = me.getRecord(modelType, id);

            if (Ext.isObject(create)) {
                rec.set(create);
                rec.commit();
                rec.phantom = true;
            }

            // Force creation at the root level. If an existing stub is there
            // it will be grafted in place here.
            stub = me.getRoot().createStubChild(key);
            stub.set(rec);
        }
        else {
            stub = me.getStub(key);

            if (!stub.isLinkStub) {
                // Pass parent=null since we will graft in this new stub to replace us:
                linkStub = new Ext.app.bind.LinkStub(me, stub.name);
                stub.graft(linkStub);
                stub = linkStub;
            }

            stub.link(reference);
        }
    },

    /**
     * Forces all bindings in this ViewModel hierarchy to evaluate immediately. Use this to do
     * a synchronous flush of all bindings.
     */
    notify: function() {
        var scheduler = this.getScheduler();

        if (!scheduler.firing) {
            scheduler.notify();
        }
    },

    /**
     * Get a value from the data for this viewmodel.
     * @param {String} path The path of the data to retrieve.
     *
     *     var value = vm.get('theUser.address.city');
     *
     * @return {Object} The data stored at the passed path.
     */
    get: function(path) {
        return this.getStub(path).getValue();
    },

    /**
     * Set a value in the data for this viewmodel. This method will climb to set data on
     * a parent view model if appropriate. See "Inheriting Data" in the class introduction for
     * more information.
     * 
     * @param {Object/String} path The path of the value to set, or an object literal to set
     * at the root of the viewmodel.
     * @param {Object} value The data to set at the value. If the value is an object literal,
     * any required paths will be created.
     *
     *     // Set a single property at the root level
     *     viewModel.set('expiry', Ext.Date.add(new Date(), Ext.Date.DAY, 7));
     *     console.log(viewModel.get('expiry'));
     *     // Sets a single property in user.address, does not overwrite any hierarchy.
     *     viewModel.set('user.address.city', 'London');
     *     console.log(viewModel.get('user.address.city'));
     *     // Sets 2 properties of "user". Overwrites any existing hierarchy.
     *     viewModel.set('user', {firstName: 'Foo', lastName: 'Bar'});
     *     console.log(viewModel.get('user.firstName'));
     *     // Sets a single property at the root level. Overwrites any existing hierarchy.
     *     viewModel.set({rootKey: 1});
     *     console.log(viewModel.get('rootKey'));
     */
    set: function(path, value) {
        var me = this,
            obj, stub;

        // Force data creation
        me.getData();

        if (value === undefined && path && path.constructor === Object) {
            stub = me.getRoot();
            value = path;
        }
        else if (path && path.indexOf('.') < 0) {
            obj = {};
            obj[path] = value;
            value = obj;
            stub = me.getRoot();
        }
        else {
            stub = me.getStub(path);
        }

        stub.set(value);
    },

    /**
     * Sets data directly at the level of this viewmodel. This method does not climb
     * to set data on parent view models. Passing `undefined` will clear the value
     * in this viewmodel, which means that this viewmodel is free to inherit data
     * from a parent. See "Inheriting Data" in the class introduction for more information.
     * @param {Object} data The new data to set.
     * @method setData
     */

    //=========================================================================
    privates: {
        registerChild: function(child) {
            var children = this.children;

            if (!children) {
                this.children = children = {};
            }

            children[child.getId()] = child;
        },

        unregisterChild: function(child) {
            var children = this.children;

            // If we're destroying we'll be wiping this collection shortly, so
            // just ignore it here
            if (!this.destroying && children) {
                delete children[child.getId()];
            }
        },

        /**
         * Get a record instance given a reference descriptor. Will ask
         * the session if one exists.
         * @param {String/Ext.Class} type The model type.
         * @param {Object} id The model id.
         * @return {Ext.data.Model} The model instance.
         * @private
         */
        getRecord: function(type, id) {
            var session = this.getSession(),
                Model = type,
                hasId = id !== undefined,
                record;

            if (session) {
                if (hasId) {
                    record = session.getRecord(type, id);
                }
                else {
                    record = session.createRecord(type);
                }
            }
            else {
                if (!Model.$isClass) {
                    Model = this.getSchema().getEntity(Model);

                    //<debug>
                    if (!Model) {
                        Ext.raise('Invalid model name: ' + type);
                    }
                    //</debug>
                }

                if (hasId) {
                    record = Model.createWithId(id);
                    record.load();
                }
                else {
                    record = new Model();
                }
            }

            return record;
        },

        bindExpression: function(descriptor, callback, scope, options) {
            var stub = this.getStub(descriptor);

            return stub.bind(callback, scope, options);
        },

        applyScheduler: function(scheduler) {
            if (scheduler && !scheduler.isInstance) {
                if (scheduler === true) {
                    scheduler = {};
                }

                if (!('preSort' in scheduler)) {
                    scheduler = Ext.apply({
                        preSort: 'kind,-depth'
                    }, scheduler);
                }

                scheduler = new Ext.util.Scheduler(scheduler);
                scheduler.$owner = this;
            }

            return scheduler;
        },

        getScheduler: function() {
            var me = this,
                scheduler = me._scheduler,
                parent;

            if (!scheduler) {
                if (!(parent = me.getParent())) {
                    scheduler = new Ext.util.Scheduler({
                        // See Session#scheduler
                        preSort: 'kind,-depth'
                    });

                    scheduler.$owner = me;
                }
                else {
                    scheduler = parent.getScheduler();
                }

                me.setScheduler(scheduler);
            }

            return scheduler;
        },

        /**
         * This method looks up the `Stub` for a single bind descriptor.
         * @param {String/Object} bindDescr The bind descriptor.
         * @return {Ext.app.bind.AbstractStub} The `Stub` associated to the bind descriptor.
         * @private
         */
        getStub: function(bindDescr) {
            var root = this.getRoot();

            return bindDescr ? root.getChild(bindDescr) : root;
        },

        collect: function() {
            var me = this,
                parent = me.getParent(),
                task = me.collectTask;

            if (parent) {
                parent.collect();

                return;
            }

            if (!task) {
                task = me.collectTask = new Ext.util.DelayedTask(me.doCollect, me);
            }

            // Useful for testing
            if (me.collectTimeout === 0) {
                me.doCollect();
            }
            else {
                task.delay(me.collectTimeout);
            }
        },

        doCollect: function() {
            var children = this.children,
                key;

            // We need to loop over the children first, since they may have link stubs
            // that create bindings inside our VM. Attempt to clean them up first.
            if (children) {
                for (key in children) {
                    children[key].doCollect();
                }
            }

            this.getRoot().collect();
        },

        invalidateChildLinks: function(name, clear) {
            var children = this.children,
                key;

            if (children) {
                for (key in children) {
                    children[key].getRoot().invalidateChildLink(name, clear);
                }
            }
        },

        onBindDestroy: function(binding, fromChild) {
            var me = this,
                parent;

            if (me.destroying) {
                return;
            }

            if (!fromChild) {
                delete me.bindings[binding.id];
            }

            parent = me.getParent();

            if (parent) {
                parent.onBindDestroy(binding, true);
            }
            else {
                me.collect();
            }
        },

        //-------------------------------------------------------------------------
        // Config
        // <editor-fold>

        applyData: function(newData, data) {
            var me = this,
                linkData, parent;

            // Force any session to be invoked so we can access it
            me.getSession();

            if (!data) {
                parent = me.getParent();

                /**
                 * @property {Object} linkData
                 * This object is used to hold the result of a linked value. This is done
                 * so that the data object hasOwnProperty equates to whether or not this
                 * property is owned by this instance or inherited.
                 * @private
                 * @readonly
                 * @since 5.0.0
                 */
                me.linkData = linkData = parent ? Ext.Object.chain(parent.getData()) : {};

                /**
                 * @property {Object} data
                 * This object holds all of the properties of this `ViewModel`. It is
                 * prototype chained to the `linkData` which is, in turn, prototype chained
                 * to (if present) the `data` object of the parent `ViewModel`.
                 * @private
                 * @readonly
                 * @since 5.0.0
                 */
                me.data = me._data = Ext.Object.chain(linkData);
            }

            if (newData && newData.constructor === Object) {
                me.getRoot().set(newData, true);
            }
        },

        applyParent: function(parent) {
            if (parent) {
                parent.registerChild(this);
            }

            return parent;
        },

        applyStores: function(stores) {
            var me = this,
                root = me.getRoot(),
                key, cfg, storeBind, stub, listeners;

            me.storeInfo = {};

            me.listenerScopeFn = function() {
                return me.getView().getInheritedConfig('defaultListenerScope');
            };

            for (key in stores) {
                cfg = stores[key];

                if (cfg.isStore) {
                    cfg.$wasInstance = true;
                    me.setupStore(cfg, key);

                    continue;
                }
                else if (Ext.isString(cfg)) {
                    cfg = {
                        source: cfg
                    };
                }
                else {
                    cfg = Ext.apply({}, cfg);
                }

                // Get rid of listeners so they don't get considered as a bind
                listeners = cfg.listeners;
                delete cfg.listeners;

                storeBind = me.bind(cfg, me.onStoreBind, me, { trackStatics: true });

                if (storeBind.isStatic()) {
                    // Everything is static, we don't need to wait, so remove the
                    // binding because it will only fire the first time.
                    storeBind.destroy();
                    me.createStore(key, cfg, listeners);
                }
                else {
                    storeBind.$storeKey = key;
                    storeBind.$listeners = listeners;
                    stub = root.createStubChild(key);
                    stub.setStore(storeBind);
                }
            }
        },

        onStoreBind: function(cfg, oldValue, binding) {
            var info = this.storeInfo,
                key = binding.$storeKey,
                store = info[key],
                proxy;

            if (!store) {
                this.createStore(key, cfg, binding.$listeners, binding);
            }
            else {
                cfg = Ext.merge({}, binding.pruneStaticKeys());
                proxy = cfg.proxy;

                delete cfg.type;
                delete cfg.model;
                delete cfg.fields;
                delete cfg.proxy;
                delete cfg.listeners;

                // TODO: possibly optimize this so we can figure out what has changed
                // instead of smashing the whole lot
                if (proxy) {
                    delete proxy.reader;
                    delete proxy.writer;
                    store.getProxy().setConfig(proxy);
                }

                store.setConfig(cfg);
            }
        },

        createStore: function(key, cfg, listeners, binding) {
            var session = this.getSession(),
                store;

            cfg = Ext.apply({}, cfg);

            if (cfg.session) {
                cfg.session = session;
            }

            if (cfg.source) {
                cfg.type = cfg.type || 'chained';
            }

            // Restore the listeners from applyStores here
            cfg.listeners = listeners;
            // Ensure events fired by ctor can find their target:
            cfg.resolveListenerScope = this.listenerScopeFn;

            store = Ext.Factory.store(cfg);
            store.$binding = binding;

            this.setupStore(store, key);
        },

        setupStore: function(store, key) {
            var me = this,
                obj = {};

            // Force data object creation
            me.getData();

            // May have been given a store instance
            store.resolveListenerScope = me.listenerScopeFn;
            me.storeInfo[key] = store;

            obj[key] = store;
            me.setData(obj);
        },

        applyFormulas: function(formulas) {
            var me = this,
                root = me.getRoot(),
                name, stub;

            me.getData(); // make sure our data is setup first

            for (name in formulas) {
                //<debug>
                if (name.indexOf('.') >= 0) {
                    Ext.raise('Formula names cannot contain dots: ' + name);
                }
                //</debug>

                // Force a stub to be created
                root.createStubChild(name);

                stub = me.getStub(name);
                stub.setFormula(formulas[name]);
            }

            return formulas;
        },

        applyLinks: function(links) {
            var link;

            for (link in links) {
                this.linkTo(link, links[link]);
            }
        },

        applySchema: function(schema) {
            return Ext.data.schema.Schema.get(schema);
        },

        applyRoot: function() {
            var root = new Ext.app.bind.RootStub(this),
                parent = this.getParent();

            if (parent) {
                // We are assigning the root of a child VM such that its bindings will be
                // pre-sorted after the bindings of the parent VM.
                root.depth = parent.getRoot().depth - 1000;
            }

            return root;
        },

        getFormulaFn: function(data) {
            var me = this,
                fn = me.formulaFn;

            if (!fn) {
                fn = me.formulaFn = function(name) {
                    // Note that the `this` pointer here is the view model because
                    // the VM calls it in the VM scope.
                    return me.$formulaData[name];
                };
            }

            me.$formulaData = data;

            return fn;
        }

        // </editor-fold>
    }
});

/**
 * Base class for any Ext.Component that may contain other Components. Containers handle the basic
 * behavior of containing items, namely adding, inserting and removing items.
 *
 * The most commonly used Container classes are Ext.panel.Panel, Ext.window.Window and
 * Ext.tab.Panel. If you do not need the capabilities offered by the aforementioned classes you can
 * create a lightweight Container to be encapsulated by an HTML element to your specifications
 * by using the {@link Ext.Component#autoEl autoEl} config option.
 *
 * The code below illustrates how to explicitly create a Container:
 *
 *     @example
 *     // Explicitly create a Container
 *     Ext.create('Ext.container.Container', {
 *         layout: {
 *             type: 'hbox'
 *         },
 *         width: 400,
 *         renderTo: Ext.getBody(),
 *         border: 1,
 *         style: {borderColor:'#000000', borderStyle:'solid', borderWidth:'1px'},
 *         defaults: {
 *             labelWidth: 80,
 *             // implicitly create Container by specifying xtype
 *             xtype: 'datefield',
 *             flex: 1,
 *             style: {
 *                 padding: '10px'
 *             }
 *         },
 *         items: [{
 *             xtype: 'datefield',
 *             name: 'startDate',
 *             fieldLabel: 'Start date'
 *         },{
 *             xtype: 'datefield',
 *             name: 'endDate',
 *             fieldLabel: 'End date'
 *         }]
 *     });
 *
 * ## Layout
 *
 * Container classes delegate the rendering of child Components to a layout manager class which must
 * be configured into the Container using the `{@link #layout}` configuration property.
 *
 * When either specifying child `{@link #cfg-items}` of a Container, or dynamically
 * {@link #method-add adding} Components to a Container, remember to consider how you wish
 * the Container to arrange those child elements, and whether those child elements need to be sized
 * using one of Ext's built-in `{@link #layout}` schemes. By default, Containers use the
 * {@link Ext.layout.container.Auto Auto} scheme which only renders child components, appending them
 * one after the other inside the Container, and **does not apply any sizing** at all.
 *
 * A common mistake is when a developer neglects to specify a `{@link #layout}` (e.g. GridPanels or
 * TreePanels are added to Containers for which no `{@link #layout}` has been specified).
 * If a Container is left to use the default {@link Ext.layout.container.Auto Auto} scheme,
 * none of its child components will be resized, or changed in any way when the Container
 * is resized.
 *
 * Certain layout managers allow dynamic addition of child components. Those that do include
 * Ext.layout.container.Card, Ext.layout.container.Anchor, Ext.layout.container.VBox,
 * Ext.layout.container.HBox, and Ext.layout.container.Table. For example:
 *
 *     //  Create the GridPanel.
 *     var myNewGrid = Ext.create('Ext.grid.Panel', {
 *         store: myStore,
 *         headers: myHeaders,
 *         title: 'Results', // the title becomes the title of the tab
 *     });
 *
 *     myTabPanel.add(myNewGrid); // Ext.tab.Panel implicitly uses Ext.layout.container.Card
 *     myTabPanel.setActiveTab(myNewGrid);
 *
 * The example above adds a newly created GridPanel to a TabPanel. Note that a TabPanel uses
 * {@link Ext.layout.container.Card} as its layout manager which means all its child items are sized
 * to {@link Ext.layout.container.Fit fit} exactly into its client area.
 *
 * **_Overnesting is a common problem_**. An example of overnesting occurs when a GridPanel is added
 * to a TabPanel by wrapping the GridPanel _inside_ a wrapping Panel (that has no `{@link #layout}`
 * specified) and then add that wrapping Panel to the TabPanel. The point to realize is that
 * a GridPanel **is** a Component which can be added directly to a Container. If the wrapping Panel
 * has no `{@link #layout}` configuration, then the overnested GridPanel will not be sized
 * as expected.
 * 
 * ## {@link Ext.Component#reference References} and {@link #referenceHolder Reference Holders}
 * 
 * Reference holders are used to keep references to child components inside a hierarchy.
 * 
 * This functionality allows the connection of encapsulated references between containers
 * and their child components declaratively. Simple usage:
 * 
 *     Ext.define('Login', {
 *         extend: 'Ext.window.Window',
 *
 *         // This config is not compatible with the more common "controller" config
 *         // used to specify a ViewController for the view. When a ViewController is
 *         // specified it effectively acts as the "reference holder" for the view. In
 *         // this example we simply mark this container as the reference holder for
 *         // demonstration purposes.
 *         referenceHolder: true,
 *
 *         title: 'Login',
 *         items: [{
 *             xtype: 'form',
 *             items: [{
 *                 xtype: 'textfield',
 *                 reference: 'username', // A named reference to be held on the referenceHolder
 *                 name: 'username',
 *                 fieldLabel: 'Username'
 *             }, {
 *                 xtype: 'textfield',
 *                 reference: 'password', // A named reference to be held on the referenceHolder
 *                 name: 'password',
 *                 fieldLabel: 'Password'
 *             }] 
 *         }] 
 *     });
 *     var w = new Login();
 *     console.log(w.lookupReference('password')); // The password field
 * 
 * Reference holders are also encapsulated, so a reference will only be put on the closest
 * reference holder above it in the component hierarchy:
 * 
 *     var ct = new Ext.container.Container({
 *         referenceHolder: true,
 *         items: [{
 *             xtype: 'container',
 *             referenceHolder: true,
 *             reference: 'innerCt1',
 *             items: [{
 *                 xtype: 'component',
 *                 reference: 'a',
 *                 id: 'a1'
 *             }, {
 *                 xtype: 'component',
 *                 reference: 'b',
 *                 id: 'b1'
 *             }]
 *         }, {
 *             xtype: 'container',
 *             referenceHolder: true,
 *             reference: 'innerCt2',
 *             items: [{
 *                 xtype: 'component',
 *                 reference: 'a',
 *                 id: 'a2'
 *             }, {
 *                 xtype: 'component',
 *                 reference: 'b',
 *                 id: 'b2'
 *             }]
 *         }]
 *     });
 *     // The main container will not have references to a/b, each innerCt will
 *     console.log(ct.lookupReference('a'), ct.lookupReference('b'));
 *     var inner1 = ct.lookupReference('innerCt1');
 *     var inner2 = ct.lookupReference('innerCt2');
 * 
 *     console.log(inner1.lookupReference('a').id, inner1.lookupReference('b').id);
 *     console.log(inner2.lookupReference('a').id, inner2.lookupReference('b').id);
 *     
 * If the view has a controller attached, it will automatically become a {@link #referenceHolder}.
 * References will be available in both the view and the controller:
 * 
 *     Ext.define('ProfileController', {
 *         extend: 'Ext.app.ViewController',
 *         alias: 'controller.profile',
 *   
 *         init: function() {
 *             console.log(this.lookupReference('firstName'));
 *         }
 *     });
 *
 *     Ext.define('Profile', {
 *         extend: 'Ext.form.Panel',
 *         controller: 'profile',
 *         items: [{
 *             xtype: 'textfield',
 *             reference: 'firstName',
 *             fieldLabel: 'First Name'
 *         }]
 *     });
 * 
 *     new Profile(); 
 * 
 * ## Events & {@link #defaultListenerScope} ##
 * 
 * Events can use the default listener scope to determine at runtime the appropriate place
 * to fire. This allows for declarative binding of events in a useful way:
 * 
 *     Ext.define('MyView', {
 *         extend: 'Ext.container.Container',
 *         defaultListenerScope: true,
 *         referenceHolder: true,
 *         items: [{
 *             xtype: 'textfield',
 *             reference: 'myfield'
 *         }, {
 *             xtype: 'button',
 *             text: 'Set to A',
 *             listeners: {
 *                 click: 'onButtonAClick'
 *             }
 *         }, {
 *             xtype: 'button',
 *             text: 'Set to B',
 *             listeners: {
 *                 click: 'onButtonBClick'
 *             }
 *         }],
 * 
 *         onButtonAClick: function() {
 *             this.lookupReference('myfield').setValue('A');
 *         },
 * 
 *         onButtonBClick: function() {
 *             this.lookupReference('myfield').setValue('B');
 *         }
 *     });
 *     
 * Like {@link #referenceHolder}, the {@link #defaultListenerScope} is encapsulated, the scope will
 * be resolved at the closest {@link #defaultListenerScope} above it in the component hierarchy:
 * 
 *     var ct = new Ext.container.Container({
 *         defaultListenerScope: true,
 *         onCustomEvent: function() {
 *             console.log('Outer called'); // Will NOT be called
 *         },
 *         items: [{
 *             xtype: 'container',
 *             defaultListenerScope: true,
 *             onCustomEvent: function() {
 *                 console.log('Inner called'); // Will be called
 *             },
 *             items: [{
 *                 xtype: 'component',
 *                 itemId: 'child',
 *                 listeners: {
 *                     customevent: 'onCustomEvent'
 *                 }
 *             }]
 *         }]
 *     });
 *     // The main container will not have references to a/b, each innerCt will
 *     console.log(ct.lookupReference('a'), ct.lookupReference('b'));
 *     var inner1 = ct.lookupReference('innerCt1');
 *     var inner2 = ct.lookupReference('innerCt2');
 * 
 *     console.log(inner1.lookupReference('a').id, inner1.lookupReference('b').id);
 *     console.log(inner2.lookupReference('a').id, inner2.lookupReference('b').id);
 * 
 * Similar to references, if a {@link Ext.app.ViewController} is attached to this view, it becomes
 * the {@link #defaultListenerScope}, which means un-scoped, late bound events will be directed
 * to the controller. This is powerful as it allows views to be totally declarative:
 * 
 *     Ext.define('MyApp.controller.Login', {
 *         extend : 'Ext.app.ViewController',
 *         alias : 'controller.login',
 *   
 *         init: function() {
 *             this.sendCount = 0;    
 *         },
 *
 *         onLoginClick : function(btn) {
 *             this.login();
 *         },
 *
 *         onFieldSpecialKey : function(field, e) {
 *             if (e.getKey() === e.ENTER) {
 *                this.login();
 *             }
 *         },
 *
 *         login : function() {
 *            var form = this.lookupReference('form');
 *             this.lookupReference('error').hide();
 *             if (form.isValid()) {
 *                 console.log('Do the login!');
 *                 // Server responded...
 *                 if (++this.sendCount % 2 === 0) {
 *                     this.onServerSuccess();
 *                 } else {
 *                     this.onServerFailure();
 *                 }
 *             }
 *         },
 *   
 *         onServerSuccess: function() {
 *             // Proceed   
 *             console.log('All good');
 *         },
 *   
 *         onServerFailure: function() {
 *             var error = this.lookupReference('error');
 *             error.update('Invalid username/password');
 *             error.show();
 *         }
 *     });
 *
 *     Ext.define('MyApp.view.Login', {
 *         extend : 'Ext.window.Window',
 *         controller : 'login',
 *         referenceHolder: true,
 *
 *         title : 'Login',
 *         width : 400,
 *
 *         items : [{
 *             xtype : 'form',
 *             reference : 'form',
 *             border : false,
 *             bodyPadding : 10,
 *             defaultType : 'textfield',
 *             defaults : {
 *                 anchor : '90%',
 *                 allowBlank : false,
 *                 enableKeyEvents : true
 *             },
 *             items : [{
 *                 xtype: 'component',
 *                 reference: 'error',
 *                 hidden: true,
 *                 margin: '0 0 10 0',
 *                 style: 'color: red;'
 *             }, {
 *                 name : 'username',
 *                 fieldLabel : 'Username',
 *                 reference : 'username',
 *                 listeners : {
 *                     specialkey : 'onFieldSpecialKey'
 *                 }
 *             }, {
 *                 name : 'password',
 *                 fieldLabel : 'Password',
 *                 reference : 'password',
 *                 inputType : 'password',
 *                 listeners : {
 *                     specialkey : 'onFieldSpecialKey'
 *                 }
 *             }]
 *         }],
 *         buttons : ['->', {
 *             text : 'Login',
 *             listeners : {
 *                 click : 'onLoginClick'
 *             }
 *         }]
 *     });
 *
 * ## Adding via remote configuration
 *
 * A server side script can be used to add Components which are generated dynamically on the server.
 * An example of adding a GridPanel to a TabPanel where the GridPanel is generated by the server
 * based on certain parameters:
 *
 *     // execute an Ajax request to invoke server side script:
 *     Ext.Ajax.request({
 *         url: 'gen-invoice-grid.php',
 *         // send additional parameters to instruct server script
 *         params: {
 *             startDate: Ext.getCmp('start-date').getValue(),
 *             endDate: Ext.getCmp('end-date').getValue()
 *         },
 *         // process the response object to add it to the TabPanel:
 *         success: function(xhr) {
 *             var newComponent = eval(xhr.responseText); // see discussion below
 *             myTabPanel.add(newComponent); // add the component to the TabPanel
 *             myTabPanel.setActiveTab(newComponent);
 *         },
 *         failure: function() {
 *             Ext.Msg.alert("Grid create failed", "Server communication failure");
 *         }
 *     });
 *
 * The server script needs to return a JSON representation of a configuration object, which,
 * when decoded will return a config object with an {@link Ext.Component#xtype xtype}. The server
 * might return the following JSON:
 *
 *     {
 *         "xtype": 'grid',
 *         "title": 'Invoice Report',
 *         "store": {
 *             "model": 'Invoice',
 *             "proxy": {
 *                 "type": 'ajax',
 *                 "url": 'get-invoice-data.php',
 *                 "reader": {
 *                     "type": 'json'
 *                     "record": 'transaction',
 *                     "idProperty": 'id',
 *                     "totalRecords": 'total'
 *                 })
 *             },
 *             "autoLoad": {
 *                 "params": {
 *                     "startDate": '01/01/2008',
 *                     "endDate": '01/31/2008'
 *                 }
 *             }
 *         },
 *         "headers": [
 *             {"header": "Customer", "width": 250, "dataIndex": 'customer', "sortable": true},
 *             {"header": "Invoice Number", "width": 120, "dataIndex": 'invNo', "sortable": true},
 *             {"header": "Invoice Date", "width": 100, "dataIndex": 'date',
 *              "renderer": Ext.util.Format.dateRenderer('M d, y'), "sortable": true},
 *             {"header": "Value", "width": 120, "dataIndex": 'value', "renderer": 'usMoney',
 *              "sortable": true}
 *         ]
 *     }
 *
 * When the above code fragment is passed through the `eval` function in the success handler
 * of the Ajax request, the result will be a config object which, when added to a Container,
 * will cause instantiation of a GridPanel. **Be sure that the Container is configured with a layout
 * which sizes and positions the child items to your requirements.**
 *
 * **Note:** since the code above is _generated_ by a server script, the `autoLoad` params
 * for the Store, the user's preferred date format, the metadata to allow generation of the Model
 * layout, and the ColumnModel can all be generated into the code since these are all known
 * on the server.
 */
Ext.define('Ext.container.Container', {
    extend: 'Ext.Component',

    xtype: 'container',

    alternateClassName: [
        'Ext.Container',
        'Ext.AbstractContainer'
    ],

    requires: [
        'Ext.Action',
        'Ext.util.MixedCollection',
        'Ext.layout.container.Auto',
        'Ext.ZIndexManager',
        'Ext.util.ItemCollection'
    ],

    mixins: [
        'Ext.mixin.Queryable',
        'Ext.mixin.Container',
        'Ext.mixin.FocusableContainer'
    ],

    /**
     * @cfg renderTpl
     * @inheritdoc
     */
    renderTpl:
        '<tpl if="hasTabGuard">{% this.renderTabGuard(out, values, \'before\'); %}</tpl>' +
        '{% this.renderContainer(out,values) %}' +
        '<tpl if="hasTabGuard">{% this.renderTabGuard(out, values, \'after\'); %}</tpl>',

    // <editor-fold desc="Config">
    // ***********************************************************************************
    // Begin Config
    // ***********************************************************************************

    config: {
        /**
         * @cfg {Object} actions
         * An object containing properties which define named {@link Ext.Action actions}
         * for this container and any descendant components.
         *
         * An Action encapsulates a shareable, reusable set of properties which define a
         * "clickable" UI component such as a {@link Ext.button.Button button} or
         * {@link Ext.menu.Item menu item}, or {@link Ext.panel.Panel#tools panel header tool},
         * or an {@link Ext.grid.column.Action ActionColumn item}
         *
         * An Action, or more conveniently, the *name* of an action prefixed with `'@'`
         * may be used as a config object for creating child components which use a `handler`
         * config property to reference a Controller method to invoke when the component is
         * clicked.
         *
         * The property name is the action name, which may then be used as a child item
         * configuration in an {@link Ext.container.Container#cfg!items items} configuration in
         * any descendant component such as a toolbar or a menu, or in a
         * {@link Ext.panel.Panel#tools tools} configuration of a Panel.
         *
         * The property value is a configuration object for any clickable component.
         *
         * See the {@link Ext.Action} class for an example of reusable Actions.
         * @since 6.2.0
         */
        actions: null
    },

    /**
     * @cfg {String/Number} activeItem
     * A string component id or the numeric index of the component that should be
     * initially activated within the container's layout on render.  For example,
     * activeItem: 'item-1' or activeItem: 0 (index 0 = the first item in the
     * container's collection).  activeItem only applies to layout styles that can
     * display items one at a time (like {@link Ext.layout.container.Card} and
     * {@link Ext.layout.container.Fit}).
     *
     * @since 2.3.0
     */

    /**
     * @cfg {Boolean} autoDestroy
     * If true the container will automatically destroy any contained component that is removed
     * from it, else destruction must be handled manually.
     * @since 2.3.0
     */
    autoDestroy: true,

    /**
     * @cfg {String[]} bubbleEvents
     * An array of events that, when fired, should be bubbled to any parent container.
     * See {@link Ext.util.Observable#enableBubble}.
     * @since 3.4.0
     */

    /**
     * @cfg {Object/Function} defaults
     * This option is a means of applying default settings to all added items whether added
     * through the {@link #cfg-items} config or via the {@link #method-add} or {@link #insert}
     * methods.
     *
     * Defaults are applied to both config objects and instantiated components conditionally
     * so as not to override existing properties in the item (see {@link Ext#applyIf}).
     *
     * If the defaults option is specified as a function, then the function will be called
     * using this Container as the scope (`this` reference) and passing the added item as
     * the first parameter. Any resulting object from that call is then applied to the item
     * as default properties.
     *
     * For example, to automatically apply padding to the body of each of a set of
     * contained {@link Ext.panel.Panel} items, you could pass:
     * `defaults: {bodyStyle:'padding:15px'}`.
     *
     * Usage:
     *
     *     defaults: { // defaults are applied to items, not the container
     *         scrollable: true
     *     },
     *     items: [
     *         // default will not be applied here, panel1 will be scrollable: false
     *         {
     *             xtype: 'panel',
     *             id: 'panel1',
     *             scrollable: false
     *         },
     *         // this component will have scrollable: true
     *         new Ext.panel.Panel({
     *             id: 'panel2'
     *         })
     *     ]
     *
     * @since 2.3.0
     */

    /**
     * @cfg {String} defaultType
     * The default {@link Ext.Component xtype} of child Components to create in this Container when
     * a child item is specified as a raw configuration object, rather than as an instantiated
     * Component.
     * @since 2.3.0
     */
    defaultType: 'panel',

    /**
     * @cfg {Boolean} detachOnRemove
     * True to move any component to the {@link Ext#getDetachedBody detachedBody} when the component
     * is removed from this container. This option is only applicable when the component
     * is not destroyed while being removed, see {@link #autoDestroy} and {@link #method-remove}.
     * If this option is set to false, the DOM of the component will remain in the current place
     * until it is explicitly moved.
     */
    detachOnRemove: true,

    // eslint-disable-next-line max-len
    // @cmd-auto-dependency {aliasPrefix: "widget.", typeProperty: "xtype", defaultTypeProperty: "defaultType", defaultsProperty: "defaults"}
    /**
     * @cfg {Object/Object[]} items
     * A single item, or an array of child Components to be added to this container
     *
     * **Unless configured with a {@link #cfg!layout}, a Container simply renders child
     * Components serially into its encapsulating element and performs no sizing or
     * positioning upon them.**
     *
     * Example:
     *
     *     // specifying a single item
     *     items: {...},
     *     layout: 'fit',    // The single items is sized to fit
     *
     *     // specifying multiple items
     *     items: [{...}, {...}],
     *     layout: 'hbox', // The items are arranged horizontally
     *
     * Each item may be:
     *
     * - A {@link Ext.Component Component}
     * - A Component configuration object
     *
     * If a configuration object is specified, the actual type of Component to be
     * instantiated my be indicated by using the {@link Ext.Component#xtype xtype} option.
     *
     * Every Component class has its own {@link Ext.Component#xtype xtype}.
     *
     * If an {@link Ext.Component#xtype xtype} is not explicitly specified, the
     * {@link #cfg-defaultType} for the Container is used, which by default is usually `panel`.
     *
     * # Notes:
     *
     * Ext uses lazy rendering. Child Components will only be rendered
     * should it become necessary. Items are automatically laid out when they are first
     * shown (no sizing is done while hidden), or in response to a {@link #method-updateLayout}
     * call.
     *
     * Do not specify {@link Ext.panel.Panel#contentEl contentEl} or
     * {@link Ext.panel.Panel#html html} with `items`.
     *
     * @since 2.3.0
     */
    items: undefined,  // Not "null" so that Ext.applyIf(this, {items: []}) works

    // @cmd-auto-dependency { aliasPrefix : "layout." }
    /**
     * @cfg {Ext.enums.Layout/Object} layout
     * **Important**: In order for child items to be correctly sized and
     * positioned, typically a layout manager **must** be specified through
     * the `layout` configuration option.
     *
     * The sizing and positioning of child {@link #cfg-items} is the responsibility of
     * the Container's layout manager which creates and manages the type of layout
     * you have in mind.  For example:
     *
     * If the layout configuration is not explicitly specified for
     * a general purpose container (e.g. Container or Panel) the
     * {@link Ext.layout.container.Auto default layout manager} will be used
     * which does nothing but render child components sequentially into the
     * Container (no sizing or positioning will be performed in this situation).
     *
     * **layout** may be specified as either as an Object or as a String:
     *
     * ## Specify as an Object
     *
     * Example usage:
     *
     *     layout: {
     *         type: 'vbox',
     *         align: 'left'
     *     }
     *
     *   - **type**
     *
     *     The layout type to be used for this container.  If not specified,
     *     a default {@link Ext.layout.container.Auto} will be created and used.
     *
     *     Valid layout <code>type</code> values are listed in {@link Ext.enums.Layout}.
     *
     *   - Layout specific configuration properties
     *
     *     Additional layout specific configuration properties may also be
     *     specified. For complete details regarding the valid config options for
     *     each layout type, see the layout class corresponding to the `type`
     *     specified.
     *
     * ## Specify as a String
     *
     * Example usage:
     *
     *     layout: 'vbox'
     *
     *   - **layout**
     *
     *     The layout `type` to be used for this container (see {@link Ext.enums.Layout}
     *     for list of valid values).
     *
     *     Additional layout specific configuration properties. For complete
     *     details regarding the valid config options for each layout type, see the
     *     layout class corresponding to the `layout` specified.
     *
     * ## Configuring the default layout type
     *
     * If a certain Container class has a default layout (For example a
     * {@link Ext.toolbar.Toolbar Toolbar} with a default `Box` layout), then to simply configure
     * the default layout, use an object, but without the `type` property:
     *
     *
     *     xtype: 'toolbar',
     *     layout: {
     *         pack: 'center'
     *     }
     *
     * @since 2.3.0
     *
     */
    layout: 'auto',

    /**
     * @cfg {Boolean} suspendLayout
     * If true, suspend calls to updateLayout. Useful when batching multiple adds to a container
     * and not passing them as multiple arguments or an array.
     */
    suspendLayout: false,

    /**
     * @cfg {String} defaultFocus
     *
     * Specifies a child Component to receive focus when this Container's {@link #method-focus}
     * method is called. Should be a valid {@link Ext.ComponentQuery query} selector.
     */

    /**
     * When set to `true`, two elements are added to the container's element. These are the
     * `{@link #tabGuardBeforeEl}` and `{@link #tabGuardAfterEl}`.
     * @cfg {Boolean} tabGuard
     * @private
     * @since 6.0.0
     */

    // ***********************************************************************************
    // End Config
    // ***********************************************************************************
    // </editor-fold>

    // <editor-fold desc="Properties">
    // ***********************************************************************************
    // Begin Properties
    // ***********************************************************************************

    /* eslint-disable indent, max-len */
    /**
     * @property {String/String[]/Ext.XTemplate} tabGuardTpl
     * This template is used to generate the `tabGuard` elements. It is used once per
     * element (see `{@link #tabGuardBeforeEl}` and `{@link #tabGuardAfterEl}`).
     * @private
     * @since 6.0.0
     */
    tabGuardTpl:
        // We use span instead of div because of IE bug/misfeature: it will focus
        // block elements upon clicking or calling node.focus() regardless of
        // tabIndex attribute. It doesn't do that with inline elements, hence span.
        '<span id="{id}-{tabGuardEl}" data-ref="{tabGuardEl}" aria-hidden="true"' +
            ' class="' + Ext.baseCSSPrefix + 'tab-guard ' +
                         Ext.baseCSSPrefix + 'tab-guard-{tabGuardPosition}"' +
            ' style="width:0px;height:0px;">' +
        '</span>',
    /* eslint-enable indent, max-len */

    /**
     * @property {Object} tabGuardElements
     * Read only object containing property names for tab guard elements, keyed by position.
     * @private
     * @since 6.2.0
     */
    tabGuardElements: {
        before: 'tabGuardBeforeEl',
        after: 'tabGuardAfterEl'
    },

    /**
     * @property {Number} tabGuardBeforeIndex The tabIndex attribute value to assign
     * to the "before" tab guard element. Default is `undefined` for automatic detection
     * from the DOM.
     * @private
     * @since 6.2.0
     */

    /**
     * @property {Number} tabGuardAfterIndex The tabIndex attribute value to assign
     * to the "after" tab guard element. Default is `undefined` for automatic detection
     * from the DOM.
     * @private
     * @since 6.2.0
     */

    /**
     * This element reference is generated when `{@link #tabGuard}` is `true`. This element
     * is generated before all `dockedItems` in the DOM.
     * @property {Ext.dom.Element} tabGuardBeforeEl
     * @private
     * @since 6.0.0
     */

    /**
     * This element reference is generated when `{@link #tabGuard}` is `true`. This element
     * is generated after all `dockedItems` in the DOM.
     * @property {Ext.dom.Element} tabGuardAfterEl
     * @private
     * @since 6.0.0
     */

    /**
     * @private
     */
    _applyDefaultsOptions: {
        defaults: true,
        strict: false
    },

    /**
     * @property ariaRole
     * @inheritdoc
     */
    ariaRole: 'presentation',

    /**
     * @cfg baseCls
     * @inheritdoc
     */
    baseCls: Ext.baseCSSPrefix + 'container',

    /**
     * @property {Number} layoutCounter
     * The number of container layout calls made on this object.
     * @private
     */
    layoutCounter: 0,

    // ***********************************************************************************
    // End Properties
    // ***********************************************************************************
    // </editor-fold>

    // <editor-fold desc="Events">
    // ***********************************************************************************
    // Begin Events
    // ***********************************************************************************

    /**
     * @event add
     * Fires after any {@link Ext.Component} is added or inserted into the container.
     * @param {Ext.container.Container} this
     * @param {Ext.Component} component The component that was added
     * @param {Number} index The index at which the component was added to the container's items
     * collection
     * @since 2.3.0
     */

    /**
     * @event afterlayout
     * Fires when the components in this container are arranged by the associated layout manager.
     * @param {Ext.container.Container} this
     * @param {Ext.layout.container.Container} layout The ContainerLayout implementation for this
     * container
     * @since 2.3.0
     */

    /**
     * @event beforeadd
     * Fires before any {@link Ext.Component} is added or inserted into the container.
     * A handler can return false to cancel the add.
     * @param {Ext.container.Container} this
     * @param {Ext.Component} component The component being added
     * @param {Number} index The index at which the component will be added to the container's items
     * collection
     * @since 2.3.0
     */

    /**
     * @event beforeremove
     * Fires before any {@link Ext.Component} is removed from the container.  A handler can return
     * false to cancel the remove.
     * @param {Ext.container.Container} this
     * @param {Ext.Component} component The component being removed
     * @since 2.3.0
     */

    /**
     * @event remove
     * Fires after any {@link Ext.Component} is removed from the container.
     * @param {Ext.container.Container} this
     * @param {Ext.Component} component The component that was removed
     * @since 2.3.0
     */

    /**
     * @event childmove
     * Fires after any {@link Ext.Component} has changed its ordinal position within the container.
     * @param {Ext.container.Container} this
     * @param {Ext.Component} component The component that was moved
     * @param {Number} prevIndex The previous ordinal position of the Component
     * @param {Number} newIndex The new ordinal position of the Component
     */

    // ***********************************************************************************
    // End Events
    // ***********************************************************************************
    // </editor-fold>

    // <editor-fold desc="Methods">
    // ***********************************************************************************
    // Begin Methods
    // ***********************************************************************************

    /**
     * Adds {@link Ext.Component Component}(s) to this Container.
     *
     * ## Description:
     *
     * - Fires the {@link #beforeadd} event before adding.
     * - The Container's {@link #defaults default config values} will be applied
     *   accordingly (see `{@link #defaults}` for details).
     * - Fires the `{@link #event-add}` event after the component has been added.
     *
     * ## Notes:
     *
     * If the Container is __already rendered__ when `add`
     * is called, it will render the newly added Component into its content area.
     *
     * **If** the Container was configured with a size-managing {@link #cfg!layout} manager,
     * the Container will recalculate its internal layout at this time too.
     *
     * Note that the default layout manager simply renders child Components sequentially
     * into the content area and thereafter performs no sizing.
     *
     * If adding multiple new child Components, pass them as an array to the `add` method,
     * so that only one layout recalculation is performed.
     *
     *     tb = new Ext.toolbar.Toolbar({
     *         renderTo: document.body
     *     });  // toolbar is rendered
     *     // add multiple items.
     *     // default type for Toolbar is 'button')
     *     tb.add([{text:'Button 1'}, {text:'Button 2'}]);
     *
     * To inject components between existing ones, use the {@link #insert} method.
     *
     * ## Warning:
     *
     * Components directly managed by the BorderLayout layout manager may not be removed
     * or added.  See the Notes for {@link Ext.layout.container.Border BorderLayout} for
     * more details.
     *
     * @param {Ext.Component[]|Object[]/Ext.Component.../Object...} component
     * Either one or more Components to add or an Array of Components to add.
     * See `{@link #cfg-items}` for additional information.
     *
     * @return {Ext.Component[]/Ext.Component} The Components that were added.
     *
     * @since 2.3.0
     */
    add: function() {
        var me = this,
            args = Ext.Array.slice(arguments),
            index = (typeof args[0] === 'number') ? args.shift() : -1,
            layout = me.getLayout(),
            needsLayout = false,
            addingArray, items, i, length, item, pos, ret, instanced;

        if (args.length === 1 && Ext.isArray(args[0])) {
            items = args[0];
            addingArray = true;
        }
        else {
            items = args;
        }

        if (me.rendered) {
            Ext.suspendLayouts(); // suspend layouts while adding items...
        }

        ret = items = me.prepareItems(items, true);
        length = items.length;

        if (!addingArray && length === 1) { // an array of 1 should still return an array...
            ret = items[0];
        }

        // loop
        for (i = 0; i < length; i++) {
            item = items[i];

            //<debug>
            if (!item) {
                Ext.raise("Cannot add null item to Container with itemId/id: " + me.getItemId());
            }

            if (item.destroyed) {
                Ext.raise("Cannot add destroyed item '" + item.getId() + "' to Container '" +
                          me.getId() + "'");
            }
            //</debug>

            pos = (index < 0) ? me.items.length : (index + i);
            instanced = !!item.instancedCmp;
            delete item.instancedCmp;

            // Floating Components are not added into the items collection,
            // but to a separate floatingItems collection
            if (item.floating) {
                (me.floatingItems || (me.floatingItems = new Ext.util.ItemCollection())).add(item);
                item.onAdded(me, pos, instanced);
                delete item.$initParent;

                if (me.hasListeners.add) {
                    me.fireEvent('add', me, item, pos);
                }
            }
            // eslint-disable-next-line max-len
            else if ((!me.hasListeners.beforeadd || me.fireEvent('beforeadd', me, item, pos) !== false) && me.onBeforeAdd(item) !== false) {
                me.items.insert(pos, item);
                item.onAdded(me, pos, instanced);
                delete item.$initParent;

                if (me.focusableContainer) {
                    me.onFocusableChildAdd(item);
                }

                me.onAdd(item, pos);
                layout.onAdd(item, pos);

                needsLayout = true;

                if (me.hasListeners.add) {
                    me.fireEvent('add', me, item, pos);
                }
            }

            // This flag may be set by onBeforeAdd to tell the layout system that any remove
            // is temporary and that focus should not be reverted because Ext.layout.Layout#moveItem
            // will be moving things into place soon, and that will handle keeping focus stable.
            item.isLayoutMoving = false;
        }

        // We need to update our layout after adding all passed items
        // Unless we only added floating items.
        if (needsLayout) {
            me.updateLayout();
        }

        if (me.rendered) {
            if (length && me.focusableContainer) {
                me.$initFocusableContainerAfterLayout = true;
            }

            Ext.resumeLayouts(true);
        }

        return ret;
    },

    onAdded: function(container, pos, instanced) {
        this.callParent([container, pos, instanced]);
        this.containerOnAdded(container, instanced);
    },

    /**
     * @method onRemoved
     * @inheritdoc
     */
    onRemoved: function(destroying) {
        this.containerOnRemoved(destroying);
        this.callParent(arguments);
    },

    afterComponentLayout: function() {
        var floaters = this.floatingItems,
            floaterCount,
            i, floater;

        this.callParent(arguments);

        // Contained, unrendered, autoShow items must be shown upon next layout of the Container
        if (floaters) {
            floaters = floaters.items;
            floaterCount = floaters.length;

            for (i = 0; i < floaterCount; i++) {
                floater = floaters[i];

                if (!floater.rendered && floater.autoShow) {
                    floater.show();
                }
            }
        }
    },

    /**
     * Invoked after the Container has laid out (and rendered if necessary)
     * its child Components.
     *
     * @param {Ext.layout.container.Container} layout
     *
     * @template
     * @protected
     */
    afterLayout: function(layout) {
        var me = this;

        ++me.layoutCounter;

        if (me.hasListeners.afterlayout) {
            me.fireEvent('afterlayout', me, layout);
        }

        // focusableContainer could have changed between setting the flag in add()
        // and actual layout, so check again
        if (me.focusableContainer && me.$initFocusableContainerAfterLayout) {
            me.initFocusableContainer();
        }

        delete me.$initFocusableContainerAfterLayout;
    },

    doDestroy: function() {
        var me = this,
            items = me.items,
            floatingItems = me.floatingItems,
            c;

        if (me.focusableContainer) {
            me.destroyFocusableContainer();
        }

        if (items) {
            while ((c = items.first())) {
                me.doRemove(c, true);
            }

            items.destroy();

            me.items = null;
        }

        if (floatingItems) {
            while ((c = floatingItems.first())) {
                me.doRemove(c, true);
            }

            floatingItems.destroy();

            me.floatingItems = null;
        }

        Ext.destroy(me.layout);

        me.callParent();
    },

    beforeRender: function() {
        var me = this,
            layout = me.getLayout(),
            targetCls;

        // In beforeRender in the parent we call disable to allow onDisable to be applied here.
        me.preventChildDisable = true;
        me.callParent();
        me.preventChildDisable = false;

        if (!layout.initialized) {
            layout.initLayout();
        }

        targetCls = layout.targetCls;

        if (targetCls) {
            me.applyTargetCls(targetCls);
        }
    },

    /**
     * Cascades down the component/container heirarchy from this component (passed in
     * the first call), calling the specified function with each component. The scope
     * (this reference) of the function call will be the scope provided or the current
     * component. The arguments to the function will be the args provided or the current
     * component. If the function returns false at any point, the cascade is stopped on
     * that branch.
     * @param {Function} fn The function to call
     * @param {Object} [scope] The scope of the function(defaults to current component)
     * @param {Array} [origArgs] The args to call the function with. The current component
     * always passed as the last argument.
     * @return {Ext.Container} this
     * @since 2.3.0
     */
    cascade: function(fn, scope, origArgs) {
        var me = this,
            cs = me.items ? me.items.items : [],
            len = cs.length,
            i = 0,
            c,
            args = origArgs ? origArgs.concat(me) : [me],
            componentIndex = args.length - 1;

        if (fn.apply(scope || me, args) !== false) {
            for (; i < len; i++) {
                c = cs[i];

                if (c.cascade) {
                    c.cascade(fn, scope, origArgs);
                }
                else {
                    args[componentIndex] = c;
                    fn.apply(scope || c, args);
                }
            }
        }

        return this;
    },

    /**
     * Determines whether the passed Component is either an immediate child of this Container,
     * or whether it is a descendant.
     *
     * @param {Ext.Component} comp The Component to test.
     * @param {Boolean} [deep=false] Pass `true` to test for the Component being a descendant
     * at any level.
     * @return {Boolean} `true` if the passed Component is contained at the specified level.
     */
    contains: function(comp, deep) {
        var result = false;

        if (deep) {
            this.cascade(function(c) {
                // Only test if the item is a container
                if (c.contains && c.contains(comp)) {
                    result = true;

                    return false;
                }
            });

            return result;
        }
        else {
            return this.items.contains(comp) ||
                   (this.floatingItems && this.floatingItems.contains(comp));
        }
    },

    /**
     * Disables all child input fields and buttons.
     * @param silent
     * @param fromParent (private)
     */
    disable: function(silent, fromParent) {
        var me = this,
            wasDisabled = me.disabled,
            itemsToDisable, len, i;

        me.callParent([silent, fromParent]);

        if (!fromParent && !me.preventChildDisable && !wasDisabled) {
            itemsToDisable = me.getChildItemsToDisable();
            len = itemsToDisable.length;

            for (i = 0; i < len; i++) {
                itemsToDisable[i].disable(silent, true);
            }
        }

        if (me.focusableContainer) {
            me.activateFocusableContainer(false);
        }

        return me;
    },

    /**
     * Enables all child input fields and buttons.
     * @param silent
     * @param fromParent (private)
     */
    enable: function(silent, fromParent) {
        var me = this,
            wasDisabled = me.disabled,
            itemsToDisable, len, i;

        me.callParent([silent, fromParent]);

        if (wasDisabled) {
            itemsToDisable = me.getChildItemsToDisable();
            len = itemsToDisable.length;

            for (i = 0; i < len; i++) {
                itemsToDisable[i].enable(silent, true);
            }
        }

        if (me.focusableContainer) {
            me.activateFocusableContainer(true);
        }

        return me;
    },

    /**
     * Return the immediate child Component in which the passed element is located.
     * @param {Ext.dom.Element/HTMLElement/String} el The element to test (or ID of element).
     * @param {Boolean} deep If `true`, returns the deepest descendant Component which contains
     * the passed element.
     * @return {Ext.Component} The child item which contains the passed element.
     */
    getChildByElement: function(el, deep) {
        var item,
            itemEl,
            i = 0,
            it = this.getRefItems(),
            ln = it.length;

        el = Ext.getDom(el);

        for (; i < ln; i++) {
            item = it[i];
            itemEl = item.getEl();

            if (itemEl && ((itemEl.dom === el) || itemEl.contains(el))) {
                return (deep && item.getChildByElement) ? item.getChildByElement(el, deep) : item;
            }
        }

        return null;
    },

    /**
     * Examines this container's {@link #property-items} **property** and gets a direct child
     * component of this container.
     *
     * @param {String/Number} comp This parameter may be any of the following:
     *
     * - a **String** : representing the {@link Ext.Component#itemId itemId}
     *   or {@link Ext.Component#id id} of the child component.
     * - a **Number** : representing the position of the child component
     *   within the {@link #property-items} **property**
     *
     * For additional information see {@link Ext.util.MixedCollection#get}.
     *
     * @return {Ext.Component} The component (if found).
     *
     * @since 2.3.0
     */
    getComponent: function(comp) {
        if (Ext.isObject(comp)) {
            comp = comp.getItemId();
        }

        // eslint-disable-next-line vars-on-top
        var c = this.items.get(comp),
            floaters = this.floatingItems;

        // Only allow finding by index on the main items container
        if (!c && floaters && typeof comp !== 'number') {
            c = floaters.get(comp);
        }

        return c;
    },

    /**
     * @protected
     * Returns the focus holder element associated with this Container.
     * By default, this is the Container's target element; however if {@link #defaultFocus}
     * is defined, the child component referenced by that property will be found
     * and returned instead.
     *
     * @return {Ext.dom.Element} the focus holding element.
     */
    getFocusEl: function() {
        var delegate = this.getDefaultFocus();

        if (delegate) {
            // DO NOT drill down to delegate's focusEl or return its main el here.
            // Container's getFocusEl() is supposed to return delegates as components,
            // otherwise things break elsewhere.
            return delegate;
        }
        else if (this.focusable) {
            return this.getTargetEl();
        }

        // Containers that are not focusable should not return a focusEl
        return undefined;
    },

    /**
     * Returns the {@link Ext.layout.container.Container layout} instance currently associated
     * with this Container. If a layout has not been instantiated yet, that is done first
     * @return {Ext.layout.container.Container} The layout
     */
    getLayout: function() {
        var me = this,
            layout = me.layout;

        if (!layout || !layout.isLayout) {
            me.setLayout(layout);
        }

        return me.layout;
    },

    /**
     * @protected
     * Used by {@link Ext.ComponentQuery ComponentQuery}, {@link #child} and {@link #down}
     * to retrieve all of the items which can potentially be considered a child of this Container.
     *
     * This may be overriden by Components which have ownership of Components
     * that are not contained in the {@link #property-items} collection.
     *
     * NOTE: IMPORTANT note for maintainers:
     * Items are returned in tree traversal order. Each item is appended to the result array
     * followed by the results of that child's getRefItems call.
     * Floating child items are appended after internal child items.
     */
    getRefItems: function(deep) {
        var me = this,
            items = me.items.items,
            len = items.length,
            i = 0,
            item,
            result = [];

        for (; i < len; i++) {
            item = items[i];
            result[result.length] = item;

            if (deep && item.getRefItems) {
                result.push.apply(result, item.getRefItems(true));
            }
        }

        // Append floating items to the list.
        if (me.floatingItems) {
            items = me.floatingItems.items;
            len = items.length;

            for (i = 0; i < len; i++) {
                item = items[i];
                result[result.length] = item;

                if (deep && item.getRefItems) {
                    result.push.apply(result, item.getRefItems(true));
                }
            }
        }

        return result;
    },

    /**
     * Finds the configured default focus item. See {@link #defaultFocus}.
     */
    getDefaultFocus: function() {
        var defaultFocus = this.defaultFocus,
            result;

        // This might not work during initConfig
        if (defaultFocus && !this.isConfiguring) {
            result = this.down(defaultFocus);
        }

        // Returning undefined is ok
        return result;
    },

    setDefaultFocus: function(value) {
        this.defaultFocus = value;
    },

    initComponent: function() {
        var me = this;

        me.callParent();

        me.getLayout();

        // Set a flag to say we're constructing children, can be useful
        // to know during construction time to save work
        me.constructing = true;

        me.initItems();

        if (me.disabled) {
            me.disabled = false;
            me.disable(true);
        }

        me.reference = me.setupReference(me.reference);

        delete me.constructing;
    },

    /**
     * This method is called to initialize the `items` collection. A derived class can
     * override this method to do any last minute manipulation of `items` and then call
     * this method using `callParent`. Upon return, the `items` will no longer be a simple
     * array.
     * @protected
     */
    initItems: function() {
        var me = this,
            items = me.items;

        if (!items || !items.isMixedCollection) {
            // allow the items collection to be pre-initialized.
            // (used by Ext.draw.ComponentBase)
            /**
             * The Collection containing all the child items of this container.
             * @property {Ext.util.ItemCollection} items
             * @since 2.3.0
             */
            me.items = new Ext.util.ItemCollection();

            /**
             * The MixedCollection containing all the floating child items of this container.
             * Will be `undefined` if there are no floating child items.
             * @property {Ext.util.MixedCollection} floatingItems
             * @since 4.1.0
            */

            if (items) {
                if (!Ext.isArray(items)) {
                    items = [items];
                }

                me.$initingItems = true;
                me.add(items);
                delete me.$initingItems;
            }
        }
    },

    /**
     * Called by `getInherited` to initialize the inheritedState the first time it is
     * requested.
     * @protected
     */
    initInheritedState: function(inheritedState, inheritedStateInner) {
        var me = this,
            layout = me.layout;

        me.callParent([inheritedState, inheritedStateInner]);

        if (me.collapsed) {
            inheritedState.collapsed = true;
        }

        me.initContainerInheritedState(inheritedState, inheritedStateInner);

        if (layout && layout.initInheritedState) {
            layout.initInheritedState(inheritedState, inheritedStateInner);
        }
    },

    /**
     * Inserts a Component into this Container at a specified index. Fires the
     * {@link #beforeadd} event before inserting, then fires the {@link #event-add}
     * event after the Component has been inserted.
     *
     * @param {Number} index The index at which the Component will be inserted
     * into the Container's items collection
     *
     * @param {Ext.Component/Object/Ext.Component[]/Object[]} component The child Component
     * or config object to insert.
     *
     * Ext uses lazy rendering, and will only render the inserted Component should
     * it become necessary.
     *
     * A Component config object may be passed in order to avoid the overhead of
     * constructing a real Component object if lazy rendering might mean that the
     * inserted Component will not be rendered immediately. To take advantage of
     * this 'lazy instantiation', set the {@link Ext.Component#xtype} config
     * property to the registered type of the Component wanted.
     *
     * You can pass an array of Component instances and config objects.
     *
     * For a list of all available xtypes, see {@link Ext.enums.Widget}.
     *
     * @return {Ext.Component} component The Component (or config object) that was
     * inserted with the Container's default config values applied.
     *
     * @since 2.3.0
     */
    insert: function(index, component) {
        var compIdx;

        if (component && component.isComponent) {
            compIdx = this.items.indexOf(component);

            if (compIdx !== -1) {
                return this.move(compIdx, index);
            }
        }

        return this.add(index, component);
    },

    /**
     * @protected
     * Called when a raw config object is added to this container either during initialization
     * of the {@link #cfg-items} config, or when new items are {@link #method-add added},
     * or {@link #method-insert inserted}.
     *
     * This method converts the passed object into an instanced child component.
     *
     * This may be overridden in subclasses when special processing needs to be applied to child
     * creation. 
     *
     * @param {Object} comp The config object being added.
     * @return {Ext.Component} The component to be added.
     */
    lookupComponent: function(comp) {
        var me = this,
            defaultType = me.defaultType,
            wasAction;

        if (!comp.isComponent) {
            if (typeof comp === 'string') {
                // First char '@' means its an Action name.
                // Search this and all ancestors for a matching named Action
                // with which to create the Component.
                if (!(wasAction = (comp[0] === '@'))) {
                    // String used as a global component ID. Deprecated practice!
                    return Ext.ComponentManager.get(comp);
                }

                comp = me.getAction(comp.substr(1));
                defaultType = me.defaultActionType || defaultType;
            }

            comp = Ext.ComponentManager.create(comp, defaultType);

            // We need the inherited state to be invalidated upon add
            // because the create will have been performed outside of the
            // ownership hierarchy. The Action has no owner view (Actions are shareable).
            // This flag indicates to the new item's onAdded->onInheritedAdded machinery
            // that the inherited state must be invalidated.
            if (wasAction) {
                comp.instancedCmp = true;
            }
        }

        return comp;
    },

    /**
     * Moves a Component within the Container. This method does **not** account for things
     * like splitter components added by a layout. To better handle these situations, it
     * is recommended to use `{@link #moveBefore}` or `{@link #moveAfter}` instead.
     *
     * @param {Number/Ext.Component} fromIdx The index/component to move.
     * @param {Number} toIdx The new index for the Component.
     * @return {Ext.Component} component The Component that was moved.
     * @deprecated 5.0 Use `{@link #moveBefore}` or `{@link #moveAfter}` instead.
     */
    move: function(fromIdx, toIdx) {
        var me = this,
            items = me.items,
            item;

        if (fromIdx.isComponent) {
            fromIdx = items.indexOf(fromIdx);
        }

        item = items.getAt(fromIdx);

        if (fromIdx !== toIdx) {
            item = items.removeAt(fromIdx);

            if (item === false) {
                return false;
            }

            toIdx = Math.min(toIdx, items.getCount());
            items.insert(toIdx, item);

            me.onMove(item, fromIdx, toIdx);

            if (me.hasListeners.childmove) {
                me.fireEvent('childmove', me, item, fromIdx, toIdx);
            }

            me.updateLayout();
        }

        return item;
    },

    /**
     * Moves the given `item(s)` into this container in front of `before`. This method 
     * will account for layout-generated components like splitters and should be used 
     * instead of index based `{@link #method-move}`. If `before` is `null` then the 
     * `item` will be the last item in this container.
     * 
     *     var tb = Ext.create({
     *         xtype: 'toolbar',
     *         renderTo: Ext.getBody(),
     *         items: [{
     *             text: 'one'
     *         }, {
     *             text: 'two'
     *         }]
     *     });
     *
     *     // moves the 'two' button before the 'one' button
     *     tb.moveBefore(tb.getComponent(1), tb.getComponent(0));
     * 
     * @param {Ext.Component/Ext.Component[]} item The item to move. May be a component, 
     * component configuration object, or an array of either.
     * @param {Ext.Component} before The reference component. May be `null`.
     * @return {Ext.Component/Ext.Component[]} The moved item(s).
     * @since 5.0.0
     */
    moveBefore: function(item, before) {
        var activeEl,
            refocusEl;

        if (item !== before) {
            activeEl = Ext.Element.getActiveElement(true);

            // Do not disturb application focus state when moving a focused component.
            // Must test whether we contain the activeEl, not the containsFocus flag
            // because of asynchronous focus events.
            if (item.el && item.el.contains(activeEl)) {
                refocusEl = activeEl;
                refocusEl.suspendFocusEvents();
                item.isLayoutMoving = true;
            }

            item = this.layout.moveItemBefore(item, before);

            if (refocusEl) {
                item.isLayoutMoving = false;
                refocusEl.focus();
                refocusEl.resumeFocusEvents();
            }
        }

        return item;
    },

    /**
     * Moves the given `item(s)` into this container following `after`. This method will
     * account for layout-generated components like splitters and should be used instead
     * of index based `{@link #method-move}`. If `after` is `null` then the `item` will be the
     * first item in this container.
     * 
     *     var tb = Ext.create({
     *         xtype: 'toolbar',
     *         renderTo: Ext.getBody(),
     *         items: [{
     *             text: 'one'
     *         }, {
     *             text: 'two'
     *         }]
     *     });
     *
     *     // moves the 'one' button after the 'two' button
     *     tb.moveAfter(tb.getComponent(0), tb.getComponent(1));
     * 
     * @param {Ext.Component/Ext.Component[]} item The item to move. May be a component, 
     * component configuration object, or an array of either.
     * @param {Ext.Component} after The reference component. May be `null`.
     * @return {Ext.Component/Ext.Component[]} The moved item(s).
     * @since 5.0.0
     */
    moveAfter: function(item, after) {
        var layout = this.layout,
            index;

        if (item !== after) {
            index = after ? layout.getMoveAfterIndex(after) : 0;
            item = this.moveBefore(item, this.items.getAt(index));
        }

        return item;
    },

    /**
     * A method to find a child component after the passed child parameter. If a selector is also
     * provided, the first child component matching the selector will be returned.
     *
     * @param {Ext.Component} child The child to use as a starting point to find the next child.
     * @param {String} [selector] A {@link Ext.ComponentQuery} selector to find the next child.
     * This will return the next child matching this selector. This parameter is optional.
     * @return {Ext.Component} The next child found, `null` if no child found.
     */
    nextChild: function(child, selector) {
        var me = this,
            items = me.items,
            childIndex = items.indexOf(child),
            i = 0,
            len = items.length,
            result;

        if (childIndex !== -1) {
            if (selector) {
                for (; i < len; i++) {
                    result = items.getAt(childIndex + i);

                    if (!result || Ext.ComponentQuery.is(result, selector)) {
                        break;
                    }
                }
            }
            else {
                result = items.getAt(childIndex + 1);
            }
        }

        return result || null;
    },

    /**
     * @method
     * This method is invoked after a new Component has been added. It
     * is passed the Component which has been added. This method may
     * be used to update any internal structure which may depend upon
     * the state of the child items.
     *
     * @param {Ext.Component} component
     * @param {Number} position
     *
     * @template
     * @protected
     */
    onAdd: Ext.emptyFn,

    /**
     * This method is invoked before adding a new child Component. It
     * is passed the new Component, and may be used to modify the
     * Component, or prepare the Container in some way. Returning
     * false aborts the add operation.
     *
     * @param {Ext.Component} item
     *
     * @template
     * @protected
     */
    onBeforeAdd: function(item) {
        // Remove from current container without detaching it from the DOM if it's not us.
        var owner = item.ownerCt;

        if (item.isDetached) {
            item.reattachToBody();
        }

        if (owner && owner !== this) {
            item.isLayoutMoving = true;

            owner.remove(item, {
                destroy: false,
                detach: false
            });
        }
    },

    onMove: Ext.emptyFn,

    /**
     * @method
     * This method is invoked after a new Component has been
     * removed. It is passed the Component which has been
     * removed. This method may be used to update any internal
     * structure which may depend upon the state of the child items.
     *
     * @param {Ext.Component} component The removed component
     * @param {Boolean} isDestroying `true` if the the component is being destroyed in
     * the remove action
     *
     * @template
     * @protected
     */
    onRemove: Ext.emptyFn,

    onPosition: function() {
        this.callParent(arguments);
        this.repositionFloatingItems();
    },

    /**
     * @method onResize
     * @inheritdoc
     */
    onResize: function() {
        this.callParent(arguments);
        this.repositionFloatingItems();
    },

    /**
     * A method to find a child component before the passed child parameter. If a selector is also
     * provided, the first child component matching the selector will be returned.
     *
     * @param {Ext.Component} child The child to use as a starting point to find the previous child.
     * @param {String} [selector] A {@link Ext.ComponentQuery} selector to find the previous child.
     * This will return the first child matching this selector. This parameter is optional.
     * @return {Ext.Component} The previous child found, `null` if no child found.
     */
    prevChild: function(child, selector) {
        var me = this,
            items = me.items,
            childIndex = items.indexOf(child),
            i = 0,
            len = items.length,
            result;

        if (childIndex !== -1) {
            if (selector) {
                for (; i < len; i++) {
                    result = items.getAt(childIndex - i);

                    if (!result || Ext.ComponentQuery.is(result, selector)) {
                        break;
                    }
                }
            }
            else {
                result = items.getAt(childIndex - 1);
            }
        }

        return result || null;
    },

    /**
     * Removes a component from this container.  Fires the {@link #beforeremove} event
     * before removing, then fires the {@link #event-remove} event after the component has
     * been removed.
     *
     * @param {Ext.Component/String} component The component instance or id to remove.
     *
     * @param {Object} [autoDestroy] Flags to determine what to do with the removed component.
     * (May also be specified as a boolean `autoDestroy` flag for backward compatibility).
     * @param {Boolean} [autoDestroy.destroy] Defaults to this Container's {@link #autoDestroy}
     * config. Specifies whether to destroy the component being removed.
     * @param [autoDestroy.detach] Defaults to the {@link #detachOnRemove} configuration
     * Specifies whether to remove the component's DOM from the container and into
     * the {@link Ext#getDetachedBody detached body element}
     *
     * @return {Ext.Component} component The Component that was removed.
     * @since 2.3.0
     */
    remove: function(component, autoDestroy) {
        var me = this,
            c;

        // After destroying, items is nulled so we can't proceed
        if (me.destroyed || me.destroying) {
            return;
        }

        c = me.getComponent(component);

        //<debug>
        if (!arguments.length) {
            Ext.log.warn("Ext.container.Container: remove takes an argument of the component " +
                         "to remove. cmp.remove() is incorrect usage.");
        }
        //</debug>

        if (c && (!me.hasListeners.beforeremove || me.fireEvent('beforeremove', me, c) !== false)) {
            me.doRemove(c, autoDestroy);

            if (me.hasListeners.remove) {
                me.fireEvent('remove', me, c);
            }

            if (!me.destroying && !me.destroyAfterRemoving && !c.floating) {
                me.updateLayout();
            }

            if (me.destroyAfterRemoving) {
                me.destroy();
            }
        }

        return c;
    },

    /**
     * Removes all components from this container.
     * @param {Boolean} [autoDestroy] True to automatically invoke the removed
     * Component's {@link Ext.Component#method-destroy} function.
     * Defaults to the value of this Container's {@link #autoDestroy} config.
     * @return {Ext.Component[]} Array of the removed components
     * @since 2.3.0
     */
    removeAll: function(autoDestroy) {
        var me = this,
            removeItems,
            floaters = me.floatingItems,
            items = [],
            i = 0,
            len,
            item;

        if (floaters) {
            removeItems = me.items.items.concat(floaters.items);
        }
        else {
            removeItems = me.items.items.slice();
        }

        len = removeItems.length;

        // Suspend Layouts while we remove multiple items from the container
        Ext.suspendLayouts();
        me.removingAll = true;

        for (; i < len; i++) {
            item = removeItems[i];
            me.remove(item, autoDestroy);

            if (item.ownerCt !== me) {
                items.push(item);
            }
        }

        me.removingAll = false;

        // Resume Layouts now that all items have been removed and do a single layout
        // (if we removed anything!)
        Ext.resumeLayouts(!!len);

        return items;
    },

    /**
     * Reconfigures the initially configured {@link #cfg!layout}.
     *
     * NOTE: this method cannot be used to change the "type" of layout after the component
     * has been rendered to the DOM. After rendering, this method can only modify the
     * existing layout's configuration properties. The reason for this restriction is that
     * many container layouts insert special wrapping elements into the dom, and the
     * framework does not currently support dynamically changing these elements once
     * rendered.
     * @param {Object} configuration object for the layout
     */
    setLayout: function(configuration) {
        var me = this,
            oldLayout = me.layout,
            type;

        if (configuration) {
            if (typeof configuration === 'string') {
                configuration = {
                    type: configuration
                };
            }

            type = configuration.type;

            if (oldLayout) {
                if (oldLayout.isLayout) {
                    // Same layout type requested, or just a reconfigure object.
                    // Either way, we reconfigure the current layout.
                    if (!type || (type === oldLayout.type)) {
                        oldLayout.setConfig(configuration);
                        configuration = oldLayout;
                    }
                    // Different layout type requested (not allowed after render)
                    // just detach from the current layout.
                    else {
                        oldLayout.setOwner(null);
                    }
                }
                // Old layout has not yet been instantiated; merge new in.
                else {
                    if (typeof oldLayout === 'string') {
                        oldLayout = {
                            type: oldLayout
                        };
                    }

                    configuration = Ext.merge({}, oldLayout, configuration);
                }
            }

            if (!(configuration && configuration.isLayout)) {
                configuration.owner = this;
                configuration = Ext.Factory.layout(configuration);
            }

            configuration.setOwner(this);
        }

        me.layout = configuration;

        if (me.rendered) {
            me.updateLayout();
        }
    },

    /**
     * Sets a component as the active layout item. This only applies when using
     * a {@link Ext.layout.container.Card} layout.
     *
     *     var card1 = Ext.create('Ext.panel.Panel', {itemId: 'card-1'});
     *     var card2 = Ext.create('Ext.panel.Panel', {itemId: 'card-2'});
     *     var panel = Ext.create('Ext.panel.Panel', {
     *         layout: 'card',
     *         items: [card1, card2]
     *     });
     *     // These are all equivalent
     *     panel.getLayout().setActiveItem(card2);
     *     panel.getLayout().setActiveItem('card-2');
     *     panel.getLayout().setActiveItem(1);
     *
     * @param {Ext.Component/Number/String} item  The component, component
     * {@link Ext.Component#id id}, {@link Ext.Component#itemId itemId}, or index of component.
     * @return {Ext.Component} the activated component or false when nothing activated.
     * False is returned also when trying to activate an already active item.
     */
    setActiveItem: function(item) {
        return this.getLayout().setActiveItem(item);
    },

    updateActions: function(actions) {
        var actionName;

        // Convert action configs into Ext.Action instances.
        for (actionName in actions) {
            if (!actions[actionName].isAction) {
                actions[actionName] = new Ext.Action(actions[actionName]);
            }
        }
    },

    /**
     * Retrieves the named {@link Ext.Action Action} from this view or any ancestor which
     * has that named Action. See {@link #actions}
     */
    getAction: function(name) {
        var owner = this;

        for (owner = this; owner; owner = owner.getRefOwner()) {
            if (owner.actions && owner.actions[name]) {
                return owner.actions[name];
            }
        }
    },

    onShowComplete: function(cb, scope) {
        var me = this;

        me.callParent([cb, scope]);

        if (me.focusableContainer && me.activateFocusableContainer) {
            me.activateFocusableContainer();
        }
    },

    onFocusEnter: function(e) {
        var me = this;

        me.callParent([e]);

        // We DO NOT check if `me` is focusable here. The reason is that
        // non-focusable containers need to track focus entering their
        // children so that revertFocus would work if these children
        // become unavailable.
        if (me.focusableContainer && !me.destroying && !me.destroyed) {
            me.mixins.focusablecontainer.onFocusEnter.call(me, e);
        }
    },

    onFocusLeave: function(e) {
        var me = this;

        me.callParent([e]);

        // Ditto
        if (me.focusableContainer && !me.destroying && !me.destroyed) {
            me.mixins.focusablecontainer.onFocusLeave.call(me, e);
        }
    },

    // ***********************************************************************************
    // End Methods
    // ***********************************************************************************
    // </editor-fold>

    privates: {
        /**
         * @private
         */
        applyDefaults: function(config) {
            var me = this,
                defaults = me.defaults;

            if (defaults) {
                if (Ext.isFunction(defaults)) {
                    defaults = defaults.call(me, config);
                }

                if (Ext.isString(config)) {
                    config = Ext.ComponentManager.get(config);
                }

                if (config.isComponent) {
                    // If we already have a component instance the best we can do is
                    // conditionally set all the configs in defaults if they have not
                    // yet been set on the component instance.
                    config.setConfig(defaults, null, me._applyDefaultsOptions);
                }
                else {
                    // If we have a config object (not a component instance) we can do a
                    // full (deep) merge of the config with the defaults object.
                    // Fork the defaults object first so that we don't modify the original
                    config = me.self.getConfigurator().merge(me, Ext.Object.fork(defaults), config);
                }
            }

            return config;
        },

        // The targetCls is a CSS class that the layout needs added to the targetEl. The targetEl
        // is where the container's children are rendered and is usually just the main el.
        // Some containers (e.g. panels) use a body instead.
        //
        // In general, if a class overrides getTargetEl it will also need to override this method.
        // This is necessary to avoid a post-render step to add the targetCls.
        applyTargetCls: function(targetCls) {
            this.layoutTargetCls = targetCls;
        },

        /**
         * @private
         */
        doRemove: function(component, flags) {
            var me = this,
                layout = me.layout,
                hasLayout = layout && me.rendered,
                floating = component.floating,
                doDetach = me.detachOnRemove,
                doDestroy = me.autoDestroy,
                isDestroying;

            // Ensure the flags are set correctly
            if (typeof flags === 'boolean') {
                doDestroy = flags;
            }
            else if (typeof flags === 'object') {
                if (flags.destroy != null) {
                    doDestroy = flags.destroy;
                }

                if (flags.detach != null) {
                    doDetach = flags.detach;
                }
            }

            // isDestroying flag is true if the removal is taking place as part of destruction,
            // OR if removal is intended to *cause* destruction
            isDestroying = component.destroying || doDestroy;

            if (floating) {
                me.floatingItems.remove(component);
            }
            else {
                me.items.remove(component);
            }

            // Inform ownerLayout of removal before deleting the ownerLayout & ownerCt references
            // in the onRemoved call
            if (hasLayout && !floating) {
                // Removing a component from a running layout has to cancel the layout
                if (layout.running) {
                    Ext.Component.cancelLayout(component, isDestroying);
                }

                layout.onRemove(component, isDestroying);
            }

            // Can be already destroyed!
            if (!component.destroyed) {
                component.onRemoved(isDestroying);
            }

            if (me.focusableContainer && !me.destroying && !me.destroyed) {
                me.onFocusableChildRemove(component, isDestroying);
            }

            me.onRemove(component, isDestroying);

            // Destroy if we were explicitly told to, or we're defaulting to our autoDestroy
            // configuration. If the component is already destroyed, calling destroy() again
            // won't blow up.
            if (doDestroy) {
                component.destroy();
            }
            // Only have the layout perform remove postprocessing if the Component is not
            // being destroyed, and this container is yet alive (could be destroyed too)
            else if (!me.destroyed) {
                if (hasLayout && !floating) {
                    layout.afterRemove(component);
                }

                if (doDetach && component.rendered) {
                    component.detachFromBody();
                }
            }
        },

        finishRenderChildren: function() {
            var layout;

            this.callParent();

            layout = this.getLayout();

            if (layout) {
                layout.finishRender();
            }
        },

        /**
         * Gets a list of child components to enable/disable when the container is
         * enabled/disabled
         * @private
         * @return {Ext.Component[]} Items to be enabled/disabled
         */
        getChildItemsToDisable: function() {
            return this.query('[isLabelable],[isFocusableContainer],button');
        },

        /**
         * @private
         */
        getContentTarget: function() {
            return this.getLayout().getContentTarget();
        },

        /**
         * @private
         */
        getDefaultContentTarget: function() {
            return this.el;
        },

        /**
         * @private
         */
        prepareItems: function(items, applyDefaults) {
            // Create an Array which does not refer to the passed array.
            // The passed array is a reference to a user's config object and MUST NOT be mutated.
            if (Ext.isArray(items)) {
                items = items.slice();
            }
            else {
                items = [items];
            }

            // Make sure defaults are applied and item is initialized
            // eslint-disable-next-line vars-on-top
            var me = this,
                i = 0,
                len = items.length,
                item;

            for (; i < len; i++) {
                item = items[i];

                if (item == null) {
                    Ext.Array.erase(items, i, 1);
                    --i;
                    --len;
                }
                else {
                    if (applyDefaults) {
                        item = this.applyDefaults(item);
                    }

                    // Tell the item we're in a container during construction
                    item.$initParent = me;

                    if (item.isComponent) {
                        // When this was passed to us, it's an already constructed component
                        // This is useful to know because we can make decisions regarding the
                        // state of the component if it's newly created
                        item.instancedCmp = true;
                    }

                    items[i] = me.lookupComponent(item);

                    // delete here because item may have been a config, so we don't
                    // want to mutate it
                    delete item.$initParent;
                }
            }

            return items;
        },

        repositionFloatingItems: function() {
            var floaters = this.floatingItems,
                floaterCount,
                i, floater;

            // Ensure correct positioning of floated children before calling superclass
            if (floaters) {
                floaters = floaters.items;
                floaterCount = floaters.length;

                for (i = 0; i < floaterCount; i++) {
                    floater = floaters[i];

                    if (floater.el && !floater.hidden) {
                        floater.setPosition(floater.x, floater.y);
                    }
                }
            }
        },

        initTabGuards: function(activate) {
            var me = this,
                beforeGuard = me.tabGuardBeforeEl,
                afterGuard = me.tabGuardAfterEl,
                minTabIndex = me.tabGuardBeforeIndex || 0,
                maxTabIndex = me.tabGuardAfterIndex || 0,
                i, tabIndex, nodes;

            if (!me.rendered || !me.tabGuard) {
                return;
            }

            nodes = me.el.findTabbableElements({
                skipSelf: true
            });

            // Both tab guards may be in the list, disregard them
            if (nodes[0] === beforeGuard.dom) {
                nodes.shift();
            }

            if (nodes[nodes.length - 1] === afterGuard.dom) {
                nodes.pop();
            }

            if (nodes && nodes.length) {
                // In some cases it might be desirable to configure before and after
                // guard elements' tabIndex explicitly but if it is missing we try to
                // infer it from the DOM. If we don't and there are elements with
                // tabIndex > 0 within the container then tab order will be very
                // unintuitive.
                if (minTabIndex == null || maxTabIndex == null) {
                    for (i = 0; i < nodes.length; i++) {
                        // Can't use node.tabIndex property here, IE8 will report 0
                        // even if tabIndex attribute is missing.
                        tabIndex = +nodes[i].getAttribute('tabIndex');

                        if (tabIndex > 0) {
                            minTabIndex = Math.min(minTabIndex, tabIndex);
                            maxTabIndex = Math.max(maxTabIndex, tabIndex);
                        }
                    }
                }

                beforeGuard.dom.setAttribute('tabIndex', minTabIndex);
                afterGuard.dom.setAttribute('tabIndex', maxTabIndex);
            }
            else {
                // We don't want the guards to participate in tab flow
                // if there are no tabbable children in the container
                beforeGuard.dom.removeAttribute('tabIndex');
                afterGuard.dom.removeAttribute('tabIndex');
            }

            if (me.onTabGuardFocusEnter) {
                if (!beforeGuard.hasListeners.focusenter) {
                    beforeGuard.on('focusenter', me.onTabGuardFocusEnter, me);
                }

                if (!afterGuard.hasListeners.focusenter) {
                    afterGuard.on('focusenter', me.onTabGuardFocusEnter, me);
                }
            }
        },

        _noMargin: {
            'margin-top': '',
            'margin-right': '',
            'margin-bottom': '',
            'margin-left': ''
        },

        // Removes inline margins set by the layout system (see ContextItem#getMarginInfo)
        // TODO: fix EXTJS-13359 and remove this method
        resetItemMargins: function() {
            var items = this.items.items,
                i = items.length,
                noMargin = this._noMargin,
                item;

            while (i--) {
                item = items[i];
                item.margin$ = null;
                item.el.setStyle(noMargin);
            }
        },

        setupRenderTpl: function(renderTpl) {
            this.callParent(arguments);
            this.getLayout().setupRenderTpl(renderTpl);
        }
    } // private
});

/**
 * This class is created to manage a direct bind.  `Ext.app.ViewModel` returns this from 
 * its {@link Ext.app.ViewModel#method-bind bind} method.
 */
Ext.define('Ext.app.bind.Binding', {
    extend: 'Ext.app.bind.BaseBinding',

    /**
     * @cfg {Boolean} [deep=false]
     * Normally a binding is only notified of changes to its bound property, but if that
     * property is an object it is sometimes helpful to be notified of changes to its
     * properties. To receive notifications of changes to all properties of a bound object,
     * set this to `true`.
     * @since 5.0.0
     */

    constructor: function(stub, callback, scope, options) {
        var me = this;

        me.callParent([ stub.owner, callback, scope, options ]);

        me.stub = stub;
        me.depth = stub.depth;

        // We need to announce the current value, so if the stub is available
        // will generate its own announcement to all bindings) then we need to schedule
        if (stub.isAvailable() && !stub.scheduled) {
            me.schedule();
        }
    },

    /**
     * Destroys this binding. No further calls will be made to the callback method. No
     * methods should be called on this binding after calling this method.
     * @param {Boolean} [fromParent] (private)
     * @since 5.0.0
     */
    destroy: function(fromParent) {
        var me = this,
            stub = me.stub;

        if (stub && !fromParent) {
            stub.unbind(me);
            me.stub = null;
        }

        me.callParent();
    },

    /**
     * Binds to the `validation` association for the bound property. For example, when a
     * binding is bound to something like this:
     *
     *      var binding = viewModel.bind('{theUser.name}', ...);
     *
     * The validation status for the "name" property can be requested like so:
     *
     *      var validationBinding = binding.bindValidation(fn, scope);
     *
     * Calling this method in the above example would be equivalent to the following bind:
     *
     *      var validationBinding = viewModel.bind('{theUser.validation.name}', fn, scope);
     *
     * The primary reason to use this method is in cases where the original bind expression
     * is not known.
     *
     * For example, this method is used by `Ext.form.field.Base` when given the
     * `{@link Ext.Component#modelValidation modelValidation}` config is set. As such it
     * not common for users to need to call this method.
     *
     * @param {Function} callback The function to call when the validation changes.
     * @param {Object} [scope] The scope on which to call the `callback`.
     * @return {Ext.app.bind.Binding} A binding to the validation of the bound property.
     * @since 5.0.0
     */
    bindValidation: function(callback, scope) {
        var stub = this.stub;

        return stub && stub.bindValidation(callback, scope);
    },

    /**
     * Bind to a model field for validation
     * @param {Function/String} callback The function to call or the name of the function on the
     * scope
     * @param {Object} scope The scope for the callback
     * @return {Ext.app.bind.Binding} The binding, if available
     *
     * @private
     */
    bindValidationField: function(callback, scope) {
        var stub = this.stub;

        return stub && stub.bindValidationField(callback, scope);
    },

    /**
     * Returns the diagnostic name for this binding.
     * @return {String}
     * @since 5.0.0
     */
    getFullName: function() {
        return this.fullName || (this.fullName = '@(' + this.stub.getFullName() + ')');
    },

    /**
     * Returns the current value of the bound property. If this binding is not 
     * {@link #isAvailable available} the value will be `undefined`.
     * @return {Mixed} The value of the bound property.
     * @since 5.0.0
     */
    getValue: function() {
        var me = this,
            stub = me.stub;

        return stub && stub.getValue();
    },

    /**
     * Returns `true` if the bound property is available. If this returns `false`, 
     * it generally means the value is not reachable because the a parent value is
     * not present.
     * @return {Boolean}
     * @since 5.1.2
     */
    isAvailable: function() {
        var stub = this.stub;

        return stub && stub.isAvailable();
    },

    /**
     * Returns `true` if the bound property is loading. In the general case this means
     * that the value is just not available yet. In specific cases, when the bound property
     * is an `Ext.data.Model` it means that a request to the server is in progress to get
     * the record. For an `Ext.data.Store` it means that
     * `{@link Ext.data.Store#method-load load}` has been called on the store but it is
     * still in progress.
     * @return {Boolean}
     * @since 5.0.0
     */
    isLoading: function() {
        var stub = this.stub;

        return stub && stub.isLoading();
    },

    /**
     * This method returns `true` if this binding can only be read. If this method returns
     * `false` then the binding can be set using `setValue` (meaning this binding can be
     * a two-way binding).
     * @return {Boolean}
     * @since 5.0.0
     */
    isReadOnly: function() {
        var stub = this.stub,
            options = this.options,
            ret = true;

        if (!(options && options.twoWay === false)) {
            if (stub) {
                ret = stub.isReadOnly();
            }
        }

        return ret;
    },

    /**
     * Tells the bound property to refresh itself. This has meaning when the bound property
     * is something like an `Ext.data.Model` and an `Ext.data.Store` but does nothing in
     * most cases.
     * @since 5.0.0
     */
    refresh: function() {
        // TODO - maybe nothing to do here but entities/stores would have work to do
    },

    /**
     * Sets the value of the bound property. This will throw an error in debug mode if
     * this binding `isReadOnly`. This method will climb to set data on
     * a parent view model of this binding if appropriate. See "Inheriting Data" in the
     * {@link Ext.app.ViewModel} class introduction for more information.
     * @param {Mixed} value The new value.
     * @since 5.0.0
     */
    setValue: function(value) {
        //<debug>
        if (this.isReadOnly()) {
            Ext.raise('Cannot setValue on a readonly binding');
        }
        //</debug>

        this.stub.set(value);
    },

    privates: {
        getDataObject: function() {
            var stub = this.stub;

            return stub && stub.getDataObject();
        },

        getRawValue: function() {
            var me = this,
                stub = me.stub;

            return stub && stub.getRawValue();
        },

        isDescendantOf: function(item) {
            var stub = this.stub;

            return stub ? (item === stub) || stub.isDescendantOf(item) : false;
        },

        react: function() {
            this.notify(this.getValue());
        },

        schedule: function() {
            // If the parent stub is already scheduled, then we will be
            // called when the stub hits the next tick.
            if (!this.stub.scheduled) {
                this.callParent();
            }
        },

        sort: function() {
            var stub = this.stub;

            stub.scheduler.sortItem(stub);

            // Schedulable#sort === emptyFn
            // me.callParent();
        }
    }
});

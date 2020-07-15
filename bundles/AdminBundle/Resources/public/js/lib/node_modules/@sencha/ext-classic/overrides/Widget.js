/**
 * @class Ext.Widget
 */
Ext.define('Ext.overrides.Widget', {
    override: 'Ext.Widget',

    uses: [
        'Ext.Component',
        'Ext.layout.component.Auto'
    ],

    $configStrict: false,

    isComponent: true,

    liquidLayout: true,

    // in Ext JS the rendered flag is set as soon as a component has its element.  Since
    // widgets always have an element when constructed, they are always considered to be
    // "rendered"
    rendered: true,

    rendering: true,

    config: {
        renderTo: null
    },

    constructor: function(config) {
        var me = this,
            renderTo;

        me.callParent([config]);

        // initialize the component layout
        me.getComponentLayout();
        renderTo = me.getRenderTo();

        if (renderTo) {
            me.render(renderTo);
        }
    },

    addClsWithUI: function(cls) {
        this.el.addCls(cls);
    },

    afterComponentLayout: Ext.emptyFn,

    updateLayout: function() {
        var owner = this.getRefOwner();

        if (owner) {
            owner.updateLayout();
        }
    },

    destroy: function() {
        var me = this,
            ownerCt = me.ownerCt;

        if (ownerCt && ownerCt.remove) {
            ownerCt.remove(me, false);
        }

        me.callParent();
    },

    finishRender: function() {
        this.rendering = false;

        this.initBindable();
        this.initKeyMap();
    },

    getAnimationProps: function() {
        // see Ext.util.Animate mixin
        return {};
    },

    getComponentLayout: function() {
        var me = this,
            layout = me.componentLayout;

        if (!layout) {
            layout = me.componentLayout = new Ext.layout.component.Auto();
            layout.setOwner(me);
        }

        return layout;
    },

    getEl: function() {
        return this.element;
    },

    /**
     * @private
     * Needed for when widget is rendered into a grid cell. The class to add to the cell element.
     * @member Ext.Widget
     */
    getTdCls: function() {
        return Ext.baseCSSPrefix + this.getTdType() + '-' + (this.ui || 'default') + '-cell';
    },

    /**
     * @private
     * Partner method to {@link #getTdCls}.
     *
     * Returns the base type for the component. Defaults to return `this.xtype`, but
     * All derived classes of {@link Ext.form.field.Text TextField} can return the type 'textfield',
     * and all derived classes of {@link Ext.button.Button Button} can return the type 'button'
     * @member Ext.Widget
     */
    getTdType: function() {
        return this.xtype;
    },

    getItemId: function() {
        // needed by ComponentQuery
        return this.itemId || this.id;
    },

    getSizeModel: function() {
        return Ext.Component.prototype.getSizeModel.apply(this, arguments);
    },

    onAdded: function(container, pos, instanced) {
        var me = this;

        me.ownerCt = container;

        me.onInheritedAdd(me, instanced);

        // this component is no longer detached from the body
        me.isDetached = false;
    },

    onRemoved: function(destroying) {
        this.onInheritedRemove(destroying);

        this.ownerCt = this.ownerLayout = null;
    },

    parseBox: function(box) {
        return Ext.Element.parseBox(box);
    },

    removeClsWithUI: function(cls) {
        this.el.removeCls(cls);
    },

    render: function(container, position) {
        var me = this,
            element = me.element,
            proto = Ext.Component.prototype,
            nextSibling;

        if (!me.ownerCt || me.floating) {
            if (Ext.scopeCss) {
                element.addCls(proto.rootCls);
            }

            element.addCls(proto.borderBoxCls);
        }

        if (position) {
            nextSibling = container.childNodes[position];

            if (nextSibling) {
                Ext.fly(container).insertBefore(element, nextSibling);

                return;
            }
        }

        Ext.fly(container).appendChild(element);
        me.finishRender();
    },

    setPosition: function(x, y) {
        this.el.setLocalXY(x, y);
    },

    up: function() {
        return Ext.Component.prototype.up.apply(this, arguments);
    },

    isAncestor: function() {
        return Ext.Component.prototype.isAncestor.apply(this, arguments);
    },

    onFocusEnter: function() {
        return Ext.Component.prototype.onFocusEnter.apply(this, arguments);
    },

    onFocusLeave: function() {
        return Ext.Component.prototype.onFocusLeave.apply(this, arguments);
    },

    isLayoutChild: function(candidate) {
        var ownerCt = this.ownerCt;

        return ownerCt ? (ownerCt === candidate || ownerCt.isLayoutChild(candidate)) : false;
    },

    privates: {
        doAddListener: function(name, fn, scope, options, order, caller, manager) {
            if (name === 'painted' || name === 'resize') {
                this.element.doAddListener(name, fn, scope || this, options, order);
            }

            this.callParent([name, fn, scope, options, order, caller, manager]);
        },

        doRemoveListener: function(name, fn, scope) {
            if (name === 'painted' || name === 'resize') {
                this.element.doRemoveListener(name, fn, scope);
            }

            this.callParent([name, fn, scope]);
        }
    }

}, function(Cls) {
    var prototype = Cls.prototype;

    if (Ext.isIE9m) {
        // Since IE8/9 don't not support Object.defineProperty correctly we can't add the reference
        // nodes on demand, so we just fall back to adding all references up front.
        prototype.addElementReferenceOnDemand = prototype.addElementReference;
    }
});

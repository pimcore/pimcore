/**
 * @private
 */
Ext.define('Ext.fx.animation.Abstract', {
    extend: 'Ext.Evented',

    mixins: [
        'Ext.mixin.Factoryable'
    ],

    factoryConfig: {
        type: 'animation'
    },

    isAnimation: true,

    requires: [
        'Ext.fx.State'
    ],

    config: {
        name: '',

        element: null,

        /**
         * @cfg {Object} before
         * Before configuration.
         */
        before: null,

        from: {},

        to: {},

        after: null,

        states: {},

        duration: 300,

        /**
         * @cfg {String} easing
         * Easing type.
         */
        easing: 'linear',

        iteration: 1,

        direction: 'normal',

        delay: 0,

        onBeforeStart: null,

        callback: null,

        onEnd: null,

        onBeforeEnd: null,

        scope: null,

        reverse: null,

        preserveEndState: false,

        replacePrevious: true
    },

    STATE_FROM: '0%',

    STATE_TO: '100%',

    DIRECTION_UP: 'up',

    DIRECTION_TOP: 'top',

    DIRECTION_DOWN: 'down',

    DIRECTION_BOTTOM: 'bottom',

    DIRECTION_LEFT: 'left',

    DIRECTION_RIGHT: 'right',

    stateNameRegex: /^(?:[\d.]+)%$/,

    constructor: function() {
        this.states = {};

        this.callParent(arguments);

        return this;
    },

    applyElement: function(element) {
        return Ext.get(element);
    },

    applyBefore: function(before, current) {
        if (before) {
            return Ext.factory(before, Ext.fx.State, current);
        }
    },

    applyAfter: function(after, current) {
        if (after) {
            return Ext.factory(after, Ext.fx.State, current);
        }
    },

    setFrom: function(from) {
        return this.setState(this.STATE_FROM, from);
    },

    setTo: function(to) {
        return this.setState(this.STATE_TO, to);
    },

    getFrom: function() {
        return this.getState(this.STATE_FROM);
    },

    getTo: function() {
        return this.getState(this.STATE_TO);
    },

    setStates: function(states) {
        var validNameRegex = this.stateNameRegex,
            name;

        for (name in states) {
            if (validNameRegex.test(name)) {
                this.setState(name, states[name]);
            }
        }

        return this;
    },

    getStates: function() {
        return this.states;
    },

    updateCallback: function(callback) {
        if (callback) {
            this.setOnEnd(callback);
        }
    },

    end: function() {
        // alias for stop so that the following api is the same between ext/touch:
        // element.getActiveAnimation().end()
        this.stop();
    },

    stop: function() {
        this.fireEvent('stop', this);
    },

    destroy: function() {
        // Event handlers need to know this.
        this.destroying = true;
        this.stop();
        this.callParent();
        this.destroying = false;
        this.destroyed = true;
    },

    setState: function(name, state) {
        var states = this.getStates(),
            stateInstance;

        stateInstance = Ext.factory(state, Ext.fx.State, states[name]);

        if (stateInstance) {
            states[name] = stateInstance;
        }
        //<debug>
        else if (name === this.STATE_TO) {
            Ext.Logger.error("Setting and invalid '100%' / 'to' state of: " + state);
        }
        //</debug>

        return this;
    },

    getState: function(name) {
        return this.getStates()[name];
    },

    getData: function() {
        var me = this,
            states = me.getStates(),
            statesData = {},
            before = me.getBefore(),
            after = me.getAfter(),
            from = states[me.STATE_FROM],
            to = states[me.STATE_TO],
            fromData = from.getData(),
            toData = to.getData(),
            data, name, state;

        for (name in states) {
            if (states.hasOwnProperty(name)) {
                state = states[name];
                data = state.getData();
                statesData[name] = data;
            }
        }

        return {
            before: before ? before.getData() : {},
            after: after ? after.getData() : {},
            states: statesData,
            from: fromData,
            to: toData,
            duration: me.getDuration(),
            iteration: me.getIteration(),
            direction: me.getDirection(),
            easing: me.getEasing(),
            delay: me.getDelay(),
            onEnd: me.getOnEnd(),
            onBeforeEnd: me.getOnBeforeEnd(),
            onBeforeStart: me.getOnBeforeStart(),
            scope: me.getScope(),
            preserveEndState: me.getPreserveEndState(),
            replacePrevious: me.getReplacePrevious()
        };
    }
});

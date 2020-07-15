/**
 * @private
 */
Ext.define('Ext.button.Manager', {
    singleton: true,

    alternateClassName: 'Ext.ButtonToggleManager',

    groups: {},

    pressedButton: null,

    init: function() {
        var me = this;

        if (!me.initialized) {
            Ext.getDoc().on({
                mouseup: me.onDocumentMouseUp,
                scope: me
            });

            me.initialized = true;
        }
    },

    // Called by buton instances.
    // Track the button which was mousedowned upon so that the next *document* mouseup
    // can be delivered to it in case mouse is moved outside of button element.
    onButtonMousedown: function(button, e) {
        var pressed = this.pressedButton;

        if (pressed && !pressed.destroying && !pressed.destroyed) {
            pressed.onMouseUp(e);
        }

        this.pressedButton = button;
    },

    onDocumentMouseUp: function(e) {
        var pressed = this.pressedButton;

        if (pressed && !pressed.destroying && !pressed.destroyed) {
            pressed.onMouseUp(e);
            this.pressedButton = null;
        }
    },

    toggleGroup: function(btn, state) {
        var g, i, length;

        if (state) {
            g = this.groups[btn.toggleGroup];

            for (i = 0, length = g.length; i < length; i++) {
                if (g[i] !== btn) {
                    g[i].toggle(false);
                }
            }
        }
    },

    register: function(btn) {
        var me = this,
            groups = this.groups,
            group = groups[btn.toggleGroup];

        me.init();

        if (!btn.toggleGroup) {
            return;
        }

        if (!group) {
            group = groups[btn.toggleGroup] = [];
        }

        group.push(btn);
        btn.on('toggle', me.toggleGroup, me);
    },

    unregister: function(btn) {
        if (!btn.toggleGroup) {
            return;
        }

        // eslint-disable-next-line vars-on-top
        var me = this,
            group = me.groups[btn.toggleGroup];

        if (group) {
            Ext.Array.remove(group, btn);
            btn.un('toggle', me.toggleGroup, me);
        }
    },

    /**
     * Gets the pressed button in the passed group or null
     * @param {String} groupName
     * @return {Ext.button.Button}
     */
    getPressed: function(groupName) {
        var group = this.groups[groupName],
            i = 0,
            len;

        if (group) {
            for (len = group.length; i < len; i++) {
                if (group[i].pressed === true) {
                    return group[i];
                }
            }
        }

        return null;
    }
});

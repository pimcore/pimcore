/**
 * @class Ext.list.TreeItem
 */
Ext.define('Ext.overrides.list.TreeItem', {
    override: 'Ext.list.TreeItem',

    // Implement a setter.
    // There *is* no "floated" config in Classic.
    // We're still an inner item, we just get put inside a Container.
    setFloated: function(floated) {
        var me = this,
            el = me.element,
            placeholder = me.placeholder,
            node, wasExpanded;

        if (me.treeItemFloated !== floated) {
            if (floated) {
                placeholder = el.clone(false, true); // shallow, asDom
                placeholder.id += '-placeholder'; // avoid duplicate id
                me.placeholder = Ext.get(placeholder);

                me.wasExpanded = me.getExpanded();
                me.setExpanded(true);

                el.addCls(me.floatedCls);
                el.dom.parentNode.insertBefore(placeholder, el.dom);

                me.floater = me.createFloater(); // toolkit-specific
            }
            else if (placeholder) {
                wasExpanded = me.wasExpanded;
                node = me.getNode();
                me.setExpanded(wasExpanded);

                if (!wasExpanded && node.isExpanded()) {
                    // If we have been floating and expanded a child, we may have been
                    // expanded as part of the ancestors. Attempt to restore state.
                    me.preventAnimation = true;
                    node.collapse();
                    me.preventAnimation = false;
                }

                me.floater.remove(me, false); // don't destroy
                el.removeCls(me.floatedCls);
                placeholder.dom.parentNode.insertBefore(el.dom, placeholder.dom);

                placeholder.destroy();
                me.floater.destroy();

                me.placeholder = me.floater = null;
            }

            // Use an internal property name. We are NOT really floated
            me.treeItemFloated = floated;
        }
    },

    getFloated: function() {
        return this.treeItemFloated;
    },

    runAnimation: function(animation) {
        return this.itemContainer.addAnimation(animation);
    },

    stopAnimation: function(animation) {
        animation.jumpToEnd();
    },

    privates: {
        createFloater: function() {
            var me = this,
                owner = me.getOwner(),
                ownerTree = me.up('treelist'),
                floater,
                toolElement = me.getToolElement(),
                expandedWidth = ownerTree.expandedWidth,
                defaultListWidth = ownerTree.defaultListWidth;

            if (expandedWidth === null) {
                expandedWidth = defaultListWidth;
            }

            me.floater = floater = new Ext.container.Container({
                cls: ownerTree.self.prototype.element.cls + ' ' +
                     ownerTree.uiPrefix + ownerTree.getUi() + ' ' +
                     Ext.baseCSSPrefix + 'treelist-floater',
                floating: true,
                // We do not get element resize events on IE8
                // so fall back to 6.0.1 sizing to 200 wide.
                width: Ext.isIE8 ? defaultListWidth : (expandedWidth - toolElement.getWidth()),
                shadow: false,
                hidden: true,
                renderTo: Ext.getBody(),
                listeners: {
                    element: 'el',
                    click: function(e) {
                        return owner.onClick(e);
                    }
                }
            });

            floater.add(me);
            floater.show();
            floater.el.alignTo(toolElement, 'tr?');

            return floater;
        }
    }
});

/**
 * The default implementation of the class used for `{@link Ext.list.Tree}`.
 *
 * This class can only be used in conjunction with {@link Ext.list.Tree}.
 * @since 6.0.0
 */
Ext.define('Ext.list.TreeItem', {
    extend: 'Ext.list.AbstractTreeItem',
    xtype: 'treelistitem',

    collapsedCls: Ext.baseCSSPrefix + 'treelist-item-collapsed',
    expandedCls: Ext.baseCSSPrefix + 'treelist-item-expanded',
    floatedToolCls: Ext.baseCSSPrefix + 'treelist-item-tool-floated',
    leafCls: Ext.baseCSSPrefix + 'treelist-item-leaf',
    expandableCls: Ext.baseCSSPrefix + 'treelist-item-expandable',
    hideIconCls: Ext.baseCSSPrefix + 'treelist-item-hide-icon',
    loadingCls: Ext.baseCSSPrefix + 'treelist-item-loading',
    selectedCls: Ext.baseCSSPrefix + 'treelist-item-selected',
    selectedParentCls: Ext.baseCSSPrefix + 'treelist-item-selected-parent',
    withIconCls: Ext.baseCSSPrefix + 'treelist-item-with-icon',
    hoverCls: Ext.baseCSSPrefix + 'treelist-item-over',
    rowHoverCls: Ext.baseCSSPrefix + 'treelist-row-over',

    /**
     * This property is `true` to allow type checking for this or derived class.
     * @property {Boolean} isTreeListItem
     * @readonly
     */
    isTreeListItem: true,

    config: {
        /**
         * @cfg {String} rowCls
         * One or more CSS classes to add to the tree item's logical "row" element. This
         * element fills from left-to-right of the line.
         * @since 6.0.1
         */
        rowCls: null
    },

    /**
     * @cfg {String} [rowClsProperty="rowCls"]
     * The name of the associated record's field to map to the {@link #rowCls} config.
     * @since 6.0.1
     */
    rowClsProperty: 'rowCls',

    element: {
        reference: 'element',
        tag: 'li',
        cls: Ext.baseCSSPrefix + 'treelist-item',

        children: [{
            reference: 'rowElement',
            cls: Ext.baseCSSPrefix + 'treelist-row',

            children: [{
                reference: 'wrapElement',
                cls: Ext.baseCSSPrefix + 'treelist-item-wrap',
                children: [{
                    reference: 'iconElement',
                    cls: Ext.baseCSSPrefix + 'treelist-item-icon'
                }, {
                    reference: 'textElement',
                    cls: Ext.baseCSSPrefix + 'treelist-item-text'
                }, {
                    reference: 'expanderElement',
                    cls: Ext.baseCSSPrefix + 'treelist-item-expander'
                }]
            }]
        }, {
            reference: 'itemContainer',
            tag: 'ul',
            cls: Ext.baseCSSPrefix + 'treelist-container'
        }, {
            reference: 'toolElement',
            cls: Ext.baseCSSPrefix + 'treelist-item-tool'
        }]
    },

    constructor: function(config) {
        var toolDom;

        this.callParent([config]);

        toolDom = this.toolElement.dom;

        // We don't want the tool in the normal <li> structure but it is simpler to let
        // that process create the toolElement.
        toolDom.parentNode.removeChild(toolDom);
    },

    getToolElement: function() {
        return this.toolElement;
    },

    insertItem: function(item, refItem) {
        if (refItem) {
            item.element.insertBefore(refItem.element);
        }
        else {
            this.itemContainer.appendChild(item.element);
        }
    },

    isSelectionEvent: function(e) {
        var owner = this.getOwner();

        return (!this.isToggleEvent(e) || !owner.getExpanderOnly() || owner.getSelectOnExpander());
    },

    isToggleEvent: function(e) {
        var isExpand = false;

        if (this.getOwner().getExpanderOnly()) {
            isExpand = e.target === this.expanderElement.dom;
        }
        else {
            // contains also includes the element itself
            isExpand = !this.itemContainer.contains(e.target);
        }

        return isExpand;
    },

    nodeCollapseBegin: function(animation, collapsingForExpand) {
        var me = this,
            itemContainer = me.itemContainer,
            height;

        if (me.expanding) {
            me.stopAnimation(me.expanding); // also calls the nodeExpandDone method
        }

        // Measure before collapse since that hides the element (if animating) but after
        // ending any in progress expand animation.
        height = animation && itemContainer.getHeight();

        me.callParent([ animation, collapsingForExpand ]);

        if (animation) {
            // The collapsed state is now in effect, so itemContainer is hidden.
            itemContainer.dom.style.display = 'block';

            me.collapsingForExpand = collapsingForExpand;
            me.collapsing = this.runAnimation(Ext.merge({
                from: {
                    height: height
                },
                to: {
                    height: 0
                },
                callback: me.nodeCollapseDone,
                scope: me
            }, animation));
        }
    },

    nodeCollapseDone: function(animation) {
        var me = this,
            itemContainer = me.itemContainer;

        // stopAnimation is called on destroy, so don't
        // bother continuing if we don't need to
        if (!me.destroying && !me.destroyed) {
            me.collapsing = null;
            itemContainer.dom.style.display = '';
            itemContainer.setHeight(null);

            me.nodeCollapseEnd(me.collapsingForExpand);
        }
    },

    nodeExpandBegin: function(animation) {
        var me = this,
            itemContainer = me.itemContainer,
            height;

        if (me.collapsing) {
            me.stopAnimation(me.collapsing);
        }

        me.callParent([ animation ]);

        if (animation) {
            // The expanded state is in effect, so itemContainer is visible again.
            height = itemContainer.getHeight();
            itemContainer.setHeight(0);

            me.expanding = me.runAnimation(Ext.merge({
                to: {
                    height: height
                },
                callback: me.nodeExpandDone,
                scope: me
            }, animation));
        }
    },

    nodeExpandDone: function() {
        this.expanding = null;
        this.itemContainer.setHeight(null);
        this.nodeExpandEnd();
    },

    removeItem: function(item) {
        this.itemContainer.removeChild(item.element);
    },

    //-------------------------------------------------------------------------
    // Updaters

    updateNode: function(node, oldNode) {
        this.syncIndent();
        this.callParent([ node, oldNode ]);
    },

    updateExpandable: function(expandable) {
        this.updateExpandCls();

        // We need not to set the expandable attribute of node here, 
        // Refer to isExapndable() function of the node. 
        // This function may get called on removal of child, and thus setting expandable to false
        // But we may not need to set same to node as isExapndable() will be deciding function 
        // not the 'Exapndable' attribute. Fase 'Exapndable' attribute means node will never
        // be expandable irrespective of the child values
    },

    updateExpanded: function(expanded) {
        var node = this.getNode();

        this.updateExpandCls();

        if (node) {
            node.set('expanded', expanded);
        }
    },

    updateIconCls: function(iconCls, oldIconCls) {
        var me = this,
            el = me.element;

        me.doIconCls(me.iconElement, iconCls, oldIconCls);
        me.doIconCls(me.toolElement, iconCls, oldIconCls);

        el.toggleCls(me.withIconCls, !!iconCls);
        // Blank iconCls leaves room for icon to line up w/sibling items
        el.toggleCls(me.hideIconCls, iconCls === null);
    },

    updateLeaf: function(leaf) {
        this.element.toggleCls(this.leafCls, leaf);
    },

    updateLoading: function(loading) {
        this.element.toggleCls(this.loadingCls, loading);
    },

    updateOver: function(over) {
        var me = this;

        me.element.toggleCls(me.hoverCls, !! over); // off if over==0, on otherwise
        me.rowElement.toggleCls(me.rowHoverCls, over > 1); // off if over = 0 or 1
    },

    updateRowCls: function(value, oldValue) {
        this.rowElement.replaceCls(oldValue, value);
    },

    updateSelected: function(selected, oldSelected) {
        var me = this,
            cls = me.selectedCls,
            tool = me.getToolElement();

        me.callParent([ selected, oldSelected ]);

        me.element.toggleCls(cls, selected);

        if (tool) {
            tool.toggleCls(cls, selected);
        }
    },

    updateSelectedParent: function(selectedParent) {
        var me = this,
            tool;

        me.element.toggleCls(me.selectedParentCls, selectedParent);
        tool = me.getToolElement();

        if (tool) {
            tool.toggleCls(me.selectedCls, selectedParent);
        }
    },

    updateText: function(text) {
        this.textElement.update(text);
    },

    //-------------------------------------------------------------------------
    // Private

    privates: {
        doNodeUpdate: function(node) {
            this.callParent([ node ]);

            this.setRowCls(node && node.data[this.rowClsProperty]);
        },

        doIconCls: function(element, iconCls, oldIconCls) {
            if (oldIconCls) {
                element.removeCls(oldIconCls);
            }

            if (iconCls) {
                element.addCls(iconCls);
            }
        },

        syncIndent: function() {
            var me = this,
                indent = me.getIndent(),
                node = me.getNode(),
                depth;

            if (node) {
                depth = node.data.depth - 1;

                me.wrapElement.dom.style.marginLeft = (depth * indent) + 'px';
            }
        },

        updateExpandCls: function() {
            if (!this.updatingExpandCls) {
                // eslint-disable-next-line vars-on-top
                var me = this,
                    expandable = me.getExpandable(),
                    element = me.element,
                    expanded = me.getExpanded(),
                    expandedCls = me.expandedCls,
                    collapsedCls = me.collapsedCls;

                me.updatingExpandCls = true;

                element.toggleCls(me.expandableCls, expandable);

                if (expandable) {
                    element.toggleCls(expandedCls, expanded);
                    element.toggleCls(collapsedCls, !expanded);
                }
                else {
                    element.removeCls([expandedCls, collapsedCls]);
                }

                me.updatingExpandCls = false;
            }
        },

        updateIndent: function(value, oldValue) {
            this.syncIndent();
            this.callParent([ value, oldValue ]);
        }
    }
}, function(TreeItem) {
    TreeItem.prototype.floatedCls = [
        Ext.Widget.prototype.floatedCls,
        Ext.baseCSSPrefix + 'treelist-item-floated'
    ];
});

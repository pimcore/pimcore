/**
 * The base class for all items in the `{@link Ext.list.Tree treelist}`.
 * @since 6.0.0
 */
Ext.define('Ext.list.AbstractTreeItem', {
    extend: 'Ext.Widget',

    isTreeListItem: true,

    /**
     * @method setExpandable
     * @ignore
     */

    /**
     * @method setExpanded
     * @ignore
     */

    /**
     * @method setIconCls
     * @ignore
     */

    /**
     * @method setLeaf
     * @ignore
     */

    /**
     * @method setOwner
     * @ignore
     */

    /**
     * @method setLoading
     * @ignore
     */

    /**
     * @method setNode
     * @ignore
     */

    /**
     * @method setParentItem
     * @ignore
     */

    /**
     * @method setText
     * @ignore
     */

    cachedConfig: {
        /**
         * @cfg {Boolean} expandable
         * `true` if this item is expandable. This value is taken from
         * the underlying {@link #node}.
         */
        expandable: false,

        /**
         * @cfg {Boolean} expanded
         * `true` if this item is expanded. This value is taken from
         * the underlying {@link #node}.
         */
        expanded: false,

        /**
         * @cfg iconCls
         * @inheritdoc Ext.panel.Header#cfg-iconCls
         * @localdoc **Note:** This value is taken from the underlying {@link #node}.
         */
        iconCls: '',

        /**
         * @cfg {Boolean} leaf
         * `true` if this item is a leaf. This value is taken from
         * the underlying {@link #node}.
         */
        leaf: true,

        /**
         * @cfg {Boolean} loading
         * `true` if this item is currently loading data. This value is taken from
         * the underlying {@link #node}.
         */
        loading: false,

        /**
         * @cfg {Boolean} selected
         * `true` if this is the selected item in the tree.
         */
        selected: false,

        /**
         * @cfg {Boolean} selectedParent
         * `true` if this item contains the {@link #selected} item in the tree.
         */
        selectedParent: false
    },

    config: {
        /**
         * @cfg {String} iconClsProperty
         * The property from the {@link #node} to map for the {@link #iconCls} config.
         */
        iconClsProperty: 'iconCls',

        indent: null,

        /**
         * @cfg {Ext.list.Tree} owner
         * The owning list for this container.
         */
        owner: null,

        /**
         * @cfg {Ext.data.TreeModel} node
         * The backing node for this item.
         */
        node: null,

        /**
         * @cfg {Number} over
         * One of three possible values:
         *
         *   - 0 if mouse is not over this item or any of its descendants.
         *   - 1 if mouse is not over this item but is over one of this item's descendants.
         *   - 2 if mouse is directly over this item.
         */
        over: null,

        /**
         * @cfg {Ext.list.AbstractTreeItem} parentItem
         * The parent item for this item. 
         */
        parentItem: null,

        /**
         * @cfg {String} text
         * The text for this item. This value is taken from
         * the underlying {@link #node}.
         */
        text: {
            lazy: true,
            $value: ''
        },

        /**
         * @cfg {String} textProperty
         * The property from the {@link #node} to map for the {@link #text} config.
         */
        textProperty: 'text'
    },

    updateNode: function(node) {
        if (node) {
            // eslint-disable-next-line vars-on-top
            var me = this,
                map = me.itemMap,
                childNodes, owner, len, i, item, child;

            me.element.dom.setAttribute('data-recordId', node.internalId);

            if (!map) {
                childNodes = node.childNodes;
                owner = me.getOwner();
                me.itemMap = map = {};

                for (i = 0, len = childNodes.length; i < len; ++i) {
                    child = childNodes[i];

                    if (child.data.visible) {
                        item = owner.createItem(child, me);
                        map[child.internalId] = item;
                        me.insertItem(item, null);
                    }
                }
            }

            me.setExpanded(node.isExpanded());
            me.doNodeUpdate(node);
        }
    },

    updateSelected: function(selected) {
        var parent;

        if (!this.isConfiguring) {
            parent = this.getParentItem();

            while (parent && !parent.isRootListItem) {
                parent.setSelectedParent(selected);
                parent = parent.getParentItem();
            }
        }
    },

    /**
     * Collapse this item. Does nothing if already collapsed.
     */
    collapse: function() {
        this.getNode().collapse();
    },

    /**
     * Expand this item. Does nothing if already expanded.
     */
    expand: function() {
        this.getNode().expand();
    },

    /**
     * @method
     * Gets the element to be used for the tree when it is in 
     * {@link Ext.list.Tree#micro micro} mode.
     * @return {Ext.dom.Element} The element.
     *
     * @protected
     * @template
     */
    getToolElement: Ext.emptyFn,

    /**
     * @method
     * Append a new child item to the DOM.
     * @param {Ext.list.AbstractTreeItem} item The item to insert.
     * @param {Ext.list.AbstractTreeItem} refItem The item the node is to
     * be inserted before. `null` if the item is to be added to the end.
     *
     * @protected
     * @template
     */
    insertItem: Ext.emptyFn,

    /**
     * Check if the current item is expanded.
     * @return {Boolean} `true` if this item is expanded.
     */
    isExpanded: function() {
        return this.getExpanded();
    },

    /**
     * @method
     * Checks whether the event is an event that should select this node.
     * @param {Ext.event.Event} e The event object.
     * @return {Boolean} `true` if the event should select this node.
     *
     * @protected
     * @template
     */
    isSelectionEvent: Ext.emptyFn,

    /**
     * @method
     * Checks whether the event is an event that should toggle the expand/collapse state.
     * @param {Ext.event.Event} e The event object.
     * @return {Boolean} `true` if the event should toggle the expand/collapsed state.
     * 
     * @protected
     * @template
     */
    isToggleEvent: Ext.emptyFn,

    /**
     * Handle this node being collapsed.
     * @param {Ext.data.TreeModel} node  The node being collapsed.
     * @param collapsingForExpand
     * @protected
     */
    nodeCollapse: function(node, collapsingForExpand) {
        var me = this,
            owner = me.getOwner(),
            animation = me.preventAnimation ? null : owner.getAnimation();

        me.nodeCollapseBegin(animation, collapsingForExpand);

        if (!animation) {
            me.nodeCollapseEnd(collapsingForExpand);
        }
    },

    nodeCollapseBegin: function(animation, collapsingForExpand) {
        var me = this,
            owner = me.getOwner();

        me.setExpanded(false);

        owner.fireEvent('itemcollapse', owner, me);
    },

    nodeCollapseEnd: function(collapsingForExpand) {
        if (!collapsingForExpand && !this.destroying) {
            this.getOwner().updateLayout();
        }
    },

    /**
     * Handle this node being expanded.
     * @param {Ext.data.TreeModel} node  The node being expanded.
     *
     * @protected
     */
    nodeExpand: function(node) {
        var me = this,
            owner = me.getOwner(),
            floated = me.getFloated(),
            animation = !floated && owner.getAnimation();

        me.nodeExpandBegin(animation);

        if (!animation) {
            me.nodeExpandEnd();
        }
    },

    nodeExpandBegin: function(animation) {
        var me = this,
            owner = me.getOwner();

        me.setExpanded(true);

        owner.fireEvent('itemexpand', owner, me);
    },

    nodeExpandEnd: function() {
        if (!this.destroying) {
            this.getOwner().updateLayout();
        }
    },

    /**
     * Handle a node being inserted as a child of this item.
     * @param {Ext.data.TreeModel} node  The node being inserted.
     * @param {Ext.data.TreeModel} refNode The node that is to be inserted before. `null`
     * if this operation is an append.
     *
     * @protected
     */
    nodeInsert: function(node, refNode) {
        var me = this,
            owner = me.getOwner(),
            map = me.itemMap,
            id = node.internalId,
            item = owner.getItem(node),
            refItem = null,
            oldParent;

        if (item) {
            oldParent = item.getParentItem();
            // May have some kind of custom removal processing, allow it to happen, even if it's us
            oldParent.removeItem(item);

            if (oldParent !== me) {
                oldParent.doUpdateExpandable();
                item.setParentItem(me);
            }
        }
        else {
            item = me.getOwner().createItem(node, me);
        }

        map[id] = item;

        if (refNode) {
            refItem = map[refNode.internalId];
        }

        me.insertItem(item, refItem);
        me.doUpdateExpandable();

        owner.fireEvent('iteminsert', owner, me, item, refItem);

        owner.updateLayout();
    },

    /**
     * Handle a node being removed as a child of this item.
     * @param {Ext.data.TreeModel} node  The node being removed.
     *
     * @protected
     */
    nodeRemove: function(node) {
        var me = this,
            map = me.itemMap,
            owner = me.getOwner(),
            id = node.internalId,
            item = map[id];

        if (item) {
            delete map[id];
            me.removeItem(item);
            item.destroy();

            me.doUpdateExpandable();

            owner.fireEvent('itemremove', owner, me, item);

            owner.updateLayout();
        }
    },

    /**
     * Handle this node having fields changed.
     * 
     * @param {Ext.data.TreeModel} node The node.
     * @param {String[]} modifiedFieldNames The modified field names, if known.
     *
     * @protected
     */
    nodeUpdate: function(node, modifiedFieldNames) {
        this.doNodeUpdate(node);
    },

    /**
     * Handle a click on this item.
     * @param {Ext.event.Event} e The event
     *
     * @protected
     */
    onClick: function(e) {
        var me = this,
            owner = me.getOwner(),
            node = me.getNode(),
            info = {
                event: e,
                item: me,
                node: node,
                tree: owner,
                select: node.get('selectable') !== false && me.isSelectionEvent(e),
                toggle: me.isToggleEvent(e)
            };

        /**
         * @event itemclick
         * @member Ext.list.Tree
         *
         * @param {Ext.list.Tree} sender The `treelist` that fired this event.
         *
         * @param {Object} info
         * @param {Ext.event.Event} info.event The DOM event that precipitated this
         * event.
         * @param {Ext.list.AbstractTreeItem} info.item The tree node that was clicked.
         * @param {Ext.list.Tree} info.tree The `treelist` that fired this event.
         * @param {Boolean} info.select On input this is value is the result of the
         *   {@link #isSelectionEvent} method. On return from event handlers (assuming a
         *   `false` return does not cancel things) this property is used to determine
         *   if the clicked node should be selected.
         * @param {Boolean} info.toggle On input this is value is the result of the
         *   {@link #isToggleEvent} method. On return from event handlers (assuming a
         *   `false` return does not cancel things) this property is used to determine
         *   if the clicked node's expand/collapse state should be toggled.
         *
         * @since 6.0.1
         */
        if (owner.fireEvent('itemclick', owner, info) !== false) {
            if (info.toggle) {
                me.toggleExpanded();
                e.preventDefault();
            }

            if (info.select) {
                owner.setSelection(me.getNode());
            }
        }
    },

    /**
     * @method
     *
     * Remove a child item from the DOM.
     * @param {Ext.list.AbstractTreeItem} item The item to remove.
     *
     * @protected
     * @template
     */
    removeItem: Ext.emptyFn,

    /**
     * @method destroy
     * @inheritdoc
     */
    destroy: function() {
        var me = this,
            map = me.itemMap,
            owner = me.getOwner(),
            key;

        if (map) {
            for (key in map) {
                map[key].destroy();
            }

            me.itemMap = null;
        }

        if (owner) {
            owner.removeItem(me.getNode());
        }

        me.setNode(null);
        me.setParentItem(null);
        me.setOwner(null);

        me.callParent();
    },

    privates: {
        /**
         * Update properties after a node update.
         *
         * @param {Ext.data.TreeModel} node The node.
         * @param {String[]} modifiedFieldNames The modified field names, if known.
         *
         * @private
         */
        doNodeUpdate: function(node, modifiedFieldNames) {
            var me = this,
                textProperty = this.getTextProperty(),
                iconClsProperty = this.getIconClsProperty();

            if (textProperty) {
                me.setText(node.data[textProperty]);
            }

            if (iconClsProperty) {
                me.setIconCls(node.data[iconClsProperty]);
            }

            me.setLoading(node.isLoading());
            me.setLeaf(node.isLeaf());
            me.doUpdateExpandable();
        },

        doUpdateExpandable: function() {
            var node = this.getNode();

            this.setExpandable(node.isExpandable());
        },

        toggleExpanded: function() {
            if (this.isExpanded()) {
                this.collapse();
            }
            else {
                this.expand();
            }
        },

        updateIndent: function(value) {
            var items = this.itemMap,
                id;

            for (id in items) {
                items[id].setIndent(value);
            }
        },

        /**
         * Implementation so that the Traversable mixin which manipulates the parent
         * axis can function in a TreeList.
         * @param {Ext.list.Tree/Ext.list.TreeItem} owner The owner of this node.
         * @private
         */
        updateOwner: function(owner) {
            this.parent = owner;
        }
    }
});

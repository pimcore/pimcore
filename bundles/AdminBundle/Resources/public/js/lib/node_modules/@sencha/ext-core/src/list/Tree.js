/**
 * A lightweight component to display data in a simple tree structure using a
 * {@link Ext.data.TreeStore}.
 *
 * Simple Treelist using inline data:
 *
 *     @example
 *     Ext.create({
 *         xtype: 'treelist',
 *         store: {
 *             root: {
 *             expanded: true,
 *                 children: [{
 *                     text: 'detention',
 *                     leaf: true,
 *                     iconCls: 'x-fa fa-frown-o'
 *                 }, {
 *                     text: 'homework',
 *                     expanded: true,
 *                     iconCls: 'x-fa fa-folder',
 *                     children: [{
 *                         text: 'book report',
 *                         leaf: true,
 *                         iconCls: 'x-fa fa-book'
 *                     }, {
 *                         text: 'algebra',
 *                         leaf: true,
 *                         iconCls: 'x-fa fa-graduation-cap'
 *                     }]
 *                 }, {
 *                     text: 'buy lottery tickets',
 *                     leaf: true,
 *                     iconCls: 'x-fa fa-usd'
 *                 }]
 *             }
 *         },
 *         renderTo: Ext.getBody()
 *     });
 *
 * To collapse the Treelist for use in a smaller navigation view see {@link #micro}.
 * Parent Treelist node expansion may be refined using the {@link #singleExpand} and
 * {@link #expanderOnly} config options.  Treelist nodes will be selected when clicked /
 * tapped excluding clicks on the expander unless {@link #selectOnExpander} is set to
 * `true`.
 *
 * @since 6.0.0
 */
Ext.define('Ext.list.Tree', {
    extend: 'Ext.Gadget',
    xtype: 'treelist',

    mixins: [
        'Ext.mixin.ItemRippler'
    ],

    requires: [
        'Ext.list.RootTreeItem'
    ],

    expanderFirstCls: Ext.baseCSSPrefix + 'treelist-expander-first',
    expanderOnlyCls: Ext.baseCSSPrefix + 'treelist-expander-only',
    highlightPathCls: Ext.baseCSSPrefix + 'treelist-highlight-path',
    microCls: Ext.baseCSSPrefix + 'treelist-micro',

    uiPrefix: Ext.baseCSSPrefix + 'treelist-',

    /**
     * @property element
     * @inheritdoc
     */
    element: {
        reference: 'element',
        cls: Ext.baseCSSPrefix + 'treelist ' + Ext.baseCSSPrefix + 'unselectable',
        listeners: {
            click: 'onClick',
            touchstart: 'onTouchStart',
            touchend: 'onTouchEnd',
            mouseenter: 'onMouseEnter',
            mouseleave: 'onMouseLeave',
            mouseover: 'onMouseOver'
        },
        children: [{
            reference: 'toolsElement',
            cls: Ext.baseCSSPrefix + 'treelist-toolstrip',
            listeners: {
                click: 'onToolStripClick',
                mouseover: 'onToolStripMouseOver'
            }
        }]
    },

    cachedConfig: {
        animation: {
            duration: 500,
            easing: 'ease'
        },

        /**
         * @cfg {Boolean} expanderFirst
         * `true` to display the expander to the left of the item text.
         * `false` to display the expander to the right of the item text.
         */
        expanderFirst: true,

        /**
         * @cfg {Boolean} expanderOnly
         * `true` to expand only on the click of the expander element. Setting this to
         * `false` will allow expansion on click of any part of the element.
         */
        expanderOnly: true
    },

    config: {
        /**
         * @cfg {Boolean} floatLeafItems
         * `true` to allow the popout to show on leaf items on click/tap. This is the same popout
         * (menu) non-leaf items show their child items in. `false` to prevent the popout
         * from showing for leaf items.
         */
        floatLeafItems: false,

        /**
         * @cfg {Object} [defaults]
         * The default configuration for the widgets created for tree items.
         *
         * @cfg {String} [defaults.xtype="treelistitem"]
         * The type of item to create. By default, items are
         * `{@link Ext.list.TreeItem treelistitem}` instances. This can be customized but this
         * `xtype` must reference a class that ultimately derives from the
         * `{@link Ext.list.AbstractTreeItem}` base class.
         */
        defaults: {
            xtype: 'treelistitem'
        },

        /**
         * @cfg {Boolean}
         * Set as `true` to highlight all items on the path to the currently selected
         * node.
         */
        highlightPath: null,

        iconSize: null,

        /**
         * @cfg {Number} [indent=null]
         * 
         * The number of pixels to offset each level of tree nodes.
         */
        indent: null,

        /**
         * @cfg {Boolean}
         *
         * Set to `true` to collapse the Treelist UI to display only the
         * {@link Ext.data.NodeInterface#cfg-iconCls icons} of the root nodes.  Hovering
         * the cursor (or tapping on a touch-enabled device) shows the child nodes beside
         * the icon.
         */
        micro: false,

        overItem: null,

        /**
         * @cfg {Ext.data.TreeModel/Number/String} selection
         *
         * The current selected node or its ID.
         */
        selection: null,

        /**
         * @cfg {Boolean} selectOnExpander
         * `true` to select the node when clicking the expander.
         */
        selectOnExpander: false,

        /**
         * @cfg {Boolean} [singleExpand=false]
         * `true` if only 1 node per branch may be expanded.
         */
        singleExpand: null,

        /**
         * @cfg {String/Object/Ext.data.TreeStore} store
         * The data source to which this component is bound.
         */
        store: null,

        /**
         * @cfg ui
         * @inheritdoc
         */
        ui: null
    },

    /**
     * @event selectionchange
     * This event fires when {@link Ext.list.Tree#selection} changes
     * @param {Ext.list.Tree} treelist The component firing this event.
     * @param {Ext.data.TreeModel} record The currently selected node.
     */

    /**
     * @cfg twoWayBindable
     * @inheritdoc
     */
    twoWayBindable: {
        selection: 1
    },

    /**
     * @cfg publishes
     * @inheritdoc
     */
    publishes: {
        selection: 1
    },

    /**
     * @property defaultBindProperty
     * @inheritdoc
     */
    defaultBindProperty: 'store',

    constructor: function(config) {
        this.callParent([config]);
        // Important to publish the value here, so the vm can
        // will know our intial state.
        this.publishState('selection', this.getSelection());
    },

    destroy: function() {
        var me = this;

        me.unfloatAll();
        me.activeFloater = null;
        me.setSelection(null);
        me.setStore(null);
        me.callParent();
    },

    updateOverItem: function(over, wasOver) {
        var map = {},
            state = 2,
            c, node;

        // Walk up the node hierarchy starting at the "over" item and set their "over"
        // config appropriately (2 when over that row, 1 when over a descendant).
        //
        for (c = over; c; c = this.getItem(node.parentNode)) {
            node = c.getNode();
            map[node.internalId] = true;

            c.setOver(state);

            state = 1;
        }

        // There are some cases, like tree filtering where it's possible that the whole tree
        // gets refreshed on expand, so wasOver may be destroyed. In that case, we have nothing to
        // do since the nodes are in a new state
        if (wasOver && !wasOver.destroyed) {
            // If we wasOver something else previously, walk up that node hierarchy and
            // set their "over" to 0... until we encounter some node that we are still
            // "over" (as determined in previous loop).
            //
            for (c = wasOver; c; c = this.getItem(node.parentNode)) {
                node = c.getNode();

                if (map[node.internalId]) {
                    break;
                }

                c.setOver(0);
            }
        }
    },

    applyMicro: function(micro) {
        return Boolean(micro);
    },

    applySelection: function(selection, oldSelection) {
        var store = this.getStore();

        if (!store) {
            selection = null;
        }

        if (store && selection !== null && !(selection instanceof Ext.data.Model)) {
            selection = store.getNodeById(selection);
        }

        if (selection && selection.get('selectable') === false) {
            selection = oldSelection;
        }

        return selection;
    },

    updateSelection: function(selection, oldSelection) {
        var me = this,
            item,
            parent;

        if (!me.destroying) {
            // getItem has guards around null, so we don't
            // need to check for oldSelection/selection here
            item = me.getItem(oldSelection);

            if (item) {
                item.setSelected(false);
            }

            item = me.getItem(selection);

            if (item) {
                item.setSelected(true);

                while (parent = item.getParentItem()) { // eslint-disable-line no-cond-assign
                    parent.setExpanded(true);
                    item = parent;
                }
            }

            me.fireEvent('selectionchange', me, selection);
        }
    },

    applyStore: function(store) {
        return store && Ext.StoreManager.lookup(store, 'tree');
    },

    updateStore: function(store, oldStore) {
        var me = this,
            root;

        if (oldStore) {
            // Store could be already destroyed upstream
            if (!oldStore.destroyed) {
                if (oldStore.getAutoDestroy()) {
                    oldStore.destroy();
                }
                else {
                    me.storeListeners.destroy();
                }
            }

            me.removeRoot();
            me.storeListeners = null;
        }

        if (store) {
            me.storeListeners = store.on({
                destroyable: true,
                scope: me,
                nodeappend: 'onNodeAppend',
                nodecollapse: 'onNodeCollapse',
                nodeexpand: 'onNodeExpand',
                nodeinsert: 'onNodeInsert',
                noderemove: 'onNodeRemove',
                rootchange: 'onRootChange',
                update: 'onNodeUpdate',
                refresh: 'onRefresh'
            });

            root = store.getRoot();

            if (root) {
                me.createRootItem(root);
            }
        }

        if (!me.destroying) {
            me.updateLayout();
        }
    },

    updateExpanderFirst: function(expanderFirst) {
        this.element.toggleCls(this.expanderFirstCls, expanderFirst);
    },

    updateExpanderOnly: function(value) {
        this.element.toggleCls(this.expanderOnlyCls, !value);
    },

    updateHighlightPath: function(updatePath) {
        this.element.toggleCls(this.highlightPathCls, updatePath);
    },

    updateMicro: function(micro) {
        var me = this;

        if (!micro) {
            me.unfloatAll();
            me.activeFloater = null;
        }

        me.element.toggleCls(me.microCls, micro);
    },

    updateUi: function(ui, oldValue) {
        var me = this,
            el = me.element,
            uiPrefix = me.uiPrefix;

        if (oldValue) {
            el.removeCls(uiPrefix + oldValue);
        }

        if (ui) {
            el.addCls(uiPrefix + ui);
        }

        // Ensure that the cached iconSize is read from the style.
        delete me.iconSize;
        me.syncIconSize();
    },

    /**
     * Get a child {@link Ext.list.AbstractTreeItem item} by node.
     * @param {Ext.data.TreeModel} node The node.
     * @return {Ext.list.AbstractTreeItem} The item. `null` if not found.
     */
    getItem: function(node) {
        var map = this.itemMap,
            ret;

        if (node && map) {
            ret = map[node.internalId];
        }

        return ret || null;
    },

    /**
     * This method is called to populate and return a config object for new nodes. This
     * can be overridden by derived classes to manipulate properties or `xtype` of the
     * returned object. Upon return, the object is passed to `{@link Ext#method!create}` and the
     * reference is stored as part of this tree.
     *
     * The base class implementation will apply any configured `{@link #defaults}` to the
     * object it returns.
     *
     * @param {Ext.data.TreeModel} node The node backing the item.
     * @param {Ext.list.AbstractTreeItem} parent The parent item. This is never `null` but
     * may be an instance of `{@link Ext.list.RootTreeItem}`.
     * @return {Object} The config object to pass to `{@link Ext#method!create}` for the item.
     * @template
     */
    getItemConfig: function(node, parent) {
        return Ext.apply({
            parentItem: parent.isRootListItem ? null : parent,
            owner: this,
            node: node,
            indent: this.getIndent()
        }, this.getDefaults());
    },

    privates: {
        checkForOutsideClick: function(e) {
            var floater = this.activeFloater;

            if (!floater.element.contains(e.target)) {
                this.unfloatAll();
            }
        },

        collapsingForExpand: false,

        /**
         * Create a new list item.
         * @param {Ext.data.TreeModel} node The node backing the item.
         * @param {Ext.list.AbstractTreeItem} parent The parent item.
         * @return {Ext.list.AbstractTreeItem} The list item.
         *
         * @private
         */
        createItem: function(node, parent) {
            var me = this,
                item = Ext.create(me.getItemConfig(node, parent)),
                toolsElement = me.toolsElement,
                toolEl, previousSibling;

            if (parent.isRootListItem) {
                toolEl = item.getToolElement();

                if (toolEl) {
                    previousSibling = me.findVisiblePreviousSibling(node);

                    if (!previousSibling) {
                        toolsElement.insertFirst(toolEl);
                    }
                    else {
                        previousSibling = me.getItem(previousSibling);
                        toolEl.insertAfter(previousSibling.getToolElement());
                    }

                    toolEl.dom.setAttribute('data-recordId', node.internalId);
                    toolEl.isTool = true;
                }
            }

            me.itemMap[node.internalId] = item;

            return item;
        },

        /**
         * Create a root item for this list.
         * @param {Ext.data.TreeModel} root The root node.
         *
         * @private
         */
        createRootItem: function(root) {
            var me = this,
                item;

            me.itemMap = {};
            me.rootItem = item = new Ext.list.RootTreeItem({
                indent: me.getIndent(),
                node: root,
                owner: me
            });

            me.element.appendChild(item.element);

            me.itemMap[root.internalId] = item;
        },

        findVisiblePreviousSibling: function(node) {
            var sibling = node.previousSibling;

            while (sibling) {
                if (sibling.data.visible) {
                    return sibling;
                }

                sibling = sibling.previousSibling;
            }

            return null;
        },

        floatItem: function(item, byHover) {
            var me = this,
                floater;

            if (item.getFloated()) {
                return;
            }

            // Cancel any mouseout timer,
            if (me.toolMouseListeners) {
                me.toolMouseListeners.destroy();
                me.floaterMouseListeners.destroy();

                me.floaterMouseListeners = me.toolMouseListeners = null;
            }

            me.unfloatAll();

            if (!byHover && !me.getFloatLeafItems() && item.getNode().isLeaf()) {
                return;
            }

            me.activeFloater = floater = item;
            me.floatedByHover = byHover;

            item.setFloated(true);

            if (byHover) {
                // monitorMouseLeave allows straying out for the specified short time
                me.toolMouseListeners =
                    item.getToolElement().monitorMouseLeave(300, me.checkForMouseLeave, me);
                me.floaterMouseListeners =
                    (item.floater || item).el.monitorMouseLeave(300, me.checkForMouseLeave, me);

                floater.element.on('mouseover', 'onMouseOver', me);
            }
            else {
                Ext.on('mousedown', 'checkForOutsideClick', me);
            }
        },

        shouldRippleItem: function(item, e) {
            if (item && item.getSelected()) {
                return false;
            }

            return this.mixins.itemrippler.shouldRippleItem.call(this, item, e);
        },

        onTouchStart: function(e) {
            this.doItemRipple(e);
        },

        onTouchEnd: function(e) {
            this.doItemRipple(e);
        },

        doItemRipple: function(e) {
            var me = this,
                item = e.getTarget('[data-recordId]'),
                id;

            if (item) {
                id = item.getAttribute('data-recordId');
                item = me.itemMap[id];

                if (item && me.shouldRippleItem(item, e)) {
                    this.rippleItem(item, e);
                }
            }
        },

        /**
         * Handles when this element is clicked.
         * @param {Ext.event.Event} e The event.
         *
         * @private
         */
        onClick: function(e) {
            var item = e.getTarget('[data-recordId]'),
                id;

            if (item) {
                id = item.getAttribute('data-recordId');
                item = this.itemMap[id];

                if (item) {
                    item.onClick(e);
                }
            }
        },

        onMouseEnter: function(e) {
            this.onMouseOver(e);
        },

        onMouseLeave: function() {
            this.setOverItem(null);
        },

        onMouseOver: function(e) {
            var comp = Ext.Component.from(e);

            this.setOverItem(comp && comp.isTreeListItem && comp);
        },

        checkForMouseLeave: function(e) {
            var floater = this.activeFloater,
                relatedTarget = e.getRelatedTarget();

            if (floater) {
                if (relatedTarget !== floater.getToolElement().dom &&
                    !floater.element.contains(relatedTarget)) {
                    this.unfloatAll();
                }
            }
        },

        /**
         * Handles a node being appended to a parent.
         * @param {Ext.data.TreeModel} parentNode The parent node.
         * @param {Ext.data.TreeModel} node The appended node.
         *
         * @private
         */
        onNodeAppend: function(parentNode, node) {
            var item;

            // If it's a root we'll handle it on rootchange
            if (parentNode) {
                item = this.itemMap[parentNode.internalId];

                if (item) {
                    item.nodeInsert(node, null);
                }
            }
        },

        /**
         * Handles when a node collapses.
         * @param {Ext.data.TreeModel} node The node.
         *
         * @private
         */
        onNodeCollapse: function(node) {
            var item = this.itemMap[node.internalId];

            if (item) {
                item.nodeCollapse(node, this.collapsingForExpand);
            }
        },

        /**
         * Handles when a node expands.
         * @param {Ext.data.TreeModel} node The node.
         *
         * @private
         */
        onNodeExpand: function(node) {
            var me = this,
                item = me.itemMap[node.internalId],
                childNodes, len, i, parentNode, child;

            if (item) {
                if (!item.isRootItem && me.getSingleExpand()) {
                    me.collapsingForExpand = true;
                    parentNode = (item.getParentItem() || me.rootItem).getNode();
                    childNodes = parentNode.childNodes;

                    for (i = 0, len = childNodes.length; i < len; ++i) {
                        child = childNodes[i];

                        if (child !== node) {
                            child.collapse();
                        }
                    }

                    me.collapsing = false;
                }

                item.nodeExpand(node);
            }
        },

        /**
         * Handles a node being inserted into a parent.
         * @param {Ext.data.TreeModel} parentNode The parent node.
         * @param {Ext.data.TreeModel} node The inserted node.
         * @param {Ext.data.TreeModel} refNode The node this was inserted before.
         *
         * @private
         */
        onNodeInsert: function(parentNode, node, refNode) {
            var item = this.itemMap[parentNode.internalId];

            if (item) {
                item.nodeInsert(node, refNode);
            }
        },

        /**
         * Handles a node being removed from a parent.
         * @param {Ext.data.TreeModel} parentNode The parent node.
         * @param {Ext.data.TreeModel} node The removed node.
         * @param {Boolean} isMove `true` if this node is moving inside the tree.
         *
         * @private
         */
        onNodeRemove: function(parentNode, node, isMove) {
            var item;

            // If it's a move we don't need to do anything, we won't process it
            // as a removal, the addition will handle it all.
            // Also if the node being removed is the root we'll handle it in rootchange
            if (parentNode && !isMove) {
                item = this.itemMap[parentNode.internalId];

                if (item) {
                    item.nodeRemove(node);
                }
            }
        },

        /**
         * Handles when a node updates.
         * @param {Ext.data.TreeStore} store The store.
         * @param {Ext.data.TreeModel} node The node.
         * @param {String} type The update type.
         * @param {String[]} modifiedFieldNames The modified field names, if known.
         *
         * @private
         */
        onNodeUpdate: function(store, node, type, modifiedFieldNames) {
            var item = this.itemMap[node.internalId];

            if (item) {
                item.nodeUpdate(node, modifiedFieldNames);
            }
        },

        /**
         * Handles before a root node loads
         * @param {Ext.data.TreeStore} store The store.
         * @private
         */
        onRefresh: function(store) {
            // Because the tree can use bottom up or top down filtering (or reload), 
            // don't try and figure out, what changed here
            // just do a global refresh
            this.onRootChange(store.getRoot());
        },

        /**
         * Handles when the root node in the tree changes.
         * @param {Ext.data.TreeModel} root The root.
         *
         * @private
         */
        onRootChange: function(root) {
            var me = this;

            me.removeRoot();

            if (root) {
                me.createRootItem(root);
            }

            me.updateLayout();
            me.fireEvent('refresh', me);
        },

        /**
         * Removes a list item.
         * @param {Ext.data.TreeModel} node The node backing the item.
         *
         * @private
         */
        removeItem: function(node) {
            var map = this.itemMap,
                id = node.internalId,
                item, toolEl;

            if (map) {
                item = map[id];

                // If it's null, it means it's a root level item
                if (item.getParentItem() === null) {
                    toolEl = item.getToolElement();

                    if (toolEl) {
                        this.toolsElement.removeChild(toolEl);
                    }
                }

                delete map[id];
            }
        },

        removeRoot: function() {
            var me = this,
                rootItem = me.rootItem;

            if (rootItem) {
                me.element.removeChild(rootItem.element);
                me.rootItem = me.itemMap = Ext.destroy(rootItem);
            }
        },

        /**
         * Handles when the toolstrip has a click.
         * @param {Ext.event.Event} e The event.
         *
         * @private
         */
        onToolStripClick: function(e) {
            var item = e.getTarget('[data-recordId]'),
                id;

            if (item) {
                id = item.getAttribute('data-recordId');
                item = this.itemMap[id];

                if (item) {
                    if (item === this.activeFloater) {
                        this.unfloatAll();
                    }
                    else {
                        this.floatItem(item, false);
                    }
                }
            }
        },

        /**
         * Handles when the toolstrip has a mouseover.
         * @param {Ext.event.Event} e The event.
         *
         * @private
         */
        onToolStripMouseOver: function(e) {
            var item = e.getTarget('[data-recordId]'),
                id;

            if (item) {
                id = item.getAttribute('data-recordId');
                item = this.itemMap[id];

                if (item) {
                    this.floatItem(item, true);
                }
            }
        },

        syncIconSize: function() {
            var me = this,
                size = me.iconSize ||
                    (me.iconSize = parseInt(me.element.getStyle('background-position'), 10));

            me.setIconSize(size);
        },

        unfloatAll: function() {
            var me = this,
                floater = me.activeFloater;

            if (floater) {
                floater.setFloated(false);
                me.activeFloater = null;

                if (me.floatedByHover) {
                    if (me.toolMouseListeners) {
                        me.toolMouseListeners.destroy();
                        me.floaterMouseListeners.destroy();

                        me.floaterMouseListeners = me.toolMouseListeners = null;
                    }

                    floater.element.un('mouseover', 'onMouseOver', me);
                }
                else {
                    Ext.un('mousedown', 'checkForOutsideClick', me);
                }
            }
        },

        defaultIconSize: 22,

        updateIconSize: function(value) {
            this.setIndent(value || this.defaultIconSize);
        },

        updateIndent: function(value) {
            var rootItem = this.rootItem;

            if (rootItem) {
                rootItem.setIndent(value);
            }
        }
    }
});

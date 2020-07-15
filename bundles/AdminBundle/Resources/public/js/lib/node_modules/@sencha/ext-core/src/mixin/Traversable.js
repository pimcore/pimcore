/**
 * A Traversable mixin.
 * @private
 */
Ext.define('Ext.mixin.Traversable', {
    extend: 'Ext.Mixin',

    mixinConfig: {
        id: 'traversable'
    },

    setParent: function(parent) {
        this.parent = parent;

        return this;
    },

    /**
     * Returns `true` if this component has a parent.
     * @return {Boolean} `true` if this component has a parent.
     */
    hasParent: function() {
        return Boolean(this.getParent());
    },

    /**
     * @template
     * Selector processing function for use by {@link #nextSibling},{@link #previousibling},
     * {@link #nextNode},and {@link #previousNode}, to filter candidate nodes.
     *
     * The base implementation returns true. Classes which mix in `Traversable` may implement
     * their own implementations. `@link{Ext.Widget}` does this to implement
     * {@link Ext.ComponentQuery} based filterability.
     * @returns {boolean}
     */
    is: function() {
        return true;
    },

    /**
     * Returns the parent of this component, if it has one.
     * @return {Ext.Component} The parent of this component.
     */
    getParent: function() {
        return this.parent || this.$initParent;
    },

    getAncestors: function() {
        var ancestors = [],
            parent = this.getParent();

        while (parent) {
            ancestors.push(parent);
            parent = parent.getParent();
        }

        return ancestors;
    },

    getAncestorIds: function() {
        var ancestorIds = [],
            parent = this.getParent();

        while (parent) {
            ancestorIds.push(parent.getId());
            parent = parent.getParent();
        }

        return ancestorIds;
    },

    /**
     * Returns the previous node in the Component tree in tree traversal order.
     *
     * Note that this is not limited to siblings, and if invoked upon a node with no matching
     * siblings, will walk the tree in reverse order to attempt to find a match. Contrast with
     * {@link #previousSibling}.
     * @param {String} [selector] A {@link Ext.ComponentQuery ComponentQuery} selector to filter
     * the preceding nodes.
     * @param includeSelf (private)
     * @return {Ext.Component} The previous node (or the previous node which matches the selector).
     * Returns `null` if there is no matching node.
     */
    previousNode: function(selector, includeSelf) {
        var node = this,
            parent = node.getRefOwner(),
            result,
            it, i, sibling;

        // If asked to include self, test me
        if (includeSelf && node.is(selector)) {
            return node;
        }

        if (parent) {
            for (it = parent.items.items, i = Ext.Array.indexOf(it, node) - 1; i > -1; i--) {
                sibling = it[i];

                if (sibling.query) {
                    result = sibling.query(selector);
                    result = result[result.length - 1];

                    if (result) {
                        return result;
                    }
                }

                if (!selector || sibling.is(selector)) {
                    return sibling;
                }
            }

            return parent.previousNode(selector, true);
        }

        return null;
    },

    /**
     * Returns the previous sibling of this Component.
     *
     * Optionally selects the previous sibling which matches the passed
     * {@link Ext.ComponentQuery ComponentQuery} selector.
     *
     * May also be referred to as **`prev()`**
     *
     * Note that this is limited to siblings, and if no siblings of the item match, `null`
     * is returned. Contrast with {@link #previousNode}
     * @param {String} [selector] A {@link Ext.ComponentQuery ComponentQuery} selector to filter
     * the preceding items.
     * @return {Ext.Component} The previous sibling (or the previous sibling which matches
     * the selector). Returns `null` if there is no matching sibling.
     */
    previousSibling: function(selector) {
        var parent = this.getRefOwner(),
            it, idx, sibling;

        if (parent) {
            it = parent.items;
            idx = it.indexOf(this);

            if (idx !== -1) {
                if (selector) {
                    for (--idx; idx >= 0; idx--) {
                        if ((sibling = it.getAt(idx)).is(selector)) {
                            return sibling;
                        }
                    }
                }
                else {
                    if (idx) {
                        return it.getAt(--idx);
                    }
                }
            }
        }

        return null;
    },

    /**
     * Returns the next node in the Component tree in tree traversal order.
     *
     * Note that this is not limited to siblings, and if invoked upon a node with no matching
     * siblings, will walk the tree to attempt to find a match. Contrast with {@link #nextSibling}.
     * @param {String} [selector] A {@link Ext.ComponentQuery ComponentQuery} selector to filter
     * the following nodes.
     * @param includeSelf (private)
     * @return {Ext.Component} The next node (or the next node which matches the selector).
     * Returns `null` if there is no matching node.
     */
    nextNode: function(selector, includeSelf) {
        var node = this,
            parent = node.getRefOwner(),
            result,
            it, len, i, sibling;

        // If asked to include self, test me
        if (includeSelf && node.is(selector)) {
            return node;
        }

        if (parent) {
            // eslint-disable-next-line max-len
            for (it = parent.items.items, i = Ext.Array.indexOf(it, node) + 1, len = it.length; i < len; i++) {
                sibling = it[i];

                if (!selector || sibling.is(selector)) {
                    return sibling;
                }

                if (sibling.down) {
                    result = sibling.down(selector);

                    if (result) {
                        return result;
                    }
                }
            }

            return parent.nextNode(selector);
        }

        return null;
    },

    /**
     * Returns the next sibling of this Component.
     *
     * Optionally selects the next sibling which matches the passed
     * {@link Ext.ComponentQuery ComponentQuery} selector.
     *
     * May also be referred to as **`next()`**
     *
     * Note that this is limited to siblings, and if no siblings of the item match, `null`
     * is returned. Contrast with {@link #nextNode}
     * @param {String} [selector] A {@link Ext.ComponentQuery ComponentQuery} selector to filter
     * the following items.
     * @return {Ext.Component} The next sibling (or the next sibling which matches the selector).
     * Returns `null` if there is no matching sibling.
     */
    nextSibling: function(selector) {
        var parent = this.getRefOwner(),
            it, last, idx, sibling;

        if (parent) {
            it = parent.items;
            idx = it.indexOf(this) + 1;

            if (idx) {
                if (selector) {
                    for (last = it.getCount(); idx < last; idx++) {
                        if ((sibling = it.getAt(idx)).is(selector)) {
                            return sibling;
                        }
                    }
                }
                else {
                    if (idx < it.getCount()) {
                        return it.getAt(idx);
                    }
                }
            }
        }

        return null;
    }
});

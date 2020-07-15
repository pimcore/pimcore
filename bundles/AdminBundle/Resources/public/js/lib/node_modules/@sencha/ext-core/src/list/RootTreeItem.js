/**
 * This class implements the top-level node in a `{@link Ext.list.Tree treelist}`. Unlike
 * other nodes, this item is only a container for other items. It does not correspond to
 * a data record.
 *
 * @since 6.0.0
 */
Ext.define('Ext.list.RootTreeItem', {
    extend: 'Ext.list.AbstractTreeItem',

    /**
     * This property is `true` to allow type checking for this or derived class.
     * @property {Boolean} isRootListItem
     * @readonly
     */
    isRootListItem: true,

    element: {
        reference: 'element',
        tag: 'ul',
        cls: Ext.baseCSSPrefix + 'treelist-root-container'
    },

    insertItem: function(item, refItem) {
        if (refItem) {
            item.element.insertBefore(refItem.element);
        }
        else {
            this.element.appendChild(item.element);
        }
    },

    isToggleEvent: function(e) {
        return false;
    }
});

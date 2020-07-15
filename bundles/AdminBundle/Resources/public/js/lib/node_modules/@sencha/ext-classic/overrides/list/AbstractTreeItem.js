/**
 * @class Ext.list.AbstractTreeItem
 */

Ext.define('Ext.overrides.list.AbstractTreeItem', {
    override: 'Ext.list.AbstractTreeItem',

    // This config is used by TreeIten, however to support the generic API (RootItem),
    // we need this up here.
    config: {
        floated: null
    }
});

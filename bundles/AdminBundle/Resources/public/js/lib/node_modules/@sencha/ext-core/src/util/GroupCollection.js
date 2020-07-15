/**
 * @private
 * A collection containing the result of applying grouping to the records in the store.
 */
Ext.define('Ext.util.GroupCollection', {
    extend: 'Ext.util.Collection',

    requires: [
        'Ext.util.Group',

        // Since Collection uses sub-collections of various derived types we step up to
        // list all the requirements of Collection. The idea being that instead of a
        // "requires" of Ext.util.Collection (which cannot pull everything) you instead
        // do a "requires" of Ext.util.GroupCollection and it will.
        'Ext.util.SorterCollection',
        'Ext.util.FilterCollection'
    ],

    isGroupCollection: true,

    config: {
        grouper: null,
        groupConfig: null,
        itemRoot: null
    },

    observerPriority: -100,

    emptyGroupRetainTime: 300000, // Private timer to hang on to emptied groups. Milliseconds.

    constructor: function(config) {
        this.emptyGroups = {};
        this.callParent([config]);
        this.on('remove', 'onGroupRemove', this);
    },

    /**
     * Returns the `Ext.util.Group` associated with the given record.
     *
     * @param {Object} item The item for which the group is desired.
     * @return {Ext.util.Group}
     * @since 6.5.0
     */
    getItemGroup: function(item) {
        var key = this.getGrouper().getGroupString(item);

        return this.get(key);
    },

    //-------------------------------------------------------------------------
    // Calls from the source Collection:

    onCollectionAdd: function(source, details) {
        if (!this.isConfiguring) {
            this.addItemsToGroups(source, details.items, details.at);
        }
    },

    onCollectionBeforeItemChange: function(source, details) {
        this.changeDetails = details;
    },

    onCollectionBeginUpdate: function() {
        this.beginUpdate();
    },

    onCollectionEndUpdate: function() {
        this.endUpdate();
    },

    onCollectionItemChange: function(source, details) {
        // Check if the change to the item caused the item to move. If it did, the group
        // ordering will be handled by virtue of being removed/added to the collection.
        // If not, check whether we're in the correct group and fix up if not.
        if (!details.indexChanged) {
            this.syncItemGrouping(source, details);
        }

        this.changeDetails = null;
    },

    onCollectionRefresh: function(source) {
        if (source.generation) {
            // eslint-disable-next-line vars-on-top
            var me = this,
                itemGroupKeys = me.itemGroupKeys = {},
                groupData = me.createEntries(source, source.items),
                entries = groupData.entries,
                groupKey, i, len, entry, j;

            // The magic of Collection will automatically update the group with its new
            // members.
            for (i = 0, len = entries.length; i < len; ++i) {
                entry = entries[i];

                // Will add or replace
                entry.group.splice(0, 1e99, entry.items);

                // Add item key -> group mapping for every entry
                for (j = 0; j < entry.items.length; j++) {
                    itemGroupKeys[source.getKey(entry.items[j])] = entry.group;
                }
            }

            // Remove groups to which we have not added items.
            entries = null;

            for (groupKey in me.map) {
                if (!(groupKey in groupData.groups)) {
                    (entries || (entries = [])).push(me.map[groupKey]);
                }
            }

            if (entries) {
                me.remove(entries);
            }

            // autoSort is disabled when adding new groups because
            // it relies on there being at least one record in the group
            me.sortItems();
        }
    },

    onCollectionRemove: function(source, details) {
        var me = this,
            changeDetails = me.changeDetails,
            itemGroupKeys = me.itemGroupKeys || (me.itemGroupKeys = {}),
            entries, entry, group, i, n, j, removeGroups, item;

        if (source.getCount()) {
            if (changeDetails) {
                // The item has changed, so the group key may be different, need
                // to look it up
                item = changeDetails.item || changeDetails.items[0];
                entries = me.createEntries(source, [item], false).entries;
                entries[0].group =
                    itemGroupKeys['oldKey' in details ? details.oldKey : source.getKey(item)];
            }
            else {
                entries = me.createEntries(source, details.items, false).entries;
            }

            for (i = 0, n = entries.length; i < n; ++i) {
                group = (entry = entries[i]).group;

                if (group) {
                    group.remove(entry.items);
                }

                // Delete any item key -> group mapping
                for (j = 0; j < entry.items.length; j++) {
                    delete itemGroupKeys[source.getKey(entry.items[j])];
                }

                if (group && !group.length) {
                    (removeGroups || (removeGroups = [])).push(group);
                }
            }
        }
        // Straight cleardown
        else {
            me.itemGroupKeys = {};
            removeGroups = me.items;

            for (i = 0, n = removeGroups.length; i < n; ++i) {
                removeGroups[i].clear();
            }
        }

        if (removeGroups) {
            me.remove(removeGroups);
        }
    },

    // If the SorterCollection instance is not changing, the Group will react to
    // changes inside the SorterCollection, but if the instance changes we need
    // to sync the Group to the new SorterCollection.
    onCollectionSort: function(source) {
        // sorting the collection effectively sorts the items in each group...
        var me = this,
            sorters = source.getSorters(false),
            items, length, i, group;

        if (sorters) {
            items = me.items;
            length = me.length;

            for (i = 0; i < length; ++i) {
                group = items[i];

                if (group.getSorters() === sorters) {
                    group.sortItems();
                }
                else {
                    group.setSorters(sorters);
                }
            }
        }
    },

    onCollectionUpdateKey: function(source, details) {
        if (!details.indexChanged) {
            details.oldIndex = source.indexOf(details.item);
            this.syncItemGrouping(source, details);
        }
    },

    //-------------------------------------------------------------------------
    // Private

    addItemsToGroups: function(source, items, at, oldIndex) {
        var me = this,
            itemGroupKeys = me.itemGroupKeys || (me.itemGroupKeys = {}),
            entries = me.createEntries(source, items).entries,
            index = -1,
            sourceStartIndex, entry, i, len, j, group, firstIndex, item;

        for (i = 0, len = entries.length; i < len; ++i) {
            entry = entries[i];
            group = entry.group;

            // A single item moved - from onCollectionItemChange
            if (oldIndex || oldIndex === 0) {
                item = items[0];

                if (group.getCount() > 0 && source.getSorters().getCount() === 0) {
                    // We have items in the group & it's not sorted, so find the
                    // correct position in the group to insert.
                    firstIndex = source.indexOf(group.items[0]);

                    if (oldIndex < firstIndex) {
                        index = 0;
                    }
                    else {
                        index = oldIndex - firstIndex;
                    }
                }

                if (index === -1) {
                    group.add(item);
                }
                else {
                    group.insert(index, item);
                }
            }
            else {
                if (me.length > 1 && at) {
                    sourceStartIndex = source.indexOf(entries[0].group.getAt(0));
                    at = Math.max(at - sourceStartIndex, 0);
                }

                entry.group.insert(at != null ? at : group.items.length, entry.items);

                // Add item key -> group mapping
                for (j = 0; j < entry.items.length; j++) {
                    itemGroupKeys[source.getKey(entry.items[j])] = entry.group;
                }
            }
        }

        // autoSort is disabled when adding new groups because
        // it relies on there being at least one record in the group
        me.sortItems();
    },

    createEntries: function(source, items, createGroups) {
    // Separate the items out into arrays by group
        var me = this,
            groups = {},
            entries = [],
            grouper = me.getGrouper(),
            entry, group, groupKey, i, item, len;

        for (i = 0, len = items.length; i < len; ++i) {
            groupKey = grouper.getGroupString(item = items[i]);

            if (!(entry = groups[groupKey])) {
                group = me.getGroup(source, groupKey, createGroups);

                entries.push(groups[groupKey] = entry = {
                    group: group,
                    items: []
                });
            }

            // Collect items to add/remove for each group
            // which has items in the array
            entry.items.push(item);
        }

        return {
            groups: groups,
            entries: entries
        };
    },

    syncItemGrouping: function(source, details) {
        var me = this,
            itemGroupKeys = me.itemGroupKeys || (me.itemGroupKeys = {}),
            item = details.item,
            oldKey, itemKey, oldGroup, group;

        itemKey = source.getKey(item);
        oldKey = 'oldKey' in details ? details.oldKey : itemKey;

        // The group the item was in before the change took place.
        oldGroup = itemGroupKeys[oldKey];

        // Look up/create the group into which the item now must be added.
        group = me.getGroup(source, me.getGrouper().getGroupString(item));

        details.group = group;
        details.oldGroup = oldGroup;

        // The change did not cause a change in group
        if (!(details.groupChanged = group !== oldGroup)) {
            // Inform group about change
            oldGroup.itemChanged(item, details.modified, details.oldKey, details);
        }
        else {
            // Remove from its old group if there was one.
            if (oldGroup) {
                // Ensure Geoup knows about any unknown key changes, or item will not be removed.
                oldGroup.updateKey(item, oldKey, itemKey);
                oldGroup.remove(item);

                // Queue newly empy group for destruction.
                if (!oldGroup.length) {
                    me.remove(oldGroup);
                }
            }

            // Add to new group
            me.addItemsToGroups(source, [item], null, details.oldIndex);
        }

        // Keep item key -> group mapping up to date
        delete itemGroupKeys[oldKey];
        itemGroupKeys[itemKey] = group;
    },

    getGroup: function(source, key, createGroups) {
        var me = this,
            group = me.get(key),
            autoSort = me.getAutoSort();

        if (group) {
            group.setSorters(source.getSorters());
        }
        else if (createGroups !== false) {
            group = me.emptyGroups[key] || Ext.create(Ext.apply({
                xclass: 'Ext.util.Group',
                //<debug>
                id: me.getId() + '-group-' + key,
                //</debug>
                groupKey: key,
                rootProperty: me.getItemRoot(),
                sorters: source.getSorters()
            }, me.getGroupConfig()));

            group.ejectTime = null;

            me.setAutoSort(false);
            me.add(group);
            me.setAutoSort(autoSort);
        }

        return group;
    },

    getKey: function(item) {
        return item.getGroupKey();
    },

    createSortFn: function() {
        var me = this,
            grouper = me.getGrouper(),
            sorterFn = me.getSorters().getSortFn();

        if (!grouper) {
            return sorterFn;
        }

        return function(lhs, rhs) {
            // The grouper has come from the collection, so we pass the items in
            // the group for comparison because the grouper is also used to
            // sort the data in the collection
            return grouper.sort(lhs.items[0], rhs.items[0]) || sorterFn(lhs, rhs);
        };
    },

    updateGrouper: function(grouper) {
        var me = this;

        me.grouped = !!(grouper && me.$groupable.getAutoGroup());
        me.onSorterChange();
        me.onEndUpdateSorters(me.getSorters());
    },

    destroy: function() {
        var me = this;

        me.$groupable = null;

        // Ensure group objects get destroyed, they may have
        // added listeners to the main collection sorters.
        me.destroyGroups(me.items);
        Ext.undefer(me.checkRemoveQueueTimer);
        me.callParent();
    },

    privates: {
        destroyGroups: function(groups) {
            var len = groups.length,
                i;

            for (i = 0; i < len; ++i) {
                groups[i].destroy();
            }
        },

        onGroupRemove: function(collection, info) {
            var me = this,
                groups = info.items,
                emptyGroups = me.emptyGroups,
                len, group, i;

            groups = Ext.Array.from(groups);

            for (i = 0, len = groups.length; i < len; i++) {
                group = groups[i];
                group.setSorters(null);
                emptyGroups[group.getGroupKey()] = group;
                group.ejectTime = Ext.now();
            }

            // Removed empty groups are reclaimable by getGroup
            // for emptyGroupRetainTime milliseconds
            me.checkRemoveQueue();
        },

        checkRemoveQueue: function() {
            var me = this,
                emptyGroups = me.emptyGroups,
                groupKey, group, reschedule;

            for (groupKey in emptyGroups) {
                group = emptyGroups[groupKey];

                // If the group's retain time has expired, destroy it.
                if (!group.getCount() && Ext.now() - group.ejectTime > me.emptyGroupRetainTime) {
                    Ext.destroy(group);
                    delete emptyGroups[groupKey];
                }
                else {
                    reschedule = true;
                }
            }

            // Still some to remove in the future. Check back in emptyGroupRetainTime
            if (reschedule) {
                Ext.undefer(me.checkRemoveQueueTimer);
                me.checkRemoveQueueTimer =
                    Ext.defer(me.checkRemoveQueue, me.emptyGroupRetainTime, me);
            }
        }
    }
});

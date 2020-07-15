/**
 * @private
 */
Ext.define('Ext.grid.ViewDropZone', {
    extend: 'Ext.view.DropZone',

    indicatorHtml: '<div class="' + Ext.baseCSSPrefix +
                   'grid-drop-indicator-left" role="presentation"></div><div class="' +
                   Ext.baseCSSPrefix + 'grid-drop-indicator-right" role="presentation"></div>',
    indicatorCls: Ext.baseCSSPrefix + 'grid-drop-indicator',

    handleNodeDrop: function(data, record, position) {
        var view = this.view,
            store = view.getStore(),
            crossView = view !== data.view,
            selectAfter = crossView || data.records.length > 1,
            index, records, i, len;

        // If the copy flag is set, create a copy of the models
        if (data.copy) {
            records = data.records;

            for (i = 0, len = records.length; i < len; i++) {
                records[i] = records[i].copy();
            }
        }
        else if (selectAfter) {
            /*
             * Remove from the source store only if we are moving to a different store
             * or shifting multiple records.
             */
            data.view.store.remove(data.records);
        }

        if (record && position) {
            index = store.indexOf(record);

            // 'after', or undefined (meaning a drop at index -1 on an empty View)...
            if (position !== 'before') {
                index++;
            }

            store.insert(index, data.records);
        }
        // No position specified - append.
        else {
            store.add(data.records);
        }

        // Select the dropped nodes if needed
        if (selectAfter) {
            view.getSelectionModel().select(data.records);
        }

        // Focus the first dropped node.
        view.getNavigationModel().setPosition(data.records[0]);
    }
});

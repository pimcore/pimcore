/**
 * This component provides a grid holding selected items from a second store of potential
 * members. The `store` of this component represents the selected items. The "search store"
 * represents the potentially selected items.
 *
 * While this component is a grid and so you can configure `columns`, it is best to leave
 * that to this class in its `initComponent` method. That allows this class to create the
 * extra column that allows the user to remove rows. Instead use `{@link #fieldName}` and
 * `{@link #fieldTitle}` to configure the primary column's `dataIndex` and column `text`,
 * respectively.
 *
 * @since 5.0.0
 */
Ext.define('Ext.view.MultiSelector', {
    extend: 'Ext.grid.Panel',

    xtype: 'multiselector',

    config: {
        /**
         * @cfg {Object} search
         * This object configures the search popup component. By default this contains the
         * `xtype` for a `Ext.view.MultiSelectorSearch` component and specifies `autoLoad`
         * for its `store`.
         */
        search: {
            xtype: 'multiselector-search',
            width: 200,
            height: 200,
            store: {
                autoLoad: true
            }
        }
    },

    /**
     * @cfg {String} [fieldName="name"]
     * The name of the data field to display in the primary column of the grid.
     * @since 5.0.0
     */
    fieldName: 'name',

    /**
     * @cfg {String} [fieldTitle]
     * The text to display in the column header for the primary column of the grid.
     * @since 5.0.0
     */
    fieldTitle: null,

    /**
     * @cfg {String} removeRowText
     * The text to display in the "remove this row" column. By default this is a Unicode
     * "X" looking glyph.
     * @since 5.0.0
     */
    removeRowText: '\u2716',

    /**
     * @cfg {String} removeRowTip
     * The tooltip to display when the user hovers over the remove cell.
     * @since 5.0.0
     */
    removeRowTip: 'Remove this item',

    emptyText: 'Nothing selected',

    /**
     * @cfg {String} addToolText
     * The tooltip to display when the user hovers over the "+" tool in the panel header.
     * @since 5.0.0
     */
    addToolText: 'Search for items to add',

    initComponent: function() {
        var me = this,
            emptyText = me.emptyText,
            store = me.getStore(),
            search = me.getSearch(),
            fieldTitle = me.fieldTitle,
            searchStore, model;

        //<debug>
        if (!search) {
            Ext.raise('The search configuration is required for the multi selector');
        }
        //</debug>

        searchStore = search.store;

        if (searchStore.isStore) {
            model = searchStore.getModel();
        }
        else {
            model = searchStore.model;
        }

        if (!store) {
            me.store = {
                model: model
            };
        }

        if (emptyText && !me.viewConfig) {
            me.viewConfig = {
                deferEmptyText: false,
                emptyText: emptyText
            };
        }

        if (!me.columns) {
            me.hideHeaders = !fieldTitle;
            me.columns = [
                { text: fieldTitle, dataIndex: me.fieldName, flex: 1 },
                me.makeRemoveRowColumn()
            ];
        }

        me.callParent();
    },

    addTools: function() {
        var me = this;

        me.addTool({
            type: 'plus',
            tooltip: me.addToolText,
            callback: 'onShowSearch',
            scope: me
        });
        me.searchTool = me.tools[me.tools.length - 1];
    },

    convertSearchRecord: Ext.identityFn,

    convertSelectionRecord: Ext.identityFn,

    makeRemoveRowColumn: function() {
        var me = this;

        return {
            width: 32,
            align: 'center',
            menuDisabled: true,
            tdCls: Ext.baseCSSPrefix + 'multiselector-remove',
            processEvent: me.processRowEvent.bind(me),
            renderer: me.renderRemoveRow,
            updater: Ext.emptyFn,
            scope: me
        };
    },

    processRowEvent: function(type, view, cell, recordIndex, cellIndex, e, record, row) {
        var body = Ext.getBody();

        if (e.type === 'click' ||
            (e.type === 'keydown' && (e.keyCode === e.SPACE || e.keyCode === e.ENTER))) {
            // Deleting the focused row will momentarily focusLeave
            // That would dismiss the popup, so disable that.
            body.suspendFocusEvents();
            this.store.remove(record);
            body.resumeFocusEvents();

            if (this.searchPopup) {
                this.searchPopup.deselectRecords(record);
            }
        }
    },

    renderRemoveRow: function() {
        return '<span data-qtip="' + this.removeRowTip + '" role="button" tabIndex="0">' +
            this.removeRowText + '</span>';
    },

    onFocusLeave: function(e) {
        this.onDismissSearch();
        this.callParent([e]);
    },

    afterComponentLayout: function(width, height, prevWidth, prevHeight) {
        var me = this,
            popup = me.searchPopup;

        me.callParent([width, height, prevWidth, prevHeight]);

        if (popup && popup.isVisible()) {
            popup.showBy(me, me.popupAlign);
        }
    },

    privates: {
        popupAlign: 'tl-tr?',

        onGlobalScroll: function(scroller) {
            // Collapse if the scroll is anywhere but inside this selector or the popup
            if (!this.owns(scroller.getElement())) {
                this.onDismissSearch();
            }
        },

        onDismissSearch: function(e) {
            var searchPopup = this.searchPopup;

            if (searchPopup &&
                (!e || !(searchPopup.owns(e.getTarget()) || this.owns(e.getTarget())))) {
                this.scrollListeners.destroy();
                this.touchListeners.destroy();
                searchPopup.hide();
            }
        },

        onShowSearch: function(panel, tool, event) {
            var me = this,
                searchPopup = me.searchPopup,
                store = me.getStore();

            if (!searchPopup) {
                searchPopup = Ext.merge({
                    owner: me,
                    field: me.fieldName,
                    floating: true,
                    alignOnScroll: false
                }, me.getSearch());
                me.searchPopup = searchPopup = me.add(searchPopup);

                // If we were configured with records prior to the UI requesting the popup,
                // ensure that the records are selected in the popup.
                if (store.getCount()) {
                    searchPopup.selectRecords(store.getRange());
                }
            }

            searchPopup.invocationEvent = event;
            searchPopup.showBy(me, me.popupAlign);

            // It only autofocuses its defaultFocus target if it was hidden.
            // If they're reactivating the show tool, they'll expect to focus the search.
            if (!event || event.pointerType !== 'touch') {
                searchPopup.lookupReference('searchField').focus();
            }

            me.scrollListeners = Ext.on({
                scroll: 'onGlobalScroll',
                scope: me,
                destroyable: true
            });

            // Dismiss on touch outside this component tree.
            // Because touch platforms do not focus document.body on touch
            // so no focusleave would occur to trigger a collapse.
            me.touchListeners = Ext.getDoc().on({
                // Do not translate on non-touch platforms.
                // mousedown will blur the field.
                translate: false,
                touchstart: me.onDismissSearch,
                scope: me,
                delegated: false,
                destroyable: true
            });
        }
    }
});

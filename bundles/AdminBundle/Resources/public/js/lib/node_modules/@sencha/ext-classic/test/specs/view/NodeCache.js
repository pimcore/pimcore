topSuite("Ext.view.NodeCache", ['Ext.grid.Panel'], function() {
    var grid, store, view, rows,
        synchronousLoad = true,
        proxyStoreLoad = Ext.data.ProxyStore.prototype.load,
        loadStore = function() {
            proxyStoreLoad.apply(this, arguments);

            if (synchronousLoad) {
                this.flushLoad.apply(this, arguments);
            }

            return this;
        };

    beforeEach(function() {
        // Override so that we can control asynchronous loading
        Ext.data.ProxyStore.prototype.load = loadStore;

        store = Ext.create('Ext.data.Store', {
            fields: ['name'],
            autoDestroy: true,

            data: {
                'items': [
                    { 'name': 'Lisa' },
                    { 'name': 'Bart' },
                    { 'name': 'Homer' },
                    { 'name': 'Marge' }
                ]
            },

            proxy: {
                type: 'memory',
                reader: {
                    type: 'json',
                    rootProperty: 'items'
                }
            }
        });

        grid = Ext.create('Ext.grid.Panel', {
            store: store,
            height: 100,
            width: 100,
            renderTo: Ext.getBody(),
            columns: [
                {
                    text: 'Name',
                    dataIndex: 'name'
                }
            ]
        });
        view = grid.getView();
        rows = view.all;
    });

    afterEach(function() {
        // Undo the overrides.
        Ext.data.ProxyStore.prototype.load = proxyStoreLoad;

        grid.destroy();
    });

    // EXTJSIV-9765
    it("Store rejectChanges() should not break NodeCache insert()", function() {
        // have to create a scoped function that because Jasmine expect() changes our scope.
        var scopedFn = function() {
            store.rejectChanges();
        };

        var count = store.getCount();

        store.removeAt(count - 1);
        store.removeAt(count - 2);

        expect(scopedFn).not.toThrow();

        expect(store.getAt(3).get('name')).toBe('Marge');
        expect(store.getAt(2).get('name')).toBe('Homer');
    });

    // EXTJS-17399
    it('should not mutate the rendered block on moveBlock(0)', function() {
        var start = rows.startIndex,
            end = rows.endIndex,
            elements = rows.slice();

        // Request to move the block not at all.
        // Should not mutate the rendered block in any way
        rows.moveBlock(0);

        // Everything should be identical.
        expect(rows.startIndex).toBe(start);
        expect(rows.endIndex).toBe(end);
        expect(rows.slice()).toEqual(elements);
    });
});

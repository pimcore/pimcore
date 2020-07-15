topSuite("Ext.data.proxy.Memory", ['Ext.data.ArrayStore'], function() {
    var proxy, operation, records;

    function createSmallProxy(cfg) {
        proxy = new Ext.data.proxy.Memory(Ext.apply({
            data: {
                users: [{
                    id: 1,
                    name: 'Ed Spencer',
                    phoneNumber: '555 1234'
                }, {
                    id: 2,
                    name: 'Abe Elias',
                    phoneNumber: '666 1234'
                }]
            },
            model: 'spec.User',
            reader: {
                type: 'json',
                rootProperty: 'users'
            }
        }, cfg));
    }

    function createLargeProxy(page) {
        var largeDataSet = [],
            i;

        for (i = 1; i <= 100; ++i) {
            largeDataSet.push({
                id: i,
                name: 'Item ' + i
            });
        }

        proxy = new Ext.data.proxy.Memory({
            data: largeDataSet,
            model: 'spec.User',
            enablePaging: page
        });
    }

    function createOperation() {
        operation = new Ext.data.operation.Read({
        });
    }

    beforeEach(function() {
        Ext.define('spec.User', {
            extend: 'Ext.data.Model',
            fields: [
                { name: 'id',    type: 'int' },
                { name: 'name',  type: 'string' },
                { name: 'phone', type: 'string', mapping: 'phoneNumber' }
            ]
        });
    });

    afterEach(function() {
        Ext.data.Model.schema.clear();
        Ext.undefine('spec.User');
    });

    describe("reading data", function() {
        beforeEach(function() {
            createSmallProxy();
            createOperation();

            proxy.read(operation);
            records = operation.getRecords();
        });

        it("should read the records correctly", function() {
            expect(records.length).toEqual(2);

            expect(records[0].get('phone')).toEqual('555 1234');
        });

        it("should keep raw data by default", function() {
            var reader = proxy.getReader();

            expect(reader.rawData).toBeDefined();
        });
    });

    describe("filtering", function() {
        it("should filter data", function() {
            createLargeProxy();
            createOperation();
            operation.setFilters([new Ext.util.Filter({
                filterFn: function(rec) {
                    return rec.getId() % 2 === 0;
                }
            })]);

            proxy.read(operation);
            expect(operation.getRecords().length).toBe(50);
        });

        it("should filter with paging", function() {
            createLargeProxy();
            createOperation();
            operation.setFilters([new Ext.util.Filter({
                filterFn: function(rec) {
                    return rec.getId() < 10;
                }
            })]);
            operation.setStart(0);
            operation.setLimit(20);

            proxy.read(operation);
            expect(operation.getRecords().length).toBe(9);
            expect(operation.getResultSet().getTotal()).toBe(9);
        });

        it('should call onCollectionAdd on receipt of autoLoad data from synchronous proxies', function() {
            var StoreSubclass = Ext.define(null, {
                extend: 'Ext.data.Store',

                onCollectionAddCallCount: 0,

                onCollectionAdd: function() {
                    this.onCollectionAddCallCount++;
                    this.callParent(arguments);
                }
            });

            createSmallProxy();
            var store = new StoreSubclass({
                proxy: proxy,
                autoLoad: true
            });

            // One block of data has arrived in the collection
            expect(store.onCollectionAddCallCount).toBe(1);
        });
    });

    describe("sorting", function() {
        it("should apply sorting", function() {
            createLargeProxy();
            createOperation();
            operation.setSorters([new Ext.util.Sorter({
                root: 'data',
                property: 'id',
                direction: 'DESC'
            })]);

            proxy.read(operation);
            expect(operation.getRecords()[0].getId()).toBe(100);
        });
    });

    describe("paging", function() {
        it("should page the data", function() {
            createLargeProxy(true);
            createOperation();
            operation.setStart(0);
            operation.setLimit(20);
            proxy.read(operation);

            records = operation.getRecords();
            expect(operation.getResultSet().getTotal()).toBe(100);
            expect(records[0].getId()).toBe(1);
            expect(records[records.length - 1].getId()).toBe(20);
        });
    });

    describe("with a store", function() {
        var store;

        function createStore(cfg) {
            store = new Ext.data.Store(Ext.apply({
                model: 'spec.User'
            }, cfg));
        }

        afterEach(function() {
            store.destroy();
            store = null;
        });

        it("should load the store with correctly paged data", function() {
            createLargeProxy(true);
            createStore({
                proxy: proxy,
                pageSize: 10
            });
            store.load();
            expect(store.getCount()).toBe(10);
        });

        it("should load filtered data", function() {
            createLargeProxy(true);
            createStore({
                proxy: proxy,
                pageSize: 10000,
                filters: [{
                    filterFn: function(rec) {
                        return rec.getId() % 4 === 0;
                    }
                }]
            });
            store.load();
            expect(store.getCount()).toBe(25);
        });

        it("should load sorted data", function() {
            createSmallProxy();
            createStore({
                proxy: proxy,
                pageSize: 10000,
                sorters: [{
                    property: 'name',
                    direction: 'ASC'
                }]
            });
            store.load();
            expect(store.first().get('name')).toBe('Abe Elias');
            expect(store.last().get('name')).toBe('Ed Spencer');
        });

        it("removeAll should delete unfiltered records when there is a synchronous store sync() call", function() {
            createLargeProxy(true);
            createStore({
                autoSync: true,
                proxy: proxy,
                pageSize: 10000
            });
            store.load();
            expect(store.getCount()).toBe(100);

            store.addFilter({
                filterFn: function(rec) {
                    return rec.getId() % 4 === 0;
                }
            });
            expect(store.getCount()).toBe(25);

            store.removeAll();
            expect(store.getCount()).toBe(0);

            store.clearFilter();
            expect(store.getCount()).toBe(75);
        });

        it('should be able to read the hardcoded data multiple times', function() {
            createSmallProxy();
            createStore({
                proxy: proxy
            });
            store.load();
            expect(store.getCount()).toBe(2);
            expect(store.first().get('name')).toBe('Ed Spencer');
            expect(store.last().get('name')).toBe('Abe Elias');

            // Should read the same data again
            store.load();
            expect(store.getCount()).toBe(2);
            expect(store.first().get('name')).toBe('Ed Spencer');
            expect(store.last().get('name')).toBe('Abe Elias');
        });

        it('should be not able to read the hardcoded data multiple times if proxy is configured to clearOnRead', function() {
            createSmallProxy({
                clearOnRead: true
            });
            createStore({
                proxy: proxy
            });
            store.load();
            expect(store.getCount()).toBe(2);
            expect(store.first().get('name')).toBe('Ed Spencer');
            expect(store.last().get('name')).toBe('Abe Elias');

            // data should be gone...
            store.load();
            expect(store.getCount()).toBe(0);
        });
    });
});

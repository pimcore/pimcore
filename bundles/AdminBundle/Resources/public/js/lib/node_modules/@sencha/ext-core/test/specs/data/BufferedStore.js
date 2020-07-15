topSuite("Ext.data.BufferedStore", function() {
    var bufferedStore, captured,
        synchronousLoad = true,
        bufferedStoreLoad = Ext.data.BufferedStore.prototype.load,
        loadStore = function() {
            bufferedStoreLoad.apply(this, arguments);

            if (synchronousLoad) {
                this.flushLoad.apply(this, arguments);
            }

            return this;
        };

    function getData(start, limit) {
        var end = start + limit,
            recs = [],
            i;

        for (i = start; i < end; ++i) {
            recs.push({
                id: i + 1,
                threadid: i + 1,
                title: 'Title' + (i + 1)
            });
        }

        return recs;
    }

    function satisfyRequests(total) {
        var requests = Ext.Ajax.mockGetAllRequests(),
            empty = total === 0,
            request, params, data;

        while (requests.length) {
            request = requests[0];

            captured.push(request.options.params);

            params = request.options.params;
            data = getData(empty ? 0 : params.start, empty ? 0 : params.limit);

            Ext.Ajax.mockComplete({
                status: 200,
                responseText: Ext.encode({
                    total: (total || empty) ? total : 5000,
                    data: data
                })
            });

            requests = Ext.Ajax.mockGetAllRequests();
        }
    }

    function createStore(cfg) {
        bufferedStore = new Ext.data.BufferedStore(Ext.apply({
            model: 'spec.ForumThread',
            pageSize: 100,
            proxy: {
                type: 'ajax',
                url: 'fakeUrl',
                reader: {
                    type: 'json',
                    rootProperty: 'data'
                }
            }
        }, cfg));
    }

    beforeEach(function() {
        // Override so that we can control asynchronous loading
        Ext.data.BufferedStore.prototype.load = loadStore;

        Ext.define('spec.ForumThread', {
            extend: 'Ext.data.Model',
            fields: [
                'title', 'forumtitle', 'forumid', 'username', {
                    name: 'replycount',
                    type: 'int'
                }, {
                    name: 'lastpost',
                    mapping: 'lastpost',
                    type: 'date',
                    dateFormat: 'timestamp'
                },
                'lastposter', 'excerpt', 'threadid'
            ],
            idProperty: 'threadid'
        });

        MockAjaxManager.addMethods();
        captured = [];
    });

    afterEach(function() {
        // Undo the overrides.
        Ext.data.BufferedStore.prototype.load = bufferedStoreLoad;

        MockAjaxManager.removeMethods();
        bufferedStore.destroy();
        captured = bufferedStore = null;
        Ext.data.Model.schema.clear();
        Ext.undefine('spec.ForumThread');
    });

    it("should be able to lookup a record by its internalId", function() {
        createStore();
        bufferedStore.loadPage(1);
        satisfyRequests();

        var rec0 = bufferedStore.getAt(0);

        // Lookup by the string version of internalId because that's how we get from DOM to record: https://sencha.jira.com/browse/EXTJS-15388
        expect(bufferedStore.getByInternalId(String(rec0.internalId))).toBe(rec0);
    });

    it("should return undefined when the internalId does not exist", function() {
        createStore();
        bufferedStore.loadPage(1);
        satisfyRequests();

        // Looking up nonexistent internalId should return undefined
        expect(bufferedStore.getByInternalId('DefinitelyDoesntExist')).toBeUndefined();
    });

    it("should be able to start from any page", function() {
        createStore();
        bufferedStore.loadPage(10);
        satisfyRequests();

        expect(bufferedStore.currentPage).toBe(10);
        var page10 = bufferedStore.getRange(900, 999);

        expect(page10.length).toBe(100);

        // Page 10 contains records 900 to 999.
        expect(page10[0].get('title')).toBe('Title901');
        expect(page10[99].get('title')).toBe('Title1000');
    });

    it("should be able to find records in a buffered store", function() {
        createStore();
        bufferedStore.load();

        satisfyRequests();

        expect(bufferedStore.findBy(function(rec) {
            return rec.get('title') === 'Title10';
        })).toBe(9);

        expect(bufferedStore.findExact('title', 'Title10')).toBe(9);

        expect(bufferedStore.find('title', 'title10')).toBe(9);
    });

    it("should load the store when filtered", function() {
        var spy = jasmine.createSpy();

        createStore({
            listeners: {
                load: spy
            }
        });

        // Filter mutation shuold trigger a load
        bufferedStore.filter('title', 'panel');
        satisfyRequests();
        expect(spy).toHaveBeenCalled();
    });

    describe("sorting", function() {
        it("should clear the data when calling sort with parameters when remote sorting", function() {
            createStore();
            bufferedStore.load();

            satisfyRequests();

            bufferedStore.sort();
            expect(bufferedStore.data.getCount()).toBe(0);
            satisfyRequests();
            expect(bufferedStore.data.getCount()).toBe(300);
        });

        it("should call the beforesort event", function() {
            var spy = jasmine.createSpy();

            createStore({
                listeners: {
                    beforesort: spy
                }
            });

            // Sorter mutation should trigger a load
            bufferedStore.sort('title', 'ASC');
            satisfyRequests();
            expect(spy).toHaveBeenCalled();
        });

        it("should load the store when sorted", function() {
            var spy = jasmine.createSpy();

            createStore({
                listeners: {
                    load: spy
                }
            });

            // Sorter mutation should trigger a load
            bufferedStore.sort('title', 'ASC');
            satisfyRequests();
            expect(spy).toHaveBeenCalled();
        });

        it("should update the sorters when sorting by an existing key", function() {
            createStore({
                sorters: [{
                    property: 'title'
                }]
            });

            bufferedStore.sort('title', 'DESC');
            var sorter = bufferedStore.getSorters().getAt(0);

            expect(sorter.getProperty()).toBe('title');
            expect(sorter.getDirection()).toBe('DESC');
        });

        it('should only make one network request', function() {
            var spy = jasmine.createSpy();

            createStore({
                listeners: {
                    load: spy
                }
            });

            bufferedStore.filter('username', 'germanicus');
            satisfyRequests();

            expect(spy.callCount).toBe(1);
        });
    });

    // Test for https://sencha.jira.com/browse/EXTJSIV-10338
    // purgePageCount ensured that the viewSize could never be satisfied
    // by small pages because they would keep being pruned.
    it("should load the requested range when the pageSize is small", function() {
        var spy = jasmine.createSpy();

        createStore({
            pageSize: 5,
            listeners: {
                load: spy
            }
        });

        bufferedStore.load();

        satisfyRequests();
        expect(spy).toHaveBeenCalled();
    });

    describe('load', function() {
        function doTest(records, status, str) {
            var success = status >= 500;

            it("should pass the records loaded, the operation & success=" + success + " to the callback, " + str, function() {
                var spy = jasmine.createSpy(),
                    args;

                createStore();

                bufferedStore.load({
                    // Called after first prefetch and first page has been added.
                    callback: spy
                });

                Ext.Ajax.mockComplete({
                    status: status,
                    responseText: Ext.encode({
                        total: records.length,
                        data: records
                    })
                });

                args = spy.mostRecentCall.args;

                expect(Ext.isArray(args[0])).toBe(true);

                if (args[0].length) {
                    expect(args[0][0].isModel).toBe(true);
                }
                else {
                    expect(Ext.isArray(args)).toBe(true);
                }

                expect(args[1].action).toBe('read');
                expect(args[1].$className).toBe('Ext.data.operation.Read');

                expect(args[2]).toBe(true);

            });
        }

        doTest([{}], 200, 'loaded with records');
        doTest([{}], 500, 'loaded with records');
        doTest([], 200, 'no records');
        doTest([], 500, 'no records');

        describe("should assign dataset index numbers to the records in the Store dependent upon configured pageSize", function() {
            it("should not exceed 100 records", function() {
                createStore();

                var spy = jasmine.createSpy();

                bufferedStore.load({
                    // Called after first prefetch and first page has been added.
                    callback: spy
                });

                satisfyRequests();

                expect(spy).toHaveBeenCalled();
                expect(bufferedStore.indexOf(bufferedStore.getAt(0))).toBe(0);
                expect(bufferedStore.indexOf(bufferedStore.getAt(99))).toBe(99);
                expect(spy.mostRecentCall.args[0].length).toBe(100);
            });

            it("should not exceed 50 records", function() {
                createStore({
                    pageSize: 50
                });

                var spy = jasmine.createSpy();

                bufferedStore.load({
                    // Called after first prefetch and first page has been added.
                    callback: spy
                });

                satisfyRequests(50);
                expect(spy).toHaveBeenCalled();

                expect(bufferedStore.indexOf(bufferedStore.getAt(0))).toBe(0);
                expect(bufferedStore.indexOf(bufferedStore.getAt(49))).toBe(49);
                expect(spy.mostRecentCall.args[0].length).toBe(50);
            });
        });
    });

    describe("reload", function() {

        describe("beforeload event", function() {
            it("should not clear the total count or data if beforeload returns false", function() {
                createStore();
                bufferedStore.load();
                satisfyRequests();

                var spy = jasmine.createSpy().andReturn(false);

                bufferedStore.on('beforeload', spy);
                bufferedStore.reload();
                expect(bufferedStore.getTotalCount()).toBe(5000);
                expect(bufferedStore.getAt(0).id).toBe(1);
                expect(bufferedStore.isLoading()).toBe(false);
            });
        });

        it("should work when holding only 1 record", function() {
            createStore();
            bufferedStore.load();
            satisfyRequests(1);

            expect(function() {
                bufferedStore.reload();
                satisfyRequests(1);
            }).not.toThrow();
        });

        it("should not increase the number of pages when reloading", function() {
            var refreshed = 0,
                count;

            createStore();
            bufferedStore.load();

            satisfyRequests();

            bufferedStore.on('refresh', function() {
                refreshed++;
            });

            bufferedStore.reload();
            satisfyRequests();

            expect(refreshed).toBe(1);
            count = bufferedStore.getData().getCount();

            bufferedStore.reload();
            satisfyRequests();

            expect(bufferedStore.getData().getCount()).toBe(count);
        });

        it("should fire the load & refresh event when the store reloads with no data", function() {
            var loadSpy = jasmine.createSpy(),
                refreshSpy = jasmine.createSpy();

            createStore();
            bufferedStore.load();
            satisfyRequests();

            bufferedStore.on('load', loadSpy);
            bufferedStore.on('refresh', refreshSpy);

            bufferedStore.reload();
            satisfyRequests(0);

            expect(loadSpy.callCount).toBe(1);
            expect(loadSpy.mostRecentCall.args[0]).toBe(bufferedStore);
            expect(loadSpy.mostRecentCall.args[1]).toEqual([]);
            expect(loadSpy.mostRecentCall.args[2]).toBe(true);

            expect(refreshSpy.callCount).toBe(1);
            expect(refreshSpy.mostRecentCall.args[0]).toBe(bufferedStore);
        });

        it("should not request larger than the previous total, preserveScrollOnReload: true", function() {
            var total = 6679,
                viewSize = 50;

            createStore({
                leadingBufferZone: 300,
                pageSize: 100,
                defaultViewSize: viewSize,
                preserveScrollOnReload: true
            });

            bufferedStore.load();
            satisfyRequests(total);

            bufferedStore.getRange(total - 1 - viewSize, total - 1);
            satisfyRequests(total);

            captured.length = 0;

            bufferedStore.reload();
            expect(function() {
                satisfyRequests(total);
            }).not.toThrow();

            // Reloaded the last requested range
            expect(captured[captured.length - 1]).toEqual({
                page: 67,
                start: 6600,
                limit: 100
            });
        });

        it("should not request larger than the previous total, preserveScrollOnReload: false", function() {
            var total = 6679,
                viewSize = 50;

            createStore({
                leadingBufferZone: 300,
                pageSize: 100,
                defaultViewSize: viewSize
            });

            bufferedStore.load();
            satisfyRequests(total);

            bufferedStore.getRange(total - 1 - viewSize, total - 1);
            satisfyRequests(total);

            captured.length = 0;

            bufferedStore.reload();
            expect(function() {
                satisfyRequests(total);
            }).not.toThrow();

            // Reloaded from start
            expect(captured[captured.length - 1]).toEqual({
                page: 3,
                start: 200,
                limit: 100
            });
        });
    });

    describe("pruning", function() {
        it("should prune least recently used pages as new ones are added above the purgePageCount", function() {
            var keys;

            // Keep it simple
            createStore({
                pageSize: 10,
                viewSize: 10,
                leadingBufferZone: 0,
                trailingBufferZone: 0,
                purgePageCount: new Number(0)
            });
            bufferedStore.load();
            satisfyRequests();

            // The PageMap should contain page 1
            keys = [];
            bufferedStore.getData().forEach(function(rec) {
                keys.push(String(rec.internalId));
            });
            expect(keys.length).toBe(10);
            expect(Ext.Object.getKeys(bufferedStore.getData().map)).toEqual(['1']);

            // The indexMap must contain only the keys to the records that are now there.
            expect(Ext.Object.getKeys(bufferedStore.getData().indexMap)).toEqual(keys);

            // This should not evict page one because the cache size is TWICE the required zone
            bufferedStore.loadPage(2);
            satisfyRequests();

            // The PageMap should contain pages 1 and 2
            keys = [];
            bufferedStore.getData().forEach(function(rec) {
                keys.push(String(rec.internalId));
            });
            expect(keys.length).toBe(20);
            expect(Ext.Object.getKeys(bufferedStore.getData().map)).toEqual(['1', '2']);

            // The indexMap must contain only the keys to the records that are now there.
            expect(Ext.Object.getKeys(bufferedStore.getData().indexMap)).toEqual(keys);

            // This should evict page one because there are no buffer zones, and a non-falsy purgePageCount of zero
            bufferedStore.loadPage(3);
            satisfyRequests();

            // The PageMap should contain pages 2 and 3
            keys = [];
            bufferedStore.getData().forEach(function(rec) {
                keys.push(String(rec.internalId));
            });
            expect(keys.length).toBe(20);
            expect(Ext.Object.getKeys(bufferedStore.getData().map)).toEqual(['2', '3']);

            // The indexMap must contain only the keys to the records that are now there.
            expect(Ext.Object.getKeys(bufferedStore.getData().indexMap)).toEqual(keys);
        });
    });

    // only applies to stateful toolkits
    (Ext.state ? describe : xdescribe)('statefulness', function() {
        var state;

        beforeEach(function() {
            // disabling so that flushLoad() isn't immediately called
            synchronousLoad = false;
        });

        afterEach(function() {
            bufferedStore.destroy();
            synchronousLoad = true;
        });

        describe('loading', function() {
            it('should only fire the event once per creation when loading from a stateful component', function() {
                var spy = jasmine.createSpy();

                function createStatefulStore(state) {
                    createStore({
                        autoLoad: true,
                        sorters: [{
                            property: 'title',
                            direction: 'ASC'
                        }],
                        listeners: {
                            beforeload: spy
                        }
                    });

                    if (state) {
                        bufferedStore.applyState(state);
                    }

                    // wait for the request to be built since this is being handled async
                    waits(10);
                    runs(function() {
                        satisfyRequests();
                    });
                }

                // create the new grid (no existing state)
                createStatefulStore();

                waitsFor(function() {
                    return bufferedStore.isLoaded();
                });

                runs(function() {
                    // save the current state and re-apply it to the new store instance
                    // as if it were applied from a stateful component
                    state = bufferedStore.getState();
                    bufferedStore.destroy();
                    createStatefulStore(state);
                });

                waitsFor(function() {
                    return bufferedStore.isLoaded();
                });

                runs(function() {
                    expect(spy.callCount).toBe(2);
                });
            });
        });
    });
});


topSuite("Ext.data.virtual.Store", function() {
    var oldJasmineCaptureStack, oldTimerCaptureStack,
        idBase, pageMap, proxySpy, store, pageSize, total, range, dataMaker;

    var M = Ext.define(null, {
        extend: 'Ext.data.Model',
        fields: ['id', 'group', 'rate']
    });

    beforeAll(function() {
        Ext.data.operation.Operation.prototype.clearPrototypeOnDestroy = false;
        Ext.data.operation.Operation.prototype.clearPropertiesOnDestroy = false;

        // Stack capture is expensive
        oldJasmineCaptureStack = jasmine.CAPTURE_CALL_STACK;
        oldTimerCaptureStack = Ext.Timer.captureStack;
        jasmine.CAPTURE_CALL_STACK = false;
        Ext.Timer.captureStack = false;
    });

    afterAll(function() {
        delete Ext.data.operation.Operation.prototype.clearPrototypeOnDestroy;
        delete Ext.data.operation.Operation.prototype.clearPropertiesOnDestroy;
        jasmine.CAPTURE_CALL_STACK = oldJasmineCaptureStack;
        Ext.Timer.captureStack = oldTimerCaptureStack;
    });

    function getLatestOperation() {
        return proxySpy.mostRecentCall.args[0];
    }

    function makeData(count, base) {
        var data = [],
            i;

        base = base || idBase || 0;

        for (i = 0; i < count; ++i) {
            data.push({
                id: base + i + 1
            });
        }

        return data;
    }

    function makeStore(cfg) {
        store = new Ext.data.virtual.Store(Ext.apply({
            model: M,
            pageSize: pageSize,
            proxy: {
                type: 'ajax',
                url: 'bogus',
                reader: {
                    type: 'json',
                    rootProperty: 'data',
                    summaryRootProperty: 'summary',
                    groupRootProperty: 'groups'
                }
            }
        }, cfg));
        pageMap = store.pageMap;
        proxySpy = spyOn(store.getProxy(), 'read').andCallThrough();
    }

    function makeRange(cfg) {
        range = store.createActiveRange(cfg);
    }

    function completeWithData(data, extraData) {
        Ext.Ajax.mockCompleteWithData(Ext.apply({
            total: total,
            data: data
        }, extraData));
    }

    function completeOperation(op, extraData) {
        completeWithData(makeDataForOperation(op), extraData);
    }

    function completeReload() {
        // Keep this method separate for the sake of clarity
        completeLatest();
    }

    function expectAborted(spyCall) {
        var op = spyCall.args[0];

        expect(op.wasSuccessful()).toBe(false);
        expect(op.getError().statusText).toBe('transaction aborted');
    }

    function makeDataForOperation(op) {
        var start = op.getStart(),
            grouper = op.getGrouper(),
            sorters = op.getSorters(),
            sorter = sorters && sorters[0],
            reverse = (grouper && grouper.getDirection() === 'DESC') || (sorter && sorter.getDirection() === 'DESC'),
            data;

        if (reverse) {
            start = total - (start + pageSize);
        }

        data = dataMaker(op.getLimit(), start);

        if (reverse) {
            data.reverse();
        }

        return data;
    }

    function completeLatest(n, extraData) {
        n = n || 1;

        for (var i = 0; i < n; ++i) {
            completeOperation(proxySpy.mostRecentCall.args[0], extraData);

            if (i !== n - 1) {
                flushNextLoad();
            }
        }
    }

    function flushNextLoad() {
        pageMap.flushNextLoad();
    }

    function flushAllLoads() {
        flushNextLoad();

        while (Ext.Ajax.mockGetAllRequests().length) {
            completeLatest();
        }
    }

    beforeEach(function() {
        MockAjaxManager.addMethods();
        pageSize = 25;
        total = 5000;
        dataMaker = makeData;
    });

    afterEach(function() {
        idBase = dataMaker = pageMap = proxySpy = range = pageSize = total = store = Ext.destroy(store);
        MockAjaxManager.removeMethods();
    });

    describe("misc", function() {
        it("should handle a goto of a smaller range in totalcountchange after a reload", function() {
            pageSize = 300;
            makeStore();
            makeRange({
                prefetch: true
            });
            range.goto(0, 300);
            store.reload();
            store.on('totalcountchange', function() {
                range.goto(0, 42);
                flushAllLoads();
            });
            completeLatest();
            expect(store.getAt(0).id).toBe(1);
            expect(store.getAt(299).id).toBe(300);
        });
    });

    describe("reload", function() {
        function expectLoad(spyCall, page) {
            var op = spyCall.args[0];

            expect(op.getPage()).toBe(page);
        }

        beforeEach(function() {
            makeStore();
            makeRange();
        });

        describe("range goes over total", function() {
            beforeEach(function() {
                range.goto(4800, 4900);
            });

            describe("fully loaded", function() {
                it("should not make requests if beforereload returns false", function() {
                    flushAllLoads();
                    proxySpy.reset();

                    store.on('beforereload', function() {
                        return false;
                    });

                    store.reload();
                    expect(proxySpy).not.toHaveBeenCalled();
                });

                it("should not modify the current range", function() {
                    flushAllLoads();

                    proxySpy.reset();

                    total = 500;
                    store.reload();
                    completeReload();
                    flushAllLoads();

                    // Reload
                    expect(proxySpy.callCount).toBe(1);
                    expectLoad(proxySpy.mostRecentCall, 1);
                });

                it("should fire the reload event immediately", function() {
                    var spy = jasmine.createSpy();

                    store.on('reload', spy);
                    flushAllLoads();

                    total = 500;
                    store.reload();
                    expect(spy).not.toHaveBeenCalled();
                    completeReload();
                    expect(spy.callCount).toBe(1);
                    expect(spy.mostRecentCall.args[0]).toBe(store);
                    expect(spy.mostRecentCall.args[1].$className).toBe('Ext.data.operation.Read');

                    flushAllLoads();
                });
            });

            describe("with loads in flight", function() {
                it("should complete existing requests and not add new ones if beforereload returns false", function() {
                    flushNextLoad();
                    completeLatest();

                    store.on('beforereload', function() {
                        return false;
                    });

                    store.reload();
                    flushAllLoads();
                    expect(proxySpy.callCount).toBe(4);

                    for (var i = 0; i < 4; ++i) {
                        expectLoad(proxySpy.calls[i], i + 193);
                    }
                });

                it("should not modify the current range", function() {
                    flushNextLoad();
                    completeLatest();

                    total = 500;
                    store.reload();
                    completeReload();
                    flushAllLoads();

                    // Initial load, aborted load, reload
                    expect(proxySpy.callCount).toBe(3);
                    expectAborted(proxySpy.calls[1]);
                    expectLoad(proxySpy.mostRecentCall, 1);
                });

                it("should fire the reload event immediately", function() {
                    var spy = jasmine.createSpy();

                    store.on('reload', spy);
                    flushNextLoad();
                    completeLatest();

                    total = 500;
                    store.reload();
                    expect(spy).not.toHaveBeenCalled();
                    completeReload();
                    expect(spy.callCount).toBe(1);
                    expect(spy.mostRecentCall.args[0]).toBe(store);
                    expect(spy.mostRecentCall.args[1].$className).toBe('Ext.data.operation.Read');

                    flushAllLoads();
                });
            });
        });

        describe("range is within total", function() {
            beforeEach(function() {
                range.goto(100, 200);
            });

            describe("fully loaded", function() {
                it("should not make requests if beforereload returns false", function() {
                    flushAllLoads();
                    proxySpy.reset();

                    store.on('beforereload', function() {
                        return false;
                    });

                    store.reload();
                    expect(proxySpy).not.toHaveBeenCalled();
                });

                it("should reload the current range", function() {
                    flushAllLoads();

                    proxySpy.reset();

                    total = 500;
                    store.reload();
                    completeReload();
                    flushAllLoads();

                    // Reload + 4 pages
                    expect(proxySpy.callCount).toBe(5);
                    var calls = proxySpy.calls.slice(1),
                        i;

                    for (i = 0; i < 4; ++i) {
                        expectLoad(calls[i], i + 5);
                    }
                });

                it("should fire the reload event immediately", function() {
                    var spy = jasmine.createSpy();

                    store.on('reload', spy);
                    flushAllLoads();

                    total = 500;
                    store.reload();
                    expect(spy).not.toHaveBeenCalled();
                    completeReload();
                    expect(spy.callCount).toBe(1);
                    expect(spy.mostRecentCall.args[0]).toBe(store);
                    expect(spy.mostRecentCall.args[1].$className).toBe('Ext.data.operation.Read');

                    flushAllLoads();
                });
            });

            describe("with loads in flight", function() {
                it("should complete existing requests and not add new ones if beforereload returns false", function() {
                    flushNextLoad();
                    completeLatest();

                    store.on('beforereload', function() {
                        return false;
                    });

                    store.reload();
                    flushAllLoads();
                    expect(proxySpy.callCount).toBe(4);

                    for (var i = 0; i < 4; ++i) {
                        expectLoad(proxySpy.calls[i], i + 5);
                    }
                });

                it("should not modify the current range", function() {
                    flushNextLoad();
                    completeLatest();

                    total = 500;
                    store.reload();
                    completeReload();
                    flushAllLoads();

                    // Initial load, aborted load, reload + 4 pages
                    expect(proxySpy.callCount).toBe(7);
                    expectAborted(proxySpy.calls[1]);
                    var calls = proxySpy.calls.slice(3),
                        i;

                    for (i = 0; i < 4; ++i) {
                        expectLoad(calls[i], i + 5);
                    }
                });

                it("should fire the reload event immediately", function() {
                    var spy = jasmine.createSpy();

                    store.on('reload', spy);
                    flushNextLoad();
                    completeLatest();

                    total = 500;
                    store.reload();
                    expect(spy).not.toHaveBeenCalled();
                    completeReload();
                    expect(spy.callCount).toBe(1);
                    expect(spy.mostRecentCall.args[0]).toBe(store);
                    expect(spy.mostRecentCall.args[1].$className).toBe('Ext.data.operation.Read');

                    flushAllLoads();
                });
            });
        });

        describe("range for 0 records", function() {
            it("should reload store with 0 record", function() {
                // Loading store with total count of 5000
                store.reload();
                completeLatest();
                flushNextLoad();
                expect(store.getCount()).toBe(5000);

                // reload store with total count of 0
                total = 0;
                store.reload();
                completeLatest();

                // https://sencha.jira.com/browse/EXTJS-27063
                // This proves that no error is thrown for 0 record
                expect(range.goto(0, 1)).toBeUndefined();
                expect(store.getCount()).toBe(0);
            });
        });
    });

    describe("totalcountchange", function() {
        var spy;

        beforeEach(function() {
            spy = jasmine.createSpy();
            makeStore();
            store.on('totalcountchange', spy);
            makeRange();
            range.goto(100, 200);
        });

        afterEach(function() {
            spy = null;
        });

        it("should fire on the first load", function() {
            flushNextLoad();
            completeLatest();
            expect(spy.callCount).toBe(1);

            expect(spy.callCount).toBe(1);
            var args = spy.mostRecentCall.args;

            expect(args[0]).toBe(store);
            expect(args[1]).toBe(total);
            expect(args[2]).toBeNull();

            flushAllLoads();
        });

        it("should not fire if the total doesn't change", function() {
            flushAllLoads();
            spy.reset();

            range.goto(400, 500);
            flushAllLoads();
            expect(spy).not.toHaveBeenCalled();

            range.goto(4000, 4500);
            flushAllLoads();
            expect(spy).not.toHaveBeenCalled();

            range.goto(700, 800);
            flushAllLoads();
            expect(spy).not.toHaveBeenCalled();
        });

        it("should fire when the total changes", function() {
            flushAllLoads();
            spy.reset();

            var oldTotal = total;

            total = 2500;
            range.goto(200, 350);
            flushNextLoad();
            completeLatest();
            expect(spy.callCount).toBe(1);
            var args = spy.mostRecentCall.args;

            expect(args[0]).toBe(store);
            expect(args[1]).toBe(total);
            expect(args[2]).toBe(oldTotal);

            flushAllLoads();
        });
    });

    describe("filtering", function() {
        describe("configuring", function() {
            it("should not fire an event if configured with a filter", function() {
                var spy = jasmine.createSpy();

                makeStore({
                    filters: [{
                        property: 'id',
                        value: 1
                    }],
                    listeners: {
                        filterchange: spy
                    }
                });
                expect(spy).not.toHaveBeenCalled();
            });

            it("should not trigger any loads if configured with a filter", function() {
                makeStore({
                    filters: [{
                        property: 'id',
                        value: 1
                    }]
                });
                expect(proxySpy).not.toHaveBeenCalled();
            });
        });

        describe("remote calls", function() {
            it("should not send filter information if not filtered", function() {
                makeStore();
                makeRange();
                range.goto(0, 50);
                flushAllLoads();

                expect(getLatestOperation().getFilters()).toEqual([]);
            });

            it("should send the filter information", function() {
                makeStore({
                    filters: [{
                        property: 'id',
                        value: 1
                    }]
                });
                makeRange();
                range.goto(0, 50);
                flushAllLoads();

                var s = getLatestOperation().getFilters();

                expect(s.length).toBe(1);
                expect(s[0].serialize()).toEqual({
                    property: 'id',
                    value: 1
                });
            });
        });

        describe("modifying filters", function() {
            function expectLoad(spyCall, page, filter) {
                var op = spyCall.args[0],
                    f = op.getFilters();

                expect(op.getPage()).toBe(page);

                if (!filter) {
                    expect(f.length).toBe(0);
                }
                else {
                    expect(f[0].serialize()).toEqual(filter);
                }
            }

            describe("from no filters -> filters", function() {
                beforeEach(function() {
                    makeStore();
                    makeRange();
                });

                function addFilter() {
                    store.getFilters().add({
                        property: 'id',
                        value: 1
                    });
                    total = 500;
                }

                describe("range falls outside new total", function() {
                    beforeEach(function() {
                        range.goto(4800, 4900);
                    });

                    describe("fully loaded", function() {
                        it("should not modify the current range", function() {
                            flushAllLoads();

                            proxySpy.reset();

                            addFilter();
                            completeReload();
                            flushAllLoads();

                            // Reload
                            expect(proxySpy.callCount).toBe(1);
                            expectLoad(proxySpy.mostRecentCall, 1, {
                                property: 'id',
                                value: 1
                            });
                        });

                        it("should fire the filterchange event immediately", function() {
                            var spy = jasmine.createSpy();

                            store.on('filterchange', spy);
                            flushAllLoads();

                            expect(spy).not.toHaveBeenCalled();
                            addFilter();
                            expect(spy.callCount).toBe(1);
                            expect(spy.mostRecentCall.args[0]).toBe(store);
                            expect(spy.mostRecentCall.args[1][0].serialize()).toEqual({
                                property: 'id',
                                value: 1
                            });

                            completeReload();
                            flushAllLoads();
                        });

                        it("should fire the reload event immediately", function() {
                            var spy = jasmine.createSpy();

                            store.on('reload', spy);
                            flushAllLoads();

                            addFilter();
                            expect(spy).not.toHaveBeenCalled();
                            completeReload();
                            expect(spy.callCount).toBe(1);
                            expect(spy.mostRecentCall.args[0]).toBe(store);
                            expect(spy.mostRecentCall.args[1].$className).toBe('Ext.data.operation.Read');

                            flushAllLoads();
                        });
                    });

                    describe("with loads in flight", function() {
                        it("should not modify the current range", function() {
                            flushNextLoad();
                            completeLatest();

                            addFilter();
                            completeReload();
                            flushAllLoads();

                            // Initial load, aborted load, reload
                            expect(proxySpy.callCount).toBe(3);
                            expectAborted(proxySpy.calls[1]);
                            expectLoad(proxySpy.mostRecentCall, 1, {
                                property: 'id',
                                value: 1
                            });
                        });

                        it("should fire the filterchange event immediately", function() {
                            var spy = jasmine.createSpy();

                            store.on('filterchange', spy);
                            flushNextLoad();
                            completeLatest();

                            expect(spy).not.toHaveBeenCalled();
                            addFilter();
                            expect(spy.callCount).toBe(1);
                            expect(spy.mostRecentCall.args[0]).toBe(store);
                            expect(spy.mostRecentCall.args[1][0].serialize()).toEqual({
                                property: 'id',
                                value: 1
                            });

                            completeReload();
                            flushAllLoads();
                        });

                        it("should fire the reload event immediately", function() {
                            var spy = jasmine.createSpy();

                            store.on('reload', spy);
                            flushNextLoad();
                            completeLatest();

                            addFilter();
                            expect(spy).not.toHaveBeenCalled();
                            completeReload();
                            expect(spy.callCount).toBe(1);
                            expect(spy.mostRecentCall.args[0]).toBe(store);
                            expect(spy.mostRecentCall.args[1].$className).toBe('Ext.data.operation.Read');

                            flushAllLoads();
                        });
                    });
                });

                describe("range falls inside new total", function() {
                    beforeEach(function() {
                        range.goto(100, 200);
                    });

                    describe("fully loaded", function() {
                        it("should reload the current range", function() {
                            flushAllLoads();

                            proxySpy.reset();

                            addFilter();
                            completeReload();
                            flushAllLoads();

                            // Reload + active pages
                            expect(proxySpy.callCount).toBe(5);
                            var calls = proxySpy.calls.slice(1),
                                i;

                            for (i = 0; i < 4; ++i) {
                                expectLoad(calls[i], i + 5, {
                                    property: 'id',
                                    value: 1
                                });
                            }
                        });

                        it("should fire the filterchange event immediately", function() {
                            var spy = jasmine.createSpy();

                            store.on('filterchange', spy);
                            flushAllLoads();

                            expect(spy).not.toHaveBeenCalled();
                            addFilter();
                            expect(spy.callCount).toBe(1);
                            expect(spy.mostRecentCall.args[0]).toBe(store);
                            expect(spy.mostRecentCall.args[1][0].serialize()).toEqual({
                                property: 'id',
                                value: 1
                            });

                            completeReload();
                            flushAllLoads();
                        });

                        it("should fire the reload event immediately", function() {
                            var spy = jasmine.createSpy();

                            store.on('reload', spy);
                            flushAllLoads();

                            addFilter();
                            expect(spy).not.toHaveBeenCalled();
                            completeReload();
                            expect(spy.callCount).toBe(1);
                            expect(spy.mostRecentCall.args[0]).toBe(store);
                            expect(spy.mostRecentCall.args[1].$className).toBe('Ext.data.operation.Read');

                            flushAllLoads();
                        });
                    });

                    describe("with loads in flight", function() {
                        it("should reload the current range", function() {
                            flushNextLoad();
                            completeLatest();

                            addFilter();
                            completeReload();
                            flushAllLoads();

                            // Initial load, aborted load, reload, active pages
                            expect(proxySpy.callCount).toBe(7);
                            expectAborted(proxySpy.calls[1]);
                            var calls = proxySpy.calls.slice(3),
                                i;

                            for (i = 0; i < 4; ++i) {
                                expectLoad(calls[i], i + 5, {
                                    property: 'id',
                                    value: 1
                                });
                            }
                        });

                        it("should fire the filterchange event immediately", function() {
                            var spy = jasmine.createSpy();

                            store.on('filterchange', spy);
                            flushNextLoad();
                            completeLatest();

                            expect(spy).not.toHaveBeenCalled();
                            addFilter();
                            expect(spy.callCount).toBe(1);
                            expect(spy.mostRecentCall.args[0]).toBe(store);
                            expect(spy.mostRecentCall.args[1][0].serialize()).toEqual({
                                property: 'id',
                                value: 1
                            });

                            completeReload();
                            flushAllLoads();
                        });

                        it("should fire the reload event immediately", function() {
                            var spy = jasmine.createSpy();

                            store.on('reload', spy);
                            flushNextLoad();
                            completeLatest();

                            addFilter();
                            expect(spy).not.toHaveBeenCalled();
                            completeReload();
                            expect(spy.callCount).toBe(1);
                            expect(spy.mostRecentCall.args[0]).toBe(store);
                            expect(spy.mostRecentCall.args[1].$className).toBe('Ext.data.operation.Read');

                            flushAllLoads();
                        });
                    });
                });
            });

            describe("filters -> no filters", function() {
                var initial;

                beforeEach(function() {
                    initial = total;
                    total = 500;

                    makeStore({
                        filters: [{
                            property: 'id',
                            value: 1
                        }]
                    });
                });

                function clearFilters() {
                    store.getFilters().removeAll();
                    total = initial;
                }

                describe("fully loaded", function() {
                    it("should reload the current active range", function() {
                        makeRange();
                        range.goto(100, 200);
                        flushAllLoads();

                        proxySpy.reset();

                        clearFilters();
                        completeReload();
                        flushAllLoads();

                        // Reload, + 4 pages
                        expect(proxySpy.callCount).toBe(5);
                        var calls = proxySpy.calls.slice(1),
                            i;

                        for (i = 0; i < 4; ++i) {
                            expectLoad(calls[i], i + 5, null);
                        }
                    });

                    it("should fire the filterchange event immediately", function() {
                        var spy = jasmine.createSpy();

                        store.on('filterchange', spy);
                        makeRange();
                        range.goto(100, 200);
                        flushAllLoads();

                        expect(spy).not.toHaveBeenCalled();
                        clearFilters();
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1]).toEqual([]);

                        completeReload();
                        flushAllLoads();
                    });

                    it("should fire the reload event immediately", function() {
                        var spy = jasmine.createSpy();

                        store.on('reload', spy);
                        makeRange();
                        range.goto(100, 200);
                        flushAllLoads();

                        clearFilters();
                        expect(spy).not.toHaveBeenCalled();
                        completeLatest();
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1].$className).toBe('Ext.data.operation.Read');

                        flushAllLoads();
                    });
                });

                describe("with loads in flight", function() {
                    it("should cancel any pending operations and reload the range", function() {
                        makeRange();
                        range.goto(100, 200);
                        flushNextLoad();
                        completeLatest();

                        clearFilters();

                        completeReload();
                        flushAllLoads();
                        // Initial call, + 1 aborted call, + reload, + 4 new requests
                        expect(proxySpy.callCount).toBe(7);

                        expectAborted(proxySpy.calls[1]);

                        var calls = proxySpy.calls.slice(3),
                            i;

                        for (i = 0; i < 4; ++i) {
                            expectLoad(calls[i], i + 5, null);
                        }
                    });

                    it("should fire the filterchange event immediately", function() {
                        var spy = jasmine.createSpy();

                        store.on('filterchange', spy);
                        makeRange();
                        range.goto(0, 100);
                        flushNextLoad();
                        completeLatest();

                        expect(spy).not.toHaveBeenCalled();
                        clearFilters();
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1]).toEqual([]);

                        completeReload();
                        flushAllLoads();
                    });

                    it("should fire the reload event", function() {
                        var spy = jasmine.createSpy();

                        store.on('reload', spy);
                        makeRange();
                        range.goto(0, 100);
                        flushNextLoad();
                        completeLatest();

                        clearFilters();
                        expect(spy).not.toHaveBeenCalled();
                        completeLatest();
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1].$className).toBe('Ext.data.operation.Read');

                        flushAllLoads();
                    });
                });
            });
        });
    });

    describe("sorting", function() {
        describe("configuring", function() {
            it("should not fire an event if configured with a sort", function() {
                var spy = jasmine.createSpy();

                makeStore({
                    sorters: [{
                        property: 'id'
                    }],
                    listeners: {
                        sort: spy
                    }
                });
                expect(spy).not.toHaveBeenCalled();
            });

            it("should not trigger any loads if configured with a sort", function() {
                makeStore({
                    sorters: [{
                        property: 'id'
                    }]
                });
                expect(proxySpy).not.toHaveBeenCalled();
            });
        });

        describe("remote calls", function() {
            it("should not send sort information if not sorted", function() {
                makeStore();
                makeRange();
                range.goto(0, 50);
                flushAllLoads();

                expect(getLatestOperation().getSorters()).toEqual([]);
            });

            it("should send the sorter information", function() {
                makeStore({
                    sorters: 'id'
                });
                makeRange();
                range.goto(0, 50);
                flushAllLoads();

                var s = getLatestOperation().getSorters();

                expect(s.length).toBe(1);
                expect(s[0].serialize()).toEqual({
                    property: 'id',
                    direction: 'ASC'
                });
            });

            it("should pass the correct direction", function() {
                makeStore({
                    sorters: {
                        property: 'id',
                        direction: 'DESC'
                    }
                });
                makeRange();
                range.goto(0, 50);
                flushAllLoads();

                var s = getLatestOperation().getSorters();

                expect(s.length).toBe(1);
                expect(s[0].serialize()).toEqual({
                    property: 'id',
                    direction: 'DESC'
                });
            });
        });

        describe("modifying sorters", function() {
            function expectLoad(spyCall, page, sorter) {
                if (sorter) {
                    sorter.direction = sorter.direction || 'ASC';
                }

                var op = spyCall.args[0],
                    s = op.getSorters();

                expect(op.getPage()).toBe(page);

                if (!sorter) {
                    expect(s.length).toBe(0);
                }
                else {
                    expect(s[0].serialize()).toEqual(sorter);
                }
            }

            describe("from no sorting -> sorting", function() {
                beforeEach(function() {
                    makeStore();
                });

                describe("fully loaded", function() {
                    it("should reload the current active range", function() {
                        makeRange();
                        range.goto(100, 200);
                        flushAllLoads();

                        proxySpy.reset();

                        store.getSorters().add({
                            property: 'id'
                        });
                        completeReload();
                        flushAllLoads();

                        // reload + 4 pages
                        expect(proxySpy.callCount).toBe(5);

                        var calls = proxySpy.calls.slice(1),
                            i;

                        for (i = 0; i < 4; ++i) {
                            expectLoad(calls[i], i + 5, {
                                property: 'id',
                                direction: 'ASC'
                            });
                        }
                    });

                    it("should fire the sort event immediately", function() {
                        var spy = jasmine.createSpy();

                        store.on('sort', spy);

                        makeRange();
                        range.goto(100, 200);
                        flushAllLoads();

                        expect(spy).not.toHaveBeenCalled();
                        store.getSorters().add({
                            property: 'id'
                        });
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1][0].serialize()).toEqual({
                            property: 'id',
                            direction: 'ASC'
                        });

                        completeReload();
                        flushAllLoads();
                    });

                    it("should fire the reload event", function() {
                        var spy = jasmine.createSpy();

                        store.on('reload', spy);

                        makeRange();
                        range.goto(100, 200);
                        flushAllLoads();

                        store.getSorters().add({
                            property: 'id'
                        });
                        expect(spy).not.toHaveBeenCalled();
                        completeReload();
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1].$className).toBe('Ext.data.operation.Read');

                        flushAllLoads();
                    });
                });

                describe("with loads in flight", function() {
                    it("should cancel any pending operations and reload the range", function() {
                        makeRange();
                        range.goto(100, 200);
                        flushNextLoad();
                        completeLatest();

                        store.getSorters().add({
                            property: 'id'
                        });

                        completeReload();

                        flushAllLoads();
                        // Initial call, + 1 aborted call, + reload call + 4 new requests
                        expect(proxySpy.callCount).toBe(7);

                        expectAborted(proxySpy.calls[1]);

                        var calls = proxySpy.calls.slice(3),
                            i;

                        for (i = 0; i < 4; ++i) {
                            expectLoad(calls[i], i + 5, {
                                property: 'id',
                                direction: 'ASC'
                            });
                        }
                    });

                    it("should fire the sort event immediately", function() {
                        var spy = jasmine.createSpy();

                        store.on('sort', spy);
                        makeRange();
                        range.goto(0, 100);
                        flushNextLoad();
                        completeLatest();

                        expect(spy).not.toHaveBeenCalled();
                        store.getSorters().add({
                            property: 'id'
                        });
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1][0].serialize()).toEqual({
                            property: 'id',
                            direction: 'ASC'
                        });

                        completeReload();
                        flushAllLoads();
                    });

                    it("should fire the reload event", function() {
                        var spy = jasmine.createSpy();

                        store.on('reload', spy);
                        makeRange();
                        range.goto(0, 100);
                        flushNextLoad();
                        completeLatest();

                        store.getSorters().add({
                            property: 'id'
                        });
                        expect(spy).not.toHaveBeenCalled();
                        completeReload();
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1].$className).toBe('Ext.data.operation.Read');

                        flushAllLoads();
                    });
                });
            });

            describe("sorting -> no sorting", function() {
                beforeEach(function() {
                    makeStore({
                        sorters: 'id'
                    });
                });

                describe("fully loaded", function() {
                    it("should reload the current active range", function() {
                        makeRange();
                        range.goto(100, 200);
                        flushAllLoads();

                        proxySpy.reset();

                        store.getSorters().removeAll();
                        completeReload();
                        flushAllLoads();

                        // Reload, + 4 pages
                        expect(proxySpy.callCount).toBe(5);
                        var calls = proxySpy.calls.slice(1),
                            i;

                        for (i = 0; i < 4; ++i) {
                            expectLoad(calls[i], i + 5, null);
                        }
                    });

                    it("should fire the sort event immediately", function() {
                        var spy = jasmine.createSpy();

                        store.on('sort', spy);
                        makeRange();
                        range.goto(100, 200);
                        flushAllLoads();

                        expect(spy).not.toHaveBeenCalled();
                        store.getSorters().removeAll();
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1]).toEqual([]);

                        completeReload();
                        flushAllLoads();
                    });

                    it("should fire the reload event immediately", function() {
                        var spy = jasmine.createSpy();

                        store.on('reload', spy);
                        makeRange();
                        range.goto(100, 200);
                        flushAllLoads();

                        store.getSorters().removeAll();
                        expect(spy).not.toHaveBeenCalled();
                        completeReload();
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1].$className).toBe('Ext.data.operation.Read');

                        flushAllLoads();
                    });
                });

                describe("with loads in flight", function() {
                    it("should cancel any pending operations and reload the range", function() {
                        makeRange();
                        range.goto(100, 200);
                        flushNextLoad();
                        completeLatest();

                        store.getSorters().removeAll();

                        completeReload();
                        flushAllLoads();
                        // Initial call, + 1 aborted call, + reload, + 4 new requests
                        expect(proxySpy.callCount).toBe(7);

                        expectAborted(proxySpy.calls[1]);

                        var calls = proxySpy.calls.slice(3),
                            i;

                        for (i = 0; i < 4; ++i) {
                            expectLoad(calls[i], i + 5, null);
                        }
                    });

                    it("should fire the sort event immediately", function() {
                        var spy = jasmine.createSpy();

                        store.on('sort', spy);
                        makeRange();
                        range.goto(0, 100);
                        flushNextLoad();
                        completeLatest();

                        expect(spy).not.toHaveBeenCalled();
                        store.getSorters().removeAll();
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1]).toEqual([]);

                        completeReload();
                        flushAllLoads();
                    });

                    it("should fire the reload event", function() {
                        var spy = jasmine.createSpy();

                        store.on('reload', spy);
                        makeRange();
                        range.goto(0, 100);
                        flushNextLoad();
                        completeLatest();

                        store.getSorters().removeAll();
                        expect(spy).not.toHaveBeenCalled();
                        completeReload();
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1].$className).toBe('Ext.data.operation.Read');

                        flushAllLoads();
                    });
                });
            });

            describe("changing sorting", function() {
                beforeEach(function() {
                    makeStore({
                        sorters: 'id'
                    });
                });

                describe("fully loaded", function() {
                    it("should reload the current active range", function() {
                        makeRange();
                        range.goto(100, 200);
                        flushAllLoads();

                        proxySpy.reset();

                        store.sort('id', 'DESC');

                        completeReload();
                        flushAllLoads();

                        // 1 reload of first page, + 4 pages
                        expect(proxySpy.callCount).toBe(5);

                        var calls = proxySpy.calls.slice(1),
                            i;

                        for (i = 0; i < 4; ++i) {
                            expectLoad(calls[i], i + 5, {
                                property: 'id',
                                direction: 'DESC'
                            });
                        }
                    });

                    it("should fire the sort event immediately", function() {
                        var spy = jasmine.createSpy();

                        store.on('sort', spy);
                        makeRange();
                        range.goto(100, 200);

                        flushAllLoads();

                        expect(spy).not.toHaveBeenCalled();
                        store.sort('id', 'DESC');

                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1][0].serialize()).toEqual({
                            property: 'id',
                            direction: 'DESC'
                        });

                        completeReload();
                        flushAllLoads();
                    });

                    it("should fire the reload event", function() {
                        var spy = jasmine.createSpy();

                        store.on('reload', spy);
                        makeRange();
                        range.goto(100, 200);
                        flushAllLoads();

                        store.sort('id', 'DESC');
                        expect(spy).not.toHaveBeenCalled();
                        completeReload();
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1].$className).toBe('Ext.data.operation.Read');

                        flushAllLoads();
                    });
                });

                describe("with loads in flight", function() {
                    it("should cancel any pending operations and reload the range", function() {
                        makeRange();
                        range.goto(100, 200);
                        flushNextLoad();
                        completeLatest();

                        store.sort('id', 'DESC');

                        completeReload();
                        flushAllLoads();
                        // Initial call, + 1 aborted call, +first page, + 4 new requests
                        expect(proxySpy.callCount).toBe(7);

                        expectAborted(proxySpy.calls[1]);

                        var calls = proxySpy.calls.slice(3),
                            i;

                        for (i = 0; i < 4; ++i) {
                            expectLoad(calls[i], i + 5, {
                                property: 'id',
                                direction: 'DESC'
                            });
                        }
                    });

                    it("should fire the sort event immediately", function() {
                        var spy = jasmine.createSpy();

                        store.on('sort', spy);
                        makeRange();
                        range.goto(0, 100);
                        flushNextLoad();
                        completeLatest();

                        expect(spy).not.toHaveBeenCalled();
                        store.sort('id', 'DESC');
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1][0].serialize()).toEqual({
                            property: 'id',
                            direction: 'DESC'
                        });

                        completeReload();
                        flushAllLoads();
                    });

                    it("should fire the reload event", function() {
                        var spy = jasmine.createSpy();

                        store.on('reload', spy);
                        makeRange();
                        range.goto(0, 100);
                        flushNextLoad();
                        completeLatest();

                        store.sort('id', 'DESC');
                        expect(spy).not.toHaveBeenCalled();
                        completeReload();
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1].$className).toBe('Ext.data.operation.Read');

                        flushAllLoads();
                    });
                });
            });
        });
    });

    describe("grouping", function() {
        var groupOffsets, groupMap;

        function mapGroups(fn) {
            var groups = store.getGroups(),
                ret = [];

            groups.each(function(g) {
                ret.push(fn(g));
            });

            return ret;
        }

        function expectGroups(names) {
            expect(mapGroups(function(g) {
                return g.getGroupKey();
            })).toEqual(names);
        }

        function expectFirsts(ids) {
            expect(mapGroups(function(g) {
                return g.first().id;
            })).toEqual(ids);
        }

        function buildGroupMap() {
            if (groupMap) {
                return;
            }

            groupMap = {};

            var groupIdx = 0,
                id, i;

            for (i = 0; i < total; ++i) {
                id = i + 1;

                if (id >= groupOffsets[groupIdx]) {
                    ++groupIdx;
                }

                groupMap[id] = 'g' + Ext.String.leftPad(groupIdx + 1, 2, '0');
            }
        }

        function assignGroups(records) {
            buildGroupMap();

            Ext.Array.forEach(records, function(data) {
                data.group = groupMap[data.id];
            });
        }

        function makeGroupData(limit, start) {
            var ret = makeData(limit, start);

            assignGroups(ret);

            return ret;
        }

        beforeEach(function() {
            groupOffsets = [4, 39, 102, 117, 280, 289, 400, 405, 410, 415, 484, 900, 999];
            dataMaker = makeGroupData;
        });

        afterEach(function() {
            groupMap = groupOffsets = null;
        });

        describe("configuring", function() {
            it("should not fire an event if configured with a grouper", function() {
                var spy = jasmine.createSpy();

                makeStore({
                    groupField: 'group',
                    listeners: {
                        groupchange: spy
                    }
                });
                expect(spy).not.toHaveBeenCalled();
            });
        });

        describe("remote calls", function() {
            it("should not send group information if not grouped", function() {
                makeStore();
                makeRange();
                range.goto(0, 50);
                flushAllLoads();

                expect(getLatestOperation().getGrouper()).toBeNull();
            });

            it("should send the grouper information", function() {
                makeStore({
                    groupField: 'group'
                });
                makeRange();
                range.goto(0, 50);
                flushAllLoads();

                var g = getLatestOperation().getGrouper();

                expect(g.serialize()).toEqual({
                    property: 'group',
                    direction: 'ASC'
                });
            });

            it("should pass the correct direction", function() {
                makeStore({
                    grouper: {
                        property: 'group',
                        direction: 'DESC'
                    }
                });
                makeRange();
                range.goto(0, 50);
                flushAllLoads();

                var g = getLatestOperation().getGrouper();

                expect(g.serialize()).toEqual({
                    property: 'group',
                    direction: 'DESC'
                });
            });
        });

        describe("modifying groupers", function() {
            function expectLoad(spyCall, page, grouper) {
                if (grouper) {
                    grouper.direction = grouper.direction || 'ASC';
                }

                var op = spyCall.args[0];

                expect(op.getPage()).toBe(page);

                if (grouper) {
                    expect(op.getGrouper().serialize()).toEqual(grouper);
                }
                else {
                    expect(op.getGrouper()).toBeNull();
                }
            }

            describe("from no grouping -> grouping", function() {
                beforeEach(function() {
                    makeStore();
                });

                describe("fully loaded", function() {
                    it("should reload the current active range", function() {
                        makeRange();
                        range.goto(100, 200);
                        flushAllLoads();

                        proxySpy.reset();

                        store.setGrouper({
                            property: 'group'
                        });

                        completeReload();
                        flushAllLoads();

                        // 1 reload, + 4 pages
                        expect(proxySpy.callCount).toBe(5);
                        var calls = proxySpy.calls.slice(1),
                            i;

                        for (i = 0; i < 4; ++i) {
                            expectLoad(calls[i], i + 5, {
                                property: 'group',
                                direction: 'ASC'
                            });
                        }
                    });

                    it("should fire the groupchange event immediately", function() {
                        var spy = jasmine.createSpy();

                        store.on('groupchange', spy);

                        makeRange();
                        range.goto(100, 200);
                        flushAllLoads();

                        expect(spy).not.toHaveBeenCalled();
                        store.setGrouper({
                            property: 'group'
                        });
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1].serialize()).toEqual({
                            property: 'group',
                            direction: 'ASC'
                        });

                        completeReload();
                        flushAllLoads();
                    });

                    it("should fire the reload event", function() {
                        var spy = jasmine.createSpy();

                        store.on('reload', spy);

                        makeRange();
                        range.goto(100, 200);
                        flushAllLoads();

                        store.setGrouper({
                            property: 'group'
                        });
                        expect(spy).not.toHaveBeenCalled();
                        completeReload();

                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1].$className).toBe('Ext.data.operation.Read');

                        flushAllLoads();
                    });
                });

                describe("with loads in flight", function() {
                    it("should cancel any pending operations and reload the range", function() {
                        makeRange();
                        range.goto(100, 200);
                        flushNextLoad();
                        completeLatest();

                        store.setGrouper({
                            property: 'group'
                        });

                        completeReload();
                        flushAllLoads();
                        // Initial call, + 1 aborted call, +first page, + 4 new requests
                        expect(proxySpy.callCount).toBe(7);

                        expectAborted(proxySpy.calls[1]);

                        var calls = proxySpy.calls.slice(3),
                            i;

                        for (i = 0; i < 4; ++i) {
                            expectLoad(calls[i], i + 5, {
                                property: 'group',
                                direction: 'ASC'
                            });
                        }
                    });

                    it("should fire the groupchange event immediately", function() {
                        var spy = jasmine.createSpy();

                        store.on('groupchange', spy);
                        makeRange();
                        range.goto(0, 100);
                        flushNextLoad();
                        completeLatest();

                        expect(spy).not.toHaveBeenCalled();
                        store.setGrouper({
                            property: 'group'
                        });
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1].serialize()).toEqual({
                            property: 'group',
                            direction: 'ASC'
                        });

                        completeReload();
                        flushAllLoads();
                    });

                    it("should fire the reload event", function() {
                        var spy = jasmine.createSpy();

                        store.on('reload', spy);
                        makeRange();
                        range.goto(0, 100);
                        flushNextLoad();
                        completeLatest();

                        store.setGrouper({
                            property: 'group'
                        });
                        expect(spy).not.toHaveBeenCalled();
                        completeReload();
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1].$className).toBe('Ext.data.operation.Read');

                        flushAllLoads();
                    });
                });
            });

            describe("grouping -> no grouping", function() {
                beforeEach(function() {
                    makeStore({
                        groupField: ''
                    });
                });

                describe("fully loaded", function() {
                    it("should reload the current active range", function() {
                        makeRange();
                        range.goto(100, 200);
                        flushAllLoads();

                        proxySpy.reset();

                        store.setGrouper(null);
                        completeReload();
                        flushAllLoads();

                        // first page + 4 pages
                        expect(proxySpy.callCount).toBe(5);
                        var calls = proxySpy.calls.slice(1),
                            i;

                        for (i = 0; i < 4; ++i) {
                            expectLoad(calls[i], i + 5, null);
                        }
                    });

                    it("should fire the groupchange event immediately", function() {
                        var spy = jasmine.createSpy();

                        store.on('groupchange', spy);
                        makeRange();
                        range.goto(100, 200);
                        flushAllLoads();

                        expect(spy).not.toHaveBeenCalled();
                        store.setGrouper(null);
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1]).toBeNull();

                        completeReload();
                        flushAllLoads();
                    });

                    it("should fire the reload event", function() {
                        var spy = jasmine.createSpy();

                        store.on('reload', spy);
                        makeRange();
                        range.goto(100, 200);
                        flushAllLoads();

                        store.setGrouper(null);
                        expect(spy).not.toHaveBeenCalled();
                        completeReload();
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1].$className).toBe('Ext.data.operation.Read');

                        flushAllLoads();
                    });
                });

                describe("with loads in flight", function() {
                    it("should cancel any pending operations and reload the range", function() {
                        makeRange();
                        range.goto(100, 200);
                        flushNextLoad();
                        completeLatest();

                        store.setGrouper(null);

                        completeReload();
                        flushAllLoads();
                        // Initial call, + 1 aborted call, + first page, + 4 new requests
                        expect(proxySpy.callCount).toBe(7);

                        expectAborted(proxySpy.calls[1]);

                        var calls = proxySpy.calls.slice(3),
                            i;

                        for (i = 0; i < 4; ++i) {
                            expectLoad(calls[i], i + 5, null);
                        }
                    });

                    it("should fire the groupchange event immediately", function() {
                        var spy = jasmine.createSpy();

                        store.on('groupchange', spy);
                        makeRange();
                        range.goto(0, 100);
                        flushNextLoad();
                        completeLatest();

                        expect(spy).not.toHaveBeenCalled();
                        store.setGrouper(null);
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1]).toBeNull();

                        completeReload();
                        flushAllLoads();
                    });

                    it("should fire the reload event", function() {
                        var spy = jasmine.createSpy();

                        store.on('reload', spy);
                        makeRange();
                        range.goto(0, 100);
                        flushNextLoad();
                        completeLatest();

                        store.setGrouper(null);
                        expect(spy).not.toHaveBeenCalled();
                        completeReload();
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1].$className).toBe('Ext.data.operation.Read');

                        flushAllLoads();
                    });
                });
            });

            describe("changing grouping", function() {
                beforeEach(function() {
                    makeStore({
                        groupField: 'group'
                    });
                });

                describe("fully loaded", function() {
                    it("should reload the current active range", function() {
                        makeRange();
                        range.goto(100, 200);
                        flushAllLoads();

                        proxySpy.reset();

                        store.setGrouper({
                            property: 'group',
                            direction: 'DESC'
                        });

                        completeReload();
                        flushAllLoads();

                        // first page, + 4 pages
                        expect(proxySpy.callCount).toBe(5);

                        var calls = proxySpy.calls.slice(1),
                            i;

                        for (i = 0; i < 4; ++i) {
                            expectLoad(calls[i], i + 5, {
                                property: 'group',
                                direction: 'DESC'
                            });
                        }
                    });

                    it("should fire the groupchange event immediately", function() {
                        var spy = jasmine.createSpy();

                        store.on('groupchange', spy);
                        makeRange();
                        range.goto(100, 200);
                        flushAllLoads();

                        expect(spy).not.toHaveBeenCalled();
                        store.setGrouper({
                            property: 'group',
                            direction: 'DESC'
                        });
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1].serialize()).toEqual({
                            property: 'group',
                            direction: 'DESC'
                        });

                        completeReload();
                        flushAllLoads();
                    });

                    it("should fire the reload event", function() {
                        var spy = jasmine.createSpy();

                        store.on('reload', spy);
                        makeRange();
                        range.goto(100, 200);
                        flushAllLoads();

                        store.setGrouper({
                            property: 'group',
                            direction: 'DESC'
                        });
                        expect(spy).not.toHaveBeenCalled();
                        completeReload();
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1].$className).toBe('Ext.data.operation.Read');

                        flushAllLoads();
                    });
                });

                describe("with loads in flight", function() {
                    it("should cancel any pending operations and reload the range", function() {
                        makeRange();
                        range.goto(100, 200);
                        flushNextLoad();
                        completeLatest();

                        store.setGrouper({
                            property: 'group',
                            direction: 'DESC'
                        });

                        completeReload();
                        flushAllLoads();
                        // Initial call, + 1 aborted call, + first page, + 4 new requests
                        expect(proxySpy.callCount).toBe(7);

                        expectAborted(proxySpy.calls[1]);

                        var calls = proxySpy.calls.slice(3),
                            i;

                        for (i = 0; i < 4; ++i) {
                            expectLoad(calls[i], i + 5, {
                                property: 'group',
                                direction: 'DESC'
                            });
                        }
                    });

                    it("should fire the groupchange event immediately", function() {
                        var spy = jasmine.createSpy();

                        store.on('groupchange', spy);
                        makeRange();
                        range.goto(0, 100);
                        flushNextLoad();
                        completeLatest();

                        expect(spy).not.toHaveBeenCalled();
                        store.setGrouper({
                            property: 'group',
                            direction: 'DESC'
                        });
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1].serialize()).toEqual({
                            property: 'group',
                            direction: 'DESC'
                        });

                        completeReload();
                        flushAllLoads();
                    });

                    it("should fire the reload event immediately", function() {
                        var spy = jasmine.createSpy();

                        store.on('reload', spy);
                        makeRange();
                        range.goto(0, 100);
                        flushNextLoad();
                        completeLatest();

                        store.setGrouper({
                            property: 'group',
                            direction: 'DESC'
                        });
                        expect(spy).not.toHaveBeenCalled();
                        completeReload();
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store);
                        expect(spy.mostRecentCall.args[1].$className).toBe('Ext.data.operation.Read');

                        flushAllLoads();
                    });
                });
            });

            it("should clear groups correctly when reloading", function() {
                makeStore({
                    groupField: 'group'
                });
                makeRange();
                range.goto(100, 200);
                flushAllLoads();
                expectGroups(['g03', 'g04', 'g05']);

                store.setGrouper({
                    property: 'group',
                    direction: 'DESC'
                });
                expectGroups([]);
                completeReload();
                flushAllLoads();
                expectGroups(['g14']);
            });
        });

        describe("grouping during page changes", function() {
            beforeEach(function() {
                makeStore({
                    groupField: 'group'
                });
            });

            it("should populate the groups at the start as they load", function() {
                makeRange();
                range.goto(0, 100);
                flushNextLoad();
                completeLatest();

                expectGroups(['g01', 'g02']);
                expectFirsts([1, 4]);
                completeLatest();
                expectGroups(['g01', 'g02', 'g03']);
                expectFirsts([1, 4, 39]);
                flushAllLoads();
                expectGroups(['g01', 'g02', 'g03']);
                expectFirsts([1, 4, 39]);
            });

            it("should consider cached pages as part of grouping", function() {
                var size = pageMap.getCacheSize();

                makeRange();
                range.goto(0, pageSize);
                flushAllLoads();

                for (var i = 1; i <= size; ++i) {
                    range.goto(i * pageSize, i * pageSize + pageSize);
                    flushAllLoads();
                }

                // Cache full, but not overflowed, should have 1 single page left
                expect(store.getGroups().get('g01')).not.toBeNull();

                range.goto((size + 1) * pageSize, (size + 1) * pageSize + pageSize);
                flushAllLoads();
                expect(store.getGroups().get('g01')).toBeUndefined();
            });

            it("should provide groups correctly when loading in descending order", function() {
                store.setGrouper({
                    property: 'group',
                    direction: 'DESC'
                });

                completeReload();

                makeRange();
                range.goto(4800, 4900);
                flushNextLoad();
                completeLatest();

                expectGroups(['g05']);
                expectFirsts([200]);
                completeLatest();
                expectGroups(['g05']);
                expectFirsts([200]);
                completeLatest();
                expectGroups(['g05']);
                expectFirsts([200]);
                completeLatest();
                expectGroups(['g05', 'g04', 'g03']);
                expectFirsts([200, 116, 101]);
                completeLatest();
                expectGroups(['g05', 'g04', 'g03']);
                expectFirsts([200, 116, 101]);
            });

            it("should provide groups correctly when moving backwards", function() {
                makeRange();
                range.goto(130, 180);
                flushAllLoads();

                expectGroups(['g05']);
                expectFirsts([126]);
                range.goto(100, 130);
                flushNextLoad();
                completeLatest();
                expectGroups(['g03', 'g04', 'g05']);
                expectFirsts([101, 102, 117]);
                range.goto(50, 130);
                flushAllLoads();
                expectGroups(['g03', 'g04', 'g05']);
                expectFirsts([51, 102, 117]);
                range.goto(25, 50);
                flushAllLoads();
                expectGroups(['g02', 'g03', 'g04', 'g05']);
                expectFirsts([26, 39, 102, 117]);
                range.goto(0, 25);
                flushAllLoads();
                expectGroups(['g01', 'g02', 'g03', 'g04', 'g05']);
                expectFirsts([1, 4, 39, 102, 117]);
            });

            it("should include prefetch pages", function() {
                makeRange({
                    prefetch: true
                });
                range.goto(25, 400);
                flushAllLoads();
                expectGroups(['g01', 'g02', 'g03', 'g04', 'g05', 'g06', 'g07', 'g08', 'g09', 'g10', 'g11', 'g12']);
                expectFirsts([1, 4, 39, 102, 117, 280, 289, 400, 405, 410, 415, 484]);
            });
        });
    });

    describe("summaries", function() {
        describe("group summaries", function() {
            var groupMap, groupOffsets;

            function buildGroupMap() {
                if (groupMap) {
                    return;
                }

                groupMap = {};

                var groupIdx = 0,
                    id, i;

                for (i = 0; i < total; ++i) {
                    id = i + 1;

                    if (id >= groupOffsets[groupIdx]) {
                        ++groupIdx;
                    }

                    groupMap[id] = 'g' + Ext.String.leftPad(groupIdx + 1, 2, '0');
                }
            }

            function assignGroups(records) {
                buildGroupMap();

                Ext.Array.forEach(records, function(data) {
                    data.group = groupMap[data.id];
                });
            }

            function makeGroupData(limit, start) {
                var ret = makeData(limit, start);

                assignGroups(ret, start);

                return ret;
            }

            beforeEach(function() {
                groupOffsets = [4, 39, 102, 117, 280, 289, 400, 405, 410, 415, 484, 900, 999];
                dataMaker = makeGroupData;
                makeStore({
                    groupField: 'group'
                });
                makeRange();
            });

            afterEach(function() {
                groupMap = groupOffsets = null;
            });

            it("should return null by default", function() {
                range.goto(0, 25);
                flushAllLoads();

                var groups = store.getGroups();

                expect(groups.get('g01').getSummaryRecord()).toBeNull();
                expect(groups.get('g02').getSummaryRecord()).toBeNull();
            });

            it("should read the summary data if it is passed", function() {
                range.goto(0, 25);
                flushNextLoad();
                completeLatest(1, {
                    groups: [
                        { group: 'g01', rate: 1234 }
                    ]
                });

                var groups = store.getGroups();

                expect(groups.get('g01').getSummaryRecord().get('rate')).toBe(1234);
                expect(groups.get('g02').getSummaryRecord()).toBeNull();
            });

            it("should read the summary as it becomes available", function() {
                range.goto(0, 50);
                flushNextLoad();
                completeLatest(1, {
                    groups: [
                        { group: 'g01', rate: 1234 }
                    ]
                });

                var groups = store.getGroups();

                expect(groups.get('g01').getSummaryRecord().get('rate')).toBe(1234);
                expect(groups.get('g02').getSummaryRecord()).toBeNull();

                completeLatest(1, {
                    groups: [
                        { group: 'g02', rate: 2345 }
                    ]
                });

                expect(groups.get('g01').getSummaryRecord().get('rate')).toBe(1234);
                expect(groups.get('g02').getSummaryRecord().get('rate')).toBe(2345);
            });

            it("should discard unknown group information", function() {
                range.goto(0, 125);
                flushNextLoad();
                completeLatest(1, {
                    groups: [
                        { group: 'g01', rate: 1111 },
                        { group: 'g02', rate: 2222 },
                        { group: 'g03', rate: 3333 },
                        { group: 'g04', rate: 4444 }
                    ]
                });

                var groups = store.getGroups();

                expect(groups.get('g01').getSummaryRecord().get('rate')).toBe(1111);
                expect(groups.get('g02').getSummaryRecord().get('rate')).toBe(2222);
                expect(groups.get('g03')).toBeUndefined();
                expect(groups.get('g04')).toBeUndefined();

                completeLatest();

                expect(groups.get('g01').getSummaryRecord().get('rate')).toBe(1111);
                expect(groups.get('g02').getSummaryRecord().get('rate')).toBe(2222);
                expect(groups.get('g03').getSummaryRecord()).toBeNull();
                expect(groups.get('g04')).toBeUndefined();

                flushAllLoads();

                expect(groups.get('g01').getSummaryRecord().get('rate')).toBe(1111);
                expect(groups.get('g02').getSummaryRecord().get('rate')).toBe(2222);
                expect(groups.get('g03').getSummaryRecord()).toBeNull();
                expect(groups.get('g04').getSummaryRecord()).toBeNull();
            });

            it("should update existing data", function() {
                range.goto(0, 50);
                flushNextLoad();
                completeLatest(1, {
                    groups: [
                        { group: 'g02', rate: 2222 }
                    ]
                });

                var groups = store.getGroups();

                expect(groups.get('g01').getSummaryRecord()).toBeNull();
                expect(groups.get('g02').getSummaryRecord().get('rate')).toBe(2222);

                completeLatest(1, {
                    groups: [
                        { group: 'g02', rate: 3333 }
                    ]
                });

                expect(groups.get('g01').getSummaryRecord()).toBeNull();
                expect(groups.get('g02').getSummaryRecord().get('rate')).toBe(3333);
            });

            it("should not use data when the group is not present in the page", function() {
                range.goto(0, 50);
                flushNextLoad();
                completeLatest(1, {
                    groups: [
                        { group: 'g01', rate: 1111 },
                        { group: 'g02', rate: 2222 }
                    ]
                });

                var groups = store.getGroups();

                expect(groups.get('g01').getSummaryRecord().get('rate')).toBe(1111);
                expect(groups.get('g02').getSummaryRecord().get('rate')).toBe(2222);

                completeLatest(1, {
                    groups: [
                        { group: 'g01', rate: 3333 },
                        { group: 'g02', rate: 4444 }
                    ]
                });

                expect(groups.get('g01').getSummaryRecord().get('rate')).toBe(1111);
                expect(groups.get('g02').getSummaryRecord().get('rate')).toBe(4444);
            });

            it("should discard group data when the page is discarded", function() {
                var size = pageMap.getCacheSize();

                range.goto(0, pageSize);
                flushNextLoad();
                completeLatest(1, {
                    groups: [
                        { group: 'g01', rate: 1111 }
                    ]
                });

                for (var i = 1; i <= size; ++i) {
                    range.goto(i * pageSize, i * pageSize + pageSize);
                    flushAllLoads();
                }

                // Cache full, but not overflowed, should have 1 single page left
                expect(store.getGroups().get('g01').getSummaryRecord().get('rate')).toBe(1111);

                range.goto((size + 1) * pageSize, (size + 1) * pageSize + pageSize);
                flushAllLoads();
                expect(store.getGroups().get('g01')).toBeUndefined();

                range.goto(0, pageSize);
                flushAllLoads();
                expect(store.getGroups().get('g01').getSummaryRecord()).toBeNull();
            });
        });

        describe("total summaries", function() {
            beforeEach(function() {
                makeStore();
            });

            it("should return null by default", function() {
                expect(store.getSummaryRecord()).toBeNull();
            });

            it("should read the summary record", function() {
                makeRange();
                range.goto(0, 25);
                flushNextLoad();
                expect(store.getSummaryRecord()).toBeNull();
                completeLatest(1, {
                    summary: { rate: 100 }
                });
                expect(store.getSummaryRecord().get('rate')).toBe(100);
            });

            it("should take the most recent data", function() {
                makeRange();
                range.goto(0, 100);
                flushNextLoad();
                expect(store.getSummaryRecord()).toBeNull();
                completeLatest(1, {
                    summary: { rate: 100 }
                });
                expect(store.getSummaryRecord().get('rate')).toBe(100);
                completeLatest(1, {
                    summary: { rate: 101 }
                });
                expect(store.getSummaryRecord().get('rate')).toBe(101);
                completeLatest(1, {
                    summary: { rate: 102 }
                });
                expect(store.getSummaryRecord().get('rate')).toBe(102);
                completeLatest(1, {
                    summary: { rate: 103 }
                });
                expect(store.getSummaryRecord().get('rate')).toBe(103);
            });

            it("shou;d keep existing data if new data isn't presented", function() {
                makeRange();
                range.goto(0, 100);
                flushNextLoad();
                expect(store.getSummaryRecord()).toBeNull();
                completeLatest(1, {
                    summary: { rate: 100 }
                });
                expect(store.getSummaryRecord().get('rate')).toBe(100);
                completeLatest();
                expect(store.getSummaryRecord().get('rate')).toBe(100);
                completeLatest();
                expect(store.getSummaryRecord().get('rate')).toBe(100);
                completeLatest();
                expect(store.getSummaryRecord().get('rate')).toBe(100);
            });
        });
    });

    describe("query methods", function() {
        beforeEach(function() {
            makeStore();
            makeRange({
                prefetch: true
            });
            range.goto(150, 250);
        });

        describe("getAt", function() {
            it("should return a record in the active range", function() {
                flushAllLoads();

                for (var i = 150; i < 250; ++i) {
                    expect(store.getAt(i).id).toBe(i + 1);
                }
            });

            it("should return a record in the prefetch range", function() {
                flushAllLoads();

                for (var i = 100; i < 150; ++i) {
                    expect(store.getAt(i).id).toBe(i + 1);
                }

                for (i = 250; i < 450; ++i) {
                    expect(store.getAt(i).id).toBe(i + 1);
                }
            });

            it("should return null for records outside the range", function() {
                flushAllLoads();

                for (var i = 0; i < 100; ++i) {
                    expect(store.getAt(i)).toBeNull();
                }

                for (i = 450; i < 1000; ++i) {
                    expect(store.getAt(i)).toBeNull();
                }
            });

            it("should return null for not-yet loaded records", function() {
                for (var i = 150; i < 250; ++i) {
                    expect(store.getAt(i)).toBeNull();
                }

                flushAllLoads();

                for (i = 150; i < 250; ++i) {
                    expect(store.getAt(i).id).toBe(i + 1);
                }
            });

            it("should return when in reverse order", function() {
                store.getSorters().add({
                    property: 'id',
                    direction: 'DESC'
                });
                completeReload();
                flushAllLoads();
                expect(store.getAt(100).id).toBe(4900);
            });
        });

        describe("getById", function() {
            it("should return a record in the active range", function() {
                flushAllLoads();

                for (var i = 151; i < 251; ++i) {
                    expect(store.getById(i).id).toBe(i);
                }
            });

            it("should return a record in the prefetch range", function() {
                flushAllLoads();

                for (var i = 101; i < 151; ++i) {
                    expect(store.getById(i).id).toBe(i);
                }

                for (i = 251; i < 451; ++i) {
                    expect(store.getById(i).id).toBe(i);
                }
            });

            it("should return null for records outside the range", function() {
                flushAllLoads();

                for (var i = 0; i < 100; ++i) {
                    expect(store.getById(i)).toBeNull();
                }

                for (i = 451; i < 1000; ++i) {
                    expect(store.getById(i)).toBeNull();
                }
            });

            it("should return null for not-yet loaded records", function() {
                for (var i = 151; i < 251; ++i) {
                    expect(store.getById(i)).toBeNull();
                }

                flushAllLoads();

                for (i = 151; i < 251; ++i) {
                    expect(store.getById(i).id).toBe(i);
                }
            });

            it("should return when in reverse order", function() {
                store.getSorters().add({
                    property: 'id',
                    direction: 'DESC'
                });
                completeReload();
                flushAllLoads();
                expect(store.getById(4850).id).toBe(4850);
            });
        });

        describe("indexOf", function() {
            it("should return a record in the active range", function() {
                flushAllLoads();

                for (var i = 150; i < 250; ++i) {
                    expect(store.indexOf(store.getAt(i))).toBe(i);
                }
            });

            it("should return a record in the prefetch range", function() {
                flushAllLoads();

                for (var i = 100; i < 150; ++i) {
                    expect(store.indexOf(store.getAt(i))).toBe(i);
                }

                for (i = 250; i < 450; ++i) {
                    expect(store.indexOf(store.getAt(i))).toBe(i);
                }
            });

            it("should return null for records outside the range", function() {
                flushAllLoads();

                for (var i = 0; i < 100; ++i) {
                    expect(store.indexOf(new M({ id: i + 1 }))).toBe(-1);
                }

                for (i = 450; i < 1000; ++i) {
                    expect(store.indexOf(new M({ id: i + 1 }))).toBe(-1);
                }
            });

            it("should return null for not-yet loaded records", function() {
                for (var i = 150; i < 250; ++i) {
                    expect(store.indexOf(new M({ id: i + 1 }))).toBe(-1);
                }

                flushAllLoads();

                for (i = 150; i < 250; ++i) {
                    expect(store.indexOf(store.getAt(i))).toBe(i);
                }
            });

            it("should return when in reverse order", function() {
                store.getSorters().add({
                    property: 'id',
                    direction: 'DESC'
                });
                completeReload();
                flushAllLoads();
                expect(store.indexOf(store.getAt(100))).toBe(100);
            });
        });

        describe("indexOfId", function() {
            it("should return an index in the active range", function() {
                flushAllLoads();

                for (var i = 151; i < 251; ++i) {
                    expect(store.indexOfId(i)).toBe(i - 1);
                }
            });

            it("should return an index in the prefetch range", function() {
                flushAllLoads();

                for (var i = 101; i < 151; ++i) {
                    expect(store.indexOfId(i)).toBe(i - 1);
                }

                for (i = 251; i < 451; ++i) {
                    expect(store.indexOfId(i)).toBe(i - 1);
                }
            });

            it("should return -1 for records outside the range", function() {
                flushAllLoads();

                for (var i = 0; i < 100; ++i) {
                    expect(store.indexOfId(i)).toBe(-1);
                }

                for (i = 451; i < 1000; ++i) {
                    expect(store.indexOfId(i)).toBe(-1);
                }
            });

            it("should return -1 for not-yet loaded records", function() {
                for (var i = 151; i < 251; ++i) {
                    expect(store.indexOfId(i)).toBe(-1);
                }

                flushAllLoads();

                for (i = 151; i < 251; ++i) {
                    expect(store.indexOfId(i)).toBe(i - 1);
                }
            });

            it("should return when in reverse order", function() {
                store.getSorters().add({
                    property: 'id',
                    direction: 'DESC'
                });
                completeReload();
                flushAllLoads();
                expect(store.indexOfId(4850)).toBe(150);
            });
        });
    });

    describe("autoLoad", function() {
       it("should not load the store when autoLoad is undefined", function() {
            makeStore({
               autoLoad: false
           });

           expect(Ext.Ajax.mockGetAllRequests().length).toBe(0);
       });

       it("should not load the store when autoLoad is false", function() {
            makeStore({
               autoLoad: false
           });

           expect(Ext.Ajax.mockGetAllRequests().length).toBe(0);
       });

       it("should load the store when autoLoad is true", function() {
            makeStore({
               autoLoad: true
           });

           expect(Ext.Ajax.mockGetAllRequests().length).toBe(1);
       });
   });

    describe("events", function() {
       beforeEach(function() {
                makeStore();
                makeRange({
                    prefetch: true
                });

              total = 500;
              range.goto(100, 200);
        });

        it('should fire beforeload event', function() {
             var spy = jasmine.createSpy();

            store.on('beforeload', spy);
            flushAllLoads();
            expect(spy).toHaveBeenCalled();
        });

        it('should fire load event', function() {
            var spy = jasmine.createSpy();

            store.on('load', spy);
            expect(spy).not.toHaveBeenCalled();
            flushAllLoads();
            expect(spy).toHaveBeenCalled();
        });

        it('should fire beforesort event', function() {
            var spy = jasmine.createSpy();

            store.on('beforesort', spy);
            store.sort('group', 'ASC');
            completeReload();
            flushAllLoads();
            expect(spy.callCount).toBe(1);
        });

        it('should fire sort event', function() {
            var spy = jasmine.createSpy();

            store.on('sort', spy);
            store.sort('group', 'ASC');
            completeReload();
            flushAllLoads();
            expect(spy.callCount).toBe(1);
        });

        it('should fire update event', function() {
            var spy = jasmine.createSpy(),
                rec;

            store.on('update', spy);
            total = 1000;
            range.goto(0, 500);
            flushAllLoads();
            expect(spy).not.toHaveBeenCalled();
            rec = store.getAt(100);
            rec.set('id', 9999);
            expect(spy.callCount).toBe(1);
        });

        it('should fire datachanged event', function() {
            var spy = jasmine.createSpy(),
                rec;

            store.on('datachanged', spy);
            total = 1000;
            range.goto(0, 500);
            flushAllLoads();
            expect(spy).not.toHaveBeenCalled();
            rec = store.getAt(120);
            rec.set('id', 9999);
            expect(spy.callCount).toBe(1);
        });

        it('should fire clear event', function() {
            var spy = jasmine.createSpy();

            store.on('clear', spy);
            total = 1000;
            range.goto(0, 500);
            flushAllLoads();
            expect(spy).not.toHaveBeenCalled();
            store.removeAll();
            expect(spy.callCount).toBe(1);
        });

        xit('should fire beforesync event', function() {
            var spy = jasmine.createSpy(),
                rec;

            store.on('beforesync', spy);
            total = 1000;
            range.goto(0, 500);
            flushAllLoads();

            expect(spy).not.toHaveBeenCalled();
            rec = store.getAt(120);
            rec.set('id', 9999);
            store.sync();
            expect(spy.callCount).toBe(1);
        });

        it('should not fire load event if beforeload returns false', function() {
            flushAllLoads();
            proxySpy.reset();

            store.on('beforeload', function() {
                return false;
            });
            store.reload();

            expect(proxySpy).not.toHaveBeenCalled();
        });

        it('should return store and operation', function() {
            var virtualStore, op;

            store.on('beforeload', function(vstore, operation) {
                virtualStore = vstore;
                op = operation;
            });
            flushAllLoads();
            completeReload();

            expect(store).toEqual(virtualStore);
            expect(op).not.toBeNull();
        });
    });
});

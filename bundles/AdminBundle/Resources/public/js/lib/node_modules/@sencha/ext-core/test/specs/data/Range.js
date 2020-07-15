topSuite("Ext.data.Range", [
    'Ext.data.Store',
    'Ext.data.proxy.Ajax'
], function() {
    var range, store;

    var Model = Ext.define(null, {
        extend: 'Ext.data.Model'
    });

    function makeData(count) {
        var data = [];

        if (count !== 0) {
            count = count || 100;

            for (var i = 0; i < count; ++i) {
                data.push({
                    id: i + 1
                });
            }
        }

        return data;
    }

    function makeStore(data, cfg) {
        store = new Ext.data.Store(Ext.apply({
            asynchronousLoad: false,
            proxy: {
                type: 'ajax',
                url: 'foo'
            },
            model: Model,
            data: makeData(data)
        }, cfg));
    }

    function makeRange(cfg) {
        range = store.createActiveRange(cfg);
    }

    beforeEach(function() {
        MockAjaxManager.addMethods();
    });

    afterEach(function() {
        MockAjaxManager.removeMethods();
        range = store = Ext.destroy(store);
    });

    function expectRangeSize(begin, end) {
        expect(range.begin).toBe(begin);
        expect(range.end).toBe(end);
        expect(range.length).toBe(end - begin);
        expect(range.records).toEqual(store.getRange());
    }

    // In all of these tests, regardless of the range, the records should always
    // hold whatever is in the store

    describe("construction", function() {
        beforeEach(function() {
            makeStore();
        });

        describe("defaulting", function() {
            it("should not default with a filled store", function() {
                makeStore();
                makeRange();

                expectRangeSize(0, 0);
            });

            it("should not default with an empty store", function() {
                makeStore(0);
                makeRange();

                expectRangeSize(0, 0);
            });

            it("should not default with a loading store", function() {
                makeStore(0, {
                    autoLoad: true
                });
                makeRange();

                expectRangeSize(0, 0);

                waitsFor(function() {
                    return Ext.Ajax.mockGetAllRequests().length > 0;
                });
                runs(function() {
                    Ext.Ajax.mockCompleteWithData(makeData(100));
                    expectRangeSize(0, 0);
                });
            });
        });

        it("should be able to pass a range", function() {
            makeStore();
            makeRange({
                begin: 50,
                end: 75
            });

            expectRangeSize(50, 75);
        });
    });

    describe("goto", function() {
        beforeEach(function() {
            makeStore();
        });

        it("should be able to go backwards without overlap", function() {
            makeRange({
                begin: 80,
                end: 90
            });

            range.goto(10, 40);
            expectRangeSize(10, 40);
        });

        it("should be able to move backwards with overlap", function() {
            makeRange({
                begin: 80,
                end: 90
            });

            range.goto(75, 85);
            expectRangeSize(75, 85);
        });

        it("should be able to narrow a range", function() {
            makeRange({
                begin: 10,
                end: 90
            });

            range.goto(40, 60);
            expectRangeSize(40, 60);
        });

        it("should be able to widen a range", function() {
            makeRange({
                begin: 40,
                end: 60
            });

            range.goto(10, 90);
            expectRangeSize(10, 90);
        });

        it("should be able to move forwards with overlap", function() {
            makeRange({
                begin: 10,
                end: 20
            });

            range.goto(15, 25);
            expectRangeSize(15, 25);
        });

        it("should be able to go forwards without overlap", function() {
            makeRange({
                begin: 10,
                end: 20
            });

            range.goto(40, 60);
            expectRangeSize(40, 60);
        });
    });

    describe("store changes", function() {
        beforeEach(function() {
            makeStore();
            makeRange();
            range.goto(0, 100);
        });

        describe("add", function() {
            it("should not alter the size of the range, but should update the records", function() {
                store.add([{
                    id: 1001
                }, {
                    id: 1002
                }]);

                expectRangeSize(0, 100);
                expect(range.records[100].id).toBe(1001);
                expect(range.records[101].id).toBe(1002);
            });
        });

        describe("insert", function() {
            describe("inside the range", function() {
                it("should not alter the size of the range, but should update the records", function() {
                    store.insert(0, [{
                        id: 1001
                    }, {
                        id: 1002
                    }]);

                    expectRangeSize(0, 100);
                    expect(range.records[0].id).toBe(1001);
                    expect(range.records[1].id).toBe(1002);
                });
            });

            describe("outside the range", function() {
                it("should not alter the size of the range, but should update the records", function() {
                    store.insert(100, [{
                        id: 1001
                    }, {
                        id: 1002
                    }]);

                    expectRangeSize(0, 100);
                    expect(range.records[100].id).toBe(1001);
                    expect(range.records[101].id).toBe(1002);
                });
            });
        });

        describe("remove", function() {
            describe("inside the range", function() {
                it("should not alter the size of the range, but should update the records", function() {
                    store.removeAt(0);

                    expectRangeSize(0, 100);
                    expect(range.records[0].id).toBe(2);
                });
            });

            describe("outside the range", function() {
                it("should not alter the size of the range, but should update the records", function() {
                    range.goto(0, 50);
                    store.removeAt(99);

                    expectRangeSize(0, 50);
                });
            });
        });

        describe("removeAll", function() {
            it("should not alter the size of the range, but should update the records", function() {
                store.removeAll();
                expectRangeSize(0, 100);
                expect(range.records[0]).toBeUndefined();
            });
        });

        describe("sorting", function() {
            it("should not alter the size of the range, but should update the records", function() {
                store.getSorters().add({
                    property: 'id',
                    direction: 'desc'
                });

                expectRangeSize(0, 100);
                expect(range.records[0].id).toBe(100);
                expect(range.records[99].id).toBe(1);
            });
        });

        describe("filtering", function() {
            beforeEach(function() {
                store.getFilters().add({
                    filterFn: function(rec) {
                        return rec.id <= 50;
                    }
                });
            });

            describe("adding", function() {
                it("should not alter the size of the range, but should update the records", function() {
                    expectRangeSize(0, 100);

                    for (var i = 50; i < 99; ++i) {
                        expect(range.records[i]).toBeUndefined();
                    }
                });
            });

            describe("removing", function() {
                it("should not alter the size of the range, but should update the records", function() {
                    store.getFilters().removeAt(0);

                    expectRangeSize(0, 100);
                    expect(range.records[0].id).toBe(1);
                    expect(range.records[99].id).toBe(100);
                });
            });
        });

        describe("loading", function() {
            describe("loadData", function() {
                describe("smaller then the range", function() {
                    it("should not alter the size of the range, but should update the records", function() {
                        store.loadData(makeData(10));

                        expectRangeSize(0, 100);

                        for (var i = 10; i < 99; ++i) {
                            expect(range.records[i]).toBeUndefined();
                        }
                    });
                });

                describe("larger than the range", function() {
                    it("should not alter the size of the range, but should update the records", function() {
                        store.loadData(makeData(110));

                        expectRangeSize(0, 100);

                        for (var i = 0; i < 110; ++i) {
                            expect(range.records[i].id).toBe(i + 1);
                        }
                    });
                });
            });

            describe("remote load", function() {
                describe("smaller than the range", function() {
                    it("should not alter the size of the range, but should update the records", function() {
                        store.load();
                        Ext.Ajax.mockCompleteWithData(makeData(10));

                        expectRangeSize(0, 100);

                        for (var i = 10; i < 99; ++i) {
                            expect(range.records[i]).toBeUndefined();
                        }
                    });
                });

                describe("larger than the range", function() {
                    it("should not alter the size of the range, but should update the records", function() {
                        store.load();
                        Ext.Ajax.mockCompleteWithData(makeData(110));

                        expectRangeSize(0, 100);

                        for (var i = 0; i < 110; ++i) {
                            expect(range.records[i].id).toBe(i + 1);
                        }
                    });
                });
            });
        });
    });
});

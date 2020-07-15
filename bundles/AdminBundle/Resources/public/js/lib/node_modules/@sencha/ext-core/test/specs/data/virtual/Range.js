topSuite("Ext.data.virtual.Range", [
    'Ext.data.proxy.Ajax',
    'Ext.data.virtual.Store'
], function() {
    var oldJasmineCaptureStack, oldTimerCaptureStack,
        range, store, pageMap, proxySpy, callbackSpy,
        total, pageSize;

    var Model = Ext.define(null, {
        extend: 'Ext.data.Model'
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

    function completeWithData(data, theTotal) {
        Ext.Ajax.mockCompleteWithData({
            total: theTotal === undefined ? total : theTotal,
            data: data
        });
    }

    function completeOperation(op) {
        completeWithData(makeDataForOperation(op));
    }

    function makeData(count, base) {
        var data = [],
            i;

        base = base || 0;

        for (i = 0; i < count; ++i) {
            data.push({
                id: base + i + 1
            });
        }

        return data;
    }

    function makeDataForOperation(op) {
        var start = op.getStart(),
            limit = op.getLimit();

        return makeData(limit, start);
    }

    function makeStore(cfg) {
        store = new Ext.data.virtual.Store(Ext.apply({
            model: Model,
            pageSize: pageSize,
            proxy: {
                type: 'ajax',
                url: 'bogus',
                reader: {
                    type: 'json',
                    rootProperty: 'data'
                }
            }
        }, cfg));

        pageMap = store.pageMap;
        proxySpy = spyOn(store.getProxy(), 'read').andCallThrough();
    }

    function completeLatest(n) {
        n = n || 1;

        for (var i = 0; i < n; ++i) {
            completeOperation(proxySpy.mostRecentCall.args[0]);

            if (i !== n - 1) {
                flushNextLoad();
            }
        }
    }

    function completeCall(index) {
        return completeOperation(proxySpy.calls[index].args[0]);
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

    function expectCallbacks(ranges) {
        expect(Ext.Array.map(callbackSpy.calls, function(call) {
            var args = call.args;

            return [args[1], args[2]];
        })).toEqual(ranges);
    }

    function expectProxyPages(pages) {
        expect(Ext.Array.map(proxySpy.calls, function(call) {
            return call.args[0].getPage();
        })).toEqual(pages);
    }

    function expectRecords(begin, end) {
        var records = range.records,
            pass = true,
            i;

        for (i = begin; i < end; ++i) {
            if (records[i].id !== i + 1) {
                pass = false;
                break;
            }
        }

        expect(pass).toBe(true);
    }

    function expectEmpty(begin, end) {
        var records = range.records,
            pass = true,
            i;

        for (i = begin; i < end; ++i) {
            if (records[i]) {
                pass = false;
                break;
            }
        }

        expect(pass).toBe(true);
    }

    function makePageRange(start, end) {
        var pages = [],
            reverse = start > end,
            i;

        if (reverse) {
            i = start;
            start = end;
            end = i;
        }

        for (i = start; i <= end; ++i) {
            pages.push(i);
        }

        if (reverse) {
            pages.reverse();
        }

        return pages;
    }

    beforeEach(function() {
        total = 100000;
        pageSize = 25;

        MockAjaxManager.addMethods();
        callbackSpy = jasmine.createSpy();
        makeStore();
    });

    afterEach(function() {
        MockAjaxManager.removeMethods();
        total = pageSize = proxySpy = callbackSpy = pageMap = range = store = Ext.destroy(store);
    });

    function expectRangeSize(begin, end) {
        expect(range.begin).toBe(begin);
        expect(range.end).toBe(end);
        expect(range.length).toBe(end - begin);
    }

    describe("without prefetching", function() {
        function makeRange(cfg) {
            range = store.createActiveRange(Ext.apply({
                delay: 0,
                callback: callbackSpy
            }, cfg));
        }

        describe("construction", function() {
            it("should not alter the range, or make any requests", function() {
                makeRange();

                expectRangeSize(0, 0);
                flushNextLoad();
                expect(proxySpy).not.toHaveBeenCalled();
            });

            it("should make data requests if begin was specified", function() {
                makeRange({
                    begin: 0,
                    end: 100
                });

                expectRangeSize(0, 100);
                flushAllLoads();
                expect(proxySpy.callCount).toBe(4);
            });
        });

        describe("basic loading functionality", function() {
            beforeEach(function() {
                makeRange();
            });

            describe("pageSize", function() {
                it("should use the store page size", function() {
                    store.setPageSize(50);
                    range.goto(0, 100);
                    flushAllLoads();
                    expectProxyPages([1, 2]);
                    expectCallbacks([[0, 50], [50, 100]]);
                    expectRecords(0, 100);
                });
            });

            describe("range constraints", function() {
                describe("total not known", function() {
                    it("should allow for any size load", function() {
                        range.goto(total * 2, total * 2 + pageSize * 4);
                        expectRangeSize(total * 2, total * 2 + pageSize * 4);
                        flushAllLoads();
                        expectProxyPages([8001]);
                        expect(callbackSpy).not.toHaveBeenCalled();
                    });
                });

                describe("total known", function() {
                    it("should raise an exception if passing a known count", function() {
                        range.goto(0, 100);
                        flushAllLoads();
                        proxySpy.reset();
                        callbackSpy.reset();

                        expect(function() {
                            range.goto(total * 2, total * 2 + pageSize);
                        }).toThrow();
                        flushAllLoads();
                        expect(proxySpy).not.toHaveBeenCalled();
                        expect(callbackSpy).not.toHaveBeenCalled();
                    });
                });
            });

            describe("range smaller than the page size", function() {
                describe("range falls within a single page", function() {
                    it("should fulfil at the start of the dataset", function() {
                        range.goto(10, 20);
                        expectRangeSize(10, 20);
                        flushAllLoads();
                        expectProxyPages([1]);
                        expectRecords(0, 25);
                        expectEmpty(25, 100);
                        expectCallbacks([[10, 20]]);
                    });

                    it("should fulfil in the middle of the dataset", function() {
                        range.goto(20005, 20015);
                        expectRangeSize(20005, 20015);
                        flushAllLoads();
                        expectProxyPages([801]);
                        expectRecords(20000, 20025);
                        expectEmpty(19900, 20000);
                        expectEmpty(20025, 20100);
                        expectCallbacks([[20005, 20015]]);
                    });

                    it("should fulfil at the end of the dataset", function() {
                        range.goto(99980, 99990);
                        expectRangeSize(99980, 99990);
                        flushAllLoads();
                        expectProxyPages([4000]);
                        expectRecords(99975, 100000);
                        expectEmpty(99900, 99975);
                        expectCallbacks([[99980, 99990]]);
                    });
                });

                describe("range covers multiple pages", function() {
                    it("should fulfil at the start of the dataset", function() {
                        range.goto(20, 30);
                        expectRangeSize(20, 30);
                        flushAllLoads();
                        expectProxyPages([1, 2]);
                        expectRecords(0, 50);
                        expectEmpty(50, 100);
                        expectCallbacks([[20, 25], [25, 30]]);
                    });

                    it("should fulfil in the middle of the dataset", function() {
                        range.goto(20120, 20130);
                        expectRangeSize(20120, 20130);
                        flushAllLoads();
                        expectProxyPages([805, 806]);
                        expectRecords(20100, 20150);
                        expectEmpty(20000, 20100);
                        expectEmpty(20150, 20200);
                        expectCallbacks([[20120, 20125], [20125, 20130]]);
                    });

                    it("should fulfil at the end of the dataset", function() {
                        range.goto(99960, 99980);
                        expectRangeSize(99960, 99980);
                        flushAllLoads();
                        expectProxyPages([3999, 4000]);
                        expectRecords(99950, 100000);
                        expectEmpty(99900, 99950);
                        expectCallbacks([[99960, 99975], [99975, 99980]]);
                    });
                });
            });

            describe("range equal to one page", function() {
                describe("range falls within a single page", function() {
                    it("should fulfil at the start of the dataset", function() {
                        range.goto(0, 25);
                        expectRangeSize(0, 25);
                        flushAllLoads();
                        expectProxyPages([1]);
                        expectRecords(0, 25);
                        expectEmpty(25, 100);
                        expectCallbacks([[0, 25]]);
                    });

                    it("should fulfil in the middle of the dataset", function() {
                        range.goto(20000, 20025);
                        expectRangeSize(20000, 20025);
                        flushAllLoads();
                        expectProxyPages([801]);
                        expectRecords(20000, 20025);
                        expectEmpty(19900, 20000);
                        expectEmpty(20025, 20100);
                        expectCallbacks([[20000, 20025]]);
                    });

                    it("should fulfil at the end of the dataset", function() {
                        range.goto(99975, 100000);
                        expectRangeSize(99975, 100000);
                        flushAllLoads();
                        expectProxyPages([4000]);
                        expectRecords(99975, 100000);
                        expectEmpty(99900, 99975);
                        expectCallbacks([[99975, 100000]]);
                    });
                });

                describe("range covers multiple pages", function() {
                    it("should fulfil at the start of the dataset", function() {
                        range.goto(20, 45);
                        expectRangeSize(20, 45);
                        flushAllLoads();
                        expectProxyPages([1, 2]);
                        expectRecords(0, 50);
                        expectEmpty(50, 100);
                        expectCallbacks([[20, 25], [25, 45]]);
                    });

                    it("should fulfil in the middle of the dataset", function() {
                        range.goto(20120, 20145);
                        expectRangeSize(20120, 20145);
                        flushAllLoads();
                        expectProxyPages([805, 806]);
                        expectRecords(20100, 20150);
                        expectEmpty(20000, 20100);
                        expectEmpty(20150, 20200);
                        expectCallbacks([[20120, 20125], [20125, 20145]]);
                    });

                    it("should fulfil at the end of the dataset", function() {
                        range.goto(99955, 99985);
                        expectRangeSize(99955, 99985);
                        flushAllLoads();
                        expectProxyPages([3999, 4000]);
                        expectRecords(99950, 100000);
                        expectEmpty(99900, 99950);
                        expectCallbacks([[99955, 99975], [99975, 99985]]);
                    });
                });
            });

            describe("range greater than 1 page", function() {
                describe("range falls on page boundaries", function() {
                    it("should fulfil at the start of the dataset", function() {
                        range.goto(0, 50);
                        expectRangeSize(0, 50);
                        flushAllLoads();
                        expectProxyPages([1, 2]);
                        expectRecords(0, 50);
                        expectEmpty(50, 100);
                        expectCallbacks([[0, 25], [25, 50]]);
                    });

                    it("should fulfil in the middle of the dataset", function() {
                        range.goto(20000, 20050);
                        expectRangeSize(20000, 20050);
                        flushAllLoads();
                        expectProxyPages([801, 802]);
                        expectRecords(20000, 20050);
                        expectEmpty(19900, 20000);
                        expectEmpty(20050, 20100);
                        expectCallbacks([[20000, 20025], [20025, 20050]]);
                    });

                    it("should fulfil at the end of the dataset", function() {
                        range.goto(99950, 100000);
                        expectRangeSize(99950, 100000);
                        flushAllLoads();
                        expectProxyPages([3999, 4000]);
                        expectRecords(99950, 100000);
                        expectEmpty(99900, 99950);
                        expectCallbacks([[99950, 99975], [99975, 100000]]);
                    });
                });

                describe("range doesn't fall on page boundaries", function() {
                    it("should fulfil at the start of the dataset", function() {
                        range.goto(10, 115);
                        expectRangeSize(10, 115);
                        flushAllLoads();
                        expectProxyPages([1, 2, 3, 4, 5]);
                        expectRecords(0, 125);
                        expectEmpty(125, 250);
                        expectCallbacks([[10, 25], [25, 50], [50, 75], [75, 100], [100, 115]]);
                    });

                    it("should fulfil in the middle of the dataset", function() {
                        range.goto(20010, 20115);
                        expectRangeSize(20010, 20115);
                        flushAllLoads();
                        expectProxyPages([801, 802, 803, 804, 805]);
                        expectRecords(20000, 20125);
                        expectEmpty(19900, 20000);
                        expectEmpty(20150, 20200);
                        expectCallbacks([[20010, 20025], [20025, 20050], [20050, 20075], [20075, 20100], [20100, 20115]]);
                    });

                    it("should fulfil at the end of the dataset", function() {
                        range.goto(99930, 99985);
                        expectRangeSize(99930, 99985);
                        flushAllLoads();
                        expectProxyPages([3998, 3999, 4000]);
                        expectRecords(99925, 100000);
                        expectEmpty(99800, 99925);
                        expectCallbacks([[99930, 99950], [99950, 99975], [99975, 99985]]);
                    });
                });
            });
        });

        describe("moving the range", function() {
            describe("moving backwards without overlap", function() {
                beforeEach(function() {
                    makeRange({
                        begin: 510,
                        end: 590
                    });
                });

                describe("with no pending loads", function() {
                    it("should move backwards and load the correct pages", function() {
                        flushAllLoads();
                        proxySpy.reset();
                        callbackSpy.reset();

                        range.goto(110, 180);
                        expectRangeSize(110, 180);
                        flushAllLoads();
                        expectProxyPages([5, 6, 7, 8]);
                        expectCallbacks([[110, 125], [125, 150], [150, 175], [175, 180]]);
                        expectRecords(100, 200);
                        expectEmpty(0, 100);
                        expectEmpty(200, 600);
                    });
                });

                describe("with loads in flight", function() {
                    it("should not trigger unneeded pages, or callback for pages not in the range", function() {
                        flushNextLoad();
                        completeLatest();
                        expectCallbacks([[510, 525]]);
                        expectProxyPages([21, 22]);
                        expectRecords(500, 525);
                        expectEmpty(525, 600);
                        callbackSpy.reset();

                        range.goto(110, 180);
                        expectRangeSize(110, 180);
                        flushAllLoads();
                        expectProxyPages([21, 22, 5, 6, 7, 8]);
                        expectCallbacks([[110, 125], [125, 150], [150, 175], [175, 180]]);
                        expectRecords(100, 200);
                        expectEmpty(0, 100);
                        expectEmpty(200, 600);
                    });
                });
            });

            describe("moving backwards with overlap", function() {
                beforeEach(function() {
                    makeRange({
                        begin: 505,
                        end: 595
                    });
                });

                describe("with no pending loads", function() {
                    it("should move backwards and only load required pages", function() {
                        flushAllLoads();
                        proxySpy.reset();
                        callbackSpy.reset();

                        range.goto(460, 560);
                        expectRangeSize(460, 560);
                        flushAllLoads();
                        expectProxyPages([19, 20]);
                        expectCallbacks([[460, 475], [475, 500]]);
                        expectRecords(450, 575);
                        expectEmpty(400, 450);
                        expectEmpty(575, 600);
                    });
                });

                describe("with loads in flight", function() {
                    describe("no loads outside range", function() {
                        it("should not trigger unneeded pages, or callback for pages not in the range", function() {
                            flushNextLoad();
                            completeLatest();
                            expectCallbacks([[505, 525]]);
                            expectProxyPages([21, 22]);
                            expectRecords(500, 525);
                            expectEmpty(525, 600);
                            callbackSpy.reset();

                            range.goto(460, 560);
                            expectRangeSize(460, 560);
                            flushAllLoads();
                            expectProxyPages([21, 22, 23, 19, 20]);
                            expectCallbacks([[525, 550], [550, 560], [460, 475], [475, 500]]);
                            expectRecords(450, 575);
                            expectEmpty(400, 450);
                            expectEmpty(575, 650);
                        });
                    });

                    describe("loads outside range", function() {
                        it("should not trigger unneeded pages, or callback for pages not in the range", function() {
                            flushNextLoad();
                            completeLatest();
                            completeLatest();
                            completeLatest();
                            expectCallbacks([[505, 525], [525, 550], [550, 575]]);
                            expectProxyPages([21, 22, 23, 24]);
                            expectRecords(500, 575);
                            expectEmpty(575, 600);
                            callbackSpy.reset();

                            range.goto(460, 560);
                            expectRangeSize(460, 560);
                            flushAllLoads();
                            expectProxyPages([21, 22, 23, 24, 19, 20]);
                            expectCallbacks([[460, 475], [475, 500]]);
                            expectRecords(450, 575);
                            expectEmpty(400, 450);
                            expectEmpty(575, 650);
                        });
                    });
                });
            });

            describe("narrowing a range", function() {
                beforeEach(function() {
                    makeRange({
                        begin: 630,
                        end: 720
                    });
                });

                describe("with no pending loads", function() {
                    it("should not trigger any loads", function() {
                        flushAllLoads();
                        proxySpy.reset();
                        callbackSpy.reset();

                        range.goto(660, 710);
                        expectRangeSize(660, 710);
                        expect(proxySpy).not.toHaveBeenCalled();
                        expect(callbackSpy).not.toHaveBeenCalled();
                    });
                });

                describe("with loads in flight", function() {
                    it("should not trigger unneeded pages, or callback for pages not in the range", function() {
                        flushNextLoad();
                        completeLatest();

                        expectCallbacks([[630, 650]]);
                        expectProxyPages([26, 27]);
                        expectRecords(626, 650);
                        expectEmpty(650, 750);
                        callbackSpy.reset();

                        range.goto(710, 720);
                        expectRangeSize(710, 720);
                        flushAllLoads();
                        expectProxyPages([26, 27, 29]);
                        expectCallbacks([[710, 720]]);
                        expectRecords(700, 725);
                        expectEmpty(600, 700);
                        expectEmpty(725, 750);
                    });
                });
            });

            describe("widening a range", function() {
                beforeEach(function() {
                    makeRange({
                        begin: 450,
                        end: 550
                    });
                });

                describe("with no pending loads", function() {
                    it("should only load the required pages", function() {
                        flushAllLoads();
                        proxySpy.reset();
                        callbackSpy.reset();

                        range.goto(400, 600);
                        expectRangeSize(400, 600);
                        flushAllLoads();
                        expectProxyPages([17, 18, 23, 24]);
                        expectCallbacks([[400, 425], [425, 450], [550, 575], [575, 600]]);
                        expectRecords(400, 600);
                    });
                });

                describe("with loads in flight", function() {
                    it("should append any newly required loads", function() {
                        flushNextLoad();
                        completeLatest();

                        expectCallbacks([[450, 475]]);
                        expectProxyPages([19, 20]);
                        expectRecords(450, 475);
                        expectEmpty(400, 450);
                        expectEmpty(475, 600);
                        callbackSpy.reset();

                        range.goto(400, 600);
                        expectRangeSize(400, 600);
                        flushAllLoads();
                        expectProxyPages([19, 20, 21, 22, 17, 18, 23, 24]);
                        expectCallbacks([[475, 500], [500, 525], [525, 550], [400, 425], [425, 450], [550, 575], [575, 600]]);
                        expectRecords(400, 600);
                    });
                });
            });

            describe("moving forwards with overlap", function() {
                beforeEach(function() {
                    makeRange({
                        begin: 505,
                        end: 595
                    });
                });

                describe("with no pending loads", function() {
                    it("should move forwards and only load required pages", function() {
                        flushAllLoads();
                        proxySpy.reset();
                        callbackSpy.reset();

                        range.goto(550, 640);
                        expectRangeSize(550, 640);
                        flushAllLoads();
                        expectProxyPages([25, 26]);
                        expectCallbacks([[600, 625], [625, 640]]);
                        expectRecords(550, 650);
                        expectEmpty(500, 550);
                        expectEmpty(650, 700);
                    });
                });

                describe("with loads in flight", function() {
                    describe("no loads outside range", function() {
                        it("should not trigger unneeded pages, or callback for pages not in the range", function() {
                            flushNextLoad();
                            completeLatest();
                            completeLatest();
                            expectCallbacks([[505, 525], [525, 550]]);
                            expectProxyPages([21, 22, 23]);
                            expectRecords(500, 550);
                            expectEmpty(550, 600);
                            callbackSpy.reset();

                            range.goto(550, 640);
                            expectRangeSize(550, 640);
                            flushAllLoads();
                            expectProxyPages([21, 22, 23, 24, 25, 26]);
                            expectCallbacks([[550, 575], [575, 600], [600, 625], [625, 640]]);
                            expectRecords(550, 650);
                            expectEmpty(500, 550);
                            expectEmpty(650, 700);
                        });
                    });

                    describe("loads outside range", function() {
                        it("should not trigger unneeded pages, or callback for pages not in the range", function() {
                            flushNextLoad();
                            completeLatest();
                            expectCallbacks([[505, 525]]);
                            expectProxyPages([21, 22]);
                            expectRecords(500, 525);
                            expectEmpty(525, 600);
                            callbackSpy.reset();

                            range.goto(550, 640);
                            expectRangeSize(550, 640);
                            flushAllLoads();
                            expectProxyPages([21, 22, 23, 24, 25, 26]);
                            expectCallbacks([[550, 575], [575, 600], [600, 625], [625, 640]]);
                            expectRecords(550, 650);
                            expectEmpty(500, 550);
                            expectEmpty(650, 700);
                        });
                    });
                });
            });

            describe("moving forwards without overlap", function() {
                beforeEach(function() {
                    makeRange({
                        begin: 110,
                        end: 180
                    });
                });

                describe("with no pending loads", function() {
                    it("should move backwards and load the correct pages", function() {
                        flushAllLoads();
                        proxySpy.reset();
                        callbackSpy.reset();

                        range.goto(510, 590);
                        expectRangeSize(510, 590);
                        flushAllLoads();
                        expectProxyPages([21, 22, 23, 24]);
                        expectCallbacks([[510, 525], [525, 550], [550, 575], [575, 590]]);
                        expectRecords(500, 600);
                        expectEmpty(100, 500);
                        expectEmpty(600, 650);
                    });
                });

                describe("with loads in flight", function() {
                    it("should not trigger unneeded pages, or callback for pages not in the range", function() {
                        flushNextLoad();
                        completeLatest();
                        expectCallbacks([[110, 125]]);
                        expectProxyPages([5, 6]);
                        expectRecords(100, 125);
                        expectEmpty(125, 600);
                        callbackSpy.reset();

                        range.goto(510, 590);
                        expectRangeSize(510, 590);
                        flushAllLoads();
                        expectProxyPages([5, 6, 21, 22, 23, 24]);
                        expectCallbacks([[510, 525], [525, 550], [550, 575], [575, 590]]);
                        expectRecords(500, 600);
                        expectEmpty(100, 500);
                        expectEmpty(600, 650);
                    });
                });
            });

            describe("rapid movement", function() {
                it("should not trigger any loads straight away", function() {
                    makeRange({
                        begin: 0,
                        end: 100
                    });
                    flushAllLoads();
                    proxySpy.reset();

                    range.goto(50, 150);
                    expect(proxySpy).not.toHaveBeenCalled();
                    range.goto(100, 200);
                    expect(proxySpy).not.toHaveBeenCalled();
                    range.goto(150, 250);
                    expect(proxySpy).not.toHaveBeenCalled();
                    range.goto(200, 300);
                    expect(proxySpy).not.toHaveBeenCalled();
                    range.goto(2000, 2200);
                    expect(proxySpy).not.toHaveBeenCalled();
                    flushAllLoads();
                });
            });
        });

        describe("callback", function() {
            // Callback behaviour will be tested in other loading tests
            describe("types", function() {
                describe("as function", function() {
                    it("should default to global scope", function() {
                        makeRange();
                        range.goto(0, 25);
                        flushAllLoads();
                        expect(callbackSpy.mostRecentCall.object).toBe(Ext.global);
                    });

                    it("should use a passed scope", function() {
                        var scope = {};

                        makeRange({
                            scope: scope
                        });
                        range.goto(0, 25);
                        flushAllLoads();
                        expect(callbackSpy.mostRecentCall.object).toBe(scope);
                    });
                });

                describe("as string", function() {
                    it("should call the function", function() {
                        var scope = {
                            theCallback: callbackSpy
                        };

                        makeRange({
                            callback: 'theCallback',
                            scope: scope
                        });
                        range.goto(0, 25);
                        flushAllLoads();
                        expect(callbackSpy.mostRecentCall.object).toBe(scope);
                    });
                });
            });
        });

        describe("concurrent loading", function() {
            it("should only load the max concurrent pages", function() {
                pageMap.setConcurrentLoading(1);
                makeRange();
                range.goto(0, 100);
                flushNextLoad();
                expectProxyPages([1]);
                expect(Ext.Ajax.mockGetAllRequests().length).toBe(1);
                completeLatest();
                expectProxyPages([1, 2]);
                expect(Ext.Ajax.mockGetAllRequests().length).toBe(1);
                completeLatest();
                expectProxyPages([1, 2, 3]);
                expect(Ext.Ajax.mockGetAllRequests().length).toBe(1);
                completeLatest();
                expectProxyPages([1, 2, 3, 4]);
                expect(Ext.Ajax.mockGetAllRequests().length).toBe(1);
                completeLatest();
                expect(Ext.Ajax.mockGetAllRequests().length).toBe(0);
            });

            it("should be able to load multiple pages concurrently", function() {
                pageMap.setConcurrentLoading(2);
                makeRange();
                range.goto(0, 225);
                flushNextLoad();

                for (var i = 0; i < 8; ++i) {
                    expect(Ext.Ajax.mockGetAllRequests().length).toBe(2);
                    completeCall(i);
                }

                // Final request
                expect(Ext.Ajax.mockGetAllRequests().length).toBe(1);
                completeLatest();
            });
        });

        describe("caching", function() {
            var size;

            beforeEach(function() {
                size = pageMap.getCacheSize();
            });

            afterEach(function() {
                size = null;
            });

            it("should be able to hold the whole range, regardless of cache size", function() {
                makeRange();
                range.goto(0, total);
                flushAllLoads();
                expectRecords(0, total);
            });

            it("should not discard cached pages immediately", function() {
                makeRange();
                range.goto(0, 100);
                flushAllLoads();
                range.goto(100, 200);
                flushAllLoads();
                proxySpy.reset();
                callbackSpy.reset();

                range.goto(0, 100);
                expect(proxySpy).not.toHaveBeenCalled();
                expect(callbackSpy).not.toHaveBeenCalled();
            });

            it("should discard pages once they fall out of the cache", function() {
                makeRange();
                range.goto(0, 25);
                flushAllLoads();

                for (var i = 1; i <= size + 1; ++i) {
                    range.goto(i * pageSize, i * pageSize + pageSize);
                    flushAllLoads();
                }

                proxySpy.reset();
                // Page 0 should not exist in the cache
                range.goto(0, 25);
                flushAllLoads();
                expectProxyPages([1]);
            });

            it("should be able to recycle pages", function() {
                makeRange();
                range.goto(0, 25);
                flushAllLoads();

                for (var i = 1; i < size + 1; ++i) {
                    range.goto(i * pageSize, i * pageSize + pageSize);
                    flushAllLoads();
                }

                proxySpy.reset();
                callbackSpy.reset();
                // Cache is exactly full now
                range.goto(0, 25);
                flushAllLoads();

                for (i = 1; i < size + 1; ++i) {
                    range.goto(i * pageSize, i * pageSize + pageSize);
                    flushAllLoads();
                }

                expect(proxySpy).not.toHaveBeenCalled();
                expect(callbackSpy).not.toHaveBeenCalled();
            });

            it("should only remove the least recently used pages", function() {
                makeRange();
                range.goto(0, 25);
                flushAllLoads();

                for (var i = 1; i < size + 1; ++i) {
                    range.goto(i * pageSize, i * pageSize + pageSize);
                    flushAllLoads();
                }

                range.goto(0, 25);
                flushAllLoads();
                range.goto(1000, 1025);
                flushAllLoads();
                range.goto(0, 25);
                flushAllLoads();
                proxySpy.reset();
                range.goto(25, 50);
                flushAllLoads();
                expectProxyPages([2]);
            });
        });

        describe("changing range size", function() {
            it("should correct the range size when the initial size is smaller than the preflight", function() {
                makeRange();
                range.goto(0, 100);
                flushNextLoad();
                completeWithData(makeData(9), 9);
                expect(Ext.Ajax.mockGetAllRequests().length).toBe(0);
                expect(function() {
                    range.goto(0, 9);
                }).not.toThrow();
            });
        });
    });

    describe("with prefetching", function() {
        function makeRange(cfg) {
            range = store.createActiveRange(Ext.apply({
                delay: 0,
                prefetch: true,
                callback: callbackSpy
            }, cfg));
        }

        describe("construction", function() {
            it("should not alter the range, or make any requests", function() {
                makeRange();

                expectRangeSize(0, 0);
                flushNextLoad();
                expect(proxySpy).not.toHaveBeenCalled();
            });

            it("should make data requests if begin was specified", function() {
                makeRange({
                    begin: 0,
                    end: 100
                });

                expectRangeSize(0, 100);
                flushAllLoads();
                expect(proxySpy.callCount).toBe(12);
            });
        });

        describe("basic loading functionality", function() {
            beforeEach(function() {
                makeRange();
            });

            describe("pageSize", function() {
                it("should use the store page size", function() {
                    store.setPageSize(50);
                    range.goto(0, 100);
                    flushAllLoads();
                    expectProxyPages(makePageRange(1, 6));
                    expectCallbacks([[0, 50], [50, 100]]);
                    expectRecords(0, 100);
                });
            });

            describe("buffer sizes", function() {
                it("should use custom leading/trailing buffers", function() {
                    range.destroy();
                    makeRange({
                        leadingBufferZone: 100,
                        trailingBufferZone: 350
                    });

                    range.goto(500, 600);
                    flushAllLoads();
                    expectProxyPages([21, 22, 23, 24, 25, 20, 26, 19, 27, 18, 28].concat(makePageRange(17, 7)));
                    expectCallbacks([[500, 525], [525, 550], [550, 575], [575, 600]]);
                    expectRecords(500, 600);
                });
            });

            describe("range constraints", function() {
                describe("total not known", function() {
                    it("should constrain the leading buffer to 0", function() {
                        range.goto(0, 100);
                        expectRangeSize(0, 100);
                        flushAllLoads();
                        expectProxyPages(makePageRange(1, 12));
                    });

                    it("should allow for any size load", function() {
                        range.goto(total * 2, total * 2 + pageSize * 4);
                        expectRangeSize(total * 2, total * 2 + pageSize * 4);
                        flushAllLoads();
                        expectProxyPages([8001]);
                        expect(callbackSpy).not.toHaveBeenCalled();
                    });
                });

                describe("total known", function() {
                    it("should raise an exception if passing a known count", function() {
                        range.goto(0, 100);
                        flushAllLoads();
                        proxySpy.reset();
                        callbackSpy.reset();

                        expect(function() {
                            range.goto(total * 2, total * 2 + pageSize);
                        }).toThrow();
                        flushAllLoads();
                        expect(proxySpy).not.toHaveBeenCalled();
                        expect(callbackSpy).not.toHaveBeenCalled();
                    });

                    it("should limit prefetching to the upper bound", function() {
                        range.goto(0, 100);
                        expectRangeSize(0, 100);
                        flushAllLoads();
                        proxySpy.reset();
                        callbackSpy.reset();

                        range.goto(99975, 100000);
                        flushAllLoads();
                        expectProxyPages([4000, 3999, 3998]);
                    });
                });
            });

            describe("range smaller than the page size", function() {
                describe("range falls within a single page", function() {
                    it("should fulfil at the start of the dataset", function() {
                        range.goto(10, 20);
                        expectRangeSize(10, 20);
                        flushAllLoads();
                        expectProxyPages(makePageRange(1, 9));
                        expectRecords(0, 25);
                        expectEmpty(25, 100);
                        expectCallbacks([[10, 20]]);
                    });

                    it("should fulfil in the middle of the dataset", function() {
                        range.goto(20005, 20015);
                        expectRangeSize(20005, 20015);
                        flushAllLoads();
                        expectProxyPages([801, 802, 800, 803, 799, 804, 805, 806, 807, 808, 809]);
                        expectRecords(20000, 20025);
                        expectEmpty(19900, 20000);
                        expectEmpty(20025, 20100);
                        expectCallbacks([[20005, 20015]]);
                    });

                    it("should fulfil at the end of the dataset", function() {
                        range.goto(99980, 99990);
                        expectRangeSize(99980, 99990);
                        flushAllLoads();
                        expectProxyPages([4000, 3999, 3998]);
                        expectRecords(99975, 100000);
                        expectEmpty(99900, 99975);
                        expectCallbacks([[99980, 99990]]);
                    });
                });

                describe("range covers multiple pages", function() {
                    it("should fulfil at the start of the dataset", function() {
                        range.goto(20, 30);
                        expectRangeSize(20, 30);
                        flushAllLoads();
                        expectProxyPages(makePageRange(1, 10));
                        expectRecords(0, 50);
                        expectEmpty(50, 100);
                        expectCallbacks([[20, 25], [25, 30]]);
                    });

                    it("should fulfil in the middle of the dataset", function() {
                        range.goto(20120, 20130);
                        expectRangeSize(20120, 20130);
                        flushAllLoads();
                        expectProxyPages([805, 806, 807, 804, 808, 803, 809, 810, 811, 812, 813, 814]);
                        expectRecords(20100, 20150);
                        expectEmpty(20000, 20100);
                        expectEmpty(20150, 20200);
                        expectCallbacks([[20120, 20125], [20125, 20130]]);
                    });

                    it("should fulfil at the end of the dataset", function() {
                        range.goto(99960, 99980);
                        expectRangeSize(99960, 99980);
                        flushAllLoads();
                        expectProxyPages([3999, 4000, 3998, 3997]);
                        expectRecords(99950, 100000);
                        expectEmpty(99900, 99950);
                        expectCallbacks([[99960, 99975], [99975, 99980]]);
                    });
                });
            });

            describe("range equal to one page", function() {
                describe("range falls within a single page", function() {
                    it("should fulfil at the start of the dataset", function() {
                        range.goto(0, 25);
                        expectRangeSize(0, 25);
                        flushAllLoads();
                        expectProxyPages(makePageRange(1, 9));
                        expectRecords(0, 25);
                        expectEmpty(25, 100);
                        expectCallbacks([[0, 25]]);
                    });

                    it("should fulfil in the middle of the dataset", function() {
                        range.goto(20000, 20025);
                        expectRangeSize(20000, 20025);
                        flushAllLoads();
                        expectProxyPages([801, 802, 800, 803, 799, 804, 805, 806, 807, 808, 809]);
                        expectRecords(20000, 20025);
                        expectEmpty(19900, 20000);
                        expectEmpty(20025, 20100);
                        expectCallbacks([[20000, 20025]]);
                    });

                    it("should fulfil at the end of the dataset", function() {
                        range.goto(99975, 100000);
                        expectRangeSize(99975, 100000);
                        flushAllLoads();
                        expectProxyPages([4000, 3999, 3998]);
                        expectRecords(99975, 100000);
                        expectEmpty(99900, 99975);
                        expectCallbacks([[99975, 100000]]);
                    });
                });

                describe("range covers multiple pages", function() {
                    it("should fulfil at the start of the dataset", function() {
                        range.goto(20, 45);
                        expectRangeSize(20, 45);
                        flushAllLoads();
                        expectProxyPages(makePageRange(1, 10));
                        expectRecords(0, 50);
                        expectEmpty(50, 100);
                        expectCallbacks([[20, 25], [25, 45]]);
                    });

                    it("should fulfil in the middle of the dataset", function() {
                        range.goto(20120, 20145);
                        expectRangeSize(20120, 20145);
                        flushAllLoads();
                        expectProxyPages([805, 806, 807, 804, 808, 803, 809, 810, 811, 812, 813, 814]);
                        expectRecords(20100, 20150);
                        expectEmpty(20000, 20100);
                        expectEmpty(20150, 20200);
                        expectCallbacks([[20120, 20125], [20125, 20145]]);
                    });

                    it("should fulfil at the end of the dataset", function() {
                        range.goto(99955, 99985);
                        expectRangeSize(99955, 99985);
                        flushAllLoads();
                        expectProxyPages([3999, 4000, 3998, 3997]);
                        expectRecords(99950, 100000);
                        expectEmpty(99900, 99950);
                        expectCallbacks([[99955, 99975], [99975, 99985]]);
                    });
                });
            });

            describe("range greater than 1 page", function() {
                describe("range falls on page boundaries", function() {
                    it("should fulfil at the start of the dataset", function() {
                        range.goto(0, 50);
                        expectRangeSize(0, 50);
                        flushAllLoads();
                        expectProxyPages(makePageRange(1, 10));
                        expectRecords(0, 50);
                        expectEmpty(50, 100);
                        expectCallbacks([[0, 25], [25, 50]]);
                    });

                    it("should fulfil in the middle of the dataset", function() {
                        range.goto(20000, 20050);
                        expectRangeSize(20000, 20050);
                        flushAllLoads();
                        expectProxyPages([801, 802, 803, 800, 804, 799, 805, 806, 807, 808, 809, 810]);
                        expectRecords(20000, 20050);
                        expectEmpty(19900, 20000);
                        expectEmpty(20050, 20100);
                        expectCallbacks([[20000, 20025], [20025, 20050]]);
                    });

                    it("should fulfil at the end of the dataset", function() {
                        range.goto(99950, 100000);
                        expectRangeSize(99950, 100000);
                        flushAllLoads();
                        expectProxyPages([3999, 4000, 3998, 3997]);
                        expectRecords(99950, 100000);
                        expectEmpty(99900, 99950);
                        expectCallbacks([[99950, 99975], [99975, 100000]]);
                    });
                });

                describe("range doesn't fall on page boundaries", function() {
                    it("should fulfil at the start of the dataset", function() {
                        range.goto(10, 115);
                        expectRangeSize(10, 115);
                        flushAllLoads();
                        expectProxyPages(makePageRange(1, 13));
                        expectRecords(0, 125);
                        expectEmpty(125, 250);
                        expectCallbacks([[10, 25], [25, 50], [50, 75], [75, 100], [100, 115]]);
                    });

                    it("should fulfil in the middle of the dataset", function() {
                        range.goto(20010, 20115);
                        expectRangeSize(20010, 20115);
                        flushAllLoads();
                        expectProxyPages([801, 802, 803, 804, 805, 806, 800, 807, 799, 808, 809, 810, 811, 812, 813]);
                        expectRecords(20000, 20125);
                        expectEmpty(19900, 20000);
                        expectEmpty(20150, 20200);
                        expectCallbacks([[20010, 20025], [20025, 20050], [20050, 20075], [20075, 20100], [20100, 20115]]);
                    });

                    it("should fulfil at the end of the dataset", function() {
                        range.goto(99930, 99985);
                        expectRangeSize(99930, 99985);
                        flushAllLoads();
                        expectProxyPages([3998, 3999, 4000, 3997, 3996]);
                        expectRecords(99925, 100000);
                        expectEmpty(99800, 99925);
                        expectCallbacks([[99930, 99950], [99950, 99975], [99975, 99985]]);
                    });
                });
            });
        });

        describe("moving the range", function() {
            describe("moving backwards without overlap", function() {
                beforeEach(function() {
                    makeRange({
                        begin: 510,
                        end: 590
                    });
                });

                describe("with no pending loads", function() {
                    it("should move backwards and load the correct pages", function() {
                        flushAllLoads();
                        proxySpy.reset();
                        callbackSpy.reset();

                        range.goto(110, 180);
                        expectRangeSize(110, 180);
                        flushAllLoads();
                        expectProxyPages([5, 6, 7, 8, 4, 9, 3, 10, 2, 1]);
                        expectCallbacks([[110, 125], [125, 150], [150, 175], [175, 180]]);
                        expectRecords(100, 200);
                        expectEmpty(0, 100);
                        expectEmpty(200, 600);
                    });
                });

                describe("with loads in flight", function() {
                    describe("active pages in flight", function() {
                        it("should not trigger unneeded pages, or callback for pages not in the range", function() {
                            flushNextLoad();
                            completeLatest();
                            expectCallbacks([[510, 525]]);
                            expectProxyPages([21, 22]);
                            expectRecords(500, 525);
                            expectEmpty(525, 600);
                            callbackSpy.reset();

                            range.goto(110, 180);
                            expectRangeSize(110, 180);
                            flushAllLoads();
                            expectProxyPages([21, 22, 5, 6, 7, 8, 4, 9, 3, 10, 2, 1]);
                            expectCallbacks([[110, 125], [125, 150], [150, 175], [175, 180]]);
                            expectRecords(100, 200);
                            expectEmpty(0, 100);
                            expectEmpty(200, 600);
                        });
                    });

                    describe("prefetch pages in flight", function() {
                        it("should not trigger unneeded pages, or callback for pages not in the range", function() {
                            flushNextLoad();
                            completeLatest(5);
                            expectCallbacks([[510, 525], [525, 550], [550, 575], [575, 590]]);
                            expectProxyPages([21, 22, 23, 24, 25, 20]);
                            expectRecords(500, 600);
                            expectEmpty(450, 500);
                            expectEmpty(600, 650);
                            callbackSpy.reset();

                            range.goto(110, 180);
                            expectRangeSize(110, 180);
                            flushAllLoads();
                            expectProxyPages([21, 22, 23, 24, 25, 20, 5, 6, 7, 8, 4, 9, 3, 10, 2, 1]);
                            expectCallbacks([[110, 125], [125, 150], [150, 175], [175, 180]]);
                            expectRecords(100, 200);
                            expectEmpty(0, 100);
                            expectEmpty(200, 600);
                        });
                    });
                });
            });

            describe("moving backwards with overlap", function() {
                describe("previous move backwards", function() {
                    beforeEach(function() {
                        makeRange({
                            begin: 2000,
                            end: 2100
                        });
                        flushAllLoads();
                        proxySpy.reset();
                        callbackSpy.reset();
                    });

                    describe("with no pending loads", function() {
                        beforeEach(function() {
                            range.goto(705, 795);
                            flushAllLoads();
                            proxySpy.reset();
                            callbackSpy.reset();
                        });

                        describe("overlap with only the active range", function() {
                            it("should move backwards and not load any pages", function() {
                                range.goto(701, 745);
                                expectRangeSize(701, 745);
                                flushAllLoads();
                                expect(proxySpy).not.toHaveBeenCalled();
                                expect(callbackSpy).not.toHaveBeenCalled();
                                expectRecords(700, 750);
                                expectEmpty(650, 700);
                                expectEmpty(750, 800);
                            });
                        });

                        describe("overlap with the active and prefetch range", function() {
                            it("should move backwards and load the prefetch pages", function() {
                                range.goto(660, 760);
                                expectRangeSize(660, 760);
                                flushAllLoads();
                                expectProxyPages([20, 19]);
                                expect(callbackSpy).not.toHaveBeenCalled();
                                expectRecords(650, 775);
                                expectEmpty(600, 650);
                                expectEmpty(775, 800);
                            });
                        });

                        describe("overlap with only the prefetch range", function() {
                            it("should move backwards and load the prefetch pages", function() {
                                range.goto(455, 545);
                                expectRangeSize(455, 545);
                                flushAllLoads();
                                expectProxyPages([19, 20, 18, 17, 16, 15, 14, 13, 12, 11]);
                                expectCallbacks([[455, 475], [475, 500]]);
                                expectRecords(450, 550);
                                expectEmpty(400, 450);
                                expectEmpty(550, 600);
                                expectEmpty(700, 800);
                            });
                        });
                    });

                    describe("with loads in flight", function() {
                        beforeEach(function() {
                            range.goto(505, 595);
                        });

                        describe("overlap with only the active range", function() {
                            describe("with active loads in flight", function() {
                                it("should complete the appropriate loads", function() {
                                    flushNextLoad();
                                    completeLatest();
                                    expectProxyPages([21, 22]);
                                    expectCallbacks([[505, 525]]);
                                    expectRecords(500, 525);
                                    expectEmpty(450, 500);
                                    expectEmpty(525, 600);
                                    callbackSpy.reset();

                                    range.goto(501, 545);
                                    expectRangeSize(501, 545);
                                    flushAllLoads();
                                    expectProxyPages([21, 22, 20, 23, 19, 24, 18, 17, 16, 15, 14, 13]);
                                    expectCallbacks([[525, 545]]);
                                    expectRecords(500, 550);
                                    expectEmpty(450, 500);
                                    expectEmpty(550, 600);
                                });
                            });

                            describe("with prefetch loads in flight", function() {
                                it("should complete the appropriate loads", function() {
                                    flushNextLoad();
                                    completeLatest(7);
                                    expectProxyPages([21, 22, 23, 24, 20, 25, 19, 26]);
                                    expectCallbacks([[505, 525], [525, 550], [550, 575], [575, 595]]);
                                    expectRecords(500, 600);
                                    expectEmpty(450, 500);
                                    expectEmpty(600, 700);
                                    callbackSpy.reset();

                                    range.goto(501, 545);
                                    expectRangeSize(501, 545);
                                    flushAllLoads();
                                    expectProxyPages([21, 22, 23, 24, 20, 25, 19, 26, 18, 17, 16, 15, 14, 13]);
                                    expect(callbackSpy).not.toHaveBeenCalled();
                                    expectRecords(500, 550);
                                    expectEmpty(450, 500);
                                    expectEmpty(550, 600);
                                });
                            });
                        });

                        describe("overlap with the active and prefetch range", function() {
                            describe("with active loads in flight", function() {
                                it("should complete the appropriate loads", function() {
                                    flushNextLoad();
                                    completeLatest();
                                    expectProxyPages([21, 22]);
                                    expectCallbacks([[505, 525]]);
                                    expectRecords(500, 525);
                                    expectEmpty(450, 500);
                                    expectEmpty(525, 600);
                                    callbackSpy.reset();

                                    range.goto(460, 560);
                                    expectRangeSize(460, 560);
                                    flushAllLoads();
                                    expectProxyPages([21, 22, 23, 19, 20, 18, 24, 17, 25, 16, 15, 14, 13, 12, 11]);
                                    expectCallbacks([[525, 550], [550, 560], [460, 475], [475, 500]]);
                                    expectRecords(450, 575);
                                    expectEmpty(400, 450);
                                    expectEmpty(575, 600);
                                });
                            });

                            describe("with prefetch loads in flight", function() {
                                it("should complete the appropriate loads", function() {
                                    flushNextLoad();
                                    completeLatest(5);
                                    expectProxyPages([21, 22, 23, 24, 20, 25]);
                                    expectCallbacks([[505, 525], [525, 550], [550, 575], [575, 595]]);
                                    expectRecords(500, 600);
                                    expectEmpty(450, 500);
                                    expectEmpty(600, 700);
                                    callbackSpy.reset();

                                    range.goto(460, 560);
                                    expectRangeSize(460, 560);
                                    flushAllLoads();
                                    expectProxyPages([21, 22, 23, 24, 20, 25, 19, 18, 17, 16, 15, 14, 13, 12, 11]);
                                    expectCallbacks([[460, 475]]);
                                    expectRecords(450, 575);
                                    expectEmpty(400, 450);
                                    expectEmpty(575, 600);
                                });
                            });
                        });

                        describe("overlap with only the prefetch range", function() {
                            describe("with active loads in flight", function() {
                                it("should complete the appropriate loads", function() {
                                    flushNextLoad();
                                    completeLatest();
                                    expectProxyPages([21, 22]);
                                    expectCallbacks([[505, 525]]);
                                    expectRecords(500, 525);
                                    expectEmpty(450, 500);
                                    expectEmpty(525, 600);
                                    callbackSpy.reset();

                                    range.goto(350, 450);
                                    expectRangeSize(350, 450);
                                    flushAllLoads();
                                    expectProxyPages([21, 22, 15, 16, 17, 18, 14, 19, 13, 20, 12, 11, 10, 9, 8, 7]);
                                    expectCallbacks([[350, 375], [375, 400], [400, 425], [425, 450]]);
                                    expectRecords(350, 450);
                                    expectEmpty(350, 300);
                                    expectEmpty(450, 600);
                                });
                            });

                            describe("with prefetch loads in flight", function() {
                                it("should complete the appropriate loads", function() {
                                    flushNextLoad();
                                    completeLatest(7);
                                    expectProxyPages([21, 22, 23, 24, 20, 25, 19, 26]);
                                    expectCallbacks([[505, 525], [525, 550], [550, 575], [575, 595]]);
                                    expectRecords(500, 600);
                                    expectEmpty(450, 500);
                                    expectEmpty(600, 700);
                                    callbackSpy.reset();

                                    range.goto(350, 450);
                                    expectRangeSize(350, 450);
                                    flushAllLoads();
                                    expectProxyPages([21, 22, 23, 24, 20, 25, 19, 26, 15, 16, 17, 18, 14, 13, 12, 11, 10, 9, 8, 7]);
                                    expectCallbacks([[350, 375], [375, 400], [400, 425], [425, 450]]);
                                    expectRecords(350, 450);
                                    expectEmpty(350, 300);
                                    expectEmpty(450, 600);
                                });
                            });
                        });
                    });
                });

                describe("previous move forwards", function() {
                    beforeEach(function() {
                        makeRange({
                            begin: 0,
                            end: 1
                        });
                        flushAllLoads();
                        proxySpy.reset();
                        callbackSpy.reset();
                    });

                    describe("with no pending loads", function() {
                        beforeEach(function() {
                            range.goto(705, 795);
                            flushAllLoads();
                            proxySpy.reset();
                            callbackSpy.reset();
                        });

                        describe("overlap with only the active range", function() {
                            it("should move backwards and and load the prefetch pages in the other direction", function() {
                                range.goto(701, 745);
                                expectRangeSize(701, 745);
                                flushAllLoads();
                                expectProxyPages(makePageRange(26, 21));
                                expect(callbackSpy).not.toHaveBeenCalled();
                                expectRecords(700, 750);
                                expectEmpty(650, 700);
                                expectEmpty(750, 800);
                            });
                        });

                        describe("overlap with the active and prefetch range", function() {
                            it("should move backwards and load the prefetch pages", function() {
                                range.goto(660, 760);
                                expectRangeSize(660, 760);
                                flushAllLoads();
                                expectProxyPages([26, 25, 24, 23, 22, 21, 20, 19]);
                                expect(callbackSpy).not.toHaveBeenCalled();
                                expectRecords(650, 775);
                                expectEmpty(600, 650);
                                expectEmpty(775, 800);
                            });
                        });

                        describe("overlap with only the prefetch range", function() {
                            it("should move backwards and load the prefetch pages", function() {
                                range.goto(575, 675);
                                expectRangeSize(575, 675);
                                flushAllLoads();
                                expectProxyPages([24, 25, 26, 23, 22, 21, 20, 19, 18, 17, 16]);
                                expectCallbacks([[575, 600], [600, 625], [625, 650]]);
                                expectRecords(575, 675);
                                expectEmpty(500, 575);
                                expectEmpty(675, 800);
                            });
                        });
                    });

                    describe("with loads in flight", function() {
                        beforeEach(function() {
                            range.goto(505, 595);
                        });

                        describe("overlap with only the active range", function() {
                            describe("with active loads in flight", function() {
                                it("should complete the appropriate loads", function() {
                                    flushNextLoad();
                                    completeLatest();
                                    expectProxyPages([21, 22]);
                                    expectCallbacks([[505, 525]]);
                                    expectRecords(500, 525);
                                    expectEmpty(450, 500);
                                    expectEmpty(525, 600);
                                    callbackSpy.reset();

                                    range.goto(501, 545);
                                    expectRangeSize(501, 545);
                                    flushAllLoads();
                                    expectProxyPages([21, 22, 20, 23, 19, 24, 18, 17, 16, 15, 14, 13]);
                                    expectCallbacks([[525, 545]]);
                                    expectRecords(500, 550);
                                    expectEmpty(450, 500);
                                    expectEmpty(550, 600);
                                });
                            });

                            describe("with prefetch loads in flight", function() {
                                it("should complete the appropriate loads", function() {
                                    flushNextLoad();
                                    completeLatest(7);
                                    expectProxyPages([21, 22, 23, 24, 25, 20, 26, 19]);
                                    expectCallbacks([[505, 525], [525, 550], [550, 575], [575, 595]]);
                                    expectRecords(500, 600);
                                    expectEmpty(450, 500);
                                    expectEmpty(600, 700);
                                    callbackSpy.reset();

                                    range.goto(501, 545);
                                    expectRangeSize(501, 545);
                                    flushAllLoads();
                                    expectProxyPages([21, 22, 23, 24, 25, 20, 26, 19, 18, 17, 16, 15, 14, 13]);
                                    expect(callbackSpy).not.toHaveBeenCalled();
                                    expectRecords(500, 550);
                                    expectEmpty(450, 500);
                                    expectEmpty(550, 600);
                                });
                            });
                        });

                        describe("overlap with the active and prefetch range", function() {
                            describe("with active loads in flight", function() {
                                it("should complete the appropriate loads", function() {
                                    flushNextLoad();
                                    completeLatest();
                                    expectProxyPages([21, 22]);
                                    expectCallbacks([[505, 525]]);
                                    expectRecords(500, 525);
                                    expectEmpty(450, 500);
                                    expectEmpty(525, 600);
                                    callbackSpy.reset();

                                    range.goto(460, 560);
                                    expectRangeSize(460, 560);
                                    flushAllLoads();
                                    expectProxyPages([21, 22, 23, 19, 20, 18, 24, 17, 25, 16, 15, 14, 13, 12, 11]);
                                    expectCallbacks([[525, 550], [550, 560], [460, 475], [475, 500]]);
                                    expectRecords(450, 575);
                                    expectEmpty(400, 450);
                                    expectEmpty(575, 600);
                                });
                            });

                            describe("with prefetch loads in flight", function() {
                                it("should complete the appropriate loads", function() {
                                    flushNextLoad();
                                    completeLatest(5);
                                    expectProxyPages([21, 22, 23, 24, 25, 20]);
                                    expectCallbacks([[505, 525], [525, 550], [550, 575], [575, 595]]);
                                    expectRecords(500, 600);
                                    expectEmpty(450, 500);
                                    expectEmpty(600, 700);
                                    callbackSpy.reset();

                                    range.goto(460, 560);
                                    expectRangeSize(460, 560);
                                    flushAllLoads();
                                    expectProxyPages([21, 22, 23, 24, 25, 20, 19, 18, 17, 16, 15, 14, 13, 12, 11]);
                                    expectCallbacks([[475, 500], [460, 475]]);
                                    expectRecords(450, 575);
                                    expectEmpty(400, 450);
                                    expectEmpty(575, 600);
                                });
                            });
                        });

                        describe("overlap with only the prefetch range", function() {
                            describe("with active loads in flight", function() {
                                it("should complete the appropriate loads", function() {
                                    flushNextLoad();
                                    completeLatest();
                                    expectProxyPages([21, 22]);
                                    expectCallbacks([[505, 525]]);
                                    expectRecords(500, 525);
                                    expectEmpty(450, 500);
                                    expectEmpty(525, 600);
                                    callbackSpy.reset();

                                    range.goto(350, 450);
                                    expectRangeSize(350, 450);
                                    flushAllLoads();
                                    expectProxyPages([21, 22, 15, 16, 17, 18, 14, 19, 13, 20, 12, 11, 10]);
                                    expectCallbacks([[350, 375], [375, 400], [400, 425], [425, 450]]);
                                    expectRecords(350, 450);
                                    expectEmpty(350, 300);
                                    expectEmpty(450, 600);
                                });
                            });

                            describe("with prefetch loads in flight", function() {
                                it("should complete the appropriate loads", function() {
                                    flushNextLoad();
                                    completeLatest(7);
                                    expectProxyPages([21, 22, 23, 24, 25, 20, 26, 19]);
                                    expectCallbacks([[505, 525], [525, 550], [550, 575], [575, 595]]);
                                    expectRecords(500, 600);
                                    expectEmpty(450, 500);
                                    expectEmpty(600, 700);
                                    callbackSpy.reset();

                                    range.goto(350, 450);
                                    expectRangeSize(350, 450);
                                    flushAllLoads();
                                    expectProxyPages([21, 22, 23, 24, 25, 20, 26, 19, 15, 16, 17, 18, 14, 13, 12, 11, 10]);
                                    expectCallbacks([[350, 375], [375, 400], [400, 425], [425, 450]]);
                                    expectRecords(350, 450);
                                    expectEmpty(350, 300);
                                    expectEmpty(450, 600);
                                });
                            });
                        });
                    });
                });
            });

            describe("narrowing a range", function() {
                describe("previous move backwards", function() {
                    beforeEach(function() {
                        makeRange({
                            begin: 2000,
                            end: 2100
                        });
                        flushAllLoads();
                        proxySpy.reset();
                        callbackSpy.reset();

                        range.goto(630, 720);
                    });

                    describe("with no pending loads", function() {
                        it("should not trigger any loads", function() {
                            flushAllLoads();
                            proxySpy.reset();
                            callbackSpy.reset();

                            range.goto(660, 705);
                            expectRangeSize(660, 705);
                            flushAllLoads();
                            expect(proxySpy).not.toHaveBeenCalled();
                            expect(callbackSpy).not.toHaveBeenCalled();
                            expectRecords(650, 725);
                            expectEmpty(600, 650);
                            expectEmpty(725, 750);
                        });
                    });

                    describe("with loads in flight", function() {
                        it("should not trigger any extra loads", function() {
                            flushNextLoad();
                            completeLatest();
                            expectProxyPages([26, 27]);
                            expectCallbacks([[630, 650]]);
                            expectRecords(625, 650);
                            expectEmpty(600, 625);
                            expectEmpty(650, 675);
                            callbackSpy.reset();

                            range.goto(660, 705);
                            expectRangeSize(660, 705);
                            flushAllLoads();
                            expectProxyPages([26, 27, 28, 29, 30, 25, 31, 24, 23, 22, 21, 20, 19]);
                            expectCallbacks([[660, 675], [675, 700], [700, 705]]);
                            expectRecords(650, 725);
                            expectEmpty(600, 650);
                            expectEmpty(725, 750);
                        });
                    });
                });

                describe("previous move forwards", function() {
                    beforeEach(function() {
                        makeRange({
                            begin: 0,
                            end: 1
                        });
                        flushAllLoads();
                        proxySpy.reset();
                        callbackSpy.reset();

                        range.goto(630, 720);
                    });

                    describe("with no pending loads", function() {
                        it("should not trigger any loads", function() {
                            flushAllLoads();
                            proxySpy.reset();
                            callbackSpy.reset();

                            range.goto(660, 705);
                            expectRangeSize(660, 705);
                            flushAllLoads();
                            expect(proxySpy).not.toHaveBeenCalled();
                            expect(callbackSpy).not.toHaveBeenCalled();
                            expectRecords(650, 725);
                            expectEmpty(600, 650);
                            expectEmpty(725, 750);
                        });
                    });

                    describe("with loads in flight", function() {
                        it("should not trigger any extra loads", function() {
                            flushNextLoad();
                            completeLatest();
                            expectProxyPages([26, 27]);
                            expectCallbacks([[630, 650]]);
                            expectRecords(625, 650);
                            expectEmpty(600, 625);
                            expectEmpty(650, 675);
                            callbackSpy.reset();

                            range.goto(660, 705);
                            expectRangeSize(660, 705);
                            flushAllLoads();
                            expectProxyPages([26, 27, 28, 29, 30, 31, 25, 32, 33, 34, 35, 36, 37]);
                            expectCallbacks([[660, 675], [675, 700], [700, 705]]);
                            expectRecords(650, 725);
                            expectEmpty(600, 650);
                            expectEmpty(725, 750);
                        });
                    });
                });
            });

            xdescribe("widening a range", function() {
                beforeEach(function() {
                    makeRange({
                        begin: 450,
                        end: 550
                    });
                });

                describe("with no pending loads", function() {
                    it("should only load the required pages", function() {
                        flushAllLoads();
                        proxySpy.reset();
                        callbackSpy.reset();

                        range.goto(400, 600);
                        expectRangeSize(400, 600);
                        flushAllLoads();
                        expectProxyPages([17, 18, 23, 24]);
                        expectCallbacks([[400, 425], [425, 450], [550, 575], [575, 600]]);
                        expectRecords(400, 600);
                    });
                });

                describe("with loads in flight", function() {
                    it("should append any newly required loads", function() {
                        flushNextLoad();
                        completeLatest();

                        expectCallbacks([[450, 475]]);
                        expectProxyPages([19, 20]);
                        expectRecords(450, 475);
                        expectEmpty(400, 450);
                        expectEmpty(475, 600);
                        callbackSpy.reset();

                        range.goto(400, 600);
                        expectRangeSize(400, 600);
                        flushAllLoads();
                        expectProxyPages([19, 20, 21, 22, 17, 18, 23, 24]);
                        expectCallbacks([[475, 500], [500, 525], [525, 550], [400, 425], [425, 450], [550, 575], [575, 600]]);
                        expectRecords(400, 600);
                    });
                });
            });

            xdescribe("moving forwards with overlap", function() {
                beforeEach(function() {
                    makeRange({
                        begin: 505,
                        end: 595
                    });
                });

                describe("with no pending loads", function() {
                    it("should move forwards and only load required pages", function() {
                        flushAllLoads();
                        proxySpy.reset();
                        callbackSpy.reset();

                        range.goto(550, 640);
                        expectRangeSize(550, 640);
                        flushAllLoads();
                        expectProxyPages([25, 26]);
                        expectCallbacks([[600, 625], [625, 640]]);
                        expectRecords(550, 650);
                        expectEmpty(500, 550);
                        expectEmpty(650, 700);
                    });
                });

                describe("with loads in flight", function() {
                    describe("no loads outside range", function() {
                        it("should not trigger unneeded pages, or callback for pages not in the range", function() {
                            flushNextLoad();
                            completeLatest();
                            completeLatest();
                            expectCallbacks([[505, 525], [525, 550]]);
                            expectProxyPages([21, 22, 23]);
                            expectRecords(500, 550);
                            expectEmpty(550, 600);
                            callbackSpy.reset();

                            range.goto(550, 640);
                            expectRangeSize(550, 640);
                            flushAllLoads();
                            expectProxyPages([21, 22, 23, 24, 25, 26]);
                            expectCallbacks([[550, 575], [575, 600], [600, 625], [625, 640]]);
                            expectRecords(550, 650);
                            expectEmpty(500, 550);
                            expectEmpty(650, 700);
                        });
                    });

                    describe("loads outside range", function() {
                        it("should not trigger unneeded pages, or callback for pages not in the range", function() {
                            flushNextLoad();
                            completeLatest();
                            expectCallbacks([[505, 525]]);
                            expectProxyPages([21, 22]);
                            expectRecords(500, 525);
                            expectEmpty(525, 600);
                            callbackSpy.reset();

                            range.goto(550, 640);
                            expectRangeSize(550, 640);
                            flushAllLoads();
                            expectProxyPages([21, 22, 23, 24, 25, 26]);
                            expectCallbacks([[550, 575], [575, 600], [600, 625], [625, 640]]);
                            expectRecords(550, 650);
                            expectEmpty(500, 550);
                            expectEmpty(650, 700);
                        });
                    });
                });
            });

            xdescribe("moving forwards without overlap", function() {
                beforeEach(function() {
                    makeRange({
                        begin: 110,
                        end: 180
                    });
                });

                describe("with no pending loads", function() {
                    it("should move backwards and load the correct pages", function() {
                        flushAllLoads();
                        proxySpy.reset();
                        callbackSpy.reset();

                        range.goto(510, 590);
                        expectRangeSize(510, 590);
                        flushAllLoads();
                        expectProxyPages([21, 22, 23, 24]);
                        expectCallbacks([[510, 525], [525, 550], [550, 575], [575, 590]]);
                        expectRecords(500, 600);
                        expectEmpty(100, 500);
                        expectEmpty(600, 650);
                    });
                });

                describe("with loads in flight", function() {
                    it("should not trigger unneeded pages, or callback for pages not in the range", function() {
                        flushNextLoad();
                        completeLatest();
                        expectCallbacks([[110, 125]]);
                        expectProxyPages([5, 6]);
                        expectRecords(100, 125);
                        expectEmpty(125, 600);
                        callbackSpy.reset();

                        range.goto(510, 590);
                        expectRangeSize(510, 590);
                        flushAllLoads();
                        expectProxyPages([5, 6, 21, 22, 23, 24]);
                        expectCallbacks([[510, 525], [525, 550], [550, 575], [575, 590]]);
                        expectRecords(500, 600);
                        expectEmpty(100, 500);
                        expectEmpty(600, 650);
                    });
                });
            });

            xdescribe("rapid movement", function() {
                it("should not trigger any loads straight away", function() {
                    makeRange({
                        begin: 0,
                        end: 100
                    });
                    flushAllLoads();
                    proxySpy.reset();

                    range.goto(50, 150);
                    expect(proxySpy).not.toHaveBeenCalled();
                    range.goto(100, 200);
                    expect(proxySpy).not.toHaveBeenCalled();
                    range.goto(150, 250);
                    expect(proxySpy).not.toHaveBeenCalled();
                    range.goto(200, 300);
                    expect(proxySpy).not.toHaveBeenCalled();
                    range.goto(2000, 2200);
                    expect(proxySpy).not.toHaveBeenCalled();
                    flushAllLoads();
                });
            });
        });

        describe("changing range size", function() {
            it("should correct the range size when the initial size is smaller than the preflight", function() {
                makeRange();
                range.goto(0, 100);
                flushNextLoad();
                completeWithData(makeData(9), 9);
                expect(Ext.Ajax.mockGetAllRequests().length).toBe(0);
                expect(function() {
                    range.goto(0, 9);
                }).not.toThrow();
            });

            it('should not throw TypeError: Cannot read property internalId of null', function() {
               makeRange();
               range.goto(100, 200);
               flushNextLoad();

               expect(function() {
                   pageMap.indexOf(range.records[100]);
               }).not.toThrow();

               expect(function() {
                   pageMap.indexOf(range.records[200]);
               }).not.toThrow();

           });
        });
    });
});

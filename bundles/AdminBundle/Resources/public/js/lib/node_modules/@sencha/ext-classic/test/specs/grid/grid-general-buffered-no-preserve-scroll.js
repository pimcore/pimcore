topSuite("grid-general-buffered-no-preserve-scroll",
    [false, 'Ext.grid.Panel', 'Ext.data.ArrayStore', 'Ext.data.BufferedStore'],
function() {
    var grid, store,
        synchronousLoad = true,
        proxyStoreLoad = Ext.data.ProxyStore.prototype.load,
        TestModel = Ext.define(null, {
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
        }),
        loadStore = function() {
            proxyStoreLoad.apply(this, arguments);

            if (synchronousLoad) {
                this.flushLoad.apply(this, arguments);
            }

            return this;
        },
        view, bufferedRenderer;

    function getData(start, limit) {
        var end = start + limit,
            recs = [],
            i;

        for (i = start; i < end; ++i) {
            recs.push({
                threadid: i,
                title: 'Title' + i
            });
        }

        return recs;
    }

    function satisfyRequests(total) {
        var requests = Ext.Ajax.mockGetAllRequests(),
            request, params, data;

        while (requests.length) {
            request = requests[0];

            params = request.options.params;
            data = getData(params.start, params.limit);

            Ext.Ajax.mockComplete({
                status: 200,
                responseText: Ext.encode({
                    total: total || 5000,
                    data: data
                })
            });

            requests = Ext.Ajax.mockGetAllRequests();
        }
    }

    beforeEach(function() {
        // Override so that we can control asynchronous loading
        Ext.data.ProxyStore.prototype.load = loadStore;
    });

    afterEach(function() {
        // Undo the overrides.
        Ext.data.ProxyStore.prototype.load = proxyStoreLoad;

        grid = store = Ext.destroy(grid, store);
    });

    describe("BufferedStore asynchronous loading timing with rendering and preserveScrollOnReload: false", function() {
        beforeEach(function() {
            MockAjaxManager.addMethods();

            store = new Ext.data.BufferedStore({
                model: TestModel,
                pageSize: 50,
                trailingBufferZone: 50,
                leadingBufferZone: 50,
                proxy: {
                    type: 'ajax',
                    url: 'fakeUrl',
                    reader: {
                        type: 'json',
                        rootProperty: 'data'
                    }
                },
                asynchronousLoad: false
            });
            store.loadPage(1);
            satisfyRequests();

            grid = new Ext.grid.Panel({
                columns: [{
                    text: 'Title',
                    dataIndex: 'title'
                }],
                store: store,
                width: 600,
                height: 300,
                border: false,
                viewConfig: {
                    preserveScrollOnReload: false,
                    mouseOverOutBuffer: 0
                },
                renderTo: document.body,
                selModel: {
                    pruneRemoved: false
                }
            });
            view = grid.getView();
            bufferedRenderer = view.bufferedRenderer;

            // Load inline in the scroll event
            bufferedRenderer.scrollToLoadBuffer = 0;
        });

        afterEach(function() {
            MockAjaxManager.removeMethods();
        });

        it("should refresh from page 1 on buffered store reload with preserveScrollOnReload: false", function() {
            var scrollDone,
                refreshed;

            expect(view.refreshCounter).toBe(1);

            bufferedRenderer.scrollTo(1000, {
                select: true,
                focus: false,   // MUST NOT focus - focus restoration scrolls on refresh
                                // whcih breaks the test expectations
                callback: function() {
                    scrollDone = true;
                }
            });

            waitsFor(function() {
                satisfyRequests();

                return scrollDone;
            }, 'scroll to finish');

            runs(function() {
                store.on({
                    refresh: function() {
                        refreshed = true;
                    },
                    single: true
                });
                store.reload();
            });

            waitsFor(function() {
                satisfyRequests();

                return refreshed;
            }, 'store to reload');

            runs(function() {
                expect(view.refreshCounter).toBe(2);
                expect(view.all.startIndex).toBe(0);
                expect(view.all.endIndex).toBe(bufferedRenderer.viewSize - 1);
            });
        });
    });

    describe("with a non BufferedStore", function() {
        beforeEach(function() {
            MockAjaxManager.addMethods();

            store = new Ext.data.Store({
                model: TestModel,
                proxy: {
                    type: 'ajax',
                    url: 'fakeUrl',
                    reader: {
                        type: 'json',
                        rootProperty: 'data'
                    }
                },
                asynchronousLoad: false
            });
            store.load();
            satisfyRequests();

            grid = new Ext.grid.Panel({
                columns: [{
                    text: 'Title',
                    dataIndex: 'title'
                }],
                store: store,
                width: 600,
                height: 300,
                border: false,
                viewConfig: {
                    preserveScrollOnReload: false
                },
                renderTo: document.body
            });
            view = grid.getView();
            bufferedRenderer = view.bufferedRenderer;

            // Load inline in the scroll event
            bufferedRenderer.scrollToLoadBuffer = 0;
        });

        afterEach(function() {
            MockAjaxManager.removeMethods();
        });

        it("should scroll to top after reload", function() {
            var scrollDone = false;

            bufferedRenderer.scrollTo(1000, {
                select: false,
                focus: false,   // MUST NOT focus - focus restoration scrolls on refresh
                                // whcih breaks the test expectations
                callback: function() {
                    scrollDone = true;
                }
            });

            waitsFor(function() {
                return scrollDone;
            }, 'scroll to finish');

            runs(function() {
                store.reload();
                satisfyRequests();

                expect(grid.getScrollable().getPosition().y).toBe(0);
            });
        });
    });
});

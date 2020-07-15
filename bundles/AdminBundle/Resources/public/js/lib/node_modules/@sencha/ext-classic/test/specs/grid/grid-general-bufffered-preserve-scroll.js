(Ext.isIE8 || Ext.os.is.Android ? xtopSuite : topSuite)("grid-general-buffered-preserve-scroll",
    [false, 'Ext.grid.Panel', 'Ext.data.ArrayStore', 'Ext.data.BufferedStore'],
function() {
    var grid, store,
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
    });

    afterEach(function() {
        // Undo the overrides.
        Ext.data.ProxyStore.prototype.load = proxyStoreLoad;

        grid = store = Ext.destroy(grid, store);
    });

    var scrollbarWidth = Ext.getScrollbarSize().width,
        transformStyleName = 'webkitTransform' in document.documentElement.style ? 'webkitTransform' : 'transform',
        scrollbarsTakeSpace = !!scrollbarWidth,
        // Some tests should only be run if the UI shows space-taking scrollbars.
        // Specifically, those tests which test that the presence or not of a scrollbar in one dimension
        // affects the presence of a scrollbar in the other dimension.
        visibleScrollbarsIt = scrollbarsTakeSpace ? it : xit;

        function getViewTop(el) {
            var dom = Ext.getDom(el),
                transform;

            if (Ext.supports.CssTransforms && !Ext.isIE9m) {
                transform = dom.style[transformStyleName];

                return transform ? parseInt(transform.split(',')[1], 10) : 0;
            }
            else {
                return parseInt(dom.style.top || '0', 10);
            }
        }

    describe("BufferedStore asynchronous loading timing with rendering and preserveScrollOnReload: true", function() {
        var view,
            bufferedRenderer,
            scroller,
            scrollSize,
            scrollEventCount,
            scrollRequestCount,
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
            });

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

        function satisfyRequestsForPages(pages, total) {
            var requests = Ext.Ajax.mockGetAllRequests(),
                i, len, request, params, data;

            for (i = 0, len = requests.length; i < len; i++) {
                request = requests[i];
                params = request.options.params;

                if (Ext.Array.contains(pages, params.page)) {
                    data = getData(params.start, params.limit);

                    Ext.Ajax.mockComplete({
                        status: 200,
                        responseText: Ext.encode({
                            total: total || 5000,
                            data: data
                        })
                    }, request.id);
                }
            }
        }

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
                }
            });
            store.loadPage(1);
            satisfyRequests();

            scrollEventCount = 0;
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
                    preserveScrollOnReload: true,
                    mouseOverOutBuffer: 0,
                    listeners: {
                        scroll: function() {
                            scrollEventCount++;
                        }
                    }
                },
                renderTo: document.body,
                selModel: {
                    pruneRemoved: false
                }
            });
            view = grid.getView();
            bufferedRenderer = view.bufferedRenderer;
            scroller = view.getScrollable();
            scrollSize = (bufferedRenderer.viewSize * 2 + store.leadingBufferZone + store.trailingBufferZone) * bufferedRenderer.rowHeight;

            // Load inline in the scroll event
            bufferedRenderer.scrollToLoadBuffer = 0;

            scrollRequestCount = 0;
        });

        afterEach(function() {
            MockAjaxManager.removeMethods();
        });

        it("should render maintain selection when returning to a page with a previously selected record in it", function() {

            // Select record 0.
            // We plan to evict this page, but maintain that record as selected.
            view.getSelectionModel().select(0);

            // It should tolerate the focused record being evicted from the page cache.
            view.getNavigationModel().setPosition(0, 0);

            // Scroll to new areas of dataset.
            // Satisfy page requests as they arrive so that
            // old pages are evicted.
            waitsFor(function() {
                satisfyRequests();

                if (scrollEventCount === scrollRequestCount) {
                    // Scroll, until page 1 has been evicted
                    if (!store.data.peekPage(1)) {
                        return true;
                    }

                    view.scrollBy(null, 100);
                    scrollRequestCount++;
                }
            }, 'Page one to have been purged from the PageCache', 20000);

            runs(function() {
                scrollEventCount = 0;
                scroller.scrollTo(0, 0);
            });

            waitsFor(function() {
                return scrollEventCount === 1;
            }, 'A scroll event to fire', 20000);
            runs(function() {
                satisfyRequests();

                // First record still selected
                expect(view.all.item(0).hasCls(Ext.baseCSSPrefix + 'grid-item-selected')).toBe(true);
            });
        });

        it('should render page 1, and page 1 should still be in the page cache when returning to page 1 after scrolling down', function() {
            // Scroll to new areas of dataset.
            // Will queue a lot of page requests which we will satisfy in a while
            waitsFor(scroller, function() {
                //  Scroll until we have 20 page requests outstanding
                if (Ext.Ajax.mockGetAllRequests().length > 20) {
                    return true;
                }

                if (scrollEventCount === scrollRequestCount) {
                    view.scrollBy(null, scrollSize);
                    scrollRequestCount++;
                }
            });

            runs(function() {
                scrollEventCount = 0;
                scroller.scrollTo(0, 0);
            });

            waitsFor(function() {
                return scrollEventCount === 1;
            });
            runs(function() {
                satisfyRequests();

                // Page 1 should be rendered at position zero since we scrolled back to the top
                expect(getViewTop(view.body)).toBe(0);

                // Page 1 should still be in the page map, NOT purged out by the arrival of all
                // the other pages from the visited areas of the dataset.
                expect(store.data.hasPage(1)).toBe(true);
            });
        });

        it('should keep Page 1 rendered and in the page cache when returning to page 1 after scrolling down with only buffer zone pages loaded into store during scroll', function() {
            // Scroll to new areas of dataset.
            // Will queue a lot of page requests which we will satisfy in a while
            waitsFor(function() {
                //  Scroll until we have 20 page requests outstanding
                if (Ext.Ajax.mockGetAllRequests().length > 20) {
                    return true;
                }

                if (scrollEventCount === scrollRequestCount) {
                    view.scrollBy(null, scrollSize);
                    scrollRequestCount++;
                }
            });

            runs(function() {
                scrollEventCount = 0;
                scroller.scrollTo(0, 0);
            });

            waitsFor(function() {
                return scrollEventCount === 1;
            });
            runs(function() {
                // Only satisfy requests for non-rendered buffer zone pages so that no rendering is done and
                // page 1 is left undisturbed in the rendered block.
                satisfyRequestsForPages([3, 6, 7, 10, 11, 13, 14, 17, 18, 21, 22, 25]);

                // Page 1 should be rendered at position zero since we scrolled back to the top
                expect(getViewTop(view.body)).toBe(0);

                // Page 1 should still be in the page map, NOT purged out by the arrival of all
                // the other pages from the visited areas of the dataset.
                expect(store.data.hasPage(1)).toBe(true);
            });
        });

        it("should refresh the same rendered block on buffered store reload with preserveScrollOnReload: true", function() {
            var scrollDone,
                refreshed,
                startRow, endRow;

            expect(view.refreshCounter).toBe(1);

            bufferedRenderer.scrollTo(1000, {
                select: true,
                focus: true,
                callback: function() {
                    scrollDone = true;
                }
            });

            waitsFor(function() {
                satisfyRequests();

                return scrollDone;
            }, 'scroll to finish');

            runs(function() {
                startRow = view.all.startIndex;
                endRow = view.all.endIndex;
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
                expect(view.all.startIndex).toBe(startRow);
                expect(view.all.endIndex).toBe(endRow);
            });
        });
    });

});

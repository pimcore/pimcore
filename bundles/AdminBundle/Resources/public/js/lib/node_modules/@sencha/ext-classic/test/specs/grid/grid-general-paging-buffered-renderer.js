topSuite("grid-general-paging-buffered-renderer",
    [false, 'Ext.grid.Panel', 'Ext.data.ArrayStore', 'Ext.toolbar.Paging',
     'Ext.Button'],
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

    describe("paging grid with buffered renderer", function() {
        var grid;

        afterEach(function() {
            grid.destroy();
        });

        it("should refresh the view on each page change", function() {
            var store, ptoolbar,
                refreshCount = 0;

            runs(function() {
                function getRandomDate() {
                    var from = new Date(1900, 0, 1).getTime(),
                        to = new Date().getTime();

                    return new Date(from + Math.random() * (to - from));
                }

                function createFakeData(count) {
                    var firstNames   = ['Ed', 'Tommy', 'Aaron', 'Abe'],
                        lastNames    = ['Spencer', 'Maintz', 'Conran', 'Elias'];

                    var data = [];

                    for (var i = 0; i < count; i++) {
                        var dob = getRandomDate(),
                            firstNameId = Math.floor(Math.random() * firstNames.length),
                            lastNameId  = Math.floor(Math.random() * lastNames.length),
                            name        = Ext.String.format("{0} {1}", firstNames[firstNameId], lastNames[lastNameId]);

                        data.push([name, dob]);
                    }

                    return data;
                }

                // create the Data Store
                store = Ext.create('Ext.data.Store', {
                    fields: [
                        'Name', 'dob'
                    ],
                    autoLoad: true,
                    proxy: {
                        type: 'memory',
                        enablePaging: true,
                        data: createFakeData(100),
                        reader: {
                            type: 'array'
                        }
                    },
                    pageSize: 20
                });

                grid = Ext.create('Ext.grid.Panel', {
                    store: store,
                    columns: [
                        { text: "Name", width: 120, dataIndex: 'Name' },
                        { text: "dob", flex: 1, dataIndex: 'dob' }
                    ],
                    dockedItems: [
                        ptoolbar = Ext.create('Ext.toolbar.Paging', {
                            dock: 'bottom',
                            store: store
                        })
                    ],
                    renderTo: document.body,
                    width: 500,
                    height: 200,
                    plugins: [{
                        ptype: 'bufferedrenderer'
                    }]
                });
            });

            // Wait for first refresh.
            waitsFor(function() {
                return grid.view.all.getCount() === 20;
            }, 'first refresh');

            runs(function() {
                refreshCount = grid.view.refreshCounter;

                grid.view.scrollTo(0, 110);
            });

                // Wait for the scroll event to get into the BufferedRenderer                
            waitsFor(function() {
                return grid.view.getScrollable().getPosition().y >= 100;
            }, 'view to scroll to scrollTop:100');

            runs(function() {
                jasmine.fireMouseEvent(ptoolbar.down('#next').el, 'click');

                // Should be one more page refresh
                expect(grid.view.refreshCounter).toBe(refreshCount + 1);

                // A new full page of 20 records should be there
                expect(grid.view.all.getCount()).toBe(20);
            });
        });
    });
});

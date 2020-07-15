topSuite("Ext.grid.plugin.BufferedRenderer",
    ['Ext.grid.Panel', 'Ext.tree.Panel', 'Ext.grid.feature.Grouping',
     'Ext.grid.column.Widget', 'Ext.ProgressBarWidget', 'Ext.tab.Panel',
     'Ext.window.Window', 'Ext.data.BufferedStore', 'Ext.sparkline.Bar'],
function() {
    var itNotIE8 = Ext.isIE8 ? xit : it,
        store, grid, tree, view, scroller, plugin,
        synchronousLoad = true,
        proxyStoreLoad = Ext.data.ProxyStore.prototype.load,
        loadStore = function() {
            proxyStoreLoad.apply(this, arguments);

            if (synchronousLoad) {
                this.flushLoad.apply(this, arguments);
            }

            return this;
        },
        treeStoreLoad = Ext.data.TreeStore.prototype.load,
        loadTreeStore = function() {
            treeStoreLoad.apply(this, arguments);

            if (synchronousLoad) {
                this.flushLoad.apply(this, arguments);
            }

            return this;
        },
        itIE10p = Ext.isIE9m ? xit : it,
        itNotTouch = jasmine.supportsTouch ? xit : it;

    function createData(total, variableRowHeight, asymmetricRowHeight) {
        var data = [],
            i, len, n;

        for (i = 0, len = total || 100; i < len; i++) {
            n = i + 1;

            data.push({
                field1: variableRowHeight ? ('<div style="height:' + Ext.Number.randomInt(20, 40) + 'px">' + n + '</div>') : asymmetricRowHeight ? 40 : n,
                field2: n,
                field3: n,
                field4: n,
                field5: n
            });
        }

        return data;
    }

    function makeData(n, start) {
        start = start || 1;

        var data = [],
            limit = start + n,
            i;

        for (i = start; i < limit; ++i) {
            data.push({
                id: i,
                name: 'name' + i
            });
        }

        return data;
    }

    function getData(start, limit) {
        var end = start + limit,
            recs = [],
            i;

        for (i = start; i < end; ++i) {
            recs.push({
                id: i,
                namee: 'name' + i
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

    function makeGrid(gridCfg, storeCfg) {
        if (!storeCfg || !storeCfg.store) {
            store = new Ext.data.Store(Ext.apply({
                fields: ['name', 'email', 'phone'],
                groupField: 'name',
                data: [
                    { 'name': 'Lisa', 'email': 'lisa@simpsons.com', 'phone': '555-111-1224', 'age': 14 },
                    { 'name': 'Lisa', 'email': 'aunt_lisa@simpsons.com', 'phone': '555-111-1274', 'age': 34 },
                    { 'name': 'Bart', 'email': 'bart@simpsons.com', 'phone': '555-222-1234', 'age': 12 },
                    { 'name': 'Homer', 'email': 'homer@simpsons.com', 'phone': '555-222-1244', 'age': 44 },
                    { 'name': 'Marge', 'email': 'marge@simpsons.com', 'phone': '555-222-1254', 'age': 41 }
                ],
                autoDestroy: true
            }, storeCfg));
        }
        else {
            store = storeCfg.store;
        }

        grid = new Ext.grid.Panel(Ext.apply({
            columns: [
                { header: 'Name',  dataIndex: 'name', editor: 'textfield' },
                { header: 'Email', dataIndex: 'email', flex: 1,
                    editor: {
                        xtype: 'textfield',
                        allowBlank: false
                    }
                },
                { header: 'Phone', dataIndex: 'phone', editor: 'textfield' },
                { header: 'Age', dataIndex: 'age', editor: 'textfield' }
            ],
            store: store,
            width: 200,
            height: 400,
            renderTo: Ext.getBody()
        }, gridCfg));

        view = grid.view;
        scroller = view.isLockingView ? grid.getScrollable() : view.getScrollable();

        // Extract the buffered renderer from a real TableView, the topmost one might be a Locking pseudo view
        plugin = grid.down('tableview').bufferedRenderer;
    }

    function createTreeData(count) {
        var i = 0,
            j = 0,
            children = [],
            grandKids;

        for (; i < count; i++) {
            grandKids = [];

            for (j = 1; j < 7; j++) {
                grandKids.push({
                    treeData: 'Child of ' + i + ', number ' + j,
                    leaf: true
                });
            }

            children.push({
                treeData: i,
                children: grandKids
            });
        }

        return {
            children: children
        };
    }

    function makeTree(treeCfg, count) {
        store = new Ext.data.TreeStore({
            fields: ['treeData'],
            root: createTreeData(count || 20)
        });

        tree = new Ext.tree.Panel(Ext.apply({
            columns: [{
                xtype: 'treecolumn',
                text: 'Tree Column',
                width: 300,
                dataIndex: 'treeData'
            }],
            height: 800,
            width: 500,
            store: store,
            rootVisible: false,
            animate: false,
            renderTo: Ext.getBody()
        }, treeCfg || {}));

        view = tree.view;
        scroller = view.isLockingView ? tree.getScrollable() : view.getScrollable();

        return tree;
    }

    function completeWithData(data) {
        Ext.Ajax.mockComplete({
            status: 200,
            responseText: Ext.JSON.encode(data)
        });
    }

    beforeEach(function() {
        // Override so that we can control asynchronous loading
        Ext.data.BufferedStore.prototype.load = loadStore;
        Ext.data.ProxyStore.prototype.load = loadStore;
        Ext.data.TreeStore.prototype.load = loadTreeStore;

        MockAjaxManager.addMethods();
    });

    afterEach(function() {
        // Undo the overrides.
        Ext.data.BufferedStore.prototype.load = proxyStoreLoad;
        Ext.data.ProxyStore.prototype.load = proxyStoreLoad;
        Ext.data.TreeStore.prototype.load = treeStoreLoad;

        if (grid) {
            Ext.destroy(grid);
        }

        if (tree) {
            Ext.destroy(tree);
        }

        MockAjaxManager.removeMethods();

        store = grid = tree = view = plugin = null;
    });

    describe('updating scroller when changing width', function() {
        it('should update the horizontal scroll range', function() {
            makeGrid();
            var scroller = view.getScrollable(),
                maxX = scroller.getMaxPosition().x;

            grid.setWidth(grid.getWidth() - 100);

            // The scroll range should have increased
            expect(scroller.getMaxPosition().x).toBe(maxX + 100);
        });
    });

    describe('autogenerating the plugin', function() {
        var BR = Ext.grid.plugin.BufferedRenderer;

        it('should create an instance by default', function() {
            makeGrid();

            expect(plugin instanceof BR).toBe(true);
        });

        it('should not create an instance when turned off', function() {
            makeGrid({
                bufferedRenderer: false
            });

            expect(plugin instanceof BR).toBe(false);
            expect(plugin).toBeUndefined();
        });

        describe('locking grids', function() {
            var normal, locked;

            afterEach(function() {
                normal = locked = null;
            });

            describe('init', function() {
                function runTests(useBR) {
                    var not = useBR ? 'not' : '';

                    describe('buffered rendering = ' + useBR, function() {
                        beforeEach(function() {
                            makeGrid({
                                bufferedRenderer: useBR,
                                columns: [
                                    { header: 'Name',  dataIndex: 'name', editor: 'textfield' },
                                    { header: 'Phone', dataIndex: 'phone', editor: 'textfield', locked: true },
                                    { header: 'Age', dataIndex: 'age', editor: 'textfield' }
                                ]
                            });

                            normal = grid.normalGrid.bufferedRenderer;
                            locked = grid.lockedGrid.bufferedRenderer;
                        });

                        it('should ' + not + ' create an instance on the ownerGrid for locking grids', function() {
                            plugin = grid.bufferedRenderer;
                            expect(plugin instanceof BR).toBe(false);
                        });

                        it('should ' + not + ' create an instance by default on each child grid for locking grids', function() {
                            expect(normal instanceof BR).toBe(useBR);
                            expect(locked instanceof BR).toBe(useBR);
                        });
                    });
                }

                runTests(true);
                runTests(false);
            });

            describe('syncing locking partners when scrolling', function() {
                beforeEach(function() {
                    makeGrid({
                        columns: [
                            { header: 'Name',  dataIndex: 'name', editor: 'textfield', locked: true },
                            { header: 'Email', dataIndex: 'email', flex: 1,
                                editor: {
                                    xtype: 'textfield',
                                    allowBlank: false
                                }
                            },
                            { header: 'Phone', dataIndex: 'phone', editor: 'textfield' },
                            { header: 'Age', dataIndex: 'age', editor: 'textfield' }
                        ]
                    }, {
                        data: makeData(1000)
                    });

                    normal = grid.normalGrid.bufferedRenderer;
                    locked = grid.lockedGrid.bufferedRenderer;
                });

                it('should fetch the range', function() {
                    spyOn(normal, 'onRangeFetched').andCallThrough();
                    spyOn(locked, 'onRangeFetched').andCallThrough();

                    normal.scrollTo(500);

                    expect(normal.onRangeFetched).toHaveBeenCalled();

                    // Mirroring of onRangeFetched directly manipulates partner view
                    expect(locked.onRangeFetched).not.toHaveBeenCalled();

                    // And locked must stay in sync.
                    expect(locked.view.all.startIndex).toBe(normal.view.all.startIndex);
                    expect(locked.view.all.endIndex).toBe(normal.view.all.endIndex);
                });

                it('should sync the view els', function() {
                    normal.scrollTo(500);

                    expect(normal.bodyTop).toBe(locked.bodyTop);
                });
            });

            describe('locked partner with no column configuration', function() {
                beforeEach(function() {
                    makeGrid({
                        enableLocking: true,
                        columns: [
                            { header: 'Name',  dataIndex: 'name', editor: 'textfield' },
                            { header: 'Email', dataIndex: 'email', flex: 1,
                                editor: {
                                    xtype: 'textfield',
                                    allowBlank: false
                                }
                            },
                            { header: 'Phone', dataIndex: 'phone', editor: 'textfield' },
                            { header: 'Age', dataIndex: 'age', editor: 'textfield' }
                        ]
                    }, {
                        data: makeData(1000)
                    });

                    normal = grid.normalGrid.bufferedRenderer;
                });

                it('should not throw range error from locking partner', function() {
                    expect(function() {
                        normal.scrollTo(500);
                    }).not.toThrow();
                });
            });

            describe('Load requests during scrolling', function() {
                var scrollToLoadBufferValue,
                    Person = Ext.define(null, {
                        extend: 'Ext.data.Model',
                        fields: ['name'],
                        proxy: {
                            type: 'ajax',
                            url: '/foo',
                            reader: {
                                rootProperty: 'data'
                            }
                        }
                    });

                beforeEach(function() {
                    scrollToLoadBufferValue = Ext.grid.plugin.BufferedRenderer.prototype.scrollToLoadBuffer;

                    // Make the timeout from attemptLoad call to the doAttemptLoad ten seconds
                    // The doAttemptLoad DelayedTask should not fire between scroll events which take place
                    // every 50 milliseconds
                    Ext.grid.plugin.BufferedRenderer.prototype.scrollToLoadBuffer = 10000;
                    makeGrid({
                        columns: [
                            { header: 'Name',  dataIndex: 'name', editor: 'textfield' },
                            { header: 'Phone', dataIndex: 'phone', editor: 'textfield', locked: true },
                            { header: 'Age', dataIndex: 'age', editor: 'textfield' }
                        ]
                    }, {
                        store: new Ext.data.BufferedStore({
                            model: Person,
                            leadingBufferZone: 300,
                            pageSize: 100,
                            autoDestroy: true
                        })
                    });

                    // Tell the BufferedRenderer that there are 1000 rows.
                    // We plan not to satisfy any requests from now on so that
                    // on scroll, an attemptLoad call will be made.
                    // the timeout to doAttemptLoad should not expire during the scroll operation
                    store.load();
                    completeWithData({
                        total: 1000,
                        data: makeData(100)
                    });
                    completeWithData({
                        total: 1000,
                        data: makeData(100, 101)
                    });
                    completeWithData({
                        total: 1000,
                        data: makeData(100, 201)
                    });
                    completeWithData({
                        total: 1000,
                        data: makeData(100, 301)
                    });
                });
                afterEach(function() {
                    Ext.grid.plugin.BufferedRenderer.prototype.scrollToLoadBuffer = scrollToLoadBufferValue;
                });
                it('should not have time between scroll events to fire off any requests', function() {
                    spyOn(plugin, 'doAttemptLoad');

                    // Scroll to close enough to the end in a controlled manner.
                    // The doAttemptLoad timer should not timeout and fire off a page request between each scroll.
                    jasmine.waitsForScroll(scroller, function(scroller, x, y) {
                        // Allow 5px wiggle room to detect that we're at the end of the scroll range
                        if (Ext.Number.isEqual(y, scroller.getMaxUserPosition().y, 5)) {
                            return true;
                        }

                        scroller.scrollBy(0, 100);
                    }, 'grid to scroll to end', 40000);

                    // The atteptLoad timer must never have fired during the scroll.
                    runs(function() {
                        expect(plugin.doAttemptLoad.callCount).toBe(0);
                    });
                });
            });
        });
    });

    describe("Less data than the computed view size", function() {
        it("should add new rows at top while scrolled to bottom", function() {
            var Person = Ext.define(null, {
                extend: 'Ext.data.Model',
                fields: ['name'],
                proxy: {
                    type: 'ajax',
                    url: '/foo',
                    reader: {
                        rootProperty: 'data'
                    }
                }
            });

            store = new Ext.data.Store({
                model: Person,
                data: makeData(12)
            });

            grid = new Ext.grid.Panel({
                width: 600,
                height: 305,
                store: store,
                deferRowRender: false,
                columns: [{
                    dataIndex: 'id',
                    renderer: function(v) {
                        return '<span style="line-height:25px">' + v + '</span>';
                    },
                    producesHTML: true
                }, {
                    dataIndex: 'name'
                }],
                renderTo: Ext.getBody()
            });
            view = grid.getView();
            scroller = view.isLockingView ? grid.getScrollable() : view.getScrollable();

            // Wait until known correct condition is met.
            // Timeout === test failure.
            jasmine.waitsForScroll(scroller, function() {
                if (view.all.endIndex === store.getCount() - 1) {
                    return true;
                }

                scroller.scrollBy(0, 10);
            }, 'view is scrolled to the last record');

            runs(function() {
                store.insert(0, { id: 666, name: 'Old Nick' });
                view.scrollTo(0, 0);

                var r0 = view.all.item(0, true);

                // Must have been rendered
                expect(r0).not.toBeNull();

                // The new row zero must have been rendered.
                expect((r0.innerText || r0.textContent).replace(/[\r\n\t]/g, '')).toBe("666Old Nick");
            });
        });
    });

    describe("basic functionality with a buffered store", function() {
        itNotIE8("should render rows in order", function() {
            var Person = Ext.define(null, {
                extend: 'Ext.data.Model',
                fields: ['name'],
                proxy: {
                    type: 'ajax',
                    url: '/foo',
                    reader: {
                        rootProperty: 'data'
                    }
                }
            });

            store = new Ext.data.BufferedStore({
                model: Person,
                leadingBufferZone: 300,
                pageSize: 100,
                autoDestroy: true
            });

            grid = new Ext.grid.Panel({
                width: 800,
                height: 500,
                store: store,
                deferRowRender: false,
                columns: [{
                    dataIndex: 'id'
                }, {
                    dataIndex: 'name'
                }],
                renderTo: Ext.getBody()
            });
            store.load();
            completeWithData({
                total: 5000,
                data: makeData(100)
            });
            completeWithData({
                total: 5000,
                data: makeData(100, 101)
            });
            completeWithData({
                total: 5000,
                data: makeData(100, 201)
            });
            completeWithData({
                total: 5000,
                data: makeData(100, 301)
            });
            var view = grid.getView(),
                rows = view.all;

            scroller = view.isLockingView ? grid.getScrollable() : view.getScrollable();

            // Wait until known correct condition is met.
            // Timeout === test failure.
            jasmine.waitsForScroll(scroller, function() {
                if (rows.startIndex <= 100 && rows.endIndex >= 100) {
                    return true;
                }

                scroller.scrollBy(0, 100);
            }, 'View to scroll record id 100 into the rendered block', 30000);

            runs(function() {
                var nodes = view.getNodes(),
                    len = nodes.length,
                    offset = rows.startIndex + 1,
                    i, rec;

                expect(len).toBe(view.bufferedRenderer.viewSize);

                for (i = 0; i < len; ++i) {
                    rec = view.getRecord(nodes[i]);
                    expect(rec.getId()).toBe(i + offset);
                }

            });
        });

        // EXTJS-16140
        it("should scroll to the bottom of a locking grid with no error", function() {
            var Person = Ext.define(null, {
                extend: 'Ext.data.Model',
                fields: ['name'],
                proxy: {
                    type: 'ajax',
                    url: '/foo',
                    reader: {
                        rootProperty: 'data'
                    }
                }
            }),
            lockedView,
            normalView,
            maxScroll;

            store = new Ext.data.BufferedStore({
                model: Person,
                leadingBufferZone: 300,
                pageSize: 100,
                autoDestroy: true
            });

            grid = new Ext.grid.Panel({
                width: 800,
                height: 500,
                store: store,
                deferRowRender: false,
                columns: [{
                    dataIndex: 'id',
                    locked: true
                }, {
                    dataIndex: 'name'
                }],
                renderTo: Ext.getBody()
            });
            lockedView = grid.lockedGrid.view;
            normalView = grid.normalGrid.view;

            // Make the buffered renderers respond IMMEDIATELY to the scroll event, so that
            // the waits(100) will be enough to wait for the newly scrolled-to region to be rendered.
            lockedView.bufferedRenderer.scrollToLoadBuffer = normalView.bufferedRenderer.scrollToLoadBuffer = 0;

            store.load();
            completeWithData({
                total: 5000,
                data: makeData(100)
            });

            maxScroll = grid.getScrollable().getMaxPosition().y;
            grid.getScrollable().scrollTo(null, maxScroll);

            // Important: Simulate Ajax delay before returning data
            waits(100);
            runs(function() {
                satisfyRequests();
            });

            // Both views must render up to the last row with no error thrown
            waitsFor(function() {
                return lockedView.all.endIndex === 4999 && normalView.all.endIndex === 4999;
            }, 'Both views to render the last renderable block', 30000, Ext.isIE ? 100 : null);
        });

        // EXTJS-17053
        it('should maintain synchronization when scrolling locked, variable row height grid with keyboard', function() {
            store = Ext.create('Ext.data.Store', {
                idProperty: 'index',
                fields: ['index', 'name', 'email', 'phone', 'isActive', 'eyeColor', 'company', 'gender'],
                autoLoad: true,
                data: [{
                    "_id": "54ff69d95aa43325cc4eee9f",
                    "index": 0,
                    "guid": "48c42a75-09a7-4671-86a1-e9a486bee6ab",
                    "isActive": true,
                    "balance": "$3,457.90",
                    "age": 20,
                    "eyeColor": "brown",
                    "name": "Louella Morrison",
                    "gender": "female",
                    "company": "ZBOO",
                    "email": "louellamorrison@zboo.com",
                    "phone": "+1 (803) 483-2686"
                }, {
                    "_id": "54ff69d918616dbc093ca6cd",
                    "index": 1,
                    "guid": "17b3f923-96b5-40c9-b3c1-721485e5bf80",
                    "isActive": true,
                    "balance": "$1,377.85",
                    "age": 20,
                    "eyeColor": "brown",
                    "name": "Madden Coffey",
                    "gender": "male",
                    "company": "COMDOM",
                    "email": "maddencoffey@comdom.com",
                    "phone": "+1 (850) 534-2345"
                }, {
                    "_id": "54ff69d91a4917f9643178b0",
                    "index": 2,
                    "guid": "f62da049-ab17-49fd-9f4c-3439d33cbb93",
                    "isActive": false,
                    "balance": "$3,798.71",
                    "age": 22,
                    "eyeColor": "blue",
                    "name": "Chase Crawford",
                    "gender": "male",
                    "company": "PYRAMAX",
                    "email": "chasecrawford@pyramax.com",
                    "phone": "+1 (944) 494-2920"
                }, {
                    "_id": "54ff69d9a63b6103f9e1530f",
                    "index": 3,
                    "guid": "3640c402-f878-4f97-bbda-564de91d2dde",
                    "isActive": true,
                    "balance": "$2,480.02",
                    "age": 39,
                    "eyeColor": "blue",
                    "name": "Johanna Pollard",
                    "gender": "female",
                    "company": "ZEROLOGY",
                    "email": "johannapollard@zerology.com",
                    "phone": "+1 (813) 512-3311"
                }, {
                    "_id": "54ff69d9048722389ffde4bb",
                    "index": 4,
                    "guid": "361089ba-e6dc-4701-b316-c7e977b21cb2",
                    "isActive": true,
                    "balance": "$3,973.38",
                    "age": 20,
                    "eyeColor": "brown",
                    "name": "Malone Bentley",
                    "gender": "male",
                    "company": "DIGIPRINT",
                    "email": "malonebentley@digiprint.com",
                    "phone": "+1 (905) 435-2056"
                }, {
                    "_id": "54ff69d9f75a66fb8e08b335",
                    "index": 5,
                    "guid": "4cec529a-232a-4d5f-bedc-6141adc9f2fa",
                    "isActive": true,
                    "balance": "$3,956.60",
                    "age": 21,
                    "eyeColor": "green",
                    "name": "Jennings Rodgers",
                    "gender": "male",
                    "company": "LUDAK",
                    "email": "jenningsrodgers@ludak.com",
                    "phone": "+1 (833) 543-2230"
                }, {
                    "_id": "54ff69d974c1757e026721cb",
                    "index": 6,
                    "guid": "6ce0c1e1-adc6-4cd0-b9c9-e2214e63aa8f",
                    "isActive": true,
                    "balance": "$2,874.71",
                    "age": 40,
                    "eyeColor": "green",
                    "name": "Mosley Mcgowan",
                    "gender": "male",
                    "company": "AMTAP",
                    "email": "mosleymcgowan@amtap.com",
                    "phone": "+1 (990) 486-3229"
                }, {
                    "_id": "54ff69d949a3873336e2e446",
                    "index": 7,
                    "guid": "0f88d544-4aa0-4a05-a236-363c64b40988",
                    "isActive": false,
                    "balance": "$3,277.11",
                    "age": 28,
                    "eyeColor": "green",
                    "name": "Patton Whitaker",
                    "gender": "male",
                    "company": "IMMUNICS",
                    "email": "pattonwhitaker@immunics.com",
                    "phone": "+1 (944) 557-2615"
                }, {
                    "_id": "54ff69d917f9642ab989e999",
                    "index": 8,
                    "guid": "6577bb0c-f7ed-495f-a3db-7f7d35f6fe49",
                    "isActive": false,
                    "balance": "$1,091.47",
                    "age": 35,
                    "eyeColor": "green",
                    "name": "Neal Rivera",
                    "gender": "male",
                    "company": "NETILITY",
                    "email": "nealrivera@netility.com",
                    "phone": "+1 (816) 590-2358"
                }, {
                    "_id": "54ff69d94573c1c04f0a9940",
                    "index": 9,
                    "guid": "29be9274-d3cb-461f-a565-2374768689df",
                    "isActive": false,
                    "balance": "$1,885.75",
                    "age": 25,
                    "eyeColor": "brown",
                    "name": "May Barron",
                    "gender": "male",
                    "company": "TELEPARK",
                    "email": "maybarron@telepark.com",
                    "phone": "+1 (958) 486-2915"
                }, {
                    "_id": "54ff69d9c0e2f4aee9180984",
                    "index": 10,
                    "guid": "479320d7-70ef-4f55-99b9-f3ef3a76b72e",
                    "isActive": false,
                    "balance": "$2,031.57",
                    "age": 36,
                    "eyeColor": "brown",
                    "name": "Janna Howell",
                    "gender": "female",
                    "company": "PYRAMIS",
                    "email": "jannahowell@pyramis.com",
                    "phone": "+1 (823) 423-2342"
                }, {
                    "_id": "54ff69d96e7f08b674de7cb9",
                    "index": 11,
                    "guid": "9f6df0e4-b92c-4272-ad75-981305e9bb09",
                    "isActive": true,
                    "balance": "$2,279.79",
                    "age": 33,
                    "eyeColor": "green",
                    "name": "Nieves Short",
                    "gender": "male",
                    "company": "PETICULAR",
                    "email": "nievesshort@peticular.com",
                    "phone": "+1 (904) 556-3401"
                }, {
                    "_id": "54ff69d9fb987df7ecf3d8ab",
                    "index": 12,
                    "guid": "32c80cfd-d880-48a6-a3b0-e687c1e52b30",
                    "isActive": false,
                    "balance": "$3,830.03",
                    "age": 26,
                    "eyeColor": "green",
                    "name": "Tania Gillespie",
                    "gender": "female",
                    "company": "AUSTEX",
                    "email": "taniagillespie@austex.com",
                    "phone": "+1 (969) 527-3521"
                }, {
                    "_id": "54ff69d9f586c6e7e9a08b4b",
                    "index": 13,
                    "guid": "e6a006c4-1d5e-4a09-9dd6-496426fc4982",
                    "isActive": false,
                    "balance": "$3,411.76",
                    "age": 32,
                    "eyeColor": "blue",
                    "name": "Joyner Suarez",
                    "gender": "male",
                    "company": "GEOFARM",
                    "email": "joynersuarez@geofarm.com",
                    "phone": "+1 (879) 571-3671"
                }, {
                    "_id": "54ff69d94eaa44d993966048",
                    "index": 14,
                    "guid": "00927bfa-dfd7-4850-bfd7-a2c25c476327",
                    "isActive": true,
                    "balance": "$2,402.44",
                    "age": 35,
                    "eyeColor": "brown",
                    "name": "Hull Cooley",
                    "gender": "male",
                    "company": "ENERVATE",
                    "email": "hullcooley@enervate.com",
                    "phone": "+1 (910) 476-3577"
                }, {
                    "_id": "54ff69d9b341bfef697e75ac",
                    "index": 15,
                    "guid": "cd6a241c-35a4-4038-ae01-d32640719dc7",
                    "isActive": false,
                    "balance": "$2,889.76",
                    "age": 28,
                    "eyeColor": "green",
                    "name": "Cortez Fleming",
                    "gender": "male",
                    "company": "CIPROMOX",
                    "email": "cortezfleming@cipromox.com",
                    "phone": "+1 (964) 460-3159"
                }, {
                    "_id": "54ff69d9a1f835d92cb8a017",
                    "index": 16,
                    "guid": "45ad04ed-c154-4f16-a60b-d091e2ab8c13",
                    "isActive": true,
                    "balance": "$1,994.50",
                    "age": 21,
                    "eyeColor": "green",
                    "name": "Hazel Rodriguez",
                    "gender": "female",
                    "company": "SILODYNE",
                    "email": "hazelrodriguez@silodyne.com",
                    "phone": "+1 (962) 492-3107"
                }, {
                    "_id": "54ff69d93989153e00dc5f02",
                    "index": 17,
                    "guid": "b2a702d1-b47e-4f2b-98f2-4ab2e70b126f",
                    "isActive": true,
                    "balance": "$2,826.88",
                    "age": 30,
                    "eyeColor": "green",
                    "name": "Keri Pearson",
                    "gender": "female",
                    "company": "CENTICE",
                    "email": "keripearson@centice.com",
                    "phone": "+1 (858) 417-3541"
                }, {
                    "_id": "54ff69d9377beb85134e2761",
                    "index": 18,
                    "guid": "9bbb9bd7-d517-4bbb-a9b6-76f4fab0e5ab",
                    "isActive": false,
                    "balance": "$1,119.17",
                    "age": 24,
                    "eyeColor": "brown",
                    "name": "Daisy Mcconnell",
                    "gender": "female",
                    "company": "MONDICIL",
                    "email": "daisymcconnell@mondicil.com",
                    "phone": "+1 (836) 470-3995"
                }, {
                    "_id": "54ff69d999e3637d8287287f",
                    "index": 19,
                    "guid": "c2dbece0-7554-4666-9378-805a625789db",
                    "isActive": false,
                    "balance": "$3,855.83",
                    "age": 30,
                    "eyeColor": "blue",
                    "name": "Michelle Espinoza",
                    "gender": "female",
                    "company": "STEELFAB",
                    "email": "michelleespinoza@steelfab.com",
                    "phone": "+1 (882) 508-2376"
                }, {
                    "_id": "54ff69d93826cfb24af88797",
                    "index": 20,
                    "guid": "20be5f38-dc45-4a2f-8889-e9ba5ade8bed",
                    "isActive": false,
                    "balance": "$1,366.06",
                    "age": 39,
                    "eyeColor": "blue",
                    "name": "Wall Sears",
                    "gender": "male",
                    "company": "PLAYCE",
                    "email": "wallsears@playce.com",
                    "phone": "+1 (846) 505-3782"
                }, {
                    "_id": "54ff69d97f6a23afdd06849c",
                    "index": 21,
                    "guid": "66fc61ba-38ef-4d49-862d-eb7d5fa68245",
                    "isActive": false,
                    "balance": "$2,379.73",
                    "age": 20,
                    "eyeColor": "green",
                    "name": "Caldwell Cain",
                    "gender": "male",
                    "company": "ORBIXTAR",
                    "email": "caldwellcain@orbixtar.com",
                    "phone": "+1 (831) 456-3297"
                }, {
                    "_id": "54ff69d970154f7ac17b1438",
                    "index": 22,
                    "guid": "85b6d275-3bff-4475-8c42-0c4f8ec2d385",
                    "isActive": true,
                    "balance": "$2,572.21",
                    "age": 20,
                    "eyeColor": "green",
                    "name": "Viola Mckay",
                    "gender": "female",
                    "company": "SPRINGBEE",
                    "email": "violamckay@springbee.com",
                    "phone": "+1 (901) 412-3479"
                }, {
                    "_id": "54ff69d98ac67b26bce2d4a8",
                    "index": 23,
                    "guid": "1d7460af-09a7-479a-8476-c3187a85ba8d",
                    "isActive": false,
                    "balance": "$2,467.39",
                    "age": 40,
                    "eyeColor": "blue",
                    "name": "Gutierrez Conway",
                    "gender": "male",
                    "company": "ULTRASURE",
                    "email": "gutierrezconway@ultrasure.com",
                    "phone": "+1 (939) 438-3340"
                }, {
                    "_id": "54ff69d93a9b81bdb7af6473",
                    "index": 24,
                    "guid": "2da97cff-a16c-47a2-b9b0-d05d7ce8655d",
                    "isActive": false,
                    "balance": "$2,496.66",
                    "age": 20,
                    "eyeColor": "brown",
                    "name": "Chandler Schmidt",
                    "gender": "male",
                    "company": "ZANITY",
                    "email": "chandlerschmidt@zanity.com",
                    "phone": "+1 (822) 461-2247"
                }, {
                    "_id": "54ff69d99e5b0a9b8db8da2b",
                    "index": 26,
                    "guid": "b8081e85-d0a9-4575-9fdd-83baa0cfe79b",
                    "isActive": false,
                    "balance": "$3,044.12",
                    "age": 21,
                    "eyeColor": "brown",
                    "name": "Bradshaw Ware",
                    "gender": "male",
                    "company": "BEDDER",
                    "email": "bradshawware@bedder.com",
                    "phone": "+1 (932) 496-3718"
                }, {
                    "_id": "54ff69d9a9827e6a14480e37",
                    "index": 27,
                    "guid": "f0d49a25-fa17-4319-9f6a-9e88bb974755",
                    "isActive": true,
                    "balance": "$1,799.46",
                    "age": 38,
                    "eyeColor": "green",
                    "name": "Krystal Bush",
                    "gender": "female",
                    "company": "ZOLAREX",
                    "email": "krystalbush@zolarex.com",
                    "phone": "+1 (928) 577-3693"
                }, {
                    "_id": "54ff69d97e57e2cab6272464",
                    "index": 28,
                    "guid": "40860305-ba5a-44ec-9783-21d4a1a4927b",
                    "isActive": true,
                    "balance": "$1,856.24",
                    "age": 20,
                    "eyeColor": "green",
                    "name": "Madeline Grant",
                    "gender": "female",
                    "company": "APEXIA",
                    "email": "madelinegrant@apexia.com",
                    "phone": "+1 (895) 521-2877"
                }, {
                    "_id": "54ff69d9431984c9fdb6f25a",
                    "index": 29,
                    "guid": "36d78b3f-a9dd-4180-bb66-c12a1147e92d",
                    "isActive": true,
                    "balance": "$3,971.70",
                    "age": 40,
                    "eyeColor": "brown",
                    "name": "Flora Ortiz",
                    "gender": "female",
                    "company": "ACCUSAGE",
                    "email": "floraortiz@accusage.com",
                    "phone": "+1 (992) 561-2107"
                }, {
                    "_id": "54ff69d9c7c52140a4572361",
                    "index": 30,
                    "guid": "ba91387f-cab7-4487-a650-1bd94a650306",
                    "isActive": false,
                    "balance": "$1,408.31",
                    "age": 37,
                    "eyeColor": "brown",
                    "name": "Bobbie Atkins",
                    "gender": "female",
                    "company": "GRAINSPOT",
                    "email": "bobbieatkins@grainspot.com",
                    "phone": "+1 (840) 497-2564"
                }, {
                    "_id": "54ff69d982ac77cad23cb28d",
                    "index": 31,
                    "guid": "7068b1b5-cb81-4127-b252-ccc3ff8d3b5a",
                    "isActive": true,
                    "balance": "$3,375.36",
                    "age": 39,
                    "eyeColor": "brown",
                    "name": "Mayer Osborne",
                    "gender": "male",
                    "company": "DEEPENDS",
                    "email": "mayerosborne@deepends.com",
                    "phone": "+1 (952) 498-3050"
                }, {
                    "_id": "54ff69d99aefdc9cdd70a0df",
                    "index": 32,
                    "guid": "087771d5-4f39-46aa-bad7-7a051e807770",
                    "isActive": true,
                    "balance": "$3,345.55",
                    "age": 38,
                    "eyeColor": "blue",
                    "name": "Sullivan Gibson",
                    "gender": "male",
                    "company": "AQUASURE",
                    "email": "sullivangibson@aquasure.com",
                    "phone": "+1 (833) 463-3464"
                }, {
                    "_id": "54ff69d970a46678753362fc",
                    "index": 33,
                    "guid": "23e28ddc-a102-4f7c-b53b-4e3b8e45c64e",
                    "isActive": false,
                    "balance": "$2,521.22",
                    "age": 28,
                    "eyeColor": "green",
                    "name": "Nikki Whitley",
                    "gender": "female",
                    "company": "FUTURIS",
                    "email": "nikkiwhitley@futuris.com",
                    "phone": "+1 (883) 561-2974"
                }, {
                    "_id": "54ff69d9f7ca2fa585ecbac6",
                    "index": 34,
                    "guid": "b711bd69-e672-48f5-8138-d3120bbedb40",
                    "isActive": true,
                    "balance": "$2,388.02",
                    "age": 21,
                    "eyeColor": "green",
                    "name": "Juana Doyle",
                    "gender": "female",
                    "company": "HOUSEDOWN",
                    "email": "juanadoyle@housedown.com",
                    "phone": "+1 (847) 428-3271"
                }, {
                    "_id": "54ff69d9295e29d7b81a31be",
                    "index": 35,
                    "guid": "d393a87f-0789-4762-8ab8-d0fb66fc0fcb",
                    "isActive": true,
                    "balance": "$1,126.53",
                    "age": 32,
                    "eyeColor": "green",
                    "name": "Marci Shaffer",
                    "gender": "female",
                    "company": "TRASOLA",
                    "email": "marcishaffer@trasola.com",
                    "phone": "+1 (929) 502-2533"
                }, {
                    "_id": "54ff69d9d8f81e674722a7d5",
                    "index": 36,
                    "guid": "281bbeea-5bef-4c37-8e2a-22481c9616c3",
                    "isActive": false,
                    "balance": "$2,616.22",
                    "age": 26,
                    "eyeColor": "green",
                    "name": "Maynard Watts",
                    "gender": "male",
                    "company": "AEORA",
                    "email": "maynardwatts@aeora.com",
                    "phone": "+1 (896) 418-3281"
                }, {
                    "_id": "54ff69d98f504a9e6c63dedb",
                    "index": 37,
                    "guid": "48698e76-8996-40d2-9abe-3fdd8fe98cf7",
                    "isActive": true,
                    "balance": "$3,441.08",
                    "age": 21,
                    "eyeColor": "brown",
                    "name": "Ollie Mooney",
                    "gender": "female",
                    "company": "ACCUPRINT",
                    "email": "olliemooney@accuprint.com",
                    "phone": "+1 (811) 512-3357"
                }, {
                    "_id": "54ff69d9fc55f5cd34359bf9",
                    "index": 38,
                    "guid": "495c9d9e-95d8-48a2-83b4-c0f314f7aa26",
                    "isActive": true,
                    "balance": "$1,134.21",
                    "age": 30,
                    "eyeColor": "green",
                    "name": "Mattie Collins",
                    "gender": "female",
                    "company": "EARGO",
                    "email": "mattiecollins@eargo.com",
                    "phone": "+1 (800) 463-2474"
                }, {
                    "_id": "54ff69d94692077fe1587d2b",
                    "index": 39,
                    "guid": "9c259004-63be-4056-b291-7989cd582d41",
                    "isActive": true,
                    "balance": "$1,429.64",
                    "age": 23,
                    "eyeColor": "brown",
                    "name": "Weeks Mcdowell",
                    "gender": "male",
                    "company": "ORBAXTER",
                    "email": "weeksmcdowell@orbaxter.com",
                    "phone": "+1 (913) 562-2677"
                }, {
                    "_id": "54ff69d95c44a1cefcb13a58",
                    "index": 40,
                    "guid": "44fa1564-9ef2-4503-b887-5f0f282b1bd3",
                    "isActive": false,
                    "balance": "$1,340.36",
                    "age": 30,
                    "eyeColor": "brown",
                    "name": "Hammond Valencia",
                    "gender": "male",
                    "company": "IZZBY",
                    "email": "hammondvalencia@izzby.com",
                    "phone": "+1 (899) 577-2537"
                }, {
                    "_id": "54ff69d9c513f5b3c8ba15d7",
                    "index": 41,
                    "guid": "70c237d2-4a51-4b41-a5e2-e3bc1def56c5",
                    "isActive": true,
                    "balance": "$2,979.67",
                    "age": 39,
                    "eyeColor": "brown",
                    "name": "Ramona Fitzgerald",
                    "gender": "female",
                    "company": "GOLISTIC",
                    "email": "ramonafitzgerald@golistic.com",
                    "phone": "+1 (965) 432-2851"
                }, {
                    "_id": "54ff69d9affa1d1749cd11d5",
                    "index": 42,
                    "guid": "80ef3c8e-b6ca-4048-9920-e651fa2a11b0",
                    "isActive": false,
                    "balance": "$3,671.11",
                    "age": 27,
                    "eyeColor": "blue",
                    "name": "Hyde Alford",
                    "gender": "male",
                    "company": "COFINE",
                    "email": "hydealford@cofine.com",
                    "phone": "+1 (806) 588-3800"
                }, {
                    "_id": "54ff69d9bd4f8a5bc1e34937",
                    "index": 43,
                    "guid": "3d5ed46a-0b40-4e2d-8438-ada9007f4d49",
                    "isActive": true,
                    "balance": "$1,051.71",
                    "age": 36,
                    "eyeColor": "brown",
                    "name": "Sonia Salazar",
                    "gender": "female",
                    "company": "RENOVIZE",
                    "email": "soniasalazar@renovize.com",
                    "phone": "+1 (855) 547-2046"
                }, {
                    "_id": "54ff69d9c59ba53ca1b15c7f",
                    "index": 44,
                    "guid": "fe908aad-5474-4767-baf4-66a4e9fcf106",
                    "isActive": true,
                    "balance": "$3,531.45",
                    "age": 21,
                    "eyeColor": "blue",
                    "name": "Johnston Nolan",
                    "gender": "male",
                    "company": "VELOS",
                    "email": "johnstonnolan@velos.com",
                    "phone": "+1 (950) 510-3191"
                }, {
                    "_id": "54ff69d96252c760ec1787a4",
                    "index": 45,
                    "guid": "efd7b2aa-804b-4463-a1e4-7931bcac6a18",
                    "isActive": true,
                    "balance": "$2,238.60",
                    "age": 40,
                    "eyeColor": "green",
                    "name": "Salazar Walters",
                    "gender": "male",
                    "company": "GEEKNET",
                    "email": "salazarwalters@geeknet.com",
                    "phone": "+1 (834) 510-2779"
                }, {
                    "_id": "54ff69d9672f19a45f583e87",
                    "index": 46,
                    "guid": "25d3dddd-3983-49ae-b7f4-224e73bdacd9",
                    "isActive": true,
                    "balance": "$2,092.02",
                    "age": 21,
                    "eyeColor": "brown",
                    "name": "Emma Mcfarland",
                    "gender": "female",
                    "company": "COWTOWN",
                    "email": "emmamcfarland@cowtown.com",
                    "phone": "+1 (858) 410-3273"
                }, {
                    "_id": "54ff69d902facac3a9fc9fbf",
                    "index": 47,
                    "guid": "e22f488c-a5ad-49d3-b9ec-f3dd9f287318",
                    "isActive": false,
                    "balance": "$1,199.34",
                    "age": 39,
                    "eyeColor": "blue",
                    "name": "Lisa Sutton",
                    "gender": "female",
                    "company": "EXTRAGEN",
                    "email": "lisasutton@extragen.com",
                    "phone": "+1 (815) 583-2428"
                }, {
                    "_id": "54ff69d958481e8cde608526",
                    "index": 48,
                    "guid": "aa976349-e7dc-4a38-aae7-e497c986c288",
                    "isActive": true,
                    "balance": "$3,680.82",
                    "age": 37,
                    "eyeColor": "blue",
                    "name": "Blackburn Ingram",
                    "gender": "male",
                    "company": "EXTREMO",
                    "email": "blackburningram@extremo.com",
                    "phone": "+1 (800) 407-3564"
                }, {
                    "_id": "54ff69d9e28a5207c6ce32f2",
                    "index": 49,
                    "guid": "e95d0949-d9e5-4665-bee0-29697d6b6e61",
                    "isActive": true,
                    "balance": "$3,399.52",
                    "age": 24,
                    "eyeColor": "blue",
                    "name": "Preston Hardy",
                    "gender": "male",
                    "company": "PROXSOFT",
                    "email": "prestonhardy@proxsoft.com",
                    "phone": "+1 (960) 444-3169"
                }, {
                    "_id": "54ff69d92e96909171f3e1b1",
                    "index": 50,
                    "guid": "0d6b5e73-7b1d-4c96-9a16-d73d0e74c364",
                    "isActive": false,
                    "balance": "$2,920.49",
                    "age": 21,
                    "eyeColor": "green",
                    "name": "Mia Mcgee",
                    "gender": "female",
                    "company": "GEEKOLOGY",
                    "email": "miamcgee@geekology.com",
                    "phone": "+1 (921) 464-2944"
                }, {
                    "_id": "54ff69d94e56363d2f4110cb",
                    "index": 51,
                    "guid": "c355ad3a-a137-4dc4-9eeb-2ef6c5385076",
                    "isActive": true,
                    "balance": "$2,738.07",
                    "age": 36,
                    "eyeColor": "brown",
                    "name": "Doris Mccullough",
                    "gender": "female",
                    "company": "PRIMORDIA",
                    "email": "dorismccullough@primordia.com",
                    "phone": "+1 (823) 470-2961"
                }, {
                    "_id": "54ff69d99d447d751c48fefc",
                    "index": 52,
                    "guid": "564a646d-4bd9-4e4b-bb64-522eea292751",
                    "isActive": false,
                    "balance": "$1,966.01",
                    "age": 30,
                    "eyeColor": "brown",
                    "name": "Fernandez Shepard",
                    "gender": "male",
                    "company": "PAWNAGRA",
                    "email": "fernandezshepard@pawnagra.com",
                    "phone": "+1 (903) 453-3876"
                }, {
                    "_id": "54ff69d949dfc5b655d10e58",
                    "index": 53,
                    "guid": "4b07ab75-7a20-4515-9561-10323a712c37",
                    "isActive": false,
                    "balance": "$3,447.93",
                    "age": 38,
                    "eyeColor": "green",
                    "name": "Yvette Caldwell",
                    "gender": "female",
                    "company": "OLUCORE",
                    "email": "yvettecaldwell@olucore.com",
                    "phone": "+1 (861) 417-2291"
                }, {
                    "_id": "54ff69d9e7ab8c4e8029f2d6",
                    "index": 54,
                    "guid": "73917fc2-53d6-4331-8ac9-ec65943d8e75",
                    "isActive": false,
                    "balance": "$1,949.14",
                    "age": 25,
                    "eyeColor": "green",
                    "name": "Gibson Molina",
                    "gender": "male",
                    "company": "STREZZO",
                    "email": "gibsonmolina@strezzo.com",
                    "phone": "+1 (809) 485-2161"
                }, {
                    "_id": "54ff69d958fcd9a733351136",
                    "index": 55,
                    "guid": "ad310b11-965c-480e-9088-4d65c27d6d04",
                    "isActive": false,
                    "balance": "$2,512.16",
                    "age": 40,
                    "eyeColor": "brown",
                    "name": "Hicks Ferguson",
                    "gender": "male",
                    "company": "COMTEXT",
                    "email": "hicksferguson@comtext.com",
                    "phone": "+1 (897) 565-2845"
                }, {
                    "_id": "54ff69d94a48d9b5a556f495",
                    "index": 56,
                    "guid": "17bca210-7a7f-4236-badd-4d52abc4d73f",
                    "isActive": false,
                    "balance": "$3,950.51",
                    "age": 28,
                    "eyeColor": "brown",
                    "name": "Dale Stephens",
                    "gender": "female",
                    "company": "ZENSOR",
                    "email": "dalestephens@zensor.com",
                    "phone": "+1 (867) 474-3050"
                }, {
                    "_id": "54ff69d94f91296292db728e",
                    "index": 57,
                    "guid": "1cd712d4-b477-496d-aea6-d8b75f0aebcc",
                    "isActive": false,
                    "balance": "$1,385.99",
                    "age": 37,
                    "eyeColor": "brown",
                    "name": "Burris Nash",
                    "gender": "male",
                    "company": "BRAINCLIP",
                    "email": "burrisnash@brainclip.com",
                    "phone": "+1 (873) 533-2943"
                }, {
                    "_id": "54ff69d9b8edd89a22d08814",
                    "index": 58,
                    "guid": "3cab0ec2-bece-43a2-b49f-1213cbd8bbb0",
                    "isActive": false,
                    "balance": "$3,652.39",
                    "age": 37,
                    "eyeColor": "brown",
                    "name": "Janie Harrington",
                    "gender": "female",
                    "company": "ZISIS",
                    "email": "janieharrington@zisis.com",
                    "phone": "+1 (920) 441-3329"
                }, {
                    "_id": "54ff69d90550f9ffe4676013",
                    "index": 59,
                    "guid": "536e1f5f-474f-412e-a4ad-258a20a7cb77",
                    "isActive": false,
                    "balance": "$3,937.99",
                    "age": 24,
                    "eyeColor": "brown",
                    "name": "Beulah Burch",
                    "gender": "female",
                    "company": "PLASTO",
                    "email": "beulahburch@plasto.com",
                    "phone": "+1 (904) 452-2143"
                }, {
                    "_id": "54ff69d920a5eed9e3d2cb29",
                    "index": 60,
                    "guid": "54a44fee-8614-492f-a1bd-c4a6248fce48",
                    "isActive": true,
                    "balance": "$3,024.67",
                    "age": 25,
                    "eyeColor": "green",
                    "name": "Osborne Carter",
                    "gender": "male",
                    "company": "ARTIQ",
                    "email": "osbornecarter@artiq.com",
                    "phone": "+1 (937) 431-2440"
                }, {
                    "_id": "54ff69d921ee90fcb998a583",
                    "index": 61,
                    "guid": "6e899cc3-f935-405d-995c-43635d3ed121",
                    "isActive": true,
                    "balance": "$3,189.34",
                    "age": 37,
                    "eyeColor": "green",
                    "name": "Rice Mcclain",
                    "gender": "male",
                    "company": "EVENTIX",
                    "email": "ricemcclain@eventix.com",
                    "phone": "+1 (924) 448-3107"
                }, {
                    "_id": "54ff69d9c70b2ae884142f6f",
                    "index": 62,
                    "guid": "0e62a562-ced9-4922-9985-8c813a647d87",
                    "isActive": false,
                    "balance": "$2,135.22",
                    "age": 34,
                    "eyeColor": "green",
                    "name": "Jerri Knowles",
                    "gender": "female",
                    "company": "VANTAGE",
                    "email": "jerriknowles@vantage.com",
                    "phone": "+1 (926) 566-3957"
                }, {
                    "_id": "54ff69d950ea8f13f7e0adc3",
                    "index": 63,
                    "guid": "70e8cbfa-9f24-4e58-b827-9d683765e383",
                    "isActive": false,
                    "balance": "$1,360.09",
                    "age": 24,
                    "eyeColor": "blue",
                    "name": "Rose Chapman",
                    "gender": "male",
                    "company": "STEELTAB",
                    "email": "rosechapman@steeltab.com",
                    "phone": "+1 (922) 514-3065"
                }, {
                    "_id": "54ff69d939f6877321458bf6",
                    "index": 64,
                    "guid": "ddb4264b-de31-464b-89ff-dc7b89c120c5",
                    "isActive": true,
                    "balance": "$1,331.51",
                    "age": 32,
                    "eyeColor": "green",
                    "name": "Larson Baxter",
                    "gender": "male",
                    "company": "HALAP",
                    "email": "larsonbaxter@halap.com",
                    "phone": "+1 (968) 451-2570"
                }, {
                    "_id": "54ff69d9e8c4ac69c408d5a5",
                    "index": 65,
                    "guid": "92f7f97e-469f-4e61-93ba-58d440f9bdee",
                    "isActive": false,
                    "balance": "$3,771.47",
                    "age": 28,
                    "eyeColor": "green",
                    "name": "Sweeney Shepherd",
                    "gender": "male",
                    "company": "OVOLO",
                    "email": "sweeneyshepherd@ovolo.com",
                    "phone": "+1 (963) 413-2263"
                }, {
                    "_id": "54ff69d97caf29d7106b9a22",
                    "index": 66,
                    "guid": "ccff96f2-0dea-4d72-ae9b-5f2c5e1bccf8",
                    "isActive": false,
                    "balance": "$3,022.08",
                    "age": 35,
                    "eyeColor": "blue",
                    "name": "Maribel Henry",
                    "gender": "female",
                    "company": "CHILLIUM",
                    "email": "maribelhenry@chillium.com",
                    "phone": "+1 (814) 489-2410"
                }, {
                    "_id": "54ff69d9ea34002e4fc91d67",
                    "index": 67,
                    "guid": "133b0c0a-1517-487c-84b9-b0eb1fc8bfd1",
                    "isActive": false,
                    "balance": "$1,995.00",
                    "age": 20,
                    "eyeColor": "brown",
                    "name": "Deann Bray",
                    "gender": "female",
                    "company": "VERTON",
                    "email": "deannbray@verton.com",
                    "phone": "+1 (847) 540-2423"
                }, {
                    "_id": "54ff69d9837c8279be6854e0",
                    "index": 68,
                    "guid": "d562f05e-b605-475e-b7ea-e38cf8c6b32f",
                    "isActive": true,
                    "balance": "$1,035.74",
                    "age": 35,
                    "eyeColor": "brown",
                    "name": "Hart Dillon",
                    "gender": "male",
                    "company": "SOLAREN",
                    "email": "hartdillon@solaren.com",
                    "phone": "+1 (885) 540-3322"
                }, {
                    "_id": "54ff69d975a0df7615047dda",
                    "index": 69,
                    "guid": "7ec2a46e-9d61-42ad-9aa0-d06596232252",
                    "isActive": false,
                    "balance": "$1,317.78",
                    "age": 40,
                    "eyeColor": "green",
                    "name": "Daphne Mercado",
                    "gender": "female",
                    "company": "TERRASYS",
                    "email": "daphnemercado@terrasys.com",
                    "phone": "+1 (915) 491-2470"
                }, {
                    "_id": "54ff69d9a6aafdf53cf7899d",
                    "index": 70,
                    "guid": "99c23548-6731-4236-a6b1-0455172d931b",
                    "isActive": false,
                    "balance": "$3,395.05",
                    "age": 22,
                    "eyeColor": "blue",
                    "name": "Aguilar Ramsey",
                    "gender": "male",
                    "company": "SONIQUE",
                    "email": "aguilarramsey@sonique.com",
                    "phone": "+1 (918) 407-2528"
                }, {
                    "_id": "54ff69d98368d84760f7ffb4",
                    "index": 71,
                    "guid": "919aa130-2a9a-4c18-9f82-a4cc5fa9d18f",
                    "isActive": true,
                    "balance": "$3,961.57",
                    "age": 23,
                    "eyeColor": "blue",
                    "name": "Alyssa Garrett",
                    "gender": "female",
                    "company": "ZORK",
                    "email": "alyssagarrett@zork.com",
                    "phone": "+1 (816) 467-3019"
                }, {
                    "_id": "54ff69d97100f98d64a49826",
                    "index": 72,
                    "guid": "32301d04-6bd8-4db2-a1d0-b98ae4a53877",
                    "isActive": true,
                    "balance": "$2,245.64",
                    "age": 27,
                    "eyeColor": "blue",
                    "name": "Vickie Castro",
                    "gender": "female",
                    "company": "QUOTEZART",
                    "email": "vickiecastro@quotezart.com",
                    "phone": "+1 (894) 530-3406"
                }, {
                    "_id": "54ff69d944ae1efb286accdf",
                    "index": 73,
                    "guid": "a15615f1-2077-4d7f-800c-f19aad0cf45a",
                    "isActive": false,
                    "balance": "$3,610.39",
                    "age": 26,
                    "eyeColor": "brown",
                    "name": "Sheryl Cherry",
                    "gender": "female",
                    "company": "TOYLETRY",
                    "email": "sherylcherry@toyletry.com",
                    "phone": "+1 (883) 527-2393"
                }]
            });

            grid = Ext.create('Ext.grid.Panel', {
                title: 'Simpsons',
                store: store,
                height: 350,
                width: 600,
                renderTo: document.body,
                columns: [{
                    text: 'index',
                    dataIndex: 'index',
                    locked: true
                }, {
                    text: 'Name',
                    dataIndex: 'name',
                    locked: true,
                    cellWrap: true,
                    width: 75
                }, {
                    text: 'Email',
                    dataIndex: 'email',
                    flex: 1
                }, {
                    text: 'Phone',
                    dataIndex: 'phone'
                }, {
                    text: 'isActive',
                    dataIndex: 'isActive'

                }, {
                    text: 'eyeColor',
                    dataIndex: 'eyeColor'
                }, {
                    text: 'company',
                    dataIndex: 'company'
                }, {
                    text: 'gender',
                    dataIndex: 'gender'
                }],
                region: 'center'
            });

            var normalView = grid.normalGrid.getView(),
                lockedView = grid.lockedGrid.getView(),
                normalRows = normalView.all,
                lockedRows = lockedView.all,
                navModel = normalView.getNavigationModel();

            waitsFor(function() {
                return normalView.all.getCount();
            });

            runs(function() {
                navModel.setPosition(new Ext.grid.CellContext(lockedView).setPosition(0, 0));
            });

            waitsFor(function() {
                var a = Ext.Element.getActiveElement(),
                    p = navModel.getPosition();

                if (p) {
                    // Scroll only when the last scroll signal has found both views and caused them to update
                    if (navModel.getCell() && (a === navModel.getCell().dom) && normalRows.startIndex === lockedRows.startIndex) {
                        jasmine.fireKeyEvent(a, 'keydown', Ext.event.Event.PAGE_DOWN);
                    }

                    // Scroll until the end
                    return ((p = navModel.getPosition()) && p.rowIdx === store.getCount() - 1);
                }
            }, 'down arrow to scroll to the last row. 20 seconds expired', 20000);

            runs(function() {
                var i;

                // Row count must be in sync
                expect(lockedRows.getCount()).toBe(normalRows.getCount());

                // view sizes must still be in sync
                expect(lockedView.bufferedRenderer.viewSize).toBe(normalView.bufferedRenderer.viewSize);

                // Every row must be the same height
                for (i = normalRows.startIndex; i <= normalRows.endIndex; i++) {
                    expect(normalRows.item(i).getHeight()).toBe(lockedRows.item(i).getHeight());
                }
            });
        });
    });

    describe('gridpanel', function() {
        describe('locking grid', function() {
            function doIt(reconfigure, afterScrollToEnd) {
                var columns = [{
                    text: 'Col 1',
                    dataIndex: 'field1',
                    width: 100,
                    locked: true
                }, {
                    text: 'Col 2',
                    dataIndex: 'field2',
                    width: 100
                }, {
                    text: 'Col 3',
                    dataIndex: 'field3',
                    width: 100
                }, {
                    text: 'Col 4',
                    dataIndex: 'field4',
                    width: 100
                }, {
                    text: 'Col 5',
                    dataIndex: 'field5',
                    height: 25,
                    width: 150,
                    xtype: 'widgetcolumn',
                    widget: {
                        xtype: 'progressbarwidget',
                        height: 25,
                        textTpl: ['{percent:number("0")}% capacity']
                    }
                }],
                nodeCache,
                margin = (Ext.isIE || Ext.isEdge) ? 2 : 0;

                makeGrid({
                    columns: columns
                }, {
                    fields: ['field1', 'field2', 'field3', 'field4', 'field5'],
                    data: createData(100)
                });

                view = grid.view.normalView;
                nodeCache = view.all;
                scroller = grid.getScrollable();

                if (reconfigure && !afterScrollToEnd) {
                    grid.reconfigure(null, columns);
                }

                jasmine.waitsForScroll(scroller, function() {
                    if (nodeCache.endIndex === 99 && view.bufferedRenderer.getLastVisibleRowIndex() === 99) {
                        return true;
                    }

                    scroller.scrollBy(0, 40);
                }, 'last node to scroll into view', 40000);

                runs(function() {
                    if (reconfigure && afterScrollToEnd) {
                        grid.reconfigure(null, columns);
                    }

                    // MS browsers show innacuracies sometimes, so use approx for them
                    expect(Ext.fly(view.el.dom.querySelector('.x-grid-item-container')).getHeight()).toBeApprox(Ext.fly(view.lockingPartner.el.dom.querySelector('.x-grid-item-container')).getHeight(), margin);
                });
            }

            it('should have the same height for each locking partner when scrolling', function() {
                doIt(false);
            });

            it('should have the same height for each locking partner when scrolling after a reconfigure', function() {
                doIt(true);
            });

            it('should have the same height for each locking partner when scrolling after a reconfigure when scrolled to end', function() {
                doIt(true, true);
            });
        });

        describe('locking grid with variableRowHeight', function() {
            it('should keep the row heights on both sides synchronized', function() {
                var columns = [{
                    text: 'Col 1',
                    dataIndex: 'field1',
                    width: 100,
                    locked: true,
                    variableRowHeight: true
                }, {
                    text: 'Col 2',
                    dataIndex: 'field2',
                    width: 100
                }, {
                    text: 'Col 3',
                    dataIndex: 'field3',
                    width: 100
                }, {
                    text: 'Col 4',
                    dataIndex: 'field4',
                    width: 100
                }, {
                    text: 'Col 5',
                    dataIndex: 'field5',
                    height: 25,
                    width: 150,
                    xtype: 'widgetcolumn',
                    widget: {
                        xtype: 'progressbarwidget',
                        height: 25,
                        textTpl: ['{percent:number("0")}% capacity']
                    }
                }],
                nodeCache,
                lockedView,
                bufferedRendererInvocationCount = 0,
                onSyncHeights = function() {
                    var lockedItems = lockedView.all.slice(),
                        normalItems = nodeCache.slice(),
                        lockedSize = lockedItems.length,
                        normalSize = normalItems.length,
                        i,
                        allEqual = true;

                    // must be same number of rows
                    expect(lockedSize).toBe(normalSize);

                    for (i = 0; allEqual && i < lockedSize; i++) {
                        // Allow for 1px margin for Microsoft browser's innacuracies.
                        allEqual = allEqual && Math.abs(normalItems[i].offsetHeight - lockedItems[i].offsetHeight) < 2;
                    }

                    // All rows must be same size
                    expect(allEqual).toBe(true);
                    bufferedRendererInvocationCount++;
                },
                margin = (Ext.isIE || Ext.isEdge) ? 2 : 0;

                // Make grid with small buffer zones.
                makeGrid({
                    columns: columns,
                    syncRowHeight: true
                }, {
                    fields: ['field1', 'field2', 'field3', 'field4', 'field5'],
                    data: createData(1000, true)
                });

                lockedView = grid.view.lockedView;
                view = grid.view.normalView;
                nodeCache = view.all;
                scroller = grid.getScrollable();

                // Set up a sequence on the buffered renderers to check that all rows are always synced
                view.bufferedRenderer.syncRowHeights = Ext.Function.createSequence(view.bufferedRenderer.syncRowHeights, onSyncHeights);
                lockedView.bufferedRenderer.syncRowHeights = Ext.Function.createSequence(view.bufferedRenderer.syncRowHeights, onSyncHeights);

                jasmine.waitsForScroll(scroller, function() {
                    var reachedTargetRow = nodeCache.startIndex <= 99 && nodeCache.endIndex >= 99;

                    // If row 99 is in the nodeCache, we're done
                    if (reachedTargetRow && nodeCache.startIndex === lockedView.all.startIndex && lockedView.position === view.position && lockedView.bodyTop === view.bodyTop) {
                        return true;
                    }

                    scroller.scrollBy(0, 40);
                }, 'row 99 to be rendered', 40000);

                // Must have invoked the row syncher and the two body heights must be the same
                runs(function() {
                    expect(bufferedRendererInvocationCount).toBeGreaterThan(0);

                    // Allow for 2px margin for Microsoft browser's innacuracies.
                    expect(view.el.down('.x-grid-item-container', true).offsetHeight).toBeApprox(view.lockingPartner.el.down('.x-grid-item-container', true).offsetHeight, margin);
                    bufferedRendererInvocationCount = 0;
                });

                // Now teleport down to near the bottom
                jasmine.waitsForScroll(scroller, function() {
                    // We don't want to try scrolling to the very bottom; there's a race condition
                    // between us and BufferedRenderer.stretchView() that also tries to scroll
                    // the same element. 950 rows out of 1000 is enough, tf there was a problem
                    // it would have manifested itself already.
                    var reachedTargetRow = view.bufferedRenderer.getLastVisibleRowIndex() > 950;

                    if (reachedTargetRow) {
                        return true;
                    }

                    scroller.scrollBy(0, 1000);
                }, 'row 950 to scroll into view', 30000);

                // Scrolling is too fast for IE8, need to repaint the grid
                // so that measurements below will yield correct values
                if (Ext.isIE8) {
                    runs(function() {
                        grid.hide();
                        grid.show();
                    });
                }

                // Must have invoked the row syncher and the two body heights must be the same
                runs(function() {
                    expect(bufferedRendererInvocationCount).toBeGreaterThan(0);

                    var mainHeight = Ext.fly(view.el.dom.querySelector('.x-grid-item-container')).getHeight(),
                        partnerHeight = Ext.fly(view.lockingPartner.el.dom.querySelector('.x-grid-item-container')).getHeight();

                    // Allow for 1px margin for Microsoft browser's innacuracies.
                    expect(partnerHeight).toBeApprox(mainHeight, 1);
                    bufferedRendererInvocationCount = 0;
                });
            });
        });

        describe('locking grid with asymmetricRowHeight', function() {
            it('should keep the row heights on both sides synchronized', function() {
                // Note that we do NOT set variableRowHeight. All row heights are the same
                // even if one side drives the row height, and the sides need syncing.
                // This means BufferedRenderer can use simple arithmetic to find first/last visible row index.
                var columns = [{
                    text: 'Col 1',
                    dataIndex: 'field1',
                    width: 100,
                    locked: true
                }, {
                    text: 'Col 2',
                    dataIndex: 'field2',
                    width: 100
                }, {
                    text: 'Col 3',
                    dataIndex: 'field3',
                    width: 100
                }, {
                    text: 'Col 4',
                    dataIndex: 'field4',
                    width: 100
                }, {
                    text: 'Col 5',
                    dataIndex: 'field5',
                    height: 25,
                    width: 150,
                    xtype: 'widgetcolumn',
                    widget: {
                        xtype: 'progressbarwidget',
                        height: 25,
                        textTpl: ['{percent:number("0")}% capacity']
                    }
                }],
                nodeCache,
                lockedView,
                bufferedRendererInvocationCount = 0,
                onSyncHeights = function() {
                    var lockedItems = lockedView.all.slice(),
                        normalItems = nodeCache.slice(),
                        lockedSize = lockedItems.length,
                        normalSize = normalItems.length,
                        i,
                        allEqual = true;

                    // must be same number of rows
                    expect(lockedSize).toBe(normalSize);

                    for (i = 0; allEqual && i < lockedSize; i++) {
                        // Allow for 1px margin for Microsoft browser's innacuracies.
                        allEqual = allEqual && Math.abs(normalItems[i].offsetHeight - lockedItems[i].offsetHeight) < 2;
                    }

                    // All rows must be same size
                    expect(allEqual).toBe(true);
                    bufferedRendererInvocationCount++;
                },
                margin = (Ext.isIE || Ext.isEdge) ? 2 : 0;

                // Make grid with small buffer zones.
                makeGrid({
                    columns: columns,
                    syncRowHeight: true
                }, {
                    fields: ['field1', 'field2', 'field3', 'field4', 'field5'],
                    data: createData(1000, false, true),

                    // Make sure store.isGrouped() returns false
                    // otherwise variableRowHeight will be detected
                    groupField: undefined
                });

                lockedView = grid.view.lockedView;
                view = grid.view.normalView;
                nodeCache = view.all;
                scroller = grid.getScrollable();

                // Set up a sequence on the buffered renderers to check that all rows are always synced
                view.bufferedRenderer.syncRowHeights = Ext.Function.createSequence(view.bufferedRenderer.syncRowHeights, onSyncHeights);
                lockedView.bufferedRenderer.syncRowHeights = Ext.Function.createSequence(view.bufferedRenderer.syncRowHeights, onSyncHeights);

                jasmine.waitsForScroll(scroller, function() {
                    var reachedTargetRow = nodeCache.startIndex <= 99 && nodeCache.endIndex >= 99;

                    // If row 99 is in the nodeCache, we're done
                    if (reachedTargetRow && nodeCache.startIndex === lockedView.all.startIndex && lockedView.position === view.position && lockedView.bodyTop === view.bodyTop) {
                        return true;
                    }

                    scroller.scrollBy(0, 40);
                }, 'row 99 to be rendered', 40000);

                // Must have invoked the row syncher and the two body heights must be the same
                runs(function() {
                    expect(bufferedRendererInvocationCount).toBeGreaterThan(0);

                    // Allow for 2px margin for Microsoft browser's innacuracies.
                    expect(Ext.fly(view.el.dom.querySelector('.x-grid-item-container')).getHeight()).toBeApprox(Ext.fly(view.lockingPartner.el.dom.querySelector('.x-grid-item-container')).getHeight(), margin);
                    bufferedRendererInvocationCount = 0;
                });

                // Now teleport down to neat the bottom
                jasmine.waitsForScroll(scroller, function() {
                    var reachedTargetRow = view.bufferedRenderer.getLastVisibleRowIndex() > 950;

                    if (reachedTargetRow) {
                        return true;
                    }

                    scroller.scrollBy(0, 1000);
                }, 'row 990 to scroll into view', 30000);

                // Scrolling is too fast for IE8, need to repaint the grid
                // so that measurements below will yield correct values
                if (Ext.isIE8) {
                    runs(function() {
                        grid.hide();
                        grid.show();
                    });
                }

                // Must have invoked the row syncher and the two body heights must be the same
                runs(function() {
                    expect(bufferedRendererInvocationCount).toBeGreaterThan(0);

                    var mainHeight = Ext.fly(view.el.dom.querySelector('.x-grid-item-container')).getHeight(),
                        partnerHeight = Ext.fly(view.lockingPartner.el.dom.querySelector('.x-grid-item-container')).getHeight();

                    // Allow for 1px margin for Microsoft browser's innacuracies.
                    expect(partnerHeight).toBeApprox(mainHeight, 1);
                    bufferedRendererInvocationCount = 0;
                });
            });
        });

        describe("locked grid with groping having Sparkline bar widget in a column", function() {
            (Ext.isIE8 ? xit : it)("should be visible after user collapse and expands Grouped Grid", function() {
                var groupers, gridView, sparklineWidget, sparklinebarWidth,
                    barArray = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

                makeGrid({
                    columns: [{
                            header: 'Name',
                            dataIndex: 'name',
                            editor: 'textfield',
                            locked: true
                        },
                        {
                            header: 'Department',
                            dataIndex: 'department',
                            editor: 'textfield'
                        },
                        {
                            header: 'Email',
                            dataIndex: 'email',
                            flex: 1,
                            editor: {
                                xtype: 'textfield',
                                allowBlank: false
                            }
                        },
                        {
                            header: 'Phone',
                            dataIndex: 'phone',
                            editor: 'textfield'
                        },
                        {
                            header: 'Bar',
                            width: 100,
                            xtype: 'widgetcolumn',
                            widget: {
                                xtype: 'sparklinebar',
                                values: barArray
                            }
                        }
                    ],
                    width: 800,
                    height: 800,
                    features: [{
                        ftype: 'grouping'
                    }],
                    renderTo: Ext.getBody()
                }, {
                    fields: ['name', 'email', 'phone', 'department'],
                    groupField: 'department',
                    autoDestroy: true,
                    data: [{
                            'name': 'Lisa',
                            'email': 'lisa@simpsons.com',
                            'phone': '555-111-1224',
                            'age': 14,
                            'department': 'A'
                        },
                        {
                            'name': 'Lisa',
                            'email': 'aunt_lisa@simpsons.com',
                            'phone': '555-111-1274',
                            'age': 34,
                            'department': 'A'
                        },
                        {
                            'name': 'Bart',
                            'email': 'bart@simpsons.com',
                            'phone': '555-222-1234',
                            'age': 12,
                            'department': 'A'
                        },
                        {
                            'name': 'Homer',
                            'email': 'homer@simpsons.com',
                            'phone': '555-222-1244',
                            'age': 44,
                            'department': 'B'
                        },
                        {
                            'name': 'Marge',
                            'email': 'marge@simpsons.com',
                            'phone': '555-222-1254',
                            'age': 41,
                            'department': 'B'
                        }
                    ]
                });

                // TO collapse and expand the groups
                groupers = grid.lockedGrid.features[0];
                groupers.collapse('A');
                groupers.expand('A');

                gridView = grid.getColumns()[4].getView();
                sparklineWidget = gridView.getRow(0).getElementsByTagName('canvas');
                sparklinebarWidth = sparklineWidget.length > 0 ? sparklineWidget.item(0).width : undefined;

                expect(sparklinebarWidth).toBeDefined();

                // TODO This expectation matches even when sparklinebarWidth is not defined.
                expect(sparklinebarWidth).not.toBeLessThan(0);
            });
        });

        describe('reconfiguring', function() {
            it('should never return `undefined` records when called in a metachange event', function() {
                // When reconfigure is called within a metchange event listener, the view is refreshed and
                // `undefined` is returned when AbstractView.getViewRange() -> PageMap.getRange() is called.
                // This isn't usually a problem, but if there are data records in the PageMap hash that don't exist
                // in a current page then `undefined` will be returned when instead an array is expected. This, of
                // course, will throw an exception when the rows are attempted to be created by the template in
                // AbstractView.refresh(). See EXTJS-12633.
                var successData = {
                    success: true,
                    totally: 1000,
                    data: [{
                        first: 'load2 - First1',
                        last: 'load2 - Last1'
                    }, {
                        first: 'load2 - First2',
                        last: 'load2 - Last2'
                    }],
                    metaData: {
                        root: 'data',
                        totalProperty: 'totally',
                        fields: ['first', 'last'],
                        columns: [{
                            text: 'First',
                            dataIndex: 'first'
                        }, {
                            text: 'Last',
                            dataIndex: 'last'
                        }]
                    }
                },
                initialData = [
                    { 'name': 'Lisa', 'email': 'lisa@simpsons.com', 'phone': '555-111-1224', 'age': 14 },
                    { 'name': 'Lisa', 'email': 'aunt_lisa@simpsons.com', 'phone': '555-111-1274', 'age': 34 },
                    { 'name': 'Bart', 'email': 'bart@simpsons.com', 'phone': '555-222-1234', 'age': 12 },
                    { 'name': 'Homer', 'email': 'homer@simpsons.com', 'phone': '555-222-1244', 'age': 44 },
                    { 'name': 'Marge', 'email': 'marge@simpsons.com', 'phone': '555-222-1254', 'age': 41 }
                ],
                wasCalled = false,
                initialColumnCount,
                newColumnCount;

                makeGrid(null, {
                    data: null,
                    fields: ['name', 'email'],
                    leadingBufferZone: 50,
                    pageSize: 25,
                    proxy: {
                        type: 'ajax',
                        url: 'derp'
                    },
                    listeners: {
                        metachange: function(store, meta) {
                            grid.reconfigure(store, meta.columns);
                            wasCalled = true;
                        }
                    }
                });
                store.load();
                completeWithData(initialData);

                // No metadata in initial payload
                expect(wasCalled).toBe(false);
                expect(store.getCount()).toBe(5);
                initialColumnCount = grid.getVisibleColumnManager().getColumns().length;

                store.load();
                completeWithData(successData);

                // Check that reconfigure has been successful
                newColumnCount = grid.getVisibleColumnManager().getColumns().length;
                expect(wasCalled).toBe(true);
                expect(store.getCount()).toBe(2);
                expect(newColumnCount).toBe(2);
                expect(newColumnCount).toBeLessThan(initialColumnCount);
            });

            describe('with grouping feature', function() {
                it('reconfiguring should bind the groupStore to the plugin', function() {
                    // This test demonstrates that reconfiguring the grid will properly bind the feature's group
                    // store to the plugin.
                    //
                    // This bug only is reproducible when reconfigure is called on a grid with the buffered
                    // renderer plugin and grouping feature. The bug was that the buffered renderer plugin
                    // would bind the data store to the plugin rather than the group store (created when
                    // there's a grouping feature).
                    //
                    // See EXTJS-11860 and EXTJS-11892.
                    makeGrid({
                        features: [{ ftype: 'grouping' }]
                    });

                    grid.reconfigure(store);

                    expect(grid.view.bufferedRenderer.store.isFeatureStore).toBe(true);
                });
            });
        });

        describe('refreshing the view', function() {
            describe('filtering out all records', function() {
                function makeData(len) {
                    var data = [],
                        i, str;

                    len = len || 100;

                    for (i = 0; i < len; i++) {
                        str = 'emp_' + i;

                        data.push({
                            name: str,
                            email: str + '@sencha.com',
                            phone: '1-888-' + i,
                            age: i
                        });
                    }

                    return data;
                }

                function runTests(scroll) {
                    describe('scrolled = ' + scroll, function() {
                        it('should trigger a view refresh', function() {
                            var wasCalled = false;

                            makeGrid({
                                viewConfig: {
                                    listeners: {
                                        refresh: function() {
                                            wasCalled = true;
                                        }
                                    }
                                }
                            });

                            if (scroll) {
                                plugin.scrollTo(50);
                            }

                            // Filter out all data.
                            store.filter('name', '______');

                            expect(wasCalled).toBe(true);
                        });

                        it('should reset the view body', function() {
                            makeGrid(null, {
                                data: makeData(500)
                            });

                            if (scroll) {
                                plugin.scrollTo(50);

                                expect(plugin.bodyTop).toBeGreaterThan(0);
                                expect(plugin.scrollHeight).toBeGreaterThan(0);
                            }

                            // Filter out all data.
                            store.filter('name', '______');

                            expect(plugin.bodyTop).toBe(0);
                            expect(plugin.scrollHeight).toBe(0);
                        });
                    });
                }

                runTests(true);
                runTests(false);
            });
        });
    });

    describe('treepanel', function() {
        describe('expanding/collapsing', function() {
            itNotIE8('should always render the view nodes when expanding', function() {
                var nodeCache;

                makeTree({
                    height: 300
                }, 100);

                nodeCache = view.all;

                // Scroll until the last tree node is the last in the rendered block.
                jasmine.waitsForScroll(scroller, function() {
                    if (nodeCache.endIndex === 99 && view.bufferedRenderer.getLastVisibleRowIndex() === 99) {
                        return true;
                    }

                    // Scroll incrementally until the correct end point is found
                    scroller.scrollBy(0, 10);
                }, 'last node to scroll into view', 30000);

                // Expanding that last node should append some child nodes to replenish the leading buffer zone.
                runs(function() {
                    // Expand node 99
                    store.getAt(99).expand();
                });

                // Scroll until the last of those expanded children is the last in the rendered block.
                jasmine.waitsForScroll(scroller, function() {
                    if (nodeCache.endIndex === 105) {
                        return true;
                    }

                    // Scroll incrementally until the correct end point is found
                    scroller.scrollBy(0, 10);
                }, 'new last leaf node to scroll into view', 10000);

                // Expanding that last node should append the child nodes to the view even though the buffer rendered block is the correct size already
                runs(function() {
                    // expect(view.bufferedRenderer.position).toBe(view.el.dom.scrollTop);
                    expect(view.getRecord(view.all.last()).get('treeData')).toBe('Child of 99, number 6');

                    // Now let's collapse the parent node by simulating a click on the elbow node.
                    jasmine.fireMouseEvent(nodeCache.item(99).down('.x-tree-expander', true), 'click');
                });
            });
        });

        describe('loadMask config', function() {
            it('should create a mask by default if not configured', function() {
                makeTree({
                    store: {
                        proxy: {
                            type: 'ajax',
                            url: 'foo'
                        }
                    }
                });
                expect(view.loadMask instanceof Ext.LoadMask).toBe(true);
            });

            it('should honor the value if configured', function() {
                makeTree({
                    store: {
                        proxy: {
                            type: 'ajax',
                            url: 'foo'
                        }
                    },
                    viewConfig: {
                        loadMask: false
                    }
                });
                expect(view.loadMask).toBe(false);
            });
        });

        describe('Measuring row height', function() {
            it('should measure row height', function() {
                makeGrid(null, {
                    groupField: null,
                    data: [
                        { 'name': '<div style="height:30px">Lisa</div>', 'email': 'lisa@simpsons.com', 'phone': '555-111-1224', 'age': 14 },
                        { 'name': 'Lisa', 'email': 'aunt_lisa@simpsons.com', 'phone': '555-111-1274', 'age': 34 },
                        { 'name': 'Bart', 'email': 'bart@simpsons.com', 'phone': '555-222-1234', 'age': 12 },
                        { 'name': 'Homer', 'email': 'homer@simpsons.com', 'phone': '555-222-1244', 'age': 44 },
                        { 'name': 'Marge', 'email': 'marge@simpsons.com', 'phone': '555-222-1254', 'age': 41 }
                    ]
                });

                // Should measure the row height be looking at the first row when we do NOT have variableRowHeight: true
                // EXTJS-15942 - did not measure, stayed at classic default of 21
                var row = view.all.first(),
                    rowHeight = row.getHeight();

                // In IE8 we're adding a bottom border on the rows and shifting the row up
                // at -border-width to compensate for that
                if (Ext.isIE8) {
                    rowHeight -= row.getBorderWidth('b');
                }

                expect(plugin.rowHeight).toBe(rowHeight);
            });
        });
    });

    describe('filtering the store', function() {
        var Hobbit, store;

        afterEach(function() {
            Ext.destroy(Hobbit);
        });

        itNotIE8('should reset the cached position so the grid-item-container is at the top of the view on filter', function() {
            var Hobbit = Ext.define(null, {
                extend: 'Ext.data.Model',
                fields: ['name'],
                proxy: {
                    type: 'ajax',
                    url: '/foo',
                    reader: {
                        rootProperty: 'data'
                    }
                }
            });

            store = new Ext.data.BufferedStore({
                model: Hobbit,
                remoteGroup: true,
                leadingBufferZone: 300,
                pageSize: 100,
                autoDestroy: true
            });

            makeGrid({
                columns: [{
                    dataIndex: 'name',
                    width: 100
                }]
            }, {
                store: store
            });

            store.load();

            completeWithData({
                total: 5000,
                data: makeData(100)
            });

            completeWithData({
                total: 5000,
                data: makeData(100, 101)
            });

            completeWithData({
                total: 5000,
                data: makeData(100, 201)
            });

            completeWithData({
                total: 5000,
                data: makeData(100, 301)
            });

            jasmine.waitsForScroll(scroller, function() {
                if (view.all.startIndex <= 199 && view.all.endIndex >= 199) {
                    return true;
                }

                scroller.scrollBy(0, 200);
            }, 'row 199 to scroll into the rendered block', 30000);

            runs(function() {
                store.addFilter({
                    property: 'name',
                    value: 'name212'
                });

                // Unfortunately, we're testing private properties here :(
                expect(plugin.bodyTop).toBe(0);
                expect(plugin.position).toBe(0);
            });
        });

        // Uses shift/click which is not available on touch
        itNotTouch('should reset the cached position so the grid-item-container is at the top of the view on clearFilter', function() {
            var selModel;

            var Hobbit = Ext.define(null, {
                extend: 'Ext.data.Model',
                fields: ['name'],
                proxy: {
                    type: 'ajax',
                    url: '/foo',
                    reader: {
                        rootProperty: 'data'
                    }
                }
            });

            store = new Ext.data.Store({
                model: Hobbit,
                remoteFilter: false,
                autoDestroy: true,
                proxy: {
                    type: 'memory',
                    data: makeData(5000)
                },
                autoLoad: true
            });

            makeGrid({
                columns: [{
                    dataIndex: 'name',
                    width: 100
                }],
                selModel: {
                    type: 'rowmodel',
                    mode: 'MULTI'
                }
            }, {
                store: store
            });
            selModel = grid.selModel;

            // Wait for first block to be rendered
            waitsFor(function() {
                return view.all.startIndex === 0 && view.all.getCount();
            });

            runs(function() {

                // Only show the first 2500
                store.addFilter({
                    property: 'id',
                    value: 2500,
                    operator: '<='
                });

                // We have filtered out the top 2500 IDs: 2501-5000
                expect(store.getCount()).toBe(2500);

                // Click to select first row
                jasmine.fireMouseEvent(view.getCellByPosition({ row: 0, column: 0 }, true), 'click');
                expect(view.selModel.getSelection().length).toBe(1);

                // Scroll all the way to the end
                grid.ensureVisible(2499);

                // Click to select from start to end.
                jasmine.fireMouseEvent(view.getCellByPosition({ row: 2499, column: 0 }, true), 'click', null, null, null, true);
                expect(view.selModel.getSelection().length).toBe(2500);

                store.remove(selModel.getSelection());

                // All records gone. (There are still 2500) filtered out though...
                expect(store.getCount()).toBe(0);

                // Should have gone to top
                expect(view.all.getCount()).toBe(0);
                expect(view.getScrollY()).toBe(0);

                // Unfortunately, we're testing private properties here :(
                expect(plugin.bodyTop).toBe(0);
                expect(plugin.position).toBe(0);

                store.clearFilter();

                // The 2500 filtered out records should jump back in
                expect(store.getCount()).toBe(2500);

                // Unfortunately, we're testing private properties here :(
                expect(plugin.bodyTop).toBe(0);
                expect(plugin.position).toBe(0);
            });
        });
    });

    describe("reloading store", function() {
        describe("from having items to not having items", function() {
            it("should not cause an error when reloading", function() {
                var store = new Ext.data.BufferedStore({
                    fields: ['id'],
                    autoDestroy: true,
                    proxy: {
                        type: 'ajax',
                        url: '/foo',
                        reader: {
                            type: 'json',
                            rootProperty: 'data',
                            totalProperty: 'total'
                        }
                    }
                });

                makeGrid({
                    columns: [{
                        dataIndex: 'name',
                        width: 100
                    }]
                }, {
                    store: store
                });

                store.load();
                satisfyRequests();

                store.reload();
                satisfyRequests(0);

                expect(view.getNodes()).toEqual([]);
            });

            it("should show emptyText if specified", function() {
                var store = new Ext.data.BufferedStore({
                    fields: ['id'],
                    autoDestroy: true,
                    proxy: {
                        type: 'ajax',
                        url: '/foo',
                        reader: {
                            type: 'json',
                            rootProperty: 'data',
                            totalProperty: 'total'
                        }
                    }
                });

                makeGrid({
                    columns: [{
                        dataIndex: 'name',
                        width: 100
                    }],
                    emptyText: 'Empty'
                }, {
                    store: store
                });

                store.load();
                satisfyRequests();

                store.reload();
                satisfyRequests(0);

                expect(grid.el.down('.' + grid.emptyCls, true)).hasHTML('Empty');
            });
        });

        describe('loading the bound store', function() {
            function testLockable(locked) {
                makeTree({
                    columns: [{
                        xtype: 'treecolumn',
                        text: 'Tree Column',
                        width: 300,
                        locked: locked,
                        dataIndex: 'task'
                    }, {
                        text: 'Task1',
                        dataIndex: 'task1'
                    }, {
                        text: 'Task2',
                        dataIndex: 'task2'
                    }, {
                        text: 'Task3',
                        dataIndex: 'task3'
                    }, {
                        text: 'Task4',
                        dataIndex: 'task4'
                    }, {
                        text: 'Task5',
                        dataIndex: 'task5'
                    }]
                }, 1000);
            }

            it('should not scroll the view, non-locked grid', function() {

                testLockable(false);

                expect(view.el.dom.scrollTop).toBe(0);
                store.load();
                expect(view.el.dom.scrollTop).toBe(0);
            });

            it('should not scroll the view, locked grid', function() {
                var lockedView, normalView, lockedViewDom, normalViewDom;

                testLockable(true);

                lockedViewDom = view.lockedView.el.dom;
                normalViewDom = view.normalView.el.dom;

                expect(lockedViewDom.scrollTop).toBe(0);
                expect(normalViewDom.scrollTop).toBe(0);

                store.load();

                expect(lockedViewDom.scrollTop).toBe(0);
                expect(normalViewDom.scrollTop).toBe(0);
            });
        });
    });

    describe('Expanding view size', function() {
        var win;

        afterEach(function() {
            win.destroy();
        });

        it('should scroll to top when view size expands to encapsulate whole dataset', function() {
            var Person = Ext.define(null, {
                    extend: 'Ext.data.Model',
                    fields: ['name'],
                    proxy: {
                        type: 'ajax',
                        url: '/foo',
                        reader: {
                            rootProperty: 'data'
                        }
                    }
                }),
                store = new Ext.data.Store({
                    model: Person,
                    proxy: {
                        type: 'memory',
                        data: makeData(52)
                    },
                    autoDestroy: true
                }),
                store1, grid, grid1, tabPanel, view;

            store.load();

            store1 = new Ext.data.Store({
                model: Person,
                proxy: {
                    type: 'memory',
                    data: makeData(52)
                }
            });

            store1.load();

            grid = new Ext.grid.Panel({
                hideMode: 'offsets',
                title: 'Grid 1',
                store: store,
                deferRowRender: false,
                columns: [{
                    dataIndex: 'id'
                }, {
                    dataIndex: 'name'
                }]
            });

            grid1 = new Ext.grid.Panel({
                hideMode: 'offsets',
                title: 'Grid 2',
                store: store1,
                deferRowRender: false,
                columns: [{
                    dataIndex: 'id'
                }, {
                    dataIndex: 'name'
                }]
            });

            win = new Ext.window.Window({
                title: 'Test',
                height: 395,
                width: 800,
                x: 10,
                y: 10,
                autoShow: true,
                maximizable: true,
                minimizable: true,
                constrain: false,
                layout: 'fit',
                items: {
                    xtype: 'tabpanel',
                    items: [
                        grid, grid1
                    ]
                }
            });

            tabPanel = win.child('tabpanel');
            view = grid.getView();
            scroller = view.isLockingView ? grid.getScrollable() : view.getScrollable();

            // Scroll all the way to the end
            waitsFor(function() {
                if (view.all.endIndex === store.getCount() - 1) {
                    return true;
                }

                scroller.scrollBy(null, Infinity);
            }, 'scroll to end', 500);

            runs(function() {
                view.scrollBy(null, 100);

                tabPanel.setActiveTab(1);

                // inserting at top
                grid.store.insert(0, { 'title': 'hi', replycount: 5 });
                grid.store.insert(0, { 'title': 'hi2', replycount: 5 });

                grid1.store.insert(0, { 'title': 'hi', replycount: 5 });
                grid1.store.insert(0, { 'title': 'hi2', replycount: 5 });

                tabPanel.setActiveTab(0);

                win.setHeight(940);
            });

            // Scroll all the way to the start
            waitsFor(function() {
                if (view.getScrollY() < 100) {
                    return true;
                }

                scroller.scrollBy(null, -Infinity);
            }, 'scroll to the start', 500);

            runs(function() {
                expect(view.bufferedRenderer.bodyTop).toBe(0);
            });
        });
    });

    describe('Reducing view size', function() {
        var grid;

        afterEach(function() {
            grid.destroy();
        });

        itNotIE8("should reduce the size of the rendered block when the view's height is reduced", function() {
            var Person = Ext.define(null, {
                extend: 'Ext.data.Model',
                fields: ['name'],
                proxy: {
                    type: 'ajax',
                    url: '/foo',
                    reader: {
                        rootProperty: 'data'
                    }
                }
            });

            var store = new Ext.data.Store({
                model: Person,
                proxy: {
                    type: 'memory',
                    data: makeData(100)
                },
                autoDestroy: true
            });

            store.load();

            grid = new Ext.grid.Panel({
                title: 'Grid 1',
                store: store,
                deferRowRender: false,
                columns: [{
                    dataIndex: 'id'
                }, {
                    dataIndex: 'name'
                }],
                height: 500,
                renderTo: document.body
            });

            grid.view.getScrollable().scrollBy(0, 2000);

            waitsFor(function() {
                return grid.view.all.startIndex !== 0;
            }, 'rendered block to move away from position zero', 30000, 100);

            runs(function() {

                var renderedBlockSize = grid.view.all.getCount();

                expect(function() {
                    grid.setHeight(200);
                }).not.toThrow();

                expect(grid.view.all.getCount()).toBeLessThan(renderedBlockSize);
            });
        });
    });

    describe("resizing", function() {
        it("should keep the viewClientHeight property updated", function() {
            var clientHeight;

            makeGrid({
                columns: [{
                    text: 'Name',
                    dataIndex: 'name',
                    locked: true
                }, {
                    text: 'E-Mail',
                    dataIndex: 'email'
                }],
                dockedItems: [{
                    xtype: 'panel',
                    hidden: true,
                    dock: 'bottom',
                    height: 300,
                    itemId: 'extraPanel'
                }]
            }, {
                data: getData(0, 500)
            });

            clientHeight = view.normalView.bufferedRenderer.viewClientHeight;
            grid.down('#extraPanel').setHidden(false);
            grid.down('#extraPanel').setHidden(true);

            expect(view.normalView.bufferedRenderer.viewClientHeight).toBe(clientHeight);
        });
    });

    describe('ensureVisible', function() {
        it('should work in a viewready listener', function() {
            var done,
                columns = [{
                    text: 'Col 1',
                    dataIndex: 'field1',
                    width: 100
                }, {
                    text: 'Col 2',
                    dataIndex: 'field2',
                    width: 100
                }, {
                    text: 'Col 3',
                    dataIndex: 'field3',
                    width: 100
                }, {
                    text: 'Col 4',
                    dataIndex: 'field4',
                    width: 100
                }];

            makeGrid({
                columns: columns,
                listeners: {
                    viewready: function(grid) {
                        grid.ensureVisible(grid.getStore().last(), {
                            callback: function() {
                                done = true;
                            }
                        });
                    }
                }
            }, {
                fields: ['field1', 'field2', 'field3', 'field4'],
                data: createData(1000)
            });

            waitsFor(function() {
                return done;
            });

            // Should have scrolled all the way to the end
            expect(view.all.endIndex).toBe(999);
            expect(view.bufferedRenderer.getLastVisibleRowIndex()).toBe(999);
        });
    });

    // https://sencha.jira.com/browse/EXTJS-19454
    // Bug manifests when a buffer rendered tree is added by state restoration
    // on DOM scrolling devices when touch is enabled.
    describe('scroll range stretching correctly when DOM TouchScrolling is used', function() {
        var touch = Ext.supports.Touch,
            touchEvents = Ext.supports.TouchEvents,
            container;

        afterEach(function() {
            Ext.destroy(container, tree);

            // Restore to platform defaults
            Ext.supports.TouchEvents = touchEvents;
            Ext.supports.Touch = touch;
        });

        it('should always create a spacer', function() {

            // Fake up the touch scrolling environment which triggers the bug
            Ext.supports.TouchEvents = true;
            Ext.supports.Touch = true;

            container = new Ext.container.Container({
                stateful: true,
                layout: 'fit',
                height: 400,
                width: 600,
                renderTo: document.body,
                initState: function() {
                    this.add(makeTree({
                        rootVisible: false,
                        renderTo: null
                    }, 500));
                }
            });

            // There must ALWAYS be a spacer to create a virtual scroll range
            expect(view.getScrollable()._spacer).not.toBeUndefined();
        });
    });

    describe('delayed data returning after scroll position has moved', function() {
        it('should not render the returned data block if the scroll position has moved on since the load was requested', function() {
            var Person = Ext.define(null, {
                extend: 'Ext.data.Model',
                fields: ['name'],
                proxy: {
                    type: 'ajax',
                    url: '/foo',
                    reader: {
                        rootProperty: 'data'
                    }
                }
            });

            store = new Ext.data.BufferedStore({
                model: Person,
                leadingBufferZone: 300,
                pageSize: 100,
                autoDestroy: true
            });

            grid = new Ext.grid.Panel({
                width: 500,
                height: 300,
                store: store,
                deferRowRender: false,
                columns: [{
                    dataIndex: 'id',
                    locked: true
                }, {
                    dataIndex: 'name'
                }],
                renderTo: Ext.getBody()
            });
            var view = grid.normalGrid.view;

            // Load the pages around scrollTop 0
            store.load();
            satisfyRequests();

            // Scroll a long way to a part of the dataset for which we do not have data.
            view.scrollTo(null, 100000);

            // Allow the scroll event to fire and the requests for the pages
            // surrounding scrollTop 100000 to be requested.
            waits(100);

            // Before we return the data for that scroll position,
            // scroll back to zero
            runs(function() {
                view.scrollTo(null, 0);
            });

            // Allow the scroll handler to fire.
            // The pages at the top of the dataset will be already there.
            // The incoming pages for scrollTop 100000 should NOT cause
            // a render of the rows down at that range; that data
            // is not required any more.
            waits(100);

            runs(function() {
                satisfyRequests();
                expect(view.all.startIndex).toBe(0);
            });

        });
    });

    describe('refreshView', function() {
        it('should scroll to an appropriate position when refreshing the view at a specified startIndex', function() {
            var columns = [{
                    text: 'Col 1',
                    dataIndex: 'field1',
                    width: 100
                }, {
                    text: 'Col 2',
                    dataIndex: 'field2',
                    width: 100
                }, {
                    text: 'Col 3',
                    dataIndex: 'field3',
                    width: 100
                }, {
                    text: 'Col 4',
                    dataIndex: 'field4',
                    width: 100
                }];

            makeGrid({
                columns: columns
            }, {
                fields: ['field1', 'field2', 'field3', 'field4', 'field5'],
                data: createData(1000)
            });
            view.bufferedRenderer.refreshView(500);

            // After a refresh at a teleported position (no overlapping records with previous rendered block),
            // the view has moved downwards, so the first visible row index must be "trailingBufferZone" rows
            // after the requested refresh start row.
            expect(view.bufferedRenderer.getFirstVisibleRowIndex()).toBe(500 + view.bufferedRenderer.trailingBufferZone);
        });
    });

    describe('scrolling position', function() {
        it('should not scroll up when fetching a new range of rows', function() {
            makeGrid(null, {
                data: createData(1000, false, true)
            });

            grid.getNavigationModel().setPosition(0, 0);

            jasmine.waitsForScroll(scroller, function() {
                if (view.all.startIndex !== 0) {
                    return true;
                }

                scroller.scrollBy(0, 10);
            }, 'view is scrolled to the last record');

            runs(function() {
                expect(view.getScrollY()).not.toBeLessThan(600);
            });
        });
    });

    describe('non scrolling grids', function() {
        it('should not throw an error', function() {
            expect(function() {
                makeGrid({
                    scrollable: false
                }, {
                    data: []
                });
                store.fireEvent('refresh', store);
            }).not.toThrow();
        });
    });

    describe('startIndex = 0', function() {
        var store,
        grid;

        afterEach(function() {
            grid.destroy();
            store.destroy();
        });

        beforeEach(function() {
            var createMillionDataStore = function(count) {
                var fields = [{
                    name: 'revenue',
                    type: 'number'
                }];

                var millionData = [];

                for (var i = 0; i < count; i++) {
                    var millionObject = {};

                    for (var j = 0; j < fields.length; j++) {
                        millionObject[fields[j].name] = Math.floor(Math.random() * 1000 - 500);
                    }

                    millionData.push(millionObject);
                }

                return {
                    data: millionData,
                    fields: fields
                };
            };

            var data = createMillionDataStore(1000);

            store = Ext.create('Ext.data.Store', {
                data: data.data,
                fields: data.fields
            });

            grid = new Ext.grid.Panel({
                renderTo: Ext.getBody(),
                height: 200,
                columnLines: true,
                title: 'Sponsored Projects',
                viewConfig: {
                    preserveScrollOnRefresh: false
                },
                columns: [{
                    "dataIndex": "revenue",
                    "text": "revenue"
                }],
                store: store
            });
        });

        it('should maintain the value of preserveScrollOnRefresh', function() {
            var preserveScrollOnRefreshValue = true;

            view = grid.getView();
            scroller = view.getScrollable();

            spyOn(view, 'refreshView').andCallFake(function() {
                preserveScrollOnRefreshValue = view.preserveScrollOnRefresh;
            });

            // scroll to bottom of grid 
            scroller.scrollTo(0, 10000);
            // on adding filter, the preserveScrollOnRefresh should be set to false
            grid.getStore().addFilter({
                property: 'revenue',
                operator: "lt",
                value: -470
            });
            expect(view.refreshView).toHaveBeenCalled();
            expect(preserveScrollOnRefreshValue).toBe(false);

            // on clearing filter, the preserveScrollOnRefresh should be set to false
            preserveScrollOnRefreshValue = null;
            // scroll to bottom of grid 
            scroller.scrollTo(0, 10000);
            grid.getStore().clearFilter();
            expect(view.refreshView).toHaveBeenCalled();
            expect(preserveScrollOnRefreshValue).toBe(false);
        });
     });

    describe('checkbox selection', function() {

        function clickCell(rowIdx, colIdx) {
            var cell = view.getCellByPosition({
                row: rowIdx,
                column: colIdx
            }, true);

            jasmine.fireMouseEvent(cell, 'click');
        }

        it('should not cause looping while scrolling', function() {
            var data = [],
            selModel;

            for (var i = 0; i < 100; i++) {
                data.push({
                    name: 'Lisa ' + i,
                    email: 'lisa-' + i + '@simpsons.com',
                    change: 30
                });
            }

            store = Ext.create('Ext.data.Store', {
                fields: ['name', 'email', 'change'],

                data: data
            });

            makeGrid({
                columns: [{
                    header: 'Name',
                    dataIndex: 'name'
                }, {
                    header: 'Email',
                    dataIndex: 'email',
                    flex: 1
                }, {
                    header: 'Change',
                    dataIndex: 'change'
                }],
                selModel: {
                    type: 'checkboxmodel'
                },
                title: 'Simpsons',
                height: 300
            }, {
                store: store
            });
            selModel = grid.selModel;

            for (var j = 2; j <= 5;  j++) {
                clickCell(j, 0);
            }

            runs(function() {
                // check that only 4 items are checked
                expect(view.selModel.getSelection().length).toBe(4);

                spyOn(scroller, 'getPosition').andCallThrough();

                // the view should be able to scroll till last record
                scroller.scrollTo(0, 10000);
                expect(scroller.getPosition().y).toBeGreaterThan(0);
                expect(view.all.endIndex).toBe(store.getCount() - 1);

            });
        });
    });

});

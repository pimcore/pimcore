topSuite("Ext.tree.TreeGrid",
    [false, 'Ext.tree.Panel', 'Ext.grid.Panel', 'Ext.grid.column.Action',
     'Ext.layout.container.Border'],
function() {
    var TreeGridItem = Ext.define(null, {
            extend: 'Ext.data.Model',
            fields: ['f1', 'f2'],
            proxy: {
                type: 'memory'
            }
        }),
        tree,
        view,
        store,
        recordCount,
        treeData = {
            f1: 'root1',
            f2: 'root.a',

            // Add cls. Tests must not throw errors with this present.
            cls: 'test-EXTJS-16367',
            children: [{
                f1: '1',
                f2: 'a',
                children: [{
                    f1: '1.1',
                    f2: 'a.a',
                    leaf: true
                }, {
                    f1: '1.2',
                    f2: 'a.b',
                    leaf: true
                }, {
                    f1: '1.3',
                    f2: 'a.c',
                    leaf: true
                }, {
                    f1: '1.4',
                    f2: 'a.d',
                    leaf: true
                }]
            }, {
                f1: '2',
                f2: 'b',
                children: [{
                    f1: '2.1',
                    f2: 'b.a',
                    leaf: true
                }, {
                    f1: '2.2',
                    f2: 'b.b',
                    leaf: true
                }, {
                    f1: '2.3',
                    f2: 'b.c',
                    leaf: true
                }, {
                    f1: '2.4',
                    f2: 'b.d',
                    leaf: true
                }]
            }, {
                f1: '3',
                f2: 'c',
                children: [{
                    f1: '3.1',
                    f2: 'c.a',
                    leaf: true
                }, {
                    f1: '3.2',
                    f2: 'c.b',
                    leaf: true
                }, {
                    f1: '3.3',
                    f2: 'c.c',
                    leaf: true
                }, {
                    f1: '3.4',
                    f2: 'c.d',
                    leaf: true
                }]
            }, {
                f1: '4',
                f2: 'd',
                children: [{
                    f1: '4.1',
                    f2: 'd.a',
                    leaf: true
                }, {
                    f1: '4.2',
                    f2: 'd.b',
                    leaf: true
                }, {
                    f1: '4.3',
                    f2: 'd.c',
                    leaf: true
                }, {
                    f1: '4.4',
                    f2: 'd.d',
                    leaf: true
                }]
            }, {
                f1: '5',
                f2: 'e',
                children: [{
                    f1: '5.1',
                    f2: 'e.a',
                    leaf: true
                }, {
                    f1: '5.2',
                    f2: 'e.b',
                    leaf: true
                }, {
                    f1: '5.3',
                    f2: 'e.c',
                    leaf: true
                }, {
                    f1: '5.4',
                    f2: 'e.d',
                    leaf: true
                }]
            }, {
                f1: '6',
                f2: 'f',
                children: [{
                    f1: '6.1',
                    f2: 'f.a',
                    leaf: true
                }, {
                    f1: '6.2',
                    f2: 'f.b',
                    leaf: true
                }, {
                    f1: '6.3',
                    f2: 'f.c',
                    leaf: true
                }, {
                    f1: '6.4',
                    f2: 'f.d',
                    leaf: true
                }]
            }]
        },
        synchronousLoad = true,
        treeStoreLoad = Ext.data.TreeStore.prototype.load,
        loadStore = function() {
            treeStoreLoad.apply(this, arguments);

            if (synchronousLoad) {
                this.flushLoad.apply(this, arguments);
            }

            return this;
        };

    function spyOnEvent(object, eventName, fn) {
        var obj = {
            fn: fn || Ext.emptyFn
        },
        spy = spyOn(obj, "fn");

        object.addListener(eventName, obj.fn);

        return spy;
    }

    function makeTreeGrid(cfg, storeCfg) {
        tree = new Ext.tree.Panel(Ext.apply({
            animate: false,
            renderTo: Ext.getBody(),
            store: store = new Ext.data.TreeStore(Ext.apply({
                model: TreeGridItem,
                root: Ext.clone(treeData)
            }, storeCfg)),
            trailingBufferZone: 1000,
            leadingBufferZone: 1000,
            width: 200,
            columns: [{
                xtype: 'treecolumn',
                text: 'F1',
                dataIndex: 'f1',
                width: 100
            }, {
                text: 'F2',
                dataIndex: 'f2',
                flex: 1
            }]
        }, cfg));
        view = tree.getView();
    }

    beforeEach(function() {
        // Override so that we can control asynchronous loading
        Ext.data.TreeStore.prototype.load = loadStore;
    });

    afterEach(function() {
        // Undo the overrides.
        Ext.data.TreeStore.prototype.load = treeStoreLoad;

        Ext.destroy(tree);
    });

    describe('tabbability', function() {
        it('should keep all elements untabbable when not in actionable mode', function() {
            makeTreeGrid({
                width: 300,
                columns: [{
                    xtype: 'treecolumn',
                    text: 'F1',
                    dataIndex: 'f1',
                    width: 100
                }, {
                    text: 'F2',
                    dataIndex: 'f2',
                    flex: 1
                }, {
                    xtype: 'actioncolumn'
                }]
            });

            tree.getNavigationModel().setPosition(0, 0);
            waitsFor(function() {
                return tree.view.containsFocus;
            });
            runs(function() {
                expect(tree.view.el.findTabbableElements({ skipSelf: true }).length).toBe(0);
                tree.store.first().expand();
                expect(tree.view.el.findTabbableElements({ skipSelf: true }).length).toBe(0);
            });
        });
    });

    describe('Model mutation', function() {
        it('should not have to render a whole row, it should update innerHTML of cell', function() {
            var createRowSpy;

            makeTreeGrid();

            // Test cls config
            expect(view.getCellByPosition({ row: 0, column: 0 }, true)).toHaveCls('test-EXTJS-16367');

            createRowSpy = spyOn(view, 'createRowElement').andCallThrough();

            store.getAt(0).set({
                f1: 'ploot',
                f2: 'gronk'
            });

            // MUST not have created a bew row, we must have just updated the text within the cell
            expect(createRowSpy).not.toHaveBeenCalled();
        });
    });

    describe('locking and variableRowHeight', function() {
        beforeEach(function() {
            makeTreeGrid({
                preciseHeight: true,
                rootVisible: false,
                lockedGridConfig: {
                    syncRowHeight: true
                },
                width: 500,
                height: 250,
                columns: [{
                    xtype: 'treecolumn',
                    text: 'F1',
                    dataIndex: 'f1',
                    locked: true
                }, {
                    locked: true,
                    variableRowHeight: true,
                    dataIndex: 'f1',
                    renderer: function(v) {
                        return "<img height='24' width='24' src='resources/images/foo.gif' />" + v;
                    }
                }, {
                    text: 'F2',
                    dataIndex: 'f2',
                    variableRowHeight: true
                }]
            });
            tree.expandAll();
        });

        it('should synchronize row heights', function() {
            var lockedTree = tree.lockedGrid,
                normalGrid = tree.normalGrid,
                lockedView = lockedTree.view,
                normalView = normalGrid.view,
                scrollable = lockedView.getScrollable().getLockingScroller(),
                store = tree.getStore(),
                record, regNode, lockedNode,
                regNodeHeight, lockedNodeHeight;

            function getRectHeight(el) {
                var rect = el.getBoundingClientRect();

                return rect.height || (rect.bottom - rect.top);
            }

            record = store.findRecord('f1', '3.3');
            regNode = normalView.getNode(record);
            lockedNode = lockedView.getNode(record);

            scrollable.ensureVisible(regNode);

            waits(50);
            runs(function() {
                regNodeHeight = getRectHeight(regNode);
                lockedNodeHeight = getRectHeight(lockedNode);

                // This may be calculated differently between different rows that are
                // actually the "same height" despite deviation of a few thousandths
                // of a pixel, specifically in IE/Edge. Fix the value so it's within
                // 1/10th of a pixel.
                regNodeHeight = Ext.Number.toFixed(regNodeHeight, 1);
                lockedNodeHeight = Ext.Number.toFixed(lockedNodeHeight, 1);

                expect(regNodeHeight).toBe(lockedNodeHeight);
            });
        });
    });

    describe('autoloading', function() {
        it('should not autoload the store if the root is visible', function() {
            var loadCount = 0;

            // rootVisible defaults to true, so no autoload
            makeTreeGrid({
                columns: [{
                    xtype: 'treecolumn',
                    text: 'F1',
                    dataIndex: 'f1',
                    width: 100
                }],
                    store: {
                    listeners: {
                        load: function() {
                            loadCount++;
                        }
                    }
                }
            });
            expect(loadCount).toBe(0);
        });
        it('should not autoload the store if the root is visible and there is a locked column', function() {
            var loadCount = 0;

            // rootVisible defaults to true, so no autoload
            makeTreeGrid({
                columns: [{
                    xtype: 'treecolumn',
                    text: 'F1',
                    dataIndex: 'f1',
                    width: 100,
                    locked: true
                }],
                store: {
                    listeners: {
                        load: function() {
                            loadCount++;
                        }
                    }
                }
            });
            expect(loadCount).toBe(0);
        });
        it('should autoload the store if the root is visible', function() {
            var loadCount = 0;

            // rootVisible set to false, so autoload so that user sees the tree content
            makeTreeGrid({
                rootVisible: false,
                columns: [{
                    xtype: 'treecolumn',
                    text: 'F1',
                    dataIndex: 'f1',
                    width: 100
                }],
                store: {
                    proxy: 'memory',
                    listeners: {
                        load: function() {
                            loadCount++;
                        }
                    }
                }
            });
            expect(loadCount).toBe(1);
        });
        it('should autoload the store if the root is visible and there is a locked column', function() {
            var loadCount = 0;

            // rootVisible set to false, so autoload so that user sees the tree content
            makeTreeGrid({
                rootVisible: false,
                columns: [{
                    xtype: 'treecolumn',
                    text: 'F1',
                    dataIndex: 'f1',
                    width: 100,
                    locked: true
                }],
                store: {
                    proxy: 'memory',
                    listeners: {
                        load: function() {
                            loadCount++;
                        }
                    }
                }
            });
            expect(loadCount).toBe(1);
        });
    });

    describe("Buffered rendering", function() {
        var rootNode;

        beforeEach(function() {
            makeTreeGrid({
                height: 45,
                plugins: Ext.create('Ext.grid.plugin.BufferedRenderer', {
                    trailingBufferZone: 1,
                    leadingBufferZone: 1
                })
            });
            tree.expandAll();
            recordCount = tree.view.store.getCount();
            rootNode = tree.getRootNode();
        });
        it("should not render every node", function() {

            expect(recordCount).toEqual(31);

            // The view's Composite element should only contain the visible rows plus buffer zones.
            // Should be less than the total node count in the Tree structure.
            expect(tree.view.all.getCount()).toBeLessThan(recordCount);
        });
        it("should not not scroll upon node expand", function() {
            tree.collapseAll();
            rootNode.expand();
            tree.view.setScrollY(40);

            // We must wait until the Scroller knows about the scroll position
            // at which point it fires a scrollend event
            waitsForEvent(tree.getView().getScrollable(), 'scrollend', 'Tree scrollend');

            // Wait for scroll position to be read
            runs(function() {
                expect(tree.view.getScrollable().getPosition().y).toBe(40);
            });

            runs(function() {
                tree.getRootNode().childNodes[1].expand();
            });

            // Nothing should happen. The bug was that expansion caused focus-scroll.
            // No scrolling, and no event firing sohuld take place, scroll position
            // and application state should remain unchanged.
            waits(200);

            // Expanding a node should not scroll.
            runs(function() {
                expect(tree.view.getScrollY()).toEqual(40);
            });
        });

        it("should not not scroll horizontally upon node toggle", function() {
            // MUST be no scroll so that the non buffered rendering pathway is used
            // and the row count changes and a layout is triggered.
            tree.setHideHeaders(false);
            tree.setHeight(600);
            tree.collapseAll();
            tree.columns[0].setWidth(200);

            rootNode.expand();
            tree.view.setScrollX(40);

            // We must wait until the Scroller knows about the scroll position
            // at which point it fires a scrollend event
            waitsForEvent(tree.getView().getScrollable(), 'scrollend', 'Tree scrollend');

            // Wait for scroll position to be read
            runs(function() {
                expect(tree.view.getScrollable().getPosition().x).toBe(40);
            });

            runs(function() {
                tree.getRootNode().childNodes[1].expand();
            });

            // Wait for possible (but incorrect) scroll
            waits(100);

            // Expanding a node should not scroll.
            runs(function() {
                expect(tree.view.getScrollX()).toEqual(40);

                // Another operation should also not scroll.
                // https://sencha.jira.com/browse/EXTJS-21084
                // Saved scroll position was being discarded by restoreState
                // even though it may be needed multiple times.
                tree.getRootNode().childNodes[1].collapse();
            });

            // Wait for possible (but incorrect) scroll
            waits(100);

            // Expanding a node should not scroll.
            runs(function() {
                expect(tree.view.getScrollX()).toEqual(40);
            });
        });
    });

    describe('buffered rendering with locking and rootVisible: false', function() {
        var rootNode;

        beforeEach(function() {
                makeTreeGrid({
                    renderTo: Ext.getBody(),
                    height: 120,
                    store: new Ext.data.TreeStore({
                        model: TreeGridItem,
                        root: {
                            f1: 'Root',
                            f2: 'root',
                            children: [{
                                f1: 'c0',
                                f2: 'c0',
                                leaf: true
                            }, {
                                f1: 'c1',
                                f2: 'c1',
                                leaf: true
                            }, {
                                f1: 'c2',
                                f2: 'c2',
                                leaf: true
                            }]
                        }
                    }),
                    plugins: Ext.create('Ext.grid.plugin.BufferedRenderer', {
                        trailingBufferZone: 1,
                        leadingBufferZone: 1
                    }),
                    columns: [{
                        xtype: 'treecolumn',
                        text: 'F1',
                        dataIndex: 'f1',
                        width: 100,
                        locked: true
                    }, {
                        text: 'F2',
                        dataIndex: 'f2',
                        flex: 1
                    }],
                    rootVisible: false
                });
                recordCount = tree.lockedGrid.view.store.getCount();
                rootNode = tree.getRootNode();
        });

        it('should work when inserting a node at the top', function() {
            expect(tree.lockedGrid.view.all.getCount()).toEqual(3);
            expect(tree.normalGrid.view.all.getCount()).toEqual(3);
            rootNode.insertBefore({ text: 'Top' }, rootNode.childNodes[0]);

            expect(tree.lockedGrid.view.all.getCount()).toEqual(4);
            expect(tree.normalGrid.view.all.getCount()).toEqual(4);
        });
    });

    describe("Buffered rendering and locking", function() {
        var rootNode;

        beforeEach(function() {
            makeTreeGrid({
                height: 45,
                plugins: Ext.create('Ext.grid.plugin.BufferedRenderer', {
                    trailingBufferZone: 1,
                    leadingBufferZone: 1
                }),
                columns: [{
                    xtype: 'treecolumn',
                    text: 'F1',
                    dataIndex: 'f1',
                    width: 100,
                    locked: true
                }, {
                    text: 'F2',
                    dataIndex: 'f2',
                    flex: 1
                }]
            });
            tree.expandAll();
            recordCount = tree.lockedGrid.view.store.getCount();
            rootNode = tree.getRootNode();
        });
        it("should not render every node", function() {
            var lockedTree = tree.lockedGrid,
                normalGrid = tree.normalGrid,
                viewSize = lockedTree.view.all.getCount();

            expect(recordCount).toEqual(31);

            // The view's Composite element should only contain the visible rows plus buffer zones.
            // Should be less than the total node count in the Tree structure.
            expect(viewSize).toBeLE(recordCount);
            expect(normalGrid.view.all.getCount()).toEqual(viewSize);
        });

        it('should sync scroll positions between the two sides', function() {
            var lockedTree = tree.lockedGrid,
                normalGrid = tree.normalGrid,
                lockedView = lockedTree.view,
                normalView = normalGrid.view;

            tree.collapseAll();
            rootNode.expand();
            tree.getScrollable().scrollBy(0, 30);
            waits(200); // Wait for the scroll listener (deferred to next animation Frame)
            runs(function() {
                var yPos;

                expect(tree.getScrollable().getPosition().y).toEqual(30);

                // Now, at 120px high, the entire tree is rendered, scrolling will not triggert action by the buffered renderer
                // Scrolling should still sync
                tree.setHeight(120);

                normalView.setScrollY(45);

                // See where we've managed to scroll it to (May not be enough content to get 45)
                yPos = normalView.getScrollY();

                rootNode.childNodes[2].expand();

                // Root node, its 6 children, and child[2]'s 4 children: 11 records in NodeStore
                expect(normalView.store.getCount()).toEqual(11);

                // We cannot wait for an event. We are expecting nothing to happen
                // if all goes well. The scroll caused by the header layout will
                // be undone, and then 50ms later, scroll listening will be restored
                waits(100);

                runs(function() {

                    // But scroll position should not change
                    expect(lockedView.el.dom.scrollTop).toEqual(yPos);
                });
            });
        });
    });

    describe('reconfigure', function() {
        var store, store2, myTree, rt;

        it('should allow reconfigure', function() {
            var cols = [{
                xtype: 'treecolumn',
                text: 'Task',
                flex: 1,
                dataIndex: 'task'
            }, {
                text: 'URL',
                flex: 1,
                sortable: false,
                dataIndex: 'url'
            }];

            var cols2 = [{
                xtype: 'treecolumn',
                text: 'New Task',
                flex: 1,
                dataIndex: 'new_task'
            }, {
                text: 'New URL',
                flex: 1,
                sortable: false,
                dataIndex: 'new_url'
            }];

            Ext.define('ReconfigureTestTask', {
                extend: 'Ext.data.Model',
                fields: [{
                    name: 'task',
                    type: 'string'
                }, {
                    name: 'url',
                    type: 'string'
                }]
            });

            Ext.define('ReconfigureTestNewTask', {
                extend: 'Ext.data.Model',
                fields: [{
                    name: 'new_task',
                    type: 'string'
                }, {
                    name: 'new_url',
                    type: 'string'
                }]
            });

            store = Ext.create('Ext.data.TreeStore', {
                model: 'ReconfigureTestTask',
                root: {
                    expanded: true,
                    children: [{
                        task: 'task1',
                        url: 'url1',
                        expanded: true,
                        children: [{
                            task: 'task1.1',
                            url: 'url1.1',
                            leaf: true
                        }]
                    }, {
                        task: 'task2',
                        url: 'url2',
                        expanded: true,
                        children: [{
                            task: 'task2.1',
                            url: 'url2.1',
                            leaf: true
                        }]
                    }]
                }
            });

            store2 = Ext.create('Ext.data.TreeStore', {
                model: 'ReconfigureTestNewTask',
                root: {
                    expanded: true,
                    children: [{
                        new_task: 'new-task1',
                        new_url: 'new-url1',
                        expanded: true,
                        children: [{
                            new_task: 'new-task1.1',
                            new_url: 'new-url1.1',
                            leaf: true
                        }]
                    }, {
                        new_task: 'new-task2',
                        new_url: 'new-url2',
                        expanded: true,
                        children: [{
                            new_task: 'new-task2.1',
                            new_url: 'new-url2.1',
                            leaf: true
                        }]
                    }]
                }
            });

            myTree = Ext.create('Ext.tree.Panel', {
                title: 'treegrid',
                width: 600,
                height: 300,
                renderTo: Ext.getBody(),
                collapsible: true,
                rootVisible: false,
                useArrows: true,
                store: store,
                multiSelect: true,
                columns: cols
            });
            rt = myTree.getRootNode();
            expect(rt.childNodes[0].data.task).toEqual('task1');
            expect(rt.childNodes[0].data.url).toEqual('url1');
            expect(rt.childNodes[0].childNodes[0].data.task).toEqual('task1.1');
            expect(rt.childNodes[0].childNodes[0].data.url).toEqual('url1.1');
            expect(rt.childNodes[1].data.task).toEqual('task2');
            expect(rt.childNodes[1].data.url).toEqual('url2');
            expect(rt.childNodes[1].childNodes[0].data.task).toEqual('task2.1');
            expect(rt.childNodes[1].childNodes[0].data.url).toEqual('url2.1');

            myTree.reconfigure(store2, cols2);
            rt = myTree.getRootNode();
            expect(rt.childNodes[0].data.new_task).toEqual('new-task1');
            expect(rt.childNodes[0].data.new_url).toEqual('new-url1');
            expect(rt.childNodes[0].childNodes[0].data.new_task).toEqual('new-task1.1');
            expect(rt.childNodes[0].childNodes[0].data.new_url).toEqual('new-url1.1');
            expect(rt.childNodes[1].data.new_task).toEqual('new-task2');
            expect(rt.childNodes[1].data.new_url).toEqual('new-url2');
            expect(rt.childNodes[1].childNodes[0].data.new_task).toEqual('new-task2.1');
            expect(rt.childNodes[1].childNodes[0].data.new_url).toEqual('new-url2.1');

            myTree.destroy();
            Ext.undefine('ReconfigureTestTask');
            Ext.undefine('ReconfigureTestNewTask');
        });
    });

    describe('collapsing locked TreeGrid', function() {
        var collapseSpy, expandSpy;

        it('should allow animated collapse and expand', function() {
            tree = new Ext.tree.Panel({
                renderTo: Ext.getBody(),
                layout: 'border',
                width: 400,
                height: 200,
                store: new Ext.data.TreeStore({
                    fields: ['Name', 'Age'],
                    root: {
                        Name: 'root',
                        expanded: true,
                        children: [{
                            Name: '1', Age: 1
                        }, {
                            Name: '2', Age: 2
                        }]
                    }
                }),
                syncRowHeight: false,
                lockedGridConfig: {
                    collapsible: true,
                    collapseDirection: 'left'
                },
                columns: [
                    { xtype: 'treecolumn', dataIndex: 'Name', width: 100, locked: true },
                    { dataIndex: 'Age', flex: 1 }
                ]
            });

            collapseSpy = spyOnEvent(tree.lockedGrid, 'collapse');
            expandSpy = spyOnEvent(tree.lockedGrid, 'expand');

            // None of the columns have a text or title config, so the headers should be hidden.
            expect(tree.lockedGrid.headerCt.getHeight()).toBe(0);
            expect(tree.normalGrid.headerCt.getHeight()).toBe(0);

            // Because locked side is collapsible, it will acquire a header.
            // Normal side should sync with this and have a header even though
            // we have not configured it with a title.
            expect(tree.lockedGrid.getHeader()).toBeTruthy();
            expect(tree.normalGrid.getHeader()).toBeTruthy();

            tree.lockedGrid.collapse();

            // Wait for the collapse event
            waitsFor(function() {
                return collapseSpy.callCount === 1;
            });

            runs(function() {
                tree.lockedGrid.expand();
            });

            // Wait for the expand event
            waitsFor(function() {
                return expandSpy.callCount === 1;
            });
        });
    });

    describe('auto hide headers, then headers arriving from a bind', function() {
        var store = Ext.create('Ext.data.TreeStore', {
            autoDestroy: true,
            root: {
                expanded: true,
                children: [{
                    text: 'detention',
                    leaf: true
                }, {
                    text: 'homework',
                    expanded: true,
                    children: [{
                        text: 'book report',
                        leaf: true
                    }, {
                        text: 'algebra',
                        leaf: true
                    }]
                }, {
                    text: 'buy lottery tickets',
                    leaf: true
                }]
            }
        });

        it('should show the headers as soon as any header acquires text', function() {
            tree = Ext.create('Ext.tree.Panel', {
                title: 'Simple Tree',
                width: 300,
                viewModel: {
                    data: {
                        headerText: 'A header'
                    }
                },
                store: store,
                rootVisible: false,
                renderTo: Ext.getBody(),
                columns: [{
                    bind: {
                        text: '{headerText}'
                    },
                    xtype: 'treecolumn',
                    dataIndex: 'text',
                    flex: 1
                }]
            });

            // No header text anywhere in the Panel
            expect(tree.headerCt.getHeight()).toBe(0);

            // When they arrive from the bind, that should change
            waitsFor(function() {
                return tree.headerCt.getHeight() > 0;
            });
        });
    });
});

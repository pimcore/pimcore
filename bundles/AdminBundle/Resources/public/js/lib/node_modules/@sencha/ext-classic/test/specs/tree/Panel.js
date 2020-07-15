topSuite("Ext.tree.Panel", [
    'Ext.grid.Panel',
    'Ext.app.ViewModel',
    'Ext.app.ViewController',
    'Ext.grid.column.Widget'
], function() {
    var itNotTouch = jasmine.supportsTouch ? xit : it,
        TreeItem = Ext.define(null, {
            extend: 'Ext.data.TreeModel',
            fields: ['id', 'text', 'secondaryId'],
            proxy: {
                type: 'memory'
            }
        }),
        tree, view, makeTree, testNodes, store, rootNode,
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

    beforeEach(function() {
        // Override so that we can control asynchronous loading
        Ext.data.TreeStore.prototype.load = loadStore;

        MockAjaxManager.addMethods();
        testNodes = [{
            id: 'A',
            text: 'A',
            secondaryId: 'AA',
            children: [{
                id: 'B',
                text: 'B',
                secondaryId: 'BB',
                children: [{
                    id: 'C',
                    text: 'C',
                    secondaryId: 'C',
                    leaf: true
                }, {
                    id: 'D',
                    text: 'D',
                    secondaryId: 'D',
                    leaf: true
                }]
            }, {
                id: 'E',
                text: 'E',
                secondaryId: 'EE',
                leaf: true
            }, {
                id: 'F',
                text: 'F',
                secondaryId: 'FF',
                children: [{
                    id: 'G',
                    text: 'G',
                    secondaryId: 'GG',
                    children: [{
                        id: 'H',
                        text: 'H',
                        secondaryId: 'HH',
                        leaf: true
                    }]
                }]
            }]
        }, {
            id: 'I',
            text: 'I',
            secondaryId: 'II',
            children: [{
                id: 'J',
                text: 'J',
                secondaryId: 'JJ',
                children: [{
                    id: 'K',
                    text: 'K',
                    secondaryId: 'KK',
                    leaf: true
                }]
            }, {
                id: 'L',
                text: 'L',
                secondaryId: 'LL',
                leaf: true
            }]
        }, {
            id: 'M',
            text: 'M',
            secondaryId: 'MM',
            children: [{
                id: 'N',
                text: 'N',
                secondaryId: 'NN',
                leaf: true
            }]
        }];

        makeTree = function(nodes, cfg, storeCfg, rootCfg) {
            cfg = cfg || {};
            Ext.applyIf(cfg, {
                animate: false,
                renderTo: Ext.getBody(),
                viewConfig: {
                    loadMask: false
                },
                store: store = new Ext.data.TreeStore(Ext.apply({
                    model: TreeItem,
                    root: Ext.apply({
                        secondaryId: 'root',
                        id: 'root',
                        text: 'Root',
                        children: nodes
                    }, rootCfg)
                }, storeCfg))
            });
            tree = new Ext.tree.Panel(cfg);
            view = tree.view;
            rootNode = tree.getRootNode();
        };
    });

    afterEach(function() {
        // Undo the overrides.
        Ext.data.TreeStore.prototype.load = treeStoreLoad;

        Ext.destroy(tree);
        tree = view = makeTree = testNodes = store = rootNode = null;
        MockAjaxManager.removeMethods();
    });

    describe("widget column", function() {
        it("should not garbage collect a widget after being collapsed", function() {
            makeTree([{
                id: 'a',
                text: 'A',
                expanded: true,
                children: [{
                    id: 'b',
                    text: 'B'
                }]
            }], {
                rootVisible: false,
                columns: [{
                    xtype: 'treecolumn',
                    dataIndex: 'text'
                }, {
                    xtype: 'widgetcolumn',
                    dataIndex: 'text',
                    widget: {
                        xtype: 'component'
                    }
                }]
            });
            var col = tree.getColumnManager().getColumns()[1],
                widget = col.getWidget(rootNode.firstChild.firstChild);

            rootNode.firstChild.collapse();
            Ext.dom.GarbageCollector.collect();
            expect(widget.el.destroyed).toBe(false);
        });
    });

    describe("scrolling", function() {
        function expectScroll(vertical, horizontal) {
            var dom = tree.getView().getEl().dom;

            // In Mac OS X, scrollbars can be invisible until user hovers mouse cursor
            // over the scrolled area. This is hard to test so we just assume that
            // in Mac browsers scrollbars can have 0 width.
            if (vertical !== undefined) {
                if (vertical) {
                    if (Ext.isMac) {
                        expect(dom.scrollHeight).toBeGreaterThanOrEqual(dom.clientHeight);
                    }
                    else {
                        expect(dom.scrollHeight).toBeGreaterThan(dom.clientHeight);
                    }
                }
                else {
                    expect(dom.scrollHeight).toBeLessThanOrEqual(dom.clientHeight);
                }
            }

            if (horizontal !== undefined) {
                if (horizontal) {
                    if (Ext.isMac) {
                        expect(dom.scrollWidth).toBeGreaterThanOrEqual(dom.clientWidth);
                    }
                    else {
                        expect(dom.scrollWidth).toBeGreaterThan(dom.clientWidth);
                    }
                }
                else {
                    expect(dom.scrollWidth).toBeLessThanOrEqual(dom.clientWidth);
                }
            }
        }

        function makeNodes(n) {
            var nodes = [],
                i;

            for (i = 1; i <= n; ++i) {
                nodes.push({
                    text: 'Node' + i
                });
            }

            return nodes;
        }

        describe("with no columns definition", function() {
            it("should not show scrollbars when not required", function() {
                makeTree([{
                    text: 'Foo'
                }], {
                    width: 400,
                    height: 400
                }, null, {
                    expanded: true
                });
                expectScroll(false, false);
            });

            it("should show a vertical scrollbar when required", function() {
                makeTree(makeNodes(50), {
                    width: 400,
                    height: 200
                }, null, {
                    expanded: true
                });
                expectScroll(true, false);
            });

            it("should show a horizontal scrollbar when required", function() {
                makeTree([{
                    text: 'A really long node that causes horizontal scroll'
                }], {
                    width: 200,
                    height: 200
                }, null, {
                    expanded: true
                });
                expectScroll(false, true);
                expect(view.getScrollable().getX()).toBe(true);
            });

            it("should show a scrollbar in both directions", function() {
                var nodes = makeNodes(50);

                nodes.unshift({
                    text: 'A really long node that causes horizontal scroll'
                });
                makeTree(nodes, {
                    width: 200,
                    height: 400
                }, null, {
                    expanded: true
                });
                expectScroll(true, true);
            });
        });
    });

    describe("Checkbox tree nodes", function() {
        var eventRec,
            record,
            row,
            checkbox,
            spy;

        function clickCheckboxId(id) {
            var checkbox = view.getRow(store.getById(id)).querySelector(view.checkboxSelector, true);

            jasmine.fireMouseEvent(checkbox, 'click');
        }

        function getCheckedCount() {
            var checkedNodes = [];

            store.getRootNode().cascade(function(node) {
                if (node.get('checked') === true) {
                    checkedNodes.push(node);
                }
            });

            return checkedNodes.length;
        }

        beforeEach(function() {
            eventRec = null;
            spy = jasmine.createSpy('spy');
            makeTree(testNodes, {
                listeners: {
                    checkchange: function(rec) {
                        eventRec = rec;
                        spy(rec);
                    }
                }
            });
            store.getRoot().cascade(function(r) {
                r.set('checked', false);
            });
            tree.expandAll();
            record = store.getAt(1);
            row = view.getRow(record);
            checkbox = row.querySelector(view.checkboxSelector);
        });

        describe("checkchange event", function() {
            it("should fire the checkchange event", function() {
                jasmine.fireMouseEvent(checkbox, 'click');
                expect(eventRec).toBe(record);
                expect(record.get('checked')).toBe(true);

                // Test that the default checkPropagation: 'none' is honoured.
                expect(getCheckedCount()).toBe(1);
            });

            it("should veto checkchange if false is returned from a beforecheckchange handler", function() {
                tree.on({
                    beforecheckchange: function(rec) {
                        eventRec = rec;

                        return false;
                    }
                });
                jasmine.fireMouseEvent(checkbox, 'click');
                expect(eventRec).toBe(record);
                expect(record.get('checked')).toBe(false);
            });

            describe("with checkPropagation", function() {
                it("should sync parent node's check state with state of children on child check change when checkPropagation:'up'", function() {
                    tree.checkPropagation = 'up';

                    // Both parent nodes start unchecked
                    expect(store.getById('I').get('checked')).toBe(false);
                    expect(store.getById('J').get('checked')).toBe(false);

                    clickCheckboxId('K');

                    // K's parent node J should be checked now. K is the sole child.
                    expect(store.getById('J').get('checked')).toBe(true);
                    expect(store.getById('I').get('checked')).toBe(false);

                    clickCheckboxId('L');

                    // All leaf nodes below I and J are now checked, so I and J should be
                    expect(store.getById('J').get('checked')).toBe(true);
                    expect(store.getById('I').get('checked')).toBe(true);

                    // B only gets checked when both D and C are checked
                    expect(store.getById('B').get('checked')).toBe(false);
                    clickCheckboxId('D');
                    expect(store.getById('B').get('checked')).toBe(false);
                    clickCheckboxId('C');
                    expect(store.getById('B').get('checked')).toBe(true);

                    // Now reverse that process and uncheck B
                    clickCheckboxId('D');
                    expect(store.getById('B').get('checked')).toBe(false);
                    clickCheckboxId('C');
                    expect(store.getById('B').get('checked')).toBe(false);

                    // And finally, clicking a parent, should NOT propagate the checked
                    // state downwards with checkPropagation:'up'
                    clickCheckboxId('B');
                    expect(store.getById('C').get('checked')).toBe(false);
                    expect(store.getById('D').get('checked')).toBe(false);
                });
                it("should propagate a parent's checked state to child nodes when checkPropagation:'down'", function() {
                    tree.checkPropagation = 'down';

                    // Start with none checked
                    expect(getCheckedCount()).toBe(0);

                    clickCheckboxId('A');
                    expect(store.getById('B').get('checked')).toBe(true);
                    expect(store.getById('C').get('checked')).toBe(true);
                    expect(store.getById('D').get('checked')).toBe(true);
                    expect(store.getById('E').get('checked')).toBe(true);
                    expect(store.getById('F').get('checked')).toBe(true);
                    expect(store.getById('G').get('checked')).toBe(true);
                    expect(store.getById('H').get('checked')).toBe(true);

                    // Just A and its descendants should be checked.
                    expect(getCheckedCount()).toBe(8);
                });
                it("should propagate checked state both ways when checkPropagation:'both'", function() {
                    tree.checkPropagation = 'both';

                    // Start with none checked
                    expect(getCheckedCount()).toBe(0);

                    clickCheckboxId('A');
                    expect(store.getById('B').get('checked')).toBe(true);
                    expect(store.getById('C').get('checked')).toBe(true);
                    expect(store.getById('D').get('checked')).toBe(true);
                    expect(store.getById('E').get('checked')).toBe(true);
                    expect(store.getById('F').get('checked')).toBe(true);
                    expect(store.getById('G').get('checked')).toBe(true);
                    expect(store.getById('H').get('checked')).toBe(true);

                    // Just A and its descendants should be checked.
                    expect(getCheckedCount()).toBe(8);

                    // And one more click should go back to zero
                    clickCheckboxId('A');
                    expect(getCheckedCount()).toBe(0);

                    // Should propagate up to F
                    clickCheckboxId('H');
                    expect(store.getById('F').get('checked')).toBe(true);
                    expect(store.getById('G').get('checked')).toBe(true);
                    expect(getCheckedCount()).toBe(3);

                    // This should restore the whole 'A' subtree to checkedness
                    clickCheckboxId('E');
                    clickCheckboxId('D');
                    clickCheckboxId('C');

                    expect(store.getById('B').get('checked')).toBe(true);
                    expect(store.getById('C').get('checked')).toBe(true);
                    expect(store.getById('D').get('checked')).toBe(true);
                    expect(store.getById('E').get('checked')).toBe(true);
                    expect(store.getById('F').get('checked')).toBe(true);
                    expect(store.getById('G').get('checked')).toBe(true);
                    expect(store.getById('H').get('checked')).toBe(true);

                    // Just A and its descendants should be checked.
                    expect(getCheckedCount()).toBe(8);
                });

                it("should fire the checkevent only once when it has a parent and it's not changing the parent's status", function() {
                    tree.checkPropagation = 'both';
                    clickCheckboxId('C');

                    // needs to be waits because we are waiting for something not to happen
                    waits(100);

                    runs(function() {
                        expect(spy.callCount).toBe(1);
                    });
                });

                it("should fire the checkevent an additional time if changing the parent's status", function() {
                    tree.checkPropagation = 'both';
                    clickCheckboxId('C');
                    clickCheckboxId('D');

                    waitsFor(function() {
                        return spy.callCount === 3;
                    });

                    runs(function() {
                        expect(spy.mostRecentCall.args[0].getId()).toBe('B');
                    });
                });
            });
        });
    });

    // https://sencha.jira.com/browse/EXTJS-16367
    describe("record with a cls field", function() {
        it("should set the cls on the TD element", function() {
            makeTree(testNodes);
            var createRowSpy = spyOn(view, 'createRowElement').andCallThrough();

            rootNode.childNodes[0].set('cls', 'foobar');
            rootNode.expand();
            expect(view.all.item(1).down('td', true)).toHaveCls('foobar');

            // The cls is applied to the TD, so the row will have to be created. Cannot use in-cell updating
            rootNode.childNodes[0].set('cls', 'bletch');
            expect(createRowSpy).toHaveBeenCalled();
            expect(view.all.item(1).down('td', true)).not.toHaveCls('foobar');
            expect(view.all.item(1).down('td', true)).toHaveCls('bletch');
        });
    });

    describe("construction", function() {
        it("should render while the root node is loading", function() {
            expect(function() {
                makeTree(null, null, {
                    proxy: {
                        type: 'ajax',
                        url: 'fake'
                    }
                }, {
                    expanded: true
                });
            }).not.toThrow();
        });

        describe("with invisible root", function() {
            it("should expand the root node by default", function() {
                makeTree(null, {
                    rootVisible: false
                });

                expect(rootNode.isExpanded()).toBe(true);
            });

            it("should skip root.expand() when root is loaded", function() {
                spyOn(TreeItem.prototype, 'expand').andCallThrough();
                spyOn(Ext.data.TreeStore.prototype, 'onNodeExpand').andCallThrough();

                makeTree(null, {
                    rootVisible: false
                }, null, {
                    // Pretend that the root node is loaded
                    loaded: true
                });

                expect(rootNode.expand).not.toHaveBeenCalled();
                expect(rootNode.data.expanded).toBe(true);
                expect(store.onNodeExpand).toHaveBeenCalled();
            });

            it("should not expand the root node when store.autoLoad === false", function() {
                makeTree(null, {
                    rootVisible: false
                }, {
                    autoLoad: false
                });

                expect(rootNode.isExpanded()).toBe(false);
            });

            it("should not expand the root node when store has pending load", function() {
                makeTree(null, {
                    rootVisible: false
                }, {
                    // Pretend that we're loading the store
                    loading: true
                });

                expect(rootNode.isExpanded()).toBe(false);
            });
        });
    });

    describe("setting the root node", function() {
        it("should set the nodes correctly when setting root on the store", function() {
            makeTree();
            store.setRootNode({
                expanded: true,
                children: testNodes
            });
            expect(store.getCount()).toBe(4);
            expect(store.getAt(0).id).toBe('root');
            expect(store.getAt(1).id).toBe('A');
            expect(store.getAt(2).id).toBe('I');
            expect(store.getAt(3).id).toBe('M');
        });

        it("should set the nodes correctly when setting root on the tree", function() {
            makeTree();
            tree.setRootNode({
                expanded: true,
                children: testNodes
            });
            expect(store.getCount()).toBe(4);
            expect(store.getAt(0).id).toBe('root');
            expect(store.getAt(1).id).toBe('A');
            expect(store.getAt(2).id).toBe('I');
            expect(store.getAt(3).id).toBe('M');
        });

        it("should preserve events", function() {
            var spy = jasmine.createSpy();

            var root2 = {
                expanded: true,
                children: testNodes
            };

            makeTree();
            tree.on({
                beforeitemcollapse: spy,
                beforeitemexpand: spy,
                itemcollapse: spy,
                itemexpand: spy
            });
            tree.setRootNode(root2);

            rootNode = tree.getRootNode();
            rootNode.childNodes[0].expand();
            rootNode.childNodes[0].collapse();

            expect(spy.callCount).toBe(4);
        });
    });

    describe("Binding to a TreeStore", function() {
        it("should bind to a TreeStore in the ViewModel", function() {
            makeTree(testNodes, {
                viewModel: {
                    stores: {
                        nodes: {
                            type: 'tree',
                            model: TreeItem,
                            root: {
                                secondaryId: 'root',
                                id: 'root',
                                text: 'Root',
                                children: testNodes,
                                expanded: true
                            }
                        }
                    }
                },
                store: null,
                bind: '{nodes}'
            });

            tree.getViewModel().notify();

            expect(tree.getRootNode().childNodes.length).toBe(3);
            expect(tree.getView().all.getCount()).toBe(4);
        });

        it("should bind to a TreeStore in the ViewModel with locked columns", function() {
            makeTree(testNodes, {
                viewModel: {
                    stores: {
                        nodes: {
                            type: 'tree',
                            model: TreeItem,
                            root: {
                                secondaryId: 'root',
                                id: 'root',
                                text: 'Root',
                                children: testNodes,
                                expanded: true
                            }
                        }
                    }
                },
                store: null,
                bind: '{nodes}',
                columns: [{
                    dataIndex: 'text',
                    locked: true,
                    xtype: 'treecolumn',
                    width: 100
                }, {
                    dataIndex: 'secondaryId',
                    flex: 1
                }]
            });

            tree.getViewModel().notify();

            expect(tree.getRootNode().childNodes.length).toBe(3);
            expect(tree.getView().all.getCount()).toBe(4);
        });
    });

    describe("mouse click to expand/collapse", function() {
        function makeAutoTree(animate, data, cfg) {
            makeTree(data, Ext.apply({
                animate: animate
            }, cfg), null, {
                expanded: true
            });
        }

        describe("Clicking on expander", function() {
            it("should not fire a click event on click of expnder", function() {
                makeAutoTree(true, [{
                    id: 'a',
                    expanded: false,
                    children: [{
                        id: 'b'
                    }]
                }]);
                var spy = jasmine.createSpy(),
                    cellClickSpy = jasmine.createSpy(),
                    itemClickSpy = jasmine.createSpy(),
                    height = tree.getHeight(),
                    expander = view.getCell(1, 0).querySelector(view.expanderSelector),
                    cell10 = new Ext.grid.CellContext(view).setPosition(1, 0);

                // Focus must be on the tree cell upon expand
                tree.on('expand', function() {
                    expect(Ext.Element.getActiveElement).toBe(cell10.getCell(true));
                });
                tree.on('afteritemexpand', spy);
                tree.on('cellclick', cellClickSpy);
                tree.on('itemclick', itemClickSpy);
                jasmine.fireMouseEvent(expander, 'click');
                waitsFor(function() {
                    return spy.callCount > 0;
                });
                runs(function() {
                    expect(tree.getHeight()).toBeGreaterThan(height);

                    // Clicking on an expander should not trigger a cell click
                    expect(cellClickSpy).not.toHaveBeenCalled();

                    // Clicking on an expander should not trigger an item click
                    expect(itemClickSpy).not.toHaveBeenCalled();
                });
            });
        });

    });

    describe("auto height with expand/collapse", function() {
        function makeAutoTree(animate, data, cfg) {
            makeTree(data, Ext.apply({
                animate: animate,
                expandDuration: 100,
                collapseDuration: 100
            }, cfg), null, {
                expanded: true
            });
        }

        describe("with animate: true", function() {
            it("should update the height after an expand animation", function() {
                makeAutoTree(true, [{
                    id: 'a',
                    expanded: false,
                    children: [{
                        id: 'b'
                    }]
                }]);
                var spy = jasmine.createSpy(),
                    height = tree.getHeight();

                tree.on('afteritemexpand', spy);
                tree.getRootNode().firstChild.expand();
                waitsFor(function() {
                    return spy.callCount > 0;
                });
                runs(function() {
                    expect(tree.getHeight()).toBeGreaterThan(height);
                });
            });

            it("should update the height after a collapse animation", function() {
                makeAutoTree(true, [{
                    id: 'a',
                    expanded: true,
                    children: [{
                        id: 'b'
                    }]
                }]);
                var spy = jasmine.createSpy(),
                    height = tree.getHeight();

                tree.on('afteritemcollapse', spy);
                tree.getRootNode().firstChild.collapse();
                waitsFor(function() {
                    return spy.callCount > 0;
                });
                runs(function() {
                    expect(tree.getHeight()).toBeLessThan(height);
                });
            });

            it("should not scroll up when collapse/expand nodes", function() {
                var spy = jasmine.createSpy(),
                    rec, node, expander, scrollable, y, initialY;

                makeAutoTree(true, [{
                    secondaryId: 'root',
                    id: 'a',
                    text: 'Root',
                    expanded: true,
                    children: [
                        { id: 'a', expanded: true },
                        { id: 'b', leaf: true },
                        { id: 'c', leaf: true },
                        { id: 'd', leaf: true },
                        { id: 'e', leaf: true },
                        { id: 'f', leaf: true },
                        { id: 'g', leaf: true },
                        { id: 'h', leaf: true },
                        { id: 'i', leaf: true },
                        { id: 'j', leaf: true },
                        { id: 'k', expanded: false, children: [{ id: 'l', leaf: true }] }
                    ]
                }], {
                    maxWidth: 400,
                    maxHeight: 100
                });

                scrollable = view.getScrollable();
                rec = store.getById('k');
                node = view.getNodeByRecord(rec);
                expander = node.querySelector('.x-tree-expander');

                scrollable.scrollTo(0, Infinity);
                initialY = scrollable.getPosition().y;

                jasmine.fireMouseEvent(expander, 'click');

                tree.on('afteritemexpand', spy);

                waitsFor(function() {
                    return spy.callCount;
                });

                runs(function() {
                    y = scrollable.getPosition().y;

                    expect(y).not.toBe(0);
                    expect(y).toBe(initialY);
                });
            });
        });

        describe("with animate: false", function() {
            it("should update the height after an expand animation", function() {
                makeAutoTree(false, [{
                    id: 'a',
                    expanded: false,
                    children: [{
                        id: 'b'
                    }]
                }]);

                var height = tree.getHeight();

                tree.getRootNode().firstChild.expand();
                expect(tree.getHeight()).toBeGreaterThan(height);
            });

            it("should update the height after a collapse animation", function() {
                makeAutoTree(false, [{
                    id: 'a',
                    expanded: true,
                    children: [{
                        id: 'b'
                    }]
                }]);

                var height = tree.getHeight();

                tree.getRootNode().firstChild.collapse();
                expect(tree.getHeight()).toBeLessThan(height);
            });
        });
    });

    describe("collapsing when collapse zone overflows the rendered zone", function() {
        beforeEach(function() {
            for (var i = 0; i < 100; i++) {
                testNodes[0].children.push({
                    text: 'Extra node ' + i,
                    id: 'extra-node-' + i
                });
            }

            testNodes[0].expanded = true;

            makeTree(testNodes, {
                renderTo: document.body,
                height: 200,
                width: 400
            }, null, {
                expanded: true
            });
        });

        it("should collapse correctly, leaving the collapsee's siblings visible", function() {
            // Collapse node "A".
            tree.getRootNode().childNodes[0].collapse();

            // We now should have "Root", and nodes "A", "I" and "M"
            // https://sencha.jira.com/browse/EXTJS-13908
            expect(tree.getView().all.getCount()).toBe(4);
        });
    });

    describe("sortchange", function() {
        it("should only fire a single sortchange event", function() {
            var spy = jasmine.createSpy();

            makeTree(testNodes, {
                columns: [{
                    xtype: 'treecolumn',
                    dataIndex: 'text'
                }]
            });
            tree.on('sortchange', spy);
            // Pass the position so we don't click right on the edge (trigger a resize)
            jasmine.fireMouseEvent(tree.down('treecolumn').titleEl.dom, 'click', 20, 10);
            expect(spy).toHaveBeenCalled();
            expect(spy.callCount).toBe(1);
        });
    });

    describe("reconfigure", function() {
        beforeEach(function() {
            makeTree(Ext.clone(testNodes), {
                rootVisible: false,
                singleExpand: true,
                height: 200
            }, null, {
                expanded: true
            });
        });
        it("should preserve singleExpand:true", function() {
            // Expand childNodes[0]
            rootNode.childNodes[0].expand();
            expect(rootNode.childNodes[0].isExpanded()).toBe(true);

            // This must collapse childNodes[0] while expanding childNodes[1] because of singleExpand
            rootNode.childNodes[1].expand();
            expect(rootNode.childNodes[0].isExpanded()).toBe(false);
            expect(rootNode.childNodes[1].isExpanded()).toBe(true);

            // Three root's childNodes plus the two child nodes of childNode[1]
            expect(store.getCount()).toBe(5);

            // Identical Store to reconfigure with
            var newStore = new Ext.data.TreeStore({
                model: TreeItem,
                root: {
                    secondaryId: 'root',
                    id: 'root',
                    text: 'Root',
                    children: testNodes,
                    expanded: true
                }
            });

            tree.reconfigure(newStore);
            rootNode = newStore.getRootNode();

            // Back down to just the three root childNodes.
            expect(newStore.getCount()).toBe(3);

            // Expand childNodes[0]
            rootNode.childNodes[0].expand();
            expect(rootNode.childNodes[0].isExpanded()).toBe(true);

            // This must collapse childNodes[0] while expanding childNodes[1] because of singleExpand
            rootNode.childNodes[1].expand();
            expect(rootNode.childNodes[0].isExpanded()).toBe(false);
            expect(rootNode.childNodes[1].isExpanded()).toBe(true);

            // Three root's childNodes plus the two child nodes of childNode[1]
            expect(newStore.getCount()).toBe(5);
        });
    });

    describe("autoexpand collapsed ancestors", function() {
        beforeEach(function() {
            makeTree(testNodes, {
                height: 250
            });
        });
        it("should expand the whole path down to 'G' as well as 'G'", function() {
            // Start off with only the root visible.
            expect(store.getCount()).toBe(1);

            tree.getStore().getNodeById('G').expand();

            // "A" should be expanded all the way down to "H", then "I", then "M"
            expect(store.getCount()).toBe(9);
        });
    });

    describe("removeAll", function() {
        beforeEach(function() {
            makeTree(testNodes, {
                height: 100
            });
        });
        it("should only refresh once when removeAll called", function() {
            var nodeA = tree.getStore().getNodeById('A'),
                buffered;

            expect(tree.view.refreshCounter).toBe(1);
            tree.expandAll();
            buffered = view.bufferedRenderer && view.all.getCount >= view.bufferedRenderer.viewSize;

            // With all the nodes fully preloaded, a recursive expand
            // should do one refresh.
            expect(view.refreshCounter).toBe(2);

            // The bulkremove event fired by NodeInterface.removeAll should trigger the NodeStore call onNodeCollapse.
            // In response, the NodeStore removes all child nodes, and fired bulkremove. The BufferedRendererTreeView
            // override processes the removal without calling view's refresh.
            // Refresh will only be called if buffered rendering has been *used*, ie if the number of rows has reached
            // the buffered renderer's view size. If not, a regular non-buffered type update will handle the remove
            // and the refresh count will still be 2.
            nodeA.removeAll();
            expect(view.refreshCounter).toBe(buffered ? 3 : 2);
        });
    });

    describe("Getting owner tree", function() {
        beforeEach(function() {
            makeTree(testNodes);
        });
        it("should find the owner tree", function() {
            var store = tree.getStore(),
                h = store.getNodeById('H');

            expect(h.getOwnerTree()).toBe(tree);
        });
    });

    describe("updating row attributes", function() {
        beforeEach(function() {
            makeTree(testNodes);
        });

        it("should set the data-qtip attribute", function() {
            var rootRow = tree.view.getRow(rootNode),
                rootCls = rootRow.className;

            rootNode.set('qtip', 'Foo');

            // Class should not change
            expect(rootRow.className).toBe(rootCls);

            // data-qtip must be set
            expect(rootRow.getAttribute('data-qtip')).toBe('Foo');
        });

        it("should add the expanded class on expand", function() {
            var view = tree.getView(),
                cls = view.expandedCls;

            expect(view.getRow(rootNode)).not.toHaveCls(cls);
            rootNode.expand();
            expect(view.getRow(rootNode)).toHaveCls(cls);
        });

        it("should remove the expanded class on collapse", function() {
            var view = tree.getView(),
                cls = view.expandedCls;

            rootNode.expand();
            expect(view.getRow(rootNode)).toHaveCls(cls);
            rootNode.collapse();
            expect(view.getRow(rootNode)).not.toHaveCls(cls);
        });
    });

    describe("expandPath/selectPath", function() {
        describe("expandPath", function() {
            var expectedSuccess, expectedNode;

            beforeEach(function() {
                expectedSuccess = false;
                makeTree(testNodes);
            });

            describe("callbacks", function() {

                describe("empty path", function() {
                    it("should fire the callback with success false & a null node", function() {
                        tree.expandPath('', null, null, function(success, node) {
                            expectedSuccess = success;
                            expectedNode = node;
                        });
                        expect(expectedSuccess).toBe(false);
                        expect(expectedNode).toBeNull();
                    });

                    it("should default the scope to the tree", function() {
                        var scope;

                        tree.expandPath('', null, null, function() {
                            scope = this;
                        });
                        expect(scope).toBe(tree);
                    });

                    it("should use any specified scope", function() {
                        var o = {},
                            scope;

                        tree.expandPath('', null, null, function() {
                            scope = this;
                        }, o);

                        expect(scope).toBe(o);
                    });
                });

                describe("invalid root", function() {
                    it("should fire the callback with success false & the root", function() {
                        tree.expandPath('/NOTROOT', null, null, function(success, node) {
                            expectedSuccess = success;
                            expectedNode = node;
                        });

                        expect(expectedSuccess).toBe(false);
                        expect(expectedNode).toBe(tree.getRootNode());
                    });

                    it("should default the scope to the tree", function() {
                        var scope;

                        tree.expandPath('/NOTROOT', null, null, function() {
                            scope = this;
                        });

                        expect(scope).toBe(tree);
                    });

                    it("should use any specified scope", function() {
                        var o = {},
                            scope;

                        tree.expandPath('/NOTROOT', null, null, function() {
                            scope = this;
                        }, o);

                        expect(scope).toBe(o);
                    });
                });

                describe("fully successful expand", function() {
                    describe("Old API", function() {
                        it("should fire the callback with success true and the last node", function() {
                            tree.expandPath('/root/A/B', null, null, function(success, lastExpanded) {
                                expectedSuccess = success;
                                expectedNode = lastExpanded;
                            });
                            expect(expectedSuccess).toBe(true);
                            expect(expectedNode).toBe(tree.getStore().getNodeById('B'));
                            expect(view.all.getCount()).toBe(9);
                        });

                        it("should default the scope to the tree", function() {
                            var scope;

                            tree.expandPath('/root/A/B', null, null, function(success, lastExpanded) {
                                scope = this;
                            });

                            expect(scope).toBe(tree);
                        });

                        it("should use any specified scope", function() {
                            var o = {},
                                scope;

                            tree.expandPath('/root/A/B', null, null, function(success, lastExpanded) {
                                scope = this;
                            }, o);

                            expect(scope).toBe(o);
                        });

                        it("should be able to start from any existing node", function() {
                            tree.expandPath('G', null, null, function(success, lastExpanded) {
                                expectedSuccess = success;
                                expectedNode = lastExpanded;
                            });
                            expect(expectedSuccess).toBe(true);
                            expect(expectedNode).toBe(store.getNodeById('G'));
                            expect(view.all.getCount()).toBe(9);
                        });
                    });
                    describe("New API", function() {
                        var lastHtmlNode;

                        it("should fire the callback with success true and the last node", function() {
                            tree.expandPath('/root/A/B', {
                                callback: function(success, lastExpanded, lastNode) {
                                    expectedSuccess = success;
                                    expectedNode = lastExpanded;
                                    lastHtmlNode = lastNode;
                                },
                                select: true
                            });
                            waitsFor(function() {
                                return expectedSuccess;
                            });
                            runs(function() {
                                expect(expectedNode).toBe(tree.getStore().getNodeById('B'));
                                expect(view.all.getCount()).toBe(9);
                                expect(tree.getSelectionModel().getSelection()[0]).toBe(expectedNode);
                                expect(lastHtmlNode).toBe(view.getNode(tree.getStore().getNodeById('B')));
                            });
                        });

                        it("should default the scope to the tree", function() {
                            var scope;

                            tree.expandPath('/root/A/B', {
                                callback: function(success, lastExpanded) {
                                    scope = this;
                                }
                            });

                            waitsFor(function() {
                                return scope === tree;
                            });
                        });

                        it("should use any specified scope", function() {
                            var o = {},
                                scope;

                            tree.expandPath('/root/A/B', {
                                callback:
                                    function(success, lastExpanded) {
                                    scope = this;
                                },
                                scope: o
                            });
                            waitsFor(function() {
                                return scope === o;
                            });
                        });

                        it("should be able to start from any existing node", function() {
                            tree.expandPath('G', {
                                callback: function(success, lastExpanded) {
                                    expectedSuccess = success;
                                    expectedNode = lastExpanded;
                                }
                            });
                            waitsFor(function() {
                                return expectedSuccess;
                            });
                            runs(function() {
                                expect(expectedNode).toBe(store.getNodeById('G'));
                                expect(view.all.getCount()).toBe(9);
                            });
                        });
                    });
                });

                describe("partial expand", function() {
                    it("should fire the callback with success false and the last successful node", function() {
                        tree.expandPath('/root/A/FAKE', null, null, function(success, node) {
                            expectedSuccess = success;
                            expectedNode = node;
                        });
                        expect(expectedSuccess).toBe(false);
                        expect(expectedNode).toBe(tree.getStore().getById('A'));
                    });

                    it("should default the scope to the tree", function() {
                        var scope;

                        tree.expandPath('/root/A/FAKE', null, null, function() {
                            scope = this;
                        });
                        expect(scope).toBe(tree);
                    });

                    it("should use any specified scope", function() {
                        var o = {},
                            scope;

                        tree.expandPath('/root/A/FAKE', null, null, function() {
                            scope = this;
                        }, o);

                        expect(scope).toBe(o);
                    });
                });
            });

            describe("custom field", function() {
                it("should default the field to the idProperty", function() {
                    tree.expandPath('/root/M');
                    expect(tree.getStore().getById('M').isExpanded()).toBe(true);
                });

                it("should accept a custom field from the model", function() {
                    tree.expandPath('/root/AA/FF/GG', 'secondaryId');
                    expect(tree.getStore().getById('G').isExpanded()).toBe(true);
                });
            });

            describe("custom separator", function() {
                it("should default the separator to /", function() {
                    tree.expandPath('/root/A');
                    expect(tree.getStore().getById('A').isExpanded()).toBe(true);
                });

                it("should accept a custom separator", function() {
                    tree.expandPath('|root|A|B', null, '|');
                    expect(tree.getStore().getById('B').isExpanded()).toBe(true);
                });
            });

            describe("various path tests", function() {
                it("should expand the root node", function() {
                    tree.expandPath('/root');
                    expect(tree.getRootNode().isExpanded()).toBe(true);
                });

                it("should fire success if the ending node is a leaf", function() {
                    tree.expandPath('/root/I/L', null, null, function(success, node) {
                        expectedSuccess = success;
                        expectedNode = node;
                    });
                    expect(expectedSuccess).toBe(true);
                    expect(expectedNode).toBe(tree.getStore().getById('L'));
                });
            });

        });

        describe("selectPath", function() {
            var isSelected = function(id) {
                var node = tree.getStore().getById(id);

                return tree.getSelectionModel().isSelected(node);
            };

            var spy = jasmine.createSpy(),
                expectedSuccess;

            beforeEach(function() {
                expectedSuccess = false;
                spy.reset();
                makeTree(testNodes);
            });

            describe("callbacks", function() {

                describe("empty path", function() {
                    it("should fire the callback with success false & a null node", function() {
                        tree.selectPath('', null, null, spy);

                        waitsForSpy(spy);
                        runs(function() {
                            expect(spy.mostRecentCall.args[0]).toBe(false);
                            expect(spy.mostRecentCall.args[1]).toBeNull();
                        });
                    });

                    it("should default the scope to the tree", function() {
                        tree.selectPath('', null, null, spy);

                        waitsForSpy(spy);
                        runs(function() {
                            expect(spy.mostRecentCall.scope).toBe(tree);
                        });
                    });

                    it("should use any specified scope", function() {
                        var o = {};

                        tree.selectPath('', null, null, spy, o);

                        waitsForSpy(spy);
                        runs(function() {
                            expect(spy.mostRecentCall.scope).toBe(o);
                        });
                    });
                });

                describe("root", function() {
                    it("should fire the callback with success true & the root", function() {
                        tree.selectPath('/root', null, null, spy);

                        waitsForSpy(spy);
                        runs(function() {
                            expect(spy.mostRecentCall.args[0]).toBe(true);
                            expect(spy.mostRecentCall.args[1]).toBe(tree.getRootNode());
                        });
                    });

                    it("should default the scope to the tree", function() {
                        tree.selectPath('/root', null, null, spy);

                        waitsForSpy(spy);
                        runs(function() {
                            expect(spy.mostRecentCall.scope).toBe(tree);
                        });
                    });

                    it("should use any specified scope", function() {
                        var o = {};

                        tree.selectPath('/root', null, null, spy, o);

                        waitsForSpy(spy);
                        runs(function() {
                            expect(spy.mostRecentCall.scope).toBe(o);
                        });
                    });
                });

                describe("fully successful expand", function() {
                    it("should fire the callback with success true and the last node", function() {
                        tree.selectPath('/root/A/B', null, null, spy);

                        waitsForSpy(spy);
                        runs(function() {
                            expect(spy.mostRecentCall.args[0]).toBe(true);
                            expect(spy.mostRecentCall.args[1]).toBe(tree.getStore().getById('B'));
                        });
                    });

                    it("should default the scope to the tree", function() {
                        tree.selectPath('/root/A/B', null, null, spy);

                        waitsForSpy(spy);
                        runs(function() {
                            expect(spy.mostRecentCall.scope).toBe(tree);
                        });
                    });

                    it("should use any specified scope", function() {
                        var o = {};

                        tree.selectPath('/root/A/B', null, null, spy, o);

                        waitsForSpy(spy);
                        runs(function() {
                            expect(spy.mostRecentCall.scope).toBe(o);
                        });
                    });
                });

                describe("partial expand", function() {
                    it("should fire the callback with success false and the last successful node", function() {
                        var expectedSuccess, expectedNode;

                        tree.selectPath('/root/A/FAKE', null, null, function(success, node) {
                            expectedSuccess = success;
                            expectedNode = node;
                        });
                        expect(expectedSuccess).toBe(false);
                        expect(expectedNode).toBe(tree.getStore().getById('A'));
                    });

                    it("should default the scope to the tree", function() {
                        var scope;

                        tree.selectPath('/root/A/FAKE', null, null, function() {
                            scope = this;
                        });
                        expect(scope).toBe(tree);
                    });

                    it("should use any specified scope", function() {
                        var o = {},
                            scope;

                        tree.selectPath('/root/A/FAKE', null, null, function() {
                            scope = this;
                        }, o);
                        expect(scope).toBe(o);
                    });
                });
            });

            describe("custom field", function() {
                it("should default the field to the idProperty", function() {
                    tree.selectPath('/root/M', null, null, spy);

                    waitsForSpy(spy);
                    runs(function() {
                        expect(isSelected('M')).toBe(true);
                    });
                });

                it("should accept a custom field from the model", function() {
                    tree.selectPath('/root/AA/FF/GG', 'secondaryId', null, spy);

                    waitsForSpy(spy);
                    runs(function() {
                        expect(isSelected('G')).toBe(true);
                    });
                });
            });

            describe("custom separator", function() {
                it("should default the separator to /", function() {
                    tree.selectPath('/root/A', null, null, spy);

                    waitsForSpy(spy);
                    runs(function() {
                        expect(isSelected('A')).toBe(true);
                    });
                });

                it("should accept a custom separator", function() {
                    tree.selectPath('|root|A|B', null, '|', spy);

                    waitsForSpy(spy);
                    runs(function() {
                        expect(isSelected('B')).toBe(true);
                    });
                });
            });

            describe("various paths", function() {
                it("should be able to select the root", function() {
                    tree.selectPath('/root', null, null, spy);

                    waitsForSpy(spy);
                    runs(function() {
                        expect(isSelected('root')).toBe(true);
                    });
                });

                it("should select a leaf node", function() {
                    tree.selectPath('/root/I/L', null, null, spy);

                    waitsForSpy(spy);
                    runs(function() {
                        expect(isSelected('L')).toBe(true);
                    });
                });

                it("should not select a node if the full path isn't resolved", function() {
                    tree.selectPath('/root/I/FAKE', null, null, spy);

                    waitsForSpy(spy);
                    runs(function() {
                        expect(tree.getSelectionModel().getSelection().length).toBe(0);
                    });
                });
            });
        });

        describe("special cases", function() {
            var spy = jasmine.createSpy();

            beforeEach(function() {
                spy.reset();
            });

            it("should be able to select a path where the values are numeric", function() {
                Ext.define(null, {
                    extend: 'Ext.data.TreeModel',
                    fields: [{
                        name: 'id',
                        type: 'int'
                    }]
                });

                makeTree([{
                    id: 1,
                    text: 'A'
                }, {
                    id: 2,
                    text: 'B',
                    children: [{
                        id: 3,
                        text: 'B1',
                        children: [{
                            id: 4,
                            text: 'B1_1'
                        }]
                    }, {
                        id: 5,
                        text: 'B2',
                        children: [{
                            id: 6,
                            text: 'B2_1'
                        }]
                    }]
                }], null, null, {
                    id: -1
                });

                tree.selectPath('2/3/4', null, null, spy);
                waitsForSpy(spy);
                runs(function() {
                    var selection = tree.getSelectionModel().getSelection();

                    expect(selection.length).toBe(1);
                    expect(selection[0]).toBe(store.getNodeById(4));
                });
            });

            // https://sencha.jira.com/browse/EXTJS-16667
            it("should be able to select absolute path with numeric ids", function() {
                tree = Ext.create('Ext.tree.Panel', {
                    renderTo: Ext.getBody(),
                    store: {
                        type: 'tree',
                        root: {
                            id: 0,
                            text: 'root',
                            expanded: true,
                            children: [{
                                id: 1,
                                text: 'child1'
                            }]
                        }
                    }
                });

                tree.selectPath('/0/1', null, null, spy);

                waitsForSpy(spy);
                runs(function() {
                    var selection = tree.getSelectionModel().getSelection();

                    expect(selection.length).toBe(1);
                    expect(selection[0]).toBe(tree.getStore().getNodeById(1));
                });
            });

            it("should be able to select a path when subclassing Ext.tree.Panel", function() {
                var Cls = Ext.define(null, {
                    extend: 'Ext.tree.Panel',
                    animate: false,
                    viewConfig: {
                        loadMask: false
                    }
                });

                tree = new Cls({
                    renderTo: Ext.getBody(),
                    store: store = new Ext.data.TreeStore({
                        model: TreeItem,
                        root: {
                            secondaryId: 'root',
                            id: 'root',
                            text: 'Root',
                            children: testNodes
                        }
                    })
                });
                tree.selectPath('/root/A/B/C', null, null, spy);

                waitsForSpy(spy);
                runs(function() {
                    expect(tree.getSelectionModel().isSelected(store.getNodeById('C')));
                });
            });
        });

    });

    describe("expand/collapse", function() {
        var startingLayoutCounter;

        beforeEach(function() {
            makeTree(testNodes);
            startingLayoutCounter = tree.layoutCounter;
        });

        describe("expandAll", function() {

            describe("callbacks", function() {
                it("should pass the direct child nodes of the root", function() {
                    var expectedNodes,
                        callCount = 0,
                        store = tree.getStore();

                    tree.expandAll(function(nodes) {
                        expectedNodes = nodes;
                        callCount++;
                    });

                    expect(callCount).toEqual(1);
                    expect(expectedNodes[0]).toBe(store.getById('A'));
                    expect(expectedNodes[1]).toBe(store.getById('I'));
                    expect(expectedNodes[2]).toBe(store.getById('M'));

                    // Only one layout should have taken place
                    expect(tree.layoutCounter).toBe(startingLayoutCounter + 1);
                });

                it("should default the scope to the tree", function() {
                    var expectedScope;

                    tree.expandAll(function() {
                        expectedScope = this;
                    });
                    expect(expectedScope).toBe(tree);
                });

                it("should use a passed scope", function() {
                    var o = {},
                        expectedScope;

                    tree.expandAll(function() {
                        expectedScope = this;
                    }, o);

                    expect(expectedScope).toBe(o);
                });
            });

            it("should expand all nodes", function() {
                tree.expandAll();
                Ext.Array.forEach(tree.store.getRange(), function(node) {
                    if (!node.isLeaf()) {
                        expect(node.isExpanded()).toBe(true);
                    }
                });
            });

            it("should continue down the tree even if some nodes are expanded", function() {
                var store = tree.getStore();

                store.getNodeById('A').expand();
                store.getNodeById('I').expand();
                tree.expandAll();
                Ext.Array.forEach(tree.store.getRange(), function(node) {
                    if (!node.isLeaf()) {
                        expect(node.isExpanded()).toBe(true);
                    }
                });
            });

        });

        describe("collapseAll", function() {
            describe("callbacks", function() {

                it("should pass the direct child nodes of the root", function() {
                    var expectedNodes,
                        store = tree.getStore();

                    tree.collapseAll(function(nodes) {
                        expectedNodes = nodes;
                    });

                    expect(expectedNodes[0]).toBe(store.getNodeById('A'));
                    expect(expectedNodes[1]).toBe(store.getNodeById('I'));
                    expect(expectedNodes[2]).toBe(store.getNodeById('M'));
                });

                it("should default the scope to the tree", function() {
                    var expectedScope;

                    tree.collapseAll(function() {
                        expectedScope = this;
                    });
                    expect(expectedScope).toBe(tree);
                });

                it("should use a passed scope", function() {
                    var o = {},
                        expectedScope;

                    tree.expandAll(function() {
                        expectedScope = this;
                    }, o);
                    expect(expectedScope).toBe(o);
                });
            });

            it("should collapse all nodes", function() {
                tree.expandAll();
                tree.collapseAll();
                Ext.Array.forEach(tree.store.getRange(), function(node) {
                    if (!node.isLeaf()) {
                        expect(node.isExpanded()).toBe(false);
                    }
                });
            });

            it("should collapse all nodes all the way down the tree", function() {
                tree.expandPath('/root/A/B/C');
                tree.getRootNode().collapse();
                tree.collapseAll();
                Ext.Array.forEach(tree.store.getRange(), function(node) {
                    if (!node.isLeaf()) {
                        expect(node.isExpanded()).toBe(false);
                    }
                });
            });

            it("should collapse all filtered nodes using animation", function() {
                var animWait = function() {
                    var fxQueue = Ext.fx.Manager.fxQueue,
                        activeAnimations = 0,
                        targetId, queue, i, len;

                    for (targetId in fxQueue) {
                        queue = fxQueue[targetId];
                        activeAnimations += queue.length;
                    }

                    return activeAnimations === 0;
                };

                Ext.destroy(tree);
                tree = null;

                makeTree(testNodes, {
                    animate: true, rootVisible: false
                });

                tree.expandAll();

                waitsFor(animWait, 'expanding animations to finish');

                runs(function() {
                    tree.store.addFilter([{ property: 'secondaryId', operator: 'like', value: 'M' }]);
                    expect(function() {
                        tree.collapseAll();
                    }).not.toThrow();
                });

                // collapse animations need to finish before exiting and destroying the component
                waitsFor(animWait, 'collapsing animations to finish');
            });
        });

        describe("expand", function() {
            describe("callbacks", function() {
               it("should pass the nodes directly under the expanded node", function() {
                   var expectedNodes,
                        store = tree.getStore();

                   tree.expandNode(tree.getRootNode(), false, function(nodes) {
                       expectedNodes = nodes;
                   });

                   expect(expectedNodes[0]).toBe(store.getNodeById('A'));
                   expect(expectedNodes[1]).toBe(store.getNodeById('I'));
                   expect(expectedNodes[2]).toBe(store.getNodeById('M'));
               });

               it("should default the scope to the tree", function() {
                   var expectedScope;

                   tree.expandNode(tree.getRootNode(), false, function() {
                       expectedScope = this;
                   });
                   expect(expectedScope).toBe(tree);
               });

               it("should use a passed scope", function() {
                   var o = {},
                        expectedScope;

                   tree.expandNode(tree.getRootNode(), false, function() {
                       expectedScope = this;
                   }, o);
                   expect(expectedScope).toBe(o);
               });
            });

            describe("deep", function() {
                it("should only expand a single level if deep is not specified", function() {
                    var store = tree.getStore();

                    tree.expandNode(tree.getRootNode());
                    expect(store.getNodeById('A').isExpanded()).toBe(false);
                    expect(store.getNodeById('I').isExpanded()).toBe(false);
                    expect(store.getNodeById('M').isExpanded()).toBe(false);
                });

                it("should expand all nodes underneath the expanded node if deep is set", function() {
                    var store = tree.getStore();

                    tree.expandPath('/root/A');
                    tree.expandNode(store.getNodeById('A'), true);
                    expect(store.getNodeById('B').isExpanded()).toBe(true);
                    expect(store.getNodeById('F').isExpanded()).toBe(true);
                    expect(store.getNodeById('G').isExpanded()).toBe(true);
                });
            });

            describe('expanded nodes', function() {
                var ModelProxy, resp1, resp2, resp3;

                beforeEach(function() {
                    var responses = [
                        {
                            id: 'root',
                            text: 'Root',
                            children: [{
                                id: 2,
                                text: 'node1',
                                expanded: false
                            }]
                        },
                        [{
                            id: 3,
                            text: 'child1',
                            expanded: false
                        }, {
                            id: 4,
                            text: 'child2',
                            expanded: true
                        }],
                        [{
                            id: 5,
                            text: 'child2.1',
                            expanded: false
                        }, {
                            id: 6,
                            text: 'child2.2',
                            expanded: false
                        }]
                    ];

                    resp1 = responses[0];
                    resp2 = responses[1];
                    resp3 = responses[2];
                    tree.destroy();
                    ModelProxy = Ext.define(null, {
                        extend: 'Ext.data.TreeModel',
                        fields: ['id', 'text', 'secondaryId'],
                        proxy: {
                            type: 'ajax',
                            url: 'fakeUrl'
                        }
                    });
                });

                afterEach(function() {
                    ModelProxy = Ext.destroy(ModelProxy);
                });

                it('should expand nodes in the correct order', function() {
                    var store, root;

                    makeTree(null, null, {
                        model: ModelProxy
                    });
                    store = tree.getStore();
                    root = store.getRoot();

                    // expand root and load response
                    root.expand();
                    Ext.Ajax.mockComplete({
                        status: 200,
                        responseText: Ext.encode(resp1)
                    });

                    // expand node1 and load response
                    store.getNodeById(2).expand();
                    Ext.Ajax.mockComplete({
                        status: 200,
                        responseText: Ext.encode(resp2)
                    });

                    // immediately load response for expanded child2
                    Ext.Ajax.mockComplete({
                        status: 200,
                        responseText: Ext.encode(resp3)
                    });

                    Ext.Array.forEach(view.getNodes(), function(node, index) {
                        var id = view.getRecord(node).getId();

                        // each node, except for root, should have an ID that increments to
                        // the index count
                        if (id !== 'root') {
                            expect(id).toEqual(++index);
                        }
                    });
                });
            });
        });

        describe("collapse", function() {
            describe("callbacks", function() {
               it("should pass the nodes directly under the expanded node", function() {
                   var expectedNodes,
                       store = tree.getStore();

                   tree.collapseNode(tree.getRootNode(), false, function(nodes) {
                       expectedNodes = nodes;
                   });
                   expect(expectedNodes[0]).toBe(store.getNodeById('A'));
                   expect(expectedNodes[1]).toBe(store.getNodeById('I'));
                   expect(expectedNodes[2]).toBe(store.getNodeById('M'));
               });

               it("should default the scope to the tree", function() {
                   var expectedScope;

                   tree.collapseNode(tree.getRootNode(), false, function() {
                       expectedScope = this;
                   });
                   expect(expectedScope).toBe(tree);
               });

               it("should use a passed scope", function() {
                   var o = {},
                       expectedScope;

                   tree.collapseNode(tree.getRootNode(), false, function() {
                       expectedScope = this;
                   }, o);
                   expect(expectedScope).toBe(o);
               });
            });

            describe("deep", function() {
                it("should only collapse a single level if deep is not specified", function() {
                    var store = tree.getStore();

                    tree.expandAll();
                    tree.collapseNode(tree.getRootNode());
                    expect(store.getNodeById('A').isExpanded()).toBe(true);
                    expect(store.getNodeById('I').isExpanded()).toBe(true);
                    expect(store.getNodeById('M').isExpanded()).toBe(true);
                });

                it("should expand all nodes underneath the expanded node if deep is set", function() {
                    var store = tree.getStore();

                    tree.expandPath('/root/A');
                    tree.expandNode(store.getNodeById('A'), true);
                    tree.collapseNode(store.getNodeById('A'), true);
                    expect(store.getNodeById('B').isExpanded()).toBe(false);
                    expect(store.getNodeById('F').isExpanded()).toBe(false);
                    expect(store.getNodeById('G').isExpanded()).toBe(false);
                });
            });
        });
    });

    describe("animations", function() {
        var enableFx = Ext.enableFx;

        beforeEach(function() {
            makeTree = function(nodes, cfg) {
                cfg = cfg || {};
                Ext.applyIf(cfg, {
                    renderTo: Ext.getBody(),
                    store: new Ext.data.TreeStore({
                        model: TreeItem,
                        root: {
                            secondaryId: 'root',
                            id: 'root',
                            text: 'Root',
                            children: nodes
                        }
                    })
                });
                tree = new Ext.tree.Panel(cfg);
            };
        });

        afterEach(function() {
            Ext.enableFx = enableFx;
        });

        it("should enable animations when Ext.enableFx is true", function() {
            Ext.enableFx = true;

            makeTree();

            expect(tree.enableAnimations).toBeTruthy();
        });

        it("should disable animations when Ext.enableFx is false", function() {
            Ext.enableFx = false;

            makeTree();

            expect(tree.enableAnimations).toBeFalsy();
        });
    });

    describe("event order", function() {
        it("should fire 'beforeitemexpand' before 'beforeload'", function() {
            var order = 0,
                beforeitemexpandOrder,
                beforeloadOrder,
                loadOrder,
                layoutCounter;

            makeTree(null, {
                store: new Ext.data.TreeStore({
                    proxy: {
                        type: 'ajax',
                        url: 'fakeUrl'
                    },
                    root: {
                        text: 'Ext JS',
                        id: 'src'
                    },
                    folderSort: true,
                    sorters: [{
                        property: 'text',
                        direction: 'ASC'
                    }]
                }),
                listeners: {
                    beforeitemexpand: function() {
                        beforeitemexpandOrder = order;
                        order++;
                    },
                    beforeload: function() {
                        beforeloadOrder = order;
                        order++;
                    },
                    load: function() {
                        loadOrder = order;
                    }
                }
            });
            layoutCounter = tree.layoutCounter;
            tree.getStore().getRoot().expand();

            Ext.Ajax.mockComplete({
                status: 200,
                responseText: Ext.encode(testNodes)
            });

            // The order of events expected: beforeitemexpand, beforeload, load.
            expect(beforeitemexpandOrder).toBe(0);
            expect(beforeloadOrder).toBe(1);
            expect(loadOrder).toBe(2);

            // The loading plus expand of the root should only have triggered one layout
            expect(tree.layoutCounter).toBe(layoutCounter + 1);
        });
    });

    describe("selected/focused/hover css classes", function() {
        var proto = Ext.view.Table.prototype,
            selectedItemCls = proto.selectedItemCls,
            focusedItemCls = proto.focusedItemCls,
            view, store, rec;

        beforeEach(function() {
            makeTree(testNodes, {
                rowLines: true,
                selModel: {
                    selType: 'rowmodel',
                    mode: 'MULTI'
                }
            });
            tree.getRootNode().expand();
            view = tree.view;
            store = tree.store;
        });

        function blurActiveEl() {
            Ext.getBody().focus();
        }

        it("should preserve the selected classes when nodes are expanded", function() {
            tree.selModel.select([store.getNodeById('A'), store.getNodeById('M')]);
            store.getNodeById('A').expand();
            store.getNodeById('I').expand();

            expect(view.getNodeByRecord(store.getNodeById('A'))).toHaveCls(selectedItemCls);
            expect(view.getNodeByRecord(store.getNodeById('M'))).toHaveCls(selectedItemCls);
        });

        it("should preserve the focused classes when nodes are expanded", function() {
            rec = store.getNodeById('I');
            tree.getView().getNavigationModel().setPosition(rec);
            store.getNodeById('A').expand();
            expect(view.getCell(rec, view.getVisibleColumnManager().getColumns()[0]), true).toHaveCls(focusedItemCls);
        });

        it("should update the selected classes when rows are collapsed", function() {
            store.getNodeById('A').expand();
            store.getNodeById('M').expand();
            tree.selModel.select([store.getNodeById('B'), store.getNodeById('M')]);
            blurActiveEl(); // EXTJSIV-11281: make sure we're not relying on dom focus for removal of focus border
            store.getNodeById('A').collapse();
            store.getNodeById('M').collapse();

            expect(view.getNodeByRecord(store.getNodeById('M'))).toHaveCls(selectedItemCls);
        });

        itNotTouch("should add the expanderIconOverCls class when mouseover the expander icon", function() {
            var cell00 = view.getCell(0, 0);

            expect(cell00).not.toHaveCls(view.expanderIconOverCls);
            jasmine.fireMouseEvent(cell00.querySelector(view.expanderSelector), 'mouseover');
            expect(cell00).toHaveCls(view.expanderIconOverCls);
        });
    });

    describe("renderer", function() {
        var CustomTreeColumnNoScope = Ext.define(null, {
                extend: 'Ext.tree.Column',

                renderColText: function(v) {
                    return v + 'NoScope';
                },
                renderer: 'renderColText'
            }),
            CustomTreeColumnScopeThis = Ext.define(null, {
                extend: 'Ext.tree.Column',

                renderColText: function(v) {
                    return v + 'ScopeThis';
                },
                renderer: 'renderColText',
                scope: 'this'
            }),
            CustomTreeColumnScopeController = Ext.define(null, {
                extend: 'Ext.tree.Column',
                scope: 'controller'
            }),
            TreeRendererTestController = Ext.define(null, {
                extend: 'Ext.app.ViewController',
                renderColText: function(v) {
                    return v + 'ViewController';
                }
            });

        describe("String renderer in a column subclass", function() {
            it("should be able to use a named renderer in the column with no scope", function() {
                tree = new Ext.tree.Panel({
                    animate: false,
                    renderTo: Ext.getBody(),
                    store: new Ext.data.TreeStore({
                        model: TreeItem,
                        root: {
                            id: 'root',
                            text: 'Root'
                        }
                    }),
                    columns: [new CustomTreeColumnNoScope({
                        flex: 1,
                        dataIndex: 'text'
                    })]
                });
                expect(tree.el.dom.querySelector('.x-tree-node-text').innerHTML).toEqual('RootNoScope');
            });
            it("should be able to use a named renderer in the column with scope: 'this'", function() {
                tree = new Ext.tree.Panel({
                    animate: false,
                    renderTo: Ext.getBody(),
                    store: new Ext.data.TreeStore({
                        model: TreeItem,
                        root: {
                            id: 'root',
                            text: 'Root'
                        }
                    }),
                    columns: [new CustomTreeColumnScopeThis({
                        flex: 1,
                        dataIndex: 'text'
                    })]
                });
                expect(tree.el.dom.querySelector('.x-tree-node-text').innerHTML).toEqual('RootScopeThis');
            });
            // Note: xit because thrown errors inside the TableView rendering path leaves an invalid state
            // which breaks ALL subsequent tests.
            xit("should not be able to use a named renderer in the column with scope: 'controller'", function() {
                expect(function() {
                    tree = new Ext.tree.Panel({
                        animate: false,
                        store: new Ext.data.TreeStore({
                            model: TreeItem,
                            root: {
                                id: 'root',
                                text: 'Root'
                            }
                        }),
                        columns: [new CustomTreeColumnScopeController({
                            flex: 1,
                            dataIndex: 'text',
                            renderer: 'renderColText',
                            scope: 'controller'
                        })]
                    });
                    tree.render(document.body);
                }).toThrow();
            });
            it("should be able to use a named renderer in a ViewController", function() {
                tree = new Ext.tree.Panel({
                    controller: new TreeRendererTestController(),
                    animate: false,
                    renderTo: Ext.getBody(),
                    store: new Ext.data.TreeStore({
                        model: TreeItem,
                        root: {
                            id: 'root',
                            text: 'Root'
                        }
                    }),
                    columns: [new CustomTreeColumnNoScope({
                        flex: 1,
                        dataIndex: 'text',
                        renderer: 'renderColText'
                    })]
                });
                expect(tree.el.dom.querySelector('.x-tree-node-text').innerHTML).toEqual('RootViewController');
                tree.destroy();

                tree = new Ext.tree.Panel({
                    controller: new TreeRendererTestController(),
                    animate: false,
                    renderTo: Ext.getBody(),
                    store: new Ext.data.TreeStore({
                        model: TreeItem,
                        root: {
                            id: 'root',
                            text: 'Root'
                        }
                    }),
                    columns: [new CustomTreeColumnScopeController({
                        flex: 1,
                        dataIndex: 'text',
                        renderer: 'renderColText'
                    })]
                });
                expect(tree.el.dom.querySelector('.x-tree-node-text').innerHTML).toEqual('RootViewController');
                tree.destroy();

                tree = new Ext.tree.Panel({
                    animate: false,
                    renderTo: Ext.getBody(),
                    store: new Ext.data.TreeStore({
                        model: TreeItem,
                        root: {
                            id: 'root',
                            text: 'Root'
                        }
                    }),
                    columns: [new CustomTreeColumnNoScope({
                        controller: new TreeRendererTestController(),
                        flex: 1,
                        dataIndex: 'text',
                        renderer: 'renderColText',
                        scope: 'self.controller'
                    })]
                });
                expect(tree.el.dom.querySelector('.x-tree-node-text').innerHTML).toEqual('RootViewController');
            });
            it("should be able to use a named renderer in the Column with no scope when Column uses defaultListenerScope: true", function() {
                tree = new Ext.tree.Panel({
                    animate: false,
                    renderTo: Ext.getBody(),
                    store: new Ext.data.TreeStore({
                        model: TreeItem,
                        root: {
                            id: 'root',
                            text: 'Root'
                        }
                    }),
                    columns: [new CustomTreeColumnNoScope({
                        defaultListenerScope: true,
                        flex: 1,
                        dataIndex: 'text',
                        renderColText: function(v) {
                            return v + 'ColDefaultScope';
                        },
                        renderer: 'renderColText'
                    })]
                });
                expect(tree.el.dom.querySelector('.x-tree-node-text').innerHTML).toEqual('RootColDefaultScope');
            });
            it("should be able to use a named renderer in the Panel with no scope when Panel uses defaultListenerScope: true", function() {
                tree = new Ext.tree.Panel({
                    animate: false,
                    renderTo: Ext.getBody(),
                    store: new Ext.data.TreeStore({
                        model: TreeItem,
                        root: {
                            id: 'root',
                            text: 'Root'
                        }
                    }),
                    defaultListenerScope: true,
                    panelRenderColText: function(v) {
                        return v + 'PanelDefaultScope';
                    },
                    columns: [new CustomTreeColumnNoScope({
                        flex: 1,
                        dataIndex: 'text',
                        renderer: 'panelRenderColText'
                    })]
                });
                expect(tree.el.dom.querySelector('.x-tree-node-text').innerHTML).toEqual('RootPanelDefaultScope');
            });
        });

        describe("String renderer in a column definition", function() {
            it("should be able to use a named renderer in the column with no scope", function() {
                tree = new Ext.tree.Panel({
                    animate: false,
                    renderTo: Ext.getBody(),
                    store: new Ext.data.TreeStore({
                        model: TreeItem,
                        root: {
                            id: 'root',
                            text: 'Root'
                        }
                    }),
                    columns: [{
                        xtype: 'treecolumn',
                        flex: 1,
                        dataIndex: 'text',
                        renderColText: function(v) {
                            return v + 'NoScope';
                        },
                        renderer: 'renderColText'
                    }]
                });
                expect(tree.el.dom.querySelector('.x-tree-node-text').innerHTML).toEqual('RootNoScope');
            });
            it("should be able to use a named renderer in the column with scope: 'this'", function() {
                tree = new Ext.tree.Panel({
                    animate: false,
                    renderTo: Ext.getBody(),
                    store: new Ext.data.TreeStore({
                        model: TreeItem,
                        root: {
                            id: 'root',
                            text: 'Root'
                        }
                    }),
                    columns: [{
                        xtype: 'treecolumn',
                        flex: 1,
                        dataIndex: 'text',
                        renderColText: function(v) {
                            return v + 'ScopeThis';
                        },
                        renderer: 'renderColText',
                        scope: 'this'
                    }]
                });
                expect(tree.el.dom.querySelector('.x-tree-node-text').innerHTML).toEqual('RootScopeThis');
            });
            // Note: xit because thrown errors inside the TableView rendering path leaves an invalid state
            // which breaks ALL subsequent tests.
            xit("should not be able to use a named renderer in the column with scope: 'controller'", function() {
                expect(function() {
                    tree = new Ext.tree.Panel({
                        animate: false,
                        store: new Ext.data.TreeStore({
                            model: TreeItem,
                            root: {
                                id: 'root',
                                text: 'Root'
                            }
                        }),
                        columns: [{
                            xtype: 'treecolumn',
                            flex: 1,
                            dataIndex: 'text',
                            renderColText: function(v) {
                                return v + 'Foo';
                            },
                            renderer: 'renderColText',
                            scope: 'controller'
                        }]
                    });
                    tree.render(document.body);
                }).toThrow();
            });
            it("should be able to use a named renderer in a ViewController", function() {
                tree = new Ext.tree.Panel({
                    controller: new TreeRendererTestController(),
                    animate: false,
                    renderTo: Ext.getBody(),
                    store: new Ext.data.TreeStore({
                        model: TreeItem,
                        root: {
                            id: 'root',
                            text: 'Root'
                        }
                    }),
                    columns: [{
                        xtype: 'treecolumn',
                        flex: 1,
                        dataIndex: 'text',
                        renderer: 'renderColText'
                    }]
                });
                expect(tree.el.dom.querySelector('.x-tree-node-text').innerHTML).toEqual('RootViewController');
                tree.destroy();

                tree = new Ext.tree.Panel({
                    controller: new TreeRendererTestController(),
                    animate: false,
                    renderTo: Ext.getBody(),
                    store: new Ext.data.TreeStore({
                        model: TreeItem,
                        root: {
                            id: 'root',
                            text: 'Root'
                        }
                    }),
                    columns: [{
                        xtype: 'treecolumn',
                        flex: 1,
                        dataIndex: 'text',
                        renderer: 'renderColText',
                        scope: 'controller'
                    }]
                });
                expect(tree.el.dom.querySelector('.x-tree-node-text').innerHTML).toEqual('RootViewController');
                tree.destroy();

                tree = new Ext.tree.Panel({
                    animate: false,
                    renderTo: Ext.getBody(),
                    store: new Ext.data.TreeStore({
                        model: TreeItem,
                        root: {
                            id: 'root',
                            text: 'Root'
                        }
                    }),
                    columns: [{
                        controller: new TreeRendererTestController(),
                        xtype: 'treecolumn',
                        flex: 1,
                        dataIndex: 'text',
                        renderer: 'renderColText',
                        scope: 'self.controller'
                    }]
                });
                expect(tree.el.dom.querySelector('.x-tree-node-text').innerHTML).toEqual('RootViewController');
            });
            it("should be able to use a named renderer in the Column with no scope when Column uses defaultListenerScope: true", function() {
                tree = new Ext.tree.Panel({
                    animate: false,
                    renderTo: Ext.getBody(),
                    store: new Ext.data.TreeStore({
                        model: TreeItem,
                        root: {
                            id: 'root',
                            text: 'Root'
                        }
                    }),
                    columns: [{
                        xtype: 'treecolumn',
                        defaultListenerScope: true,
                        flex: 1,
                        dataIndex: 'text',
                        renderColText: function(v) {
                            return v + 'ColDefaultScope';
                        },
                        renderer: 'renderColText'
                    }]
                });
                expect(tree.el.dom.querySelector('.x-tree-node-text').innerHTML).toEqual('RootColDefaultScope');
            });
            it("should be able to use a named renderer in the Panel with no scope when Panel uses defaultListenerScope: true", function() {
                tree = new Ext.tree.Panel({
                    animate: false,
                    renderTo: Ext.getBody(),
                    store: new Ext.data.TreeStore({
                        model: TreeItem,
                        root: {
                            id: 'root',
                            text: 'Root'
                        }
                    }),
                    defaultListenerScope: true,
                    panelRenderColText: function(v) {
                        return v + 'PanelDefaultScope';
                    },
                    columns: [{
                        xtype: 'treecolumn',
                        flex: 1,
                        dataIndex: 'text',
                        renderer: 'panelRenderColText'
                    }]
                });
                expect(tree.el.dom.querySelector('.x-tree-node-text').innerHTML).toEqual('RootPanelDefaultScope');
            });
        });

        it("should be able to use a renderer to render the value", function() {
            tree = new Ext.tree.Panel({
                animate: false,
                renderTo: Ext.getBody(),
                store: new Ext.data.TreeStore({
                    model: TreeItem,
                    root: {
                        id: 'root',
                        text: 'Root'
                    }
                }),
                columns: [{
                    xtype: 'treecolumn',
                    flex: 1,
                    dataIndex: 'text',
                    renderer: function(v) {
                        return v + 'Foo';
                    }
                }]
            });
            expect(tree.el.dom.querySelector('.x-tree-node-text').innerHTML).toEqual('RootFoo');
        });

        it("should be able to use a string renderer that maps to Ext.util.Format", function() {
            tree = new Ext.tree.Panel({
                animate: false,
                renderTo: Ext.getBody(),
                store: new Ext.data.TreeStore({
                    model: TreeItem,
                    root: {
                        id: 'root',
                        text: 'Root'
                    }
                }),
                columns: [{
                    xtype: 'treecolumn',
                    flex: 1,
                    formatter: 'uppercase',
                    dataIndex: 'text'
                }]
            });
            expect(tree.el.dom.querySelector('.x-tree-node-text').innerHTML).toEqual('ROOT');
        });
    });

    // https://sencha.jira.com/browse/EXTJSIV-9533
    describe("programmatic load", function() {
        beforeEach(function() {
            Ext.define('spec.Foo', {
                extend: 'Ext.data.Model',
                fields: ['Name', 'Id'],
                idProperty: 'Id'
            });
        });

        afterEach(function() {
            Ext.undefine('spec.Foo');
            Ext.data.Model.schema.clear(true);
        });

        function getData() {
            return [{
                "BaselineEndDate": "2010-02-01",
                "Id": 1,
                "Name": "Planning",
                "PercentDone": 50,
                "StartDate": "2010-01-18",
                "BaselineStartDate": "2010-01-13",
                "Duration": 11,
                "expanded": true,
                "TaskType": "Important",
                "children": [{
                    "BaselineEndDate": "2010-01-28",
                    "Id": 11,
                    "leaf": true,
                    "Name": "Investigate",
                    "PercentDone": 50,
                    "TaskType": "LowPrio",
                    "StartDate": "2010-01-18",
                    "BaselineStartDate": "2010-01-20",
                    "Duration": 10
                }, {
                    "BaselineEndDate": "2010-02-01",
                    "Id": 12,
                    "leaf": true,
                    "Name": "Assign resources",
                    "PercentDone": 50,
                    "StartDate": "2010-01-18",
                    "BaselineStartDate": "2010-01-25",
                    "Duration": 10
                }, {
                    "BaselineEndDate": "2010-02-01",
                    "Id": 13,
                    "leaf": true,
                    "Name": "Gather documents (not resizable)",
                    "Resizable": false,
                    "PercentDone": 50,
                    "StartDate": "2010-01-18",
                    "BaselineStartDate": "2010-01-25",
                    "Duration": 10
                }, {
                    "BaselineEndDate": "2010-02-04",
                    "Id": 17,
                    "leaf": true,
                    "Name": "Report to management",
                    "TaskType": "Important",
                    "PercentDone": 0,
                    "StartDate": "2010-02-02",
                    "BaselineStartDate": "2010-02-04",
                    "Duration": 0
                }]
            }];
        }

        it("should reload the root node", function() {
            var store = new Ext.data.TreeStore({
                    model: 'spec.Foo',
                    proxy: {
                        type: 'ajax',
                        url: '/data/AjaxProxy/treeLoadData'
                    },
                    root: {
                        Name: 'ROOOOOOOOT',
                        expanded: true
                    }
                }),
                refreshSpy;

            tree = new Ext.tree.Panel({
                renderTo: Ext.getBody(),
                width: 600,
                height: 400,
                store: store,
                viewConfig: {
                    loadMask: false
                },
                columns: [{
                    xtype: 'treecolumn',
                    header: 'Tasks',
                    dataIndex: 'Name',
                    locked: true,
                    width: 200
                }, {
                    width: 200,
                    dataIndex: 'Id'
                }]
            });

            var lockedView = tree.lockedGrid.view,
                normalView = tree.normalGrid.view;

            refreshSpy = spyOnEvent(store, 'refresh');

            Ext.Ajax.mockComplete({
                status: 200,
                responseText: Ext.encode(getData())
            });

            expect(refreshSpy.callCount).toBe(1);
            expect(lockedView.getNodes().length).toBe(6);
            expect(normalView.getNodes().length).toBe(6);
        });
    });

    describe("rendering while a child node is loading and the root is specified on the tree", function() {
        it("should render the correct number of nodes", function() {
            var ProxyModel = Ext.define(null, {
                extend: 'Ext.data.TreeModel',
                fields: ['id', 'text', 'secondaryId'],
                proxy: {
                    type: 'ajax',
                    url: 'fakeUrl'
                }
            });

            makeTree(null, {
                root: {
                    expanded: true,
                    children: [{
                        id: 'node1',
                        text: 'Node1',
                        expandable: true,
                        expanded: true
                    }, {
                        id: 'node2',
                        text: 'Node2',
                        expandable: true,
                        expanded: false
                    }]
                }
            }, {
                model: ProxyModel,
                root: null
            });

            expect(view.getNodes().length).toBe(3);

            // At this point, node1 will be loading because it's expanded
            Ext.Ajax.mockComplete({
                status: 200,
                responseText: Ext.encode([{
                    id: 'node1.1'
                }])
            });
            expect(view.getNodes().length).toBe(4);
        });
    });

    describe("top down filtering", function() {
        var treeData = [{
            text: 'Top 1',
            children: [{
                text: 'foo',
                leaf: true
            }, {
                text: 'bar',
                leaf: true
            }, {
                text: 'Second level 1',
                children: [{
                    text: 'foo',
                    leaf: true
                }, {
                    text: 'bar',
                    leaf: true
                }]
            }]
        }, {
            text: 'Top 2',
            children: [{
                text: 'foo',
                leaf: true
            }, {
                text: 'wonk',
                leaf: true
            }, {
                text: 'Second level 2',
                children: [{
                    text: 'foo',
                    leaf: true
                }, {
                    text: 'wonk',
                    leaf: true
                }]
            }]
        }, {
            text: 'Top 3',
            children: [{
                text: 'zarg',
                leaf: true
            }, {
                text: 'bar',
                leaf: true
            }, {
                text: 'Second level 3',
                children: [{
                    text: 'zarg',
                    leaf: true
                }, {
                    text: 'bar',
                    leaf: true
                }]
            }]
        }];

        beforeEach(function() {
            makeTree(treeData, {
                rootVisible: false
            });
        });

        function testRowText(rowIdx, value) {
            return view.store.getAt(rowIdx).get('text') === value;
        }

        it("should only show nodes which pass a filter", function() {
            // When filtering the updating of the 'visible' field must not percolate a store update event out to views.
            var handleUpdateCallCount,
                handleUpdateSpy = spyOn(view, 'handleUpdate').andCallThrough();

            // Check correct initial state
            expect(view.all.getCount()).toBe(3);
            expect(view.store.getCount()).toBe(3);
            expect(testRowText(0, 'Top 1')).toBe(true);
            expect(testRowText(1, 'Top 2')).toBe(true);
            expect(testRowText(2, 'Top 3')).toBe(true);

            // Filter so that only "foo" nodes and their ancestors are visible.
            // filterer = 'bottomup' means that visible leaf nodes cause their ancestors to be visible.
            store.filterer = 'bottomup';
            store.filter({
                filterFn: function(node) {
                    return  node.get('text') === 'foo';
                },
                id: 'testFilter'
            });

            // The setting of the visible field in the filtered out record should NOT have resulted
            // in any update events firing to the view.
            expect(handleUpdateSpy.callCount).toBe(0);

            rootNode.childNodes[0].expand();

            // The "Second level 1" branch node is visible because it has a child with text "foo"
            expect(view.all.getCount()).toBe(4);
            expect(view.store.getCount()).toBe(4);
            expect(testRowText(0, 'Top 1')).toBe(true);
            expect(testRowText(1, 'foo')).toBe(true);
            expect(testRowText(2, 'Second level 1')).toBe(true);
            expect(testRowText(3, 'Top 2')).toBe(true);

            // Expand "Second level 1". It contains 1 "foo" child.
            rootNode.childNodes[0].childNodes[2].expand();

            expect(view.all.getCount()).toBe(5);
            expect(view.store.getCount()).toBe(5);
            expect(testRowText(0, 'Top 1')).toBe(true);
            expect(testRowText(1, 'foo')).toBe(true);
            expect(testRowText(2, 'Second level 1')).toBe(true);
            expect(testRowText(3, 'foo')).toBe(true);
            expect(testRowText(4, 'Top 2')).toBe(true);

            // The spy will have been called now because of node expansion setting the expanded field,
            // resulting in the updating of the folder icon in the view.
            // We are going to check that the filter operation below does NOT increment it.
            handleUpdateCallCount = handleUpdateSpy.callCount;

            // Now, with "Top 1" amd "Second level 1" already expanded, let's see only "bar" nodes and their ancestors.
            // View should refresh.
            store.filter({
                filterFn: function(node) {
                    return node.get('text') === 'bar';
                },
                id: 'testFilter'
            });

            // The setting of the visible field in the filtered out record should NOT have resulted
            // in any update events firing to the view.
            expect(handleUpdateSpy.callCount).toBe(handleUpdateCallCount);

            expect(view.all.getCount()).toBe(5);
            expect(view.store.getCount()).toBe(5);
            expect(testRowText(0, 'Top 1')).toBe(true);
            expect(testRowText(1, 'bar')).toBe(true);
            expect(testRowText(2, 'Second level 1')).toBe(true);
            expect(testRowText(3, 'bar')).toBe(true);
            expect(testRowText(4, 'Top 3')).toBe(true);

            // Expand "Top 3". It contains a "bar" and "Second level3", which should be visible because it contains a "bar"
            rootNode.childNodes[2].expand();

            expect(view.all.getCount()).toBe(7);
            expect(view.store.getCount()).toBe(7);
            expect(testRowText(0, 'Top 1')).toBe(true);
            expect(testRowText(1, 'bar')).toBe(true);
            expect(testRowText(2, 'Second level 1')).toBe(true);
            expect(testRowText(3, 'bar')).toBe(true);
            expect(testRowText(4, 'Top 3')).toBe(true);
            expect(testRowText(5, 'bar')).toBe(true);
            expect(testRowText(6, 'Second level 3')).toBe(true);

            // Collapse "Top 3". The "bar" and "Second level3" which contains a "bar" should disappear
            rootNode.childNodes[2].collapse();

            expect(view.all.getCount()).toBe(5);
            expect(view.store.getCount()).toBe(5);
            expect(testRowText(0, 'Top 1')).toBe(true);
            expect(testRowText(1, 'bar')).toBe(true);
            expect(testRowText(2, 'Second level 1')).toBe(true);
            expect(testRowText(3, 'bar')).toBe(true);
            expect(testRowText(4, 'Top 3')).toBe(true);

            // Collapse the top level nodes
            // So now only top levels which contain a "bar" somewhere in their hierarchy should be visible.
            rootNode.collapseChildren();
            expect(view.all.getCount()).toBe(2);
            expect(view.store.getCount()).toBe(2);
            expect(testRowText(0, 'Top 1')).toBe(true);
            expect(testRowText(1, 'Top 3')).toBe(true);
        });
    });

    describe("sorting", function() {
        it("should sort nodes", function() {
            var bNode;

            makeTree(testNodes, null, {
                folderSort: true,
                sorters: [{
                    property: 'text',
                    direction: 'ASC'
                }]
            });
            tree.expandAll();
            bNode = tree.store.getNodeById('B');

            // Insert an out of order node.
            // MUST be leaf: true so that the automatically prepended sort by leaf status has no effect.
            bNode.insertChild(0, {
                text: 'Z',
                leaf: true
            });

            // Check that we have disrupted the sorted state.
            expect(bNode.childNodes[0].get('text')).toBe('Z');
            expect(bNode.childNodes[1].get('text')).toBe('C');
            expect(bNode.childNodes[2].get('text')).toBe('D');

            // Sort using the owning TreeStore's sorter set.
            // It is by leaf status, then text, ASC.
            // These are all leaf nodes.
            bNode.sort();
            expect(bNode.childNodes[0].get('text')).toBe('C');
            expect(bNode.childNodes[1].get('text')).toBe('D');
            expect(bNode.childNodes[2].get('text')).toBe('Z');

            // Sort passing a comparator which does a descending sort on text
            bNode.sort(function(node1, node2) {
                return node1.get('text') > node2.get('text') ? -1 : 1;
            });
            expect(bNode.childNodes[0].get('text')).toBe('Z');
            expect(bNode.childNodes[1].get('text')).toBe('D');
            expect(bNode.childNodes[2].get('text')).toBe('C');
        });
    });

    describe("Buffered rendering large, expanded root node", function() {
        function makeNodes() {
            var nodes = [],
                i, j,
                ip1, jp1,
                node;

            for (i = 0; i < 50; i++) {
                ip1 = i + 1;
                node = {
                    id: 'n' + ip1,
                    text: 'Node' + ip1,
                    children: [

                    ]
                };

                for (j = 0; j < 50; j++) {
                    jp1 = j + 1;
                    node.children.push({
                        id: 'n' + ip1 + '.' + jp1,
                        text: 'Node' + ip1 + '/' + jp1,
                        leaf: true
                    });
                }

                nodes.push(node);
            }

            return nodes;
        }

        function completeWithNodes() {
            Ext.Ajax.mockComplete({
                status: 200,
                responseText: Ext.encode(makeNodes())
            });
        }

        it("should maintain scroll position on reload", function() {
            makeTree(null, {
                height: 400,
                width: 350
            }, {
                proxy: {
                    type: 'ajax',
                    url: '/tree/Panel/load'
                },
                root: {
                    id: 'root',
                    text: 'Root',
                    expanded: true
                }
            });

            completeWithNodes();

            // Child nodes of expanded root must be in store.
            expect(store.getCount()).toBe(51);

            // Child nodes must be in view
            expect(view.all.getCount()).toBe(Math.min(store.getCount(), view.bufferedRenderer.viewSize));

            view.setScrollY(500);
            store.reload();

            completeWithNodes();

            expect(view.getScrollY()).toBe(500);
        });

        it("should negate the animate flag and not throw an error", function() {
            makeTree(null, {
                height: 400,
                width: 350,
                animate: true
            }, {
                proxy: {
                    type: 'ajax',
                    url: '/tree/Panel/load'
                },
                root: {
                    id: 'root',
                    text: 'Root',
                    expanded: true
                }
            });
            completeWithNodes();

            // EXTJS-13673 buffered rendering should be turned on by default
            expect(tree.view.bufferedRenderer instanceof Ext.grid.plugin.BufferedRenderer).toBe(true);
        });

        it("should scroll to unloaded nodes by absolute path", function() {
            makeTree(null, {
                height: 400,
                width: 350
            }, {// lazyFill means childNodes do not load locally available children arrays until expanded.
                lazyFill: true,
                proxy: {
                    type: 'ajax',
                    url: '/tree/Panel/load'
                },
                root: {
                    id: 'root',
                    text: 'Root',
                    expanded: false
                }
            });

            // forces the root to load even though we configure it expanded: false.
            // We want to exercise the ability of pathing to expand all the way from the root.
            store.load();

            completeWithNodes();

            tree.ensureVisible('/root/n50/n50.50');
            expect(Ext.fly(view.getNode(store.getById('n50.50'))).getBox().bottom).toBeLessThanOrEqual(view.getBox().bottom);
        });

        it("should throw an error when being asked to scroll to an invisible root node", function() {
            makeTree(null, {
                height: 400,
                width: 350,
                rootVisible: false
            }, {
                // lazyFill means childNodes do not load locally available children arrays until expanded.
                lazyFill: true,
                proxy: {
                    type: 'ajax',
                    url: '/tree/Panel/load'
                },
                root: {
                    id: 'root',
                    text: 'Root',
                    expanded: true
                }
            });

            // forces the root to load even though we configure it expanded: false.
            // We want to exercise the ability of pathing to expand all the way from the root.
            store.load();

            completeWithNodes();

            runs(function() {
                expect(function() {
                    tree.ensureVisible(rootNode);
                }).toThrow('Unknown record passed to BufferedRenderer#scrollTo');
            });
        });

        it("should scroll to loaded nodes by relative path", function() {
            makeTree(null, {
                height: 400,
                width: 350
            }, {
                proxy: {
                    type: 'ajax',
                    url: '/tree/Panel/load'
                },
                root: {
                    id: 'root',
                    text: 'Root',
                    expanded: false
                }
            });

            // forces the root to load even though we configure it expanded: false.
            // We want to exercise the ability of pathing to expand all the way from the root.
            store.load();

            completeWithNodes();

            runs(function() {
                tree.ensureVisible('n50.50');
                expect(Ext.fly(view.getNode(store.getById('n50.50'))).getBox().bottom).toBeLessThanOrEqual(view.getBox().bottom);
            });
        });
    });

    describe("multi append node", function() {
        var layoutCounter,
            height;

        beforeEach(function() {
            makeTree(testNodes, null, null, {
                expanded: true
            });
            layoutCounter = view.componentLayoutCounter;
        });

        it("should only update the view once when an array of nodes is passed", function() {
            height = tree.getHeight();
            expect(view.all.getCount()).toEqual(4);
            tree.getRootNode().appendChild([{
                id: 'append-1',
                text: 'append-1',
                secondaryId: 'append-1'
            }, {
                id: 'append-2',
                text: 'append-2',
                secondaryId: 'append-2'
            }, {
                id: 'append-3',
                text: 'append-3',
                secondaryId: 'append-3'
            }, {
                id: 'append-4',
                text: 'append-4',
                secondaryId: 'append-4'
            }, {
                id: 'append-5',
                text: 'append-5',
                secondaryId: 'append-5'
            }]);

            // We added 5 nodes
            expect(view.all.getCount()).toEqual(9);

            // We are shrinkwrap height, so it shuold have grown
            expect(tree.getHeight()).toBeGreaterThan(height);

            // All should have been done in one, rather than one update per node
            expect(view.componentLayoutCounter).toEqual(layoutCounter + 1);
        });
    });

    describe("tracking removed nodes", function() {
        it("should not add nodes removed by virtue of their parent collapsing to the removed list", function() {
            var done = false;

            makeTree(testNodes, null, {
                trackRemoved: true
            });
            tree.expandAll(function() {
                tree.collapseAll(function() {
                    done = true;
                });
            });
            waitsFor(function() {
                return done;
            });
            runs(function() {
                expect(tree.store.getRemovedRecords().length).toBe(0);
            });
        });

        it("should add descendants of collapsed nodes to the removed list", function() {
            // Create tree with collapsed root node;
            makeTree(testNodes, null, {
                trackRemoved: true
            });
            runs(function() {
                tree.store.getRootNode().drop();

                // All nodes, even though they are not present in the store's Collection should have been added to the tracked list
                expect(tree.store.getRemovedRecords().length).toBe(14);
            });
        });

        it("should add descendants of filtered out nodes to the removed list", function() {
            var done = false;

            // Create tree with collapsed root node;
            makeTree(testNodes, null, {
                trackRemoved: true
            });
            tree.expandAll(function() {
                done = true;
            });
            waitsFor(function() {
                return done;
            });

            // When all are expanded, filter them all out.
            // Dropping the root node should still remove all descendants
            runs(function() {
                tree.store.filter('id', 'all_nodes_filtered_out');

                // Filtering should not add to remove list
                expect(tree.store.getRemovedRecords().length).toBe(0);

                tree.store.getRootNode().drop();

                // All nodes, even though they are not present in the store's Collection should have been added to the tracked list
                expect(tree.store.getRemovedRecords().length).toBe(14);
            });
        });
    });

    describe("Changing root node", function() {
        it("should remove all listeners from old root node", function() {
            tree = new Ext.tree.Panel({
                title: 'Test',
                height: 200,
                width: 400,
                root: {
                    text: 'Root',
                    expanded: true,
                    children: [{
                        text: 'A',
                        leaf: true
                    }, {
                        text: 'B',
                        leaf: true
                    }]
                }
            });

            var oldRoot = tree.getRootNode();

            // The old root should have some listeners
            expect(Ext.Object.getKeys(oldRoot.hasListeners).length).toBeGreaterThan(0);

            tree.store.setRoot({
                text: 'NewRoot',
                expanded: true,
                children: [{
                    text: 'New A',
                    leaf: true
                }, {
                    text: 'New B',
                    leaf: true
                }]
            });

            // The old root should have no listeners
            expect(Ext.Object.getKeys(oldRoot.hasListeners).length).toBe(0);

        });
    });

    describe("sorting a collapsed node", function() {
        it("should not expand a collapsed node upon sort", function() {
            makeTree(testNodes, null, {
                folderSort: true,
                sorters: [{
                    property: 'text',
                    direction: 'ASC'
                }]
            });
            rootNode.expand();
            var aNode = tree.store.getNodeById('A');

            // Sort the "A" node
            aNode.sort(function(a, b) {
                return a.get('text').localeCompare(b.get('text'));
            });

            // Should NOT have resulted in expansion
            expect(tree.store.indexOf(aNode.childNodes[0])).toBe(-1);
            expect(tree.store.indexOf(aNode.childNodes[1])).toBe(-1);
            expect(tree.store.indexOf(aNode.childNodes[2])).toBe(-1);
        });
    });

    describe("key events", function() {
        describe("not locked", function() {
            it("should expand all nodes with asterisk", function() {
                makeTree(testNodes);
                var cell = tree.getView().getCell(rootNode, tree.down('treecolumn'));

                jasmine.fireMouseEvent(cell, 'click', 5, 5);
                spyOn(tree, 'expandAll').andCallThrough();
                jasmine.fireKeyEvent(cell, 'keydown', Ext.event.Event.EIGHT, true);
                expect(tree.expandAll.callCount).toBe(1);

            });
        });

        describe("locked", function() {
            it("should expand all nodes with asterisk", function() {
                makeTree(testNodes, {
                    columns: [{
                        xtype: 'treecolumn',
                        locked: true,
                        width: 200,
                        dataIndex: 'text'
                    }, {
                        width: 200,
                        dataIndex: 'text'
                    }]
                });
                var cell = tree.getView().getCell(rootNode, tree.down('treecolumn'));

                jasmine.fireMouseEvent(cell, 'click', 5, 5);
                spyOn(tree, 'expandAll').andCallThrough();
                jasmine.fireKeyEvent(cell, 'keydown', Ext.event.Event.EIGHT, true);
                expect(tree.expandAll.callCount).toBe(1);
            });
        });
    });

    describe("bottom up filtering", function() {
        it("should show path to all filtered in leaf nodes", function() {
            makeTree(testNodes, null, {
                filterer: 'bottomup'
            });
            tree.expandAll();

            // All nodes must be visible
            expect(view.all.getCount()).toBe(15);

            // This should only pass one leaf node.
            // But its ancestors obviously have to be visible.
            store.filter({
                property: 'text',
                operator: '=',
                value: 'H'
            });

            // The H node must be visible
            expect(view.getNode(store.getById('H'))).not.toBe(null);

            // Just the path to the H node must be visible
            expect(view.all.getCount()).toBe(5);
        });
    });

    describe("glyphs for icons", function() {
        it("should render glyphs using the specified font", function() {
            makeTree(testNodes, {
                columns: [{
                    xtype: 'treecolumn',
                    width: 200,
                    dataIndex: 'text',
                    renderer: function(v, metaData, record) {
                        metaData.glyph = record.data.text + '@FontAwesome';

                        return v;
                    }
                }, {
                    width: 200,
                    dataIndex: 'text'
                }]
            });
            tree.expandAll();

            // Check that the font-family is as specified
            expect(Ext.fly(view.getCellByPosition({ row: 0, column: 0 }, true).querySelector('.x-tree-icon')).getStyle('font-family')).toBe('FontAwesome');

            // Check that the glyph is the first character of the text.
            expect(view.getCellByPosition({ row: 0, column: 0 }, true).querySelector('.x-tree-icon').innerHTML).toBe(store.getAt(0).get('text').substr(0, 1));
        });
    });

    describe("ARIA", function() {
        beforeEach(function() {
            makeTree(testNodes);
        });

        describe("role", function() {
            it("should have treegrid role", function() {
                expect(tree).toHaveAttr('role', 'treegrid');
            });

            it("should have rowgroup role", function() {
                expect(view).toHaveAttr('role', 'rowgroup');
            });
        });

        describe("aria-level", function() {
            beforeEach(function() {
                tree.expandAll();
            });

            it("should have aria-level on rows", function() {
                // A
                var row = view.getRow(1);

                expect(row).toHaveAttr('aria-level', '2');

                // B
                row = view.getRow(2);

                expect(row).toHaveAttr('aria-level', '3');

                // C
                row = view.getRow(3);

                expect(row).toHaveAttr('aria-level', '4');
            });
        });

        describe("aria-expanded", function() {
            it("should be set to false when collapsed", function() {
                // Root
                var row = view.getRow(0);

                expect(row).toHaveAttr('aria-expanded', 'false');
            });

            it("should be set to true when expanded", function() {
                tree.getStore().getNodeById('A').expand();

                // A
                var row = view.getRow(1);

                expect(row).toHaveAttr('aria-expanded', 'true');
            });

            it("should not be present on leaf nodes", function() {
                tree.getStore().getNodeById('A').expand();

                // C
                var row = view.getRow(3);

                expect(row).not.toHaveAttr('aria-expanded');
            });
        });
    });

    describe("reloading child node", function() {
        function getChildData(level) {
            Ext.Ajax.mockComplete({
                status: 200,
                responseText: Ext.encode([{
                    id: level,
                    text: 'A node'
                }])
            });
        }

        it("should correctly update the UI when reloading a child node directly", function() {
            var node, cell;

            makeTree(null, {
                store: new Ext.data.TreeStore({
                    proxy: {
                        type: 'ajax',
                        url: 'fakeUrl'
                    },
                    root: {
                        text: 'Ext JS',
                        id: 'src'
                    }
                })
            });

            tree.getRootNode().expand();
            getChildData(1);

            node = tree.getStore().getNodeById(1);
            // expand node to load data
            node.expand();
            // get remote data
            getChildData(2);
            // collapse the node
            node.collapse();

            // now let's reload the node directly
            tree.getStore().load({ node: node });
            getChildData(2);

            cell = view.getCell(1, 0).querySelector('.x-tree-expander');
            // "plus" class should be applied
            expect(cell).toHaveCls('x-tree-elbow-end-plus');
        });
    });
});

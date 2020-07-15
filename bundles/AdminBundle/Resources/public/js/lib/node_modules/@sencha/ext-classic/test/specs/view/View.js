topSuite("Ext.view.View",
    ['Ext.data.ArrayStore', 'Ext.selection.RowModel', 'Ext.app.ViewModel'],
function() {
    var view, store, TestModel, navModel,
        synchronousLoad = true,
        proxyStoreLoad = Ext.data.ProxyStore.prototype.load,
        loadStore = function() {
            proxyStoreLoad.apply(this, arguments);

            if (synchronousLoad) {
                this.flushLoad.apply(this, arguments);
            }

            return this;
        };

    TestModel = Ext.define(null, {
        extend: 'Ext.data.Model',
        fields: ['name', 'size']
    });

    function createView(cfg, data) {
        cfg = cfg || {};

        if (cfg.store === undefined) {
            cfg.store = makeStore(data);
        }

        store = cfg.store;

        view = new Ext.view.View(cfg);
        navModel = view.getNavigationModel();

        return view;
    }

    function makeStore(data) {
        if (typeof data === 'number') {
            data = makeData(data);
        }
        else if (!data && data !== null) {
            data = [{
                name: 'Item1'
            }];
        }

        return new Ext.data.Store({
            model: TestModel,
            data: data
        });
    }

    function makeData(len) {
        var nodes = [],
            i = 1;

        for (; i <= len; ++i) {
            nodes.push({
                name: 'Item ' + i
            });
        }

        return nodes;
    }

    function makeDataWithId(len) {
        var nodes = [],
            i = 1;

        for (; i <= len; ++i) {
            nodes.push({
                id: i,
                name: 'Item ' + i
            });
        }

        return nodes;
    }

    function createModel(data) {
        if (!Ext.isObject(data)) {
            data = {
                name: data
            };
        }

        return new TestModel(data);
    }

    function byName(name) {
        return store.getAt(store.findExact('name', name));
    }

    function completeRequest(data, status) {
        Ext.Ajax.mockComplete({
            status: status || 200,
            responseText: Ext.encode(data || [])
        });
    }

    beforeEach(function() {
        // Override so that we can control asynchronous loading
        Ext.data.ProxyStore.prototype.load = loadStore;

        MockAjaxManager.addMethods();
    });

    afterEach(function() {
        // Undo the overrides.
        Ext.data.ProxyStore.prototype.load = proxyStoreLoad;

        view = store = Ext.destroy(store, view);
        Ext.data.Model.schema.clear();
        MockAjaxManager.removeMethods();
    });

    describe("template with extra markup", function() {
        function expectClasses(classes) {
            var nodes = view.getEl().dom.childNodes,
                i, len;

            expect(nodes.length).toBe(classes.length);

            for (i = 0, len = classes.length; i < len; ++i) {
                expect(nodes[i].className).toBe(classes[i]);
            }
        }

        beforeEach(function() {
            createView({
                renderTo: Ext.getBody(),
                selModel: {
                    enableInitialSelection: false
                },
                itemSelector: '.foo',
                tpl: '<div class="header"></div><tpl for="."><div class="foo">{name}</div></tpl><div class="footer"></div>'
            }, makeData(3));
        });

        it("should render the entire tpl", function() {
            expectClasses(['header', 'foo', 'foo', 'foo', 'footer', 'x-tab-guard x-tab-guard-after']);
        });

        it("should not repeat nodes outside data on refresh", function() {
            view.refresh();
            expectClasses(['header', 'foo', 'foo', 'foo', 'footer']);
            view.refresh();
            expectClasses(['header', 'foo', 'foo', 'foo', 'footer']);
            view.refresh();
            expectClasses(['header', 'foo', 'foo', 'foo', 'footer']);
        });
    });

    describe("selection:single", function() {
        var sm;

        describe("classes", function() {
            beforeEach(function() {
                createView({
                    renderTo: Ext.getBody(),
                    itemTpl: '{name}'
                });

                sm = view.getSelectionModel();
            });

            afterEach(function() {
                sm = null;
            });

            it("should add the selectedItemCls when selecting", function() {
                sm.select(0);
                expect(view.getNode(0)).toHaveCls(view.selectedItemCls);
            });

            it("should remove the selectedItemCls when deselecting", function() {
                sm.select(0);
                sm.deselect(0);
                expect(view.getNode(0)).not.toHaveCls(view.selectedItemCls);
            });

            it("should retain the selectedItemCls when updating", function() {
                sm.select(0);
                store.getAt(0).set('name', 'Foo');
                expect(view.getNode(0)).toHaveCls(view.selectedItemCls);
            });

            it("should retain the selectedItemCls when refreshing", function() {
                sm.select(0);
                view.refresh();
                expect(view.getNode(0)).toHaveCls(view.selectedItemCls);
            });

            it("should retain the selectedItemCls when reloading the store", function() {
                store.removeAll();
                store.load();
                completeRequest(makeDataWithId(4));
                sm.select(1);
                store.load();
                completeRequest(makeDataWithId(4));
                expect(view.getNode(1)).toHaveCls(view.selectedItemCls);
            });
        });

        describe("cleanup", function() {
            it("should unbind the store form the selection model", function() {
                createView({
                    renderTo: Ext.getBody(),
                    itemTpl: '{name}'
                });

                sm = view.getSelectionModel();

                view.destroy();
                expect(sm.store).toBeNull();
            });
        });

        describe('disableSelection', function() {
            function doDisableSelectionTest(disableSelection, createInstance) {
                var rowModel = createInstance ? new Ext.selection.Model() : 'rowmodel';

                afterEach(function() {
                    rowModel = null;
                });

                it('when disableSelection = ' + disableSelection + ', config.selModel.isSelectionModel = ' + !!createInstance, function() {
                    createView({
                        renderTo: Ext.getBody(),
                        disableSelection: disableSelection,
                        selModel: rowModel,
                        itemSelector: '.foo',
                        tpl: '{name}'
                    });

                    sm = view.getSelectionModel();
                    sm.select(0);

                    expect(!!sm.getSelection().length).toBe(!disableSelection);
                });
            }

            doDisableSelectionTest(false, false);
            doDisableSelectionTest(true, false);
            doDisableSelectionTest(true, true);
            doDisableSelectionTest(false, true);
        });
    });

    describe("accessors", function() {
        function createSimpleView(cfg, data) {
            cfg = Ext.apply({
                renderTo: Ext.getBody(),
                itemTpl: '{name}'
            }, cfg);
            createView(cfg, data || makeData(5));
        }

        describe("getNode", function() {
            describe("param types", function() {
                it("should accept a node id and return a DOM element", function() {
                    createSimpleView();
                    var node = view.getNodes()[2],
                        id = Ext.id(node);

                    expect(view.getNode(id)).toBe(node);
                });

                it("should accept an index and return a DOM element", function() {
                    createSimpleView();
                    expect(view.getNode(1)).toBe(view.getNodes()[1]);
                });

                it("should accept a record", function() {
                    createSimpleView();
                    expect(view.getNode(store.getAt(3))).toBe(view.getNodes()[3]);
                });

                it("should accept an event object and return a DOM element", function() {
                    createSimpleView();
                    var node = view.getNodes()[1],
                        spy = jasmine.createSpy(),
                        event;

                    view.on('itemclick', spy);
                    jasmine.fireMouseEvent(node, 'click');
                    event = spy.mostRecentCall.args[4];
                    expect(view.getNode(event)).toBe(node);
                });

                it("should accept an HTMLElement that is the child of an item and return a DOM element", function() {
                    createSimpleView({
                        itemTpl: '<div class="bleh">{name}</div>'
                    });
                    var node = view.getNodes()[1];

                    expect(view.getNode(node.firstChild)).toBe(node);
                });
            });

            describe("in response to updates", function() {
                beforeEach(function() {
                    createSimpleView();
                });

                it("should return the correct node after an add", function() {
                    store.insert(0, {
                        name: 'X'
                    });
                    expect(view.getNode(0)).hasHTML('X');
                });

                it("should return the correct node after an update", function() {
                    store.first().set('name', 'Foo');
                    expect(view.getNode(0)).hasHTML('Foo');
                });

                it("should return the correct node after a remove", function() {
                    store.removeAt(0);
                    expect(view.getNode(0)).hasHTML('Item 2');
                });

                it("should return the correct node after a refresh", function() {
                    store.loadData([{
                        name: 'Foo'
                    }, {
                        name: 'Bar'
                    }]);
                    expect(view.getNode(0)).hasHTML('Foo');
                });

                it("should return null after a removeAll", function() {
                    store.removeAll();
                    expect(view.getNode(0)).toBeNull();
                });
            });

            describe("returning null for invalid items", function() {
                it("should return null when not rendered", function() {
                    createSimpleView({
                        renderTo: null
                    });
                    expect(view.getNode(0)).toBeNull();
                });

                it("should return null if the index is out of bounds", function() {
                    createSimpleView();
                    expect(view.getNode(-1)).toBeNull();
                    expect(view.getNode(5)).toBeNull();
                });

                it("should return null if the id doesn't exist", function() {
                    createSimpleView();
                    expect(view.getNode('foo')).toBeNull();
                });

                it("should return null if the model does not exist in the store", function() {
                    createSimpleView();
                    expect(view.getNode(new TestModel())).toBeNull();
                });
            });
        });

        describe("getNodes", function() {
            it("should be empty when not rendered", function() {
                createSimpleView({
                    renderTo: null
                });
                expect(view.getNodes()).toEqual([]);
            });

            it("should return an empty array when there are no records", function() {
                createSimpleView();
                store.removeAll();
                expect(view.getNodes()).toEqual([]);
            });

            describe("in response to updates", function() {
                beforeEach(function() {
                    createSimpleView({}, makeData(3));
                });

                it("should return the correct nodes after an add", function() {
                    store.insert(1, {
                        name: 'Foo'
                    });
                    var nodes = view.getNodes();

                    expect(nodes.length).toBe(4);
                    expect(nodes[0]).hasHTML('Item 1');
                    expect(nodes[1]).hasHTML('Foo');
                    expect(nodes[2]).hasHTML('Item 2');
                    expect(nodes[3]).hasHTML('Item 3');
                });

                it("should return the correct node after an update", function() {
                    store.first().set('name', 'Foo');
                    var nodes = view.getNodes();

                    expect(nodes.length).toBe(3);
                    expect(nodes[0]).hasHTML('Foo');
                    expect(nodes[1]).hasHTML('Item 2');
                    expect(nodes[2]).hasHTML('Item 3');
                });

                it("should return the correct node after a remove", function() {
                    store.removeAt(0);
                    var nodes = view.getNodes();

                    expect(nodes[0]).hasHTML('Item 2');
                    expect(nodes[1]).hasHTML('Item 3');
                });

                it("should return the correct node after a refresh", function() {
                    store.loadData([{
                        name: 'Foo'
                    }, {
                        name: 'Bar'
                    }]);
                    var nodes = view.getNodes();

                    expect(nodes[0]).hasHTML('Foo');
                    expect(nodes[1]).hasHTML('Bar');
                });

                it("should return null after a removeAll", function() {
                    store.removeAll();
                    expect(view.getNodes()).toEqual([]);
                });
            });

            describe("with start & end", function() {
                it("should return all items from the start if no end is specified", function() {
                    createSimpleView();
                    var nodes = view.getNodes(2);

                    expect(nodes.length).toBe(3);
                    expect(nodes[0]).hasHTML('Item 3');
                    expect(nodes[1]).hasHTML('Item 4');
                    expect(nodes[2]).hasHTML('Item 5');
                });

                it("should limit to the end (inclusive)", function() {
                    createSimpleView();
                    var nodes = view.getNodes(1, 3);

                    expect(nodes.length);
                    expect(nodes[0]).hasHTML('Item 2');
                    expect(nodes[1]).hasHTML('Item 3');
                    expect(nodes[2]).hasHTML('Item 4');
                });
            });
        });

        describe("getRecord/getRecords", function() {
            it("should accept a DOM element", function() {
                createSimpleView();
                var node = view.getNode(3);

                expect(view.getRecord(node)).toBe(store.getAt(3));
            });

            it("should accept an Ext.dom.Element", function() {
                createSimpleView();
                var node = Ext.get(view.getNode(1));

                expect(view.getRecord(node)).toBe(store.getAt(1));
                node.destroy();
            });

            it("should return null if no item could be found", function() {
                createSimpleView();
                var el = Ext.getBody().createChild();

                expect(view.getRecord(el)).toBeNull();
                el.destroy();
            });

            it("should return an array of records when using getRecords", function() {
                createSimpleView();
                var nodes = view.getNodes(0, 2),
                    records = view.getRecords(nodes),
                    i;

                expect(records.length).toBe(3);

                for (i = 0; i < records.length; i++) {
                    expect(records[i]).toBe(store.getAt(i));
                }
            });
        });

        describe("indexOf", function() {
            describe("param types", function() {
                it("should accept a node id", function() {
                    createSimpleView();
                    var id = Ext.id(view.getNodes()[2]);

                    expect(view.indexOf(id)).toBe(2);
                });

                it("should accept a record", function() {
                    createSimpleView();
                    expect(view.indexOf(store.getAt(3))).toBe(3);
                });

                it("should accept an event object", function() {
                    createSimpleView();
                    var node = view.getNodes()[1],
                        spy = jasmine.createSpy(),
                        event;

                    view.on('itemclick', spy);
                    jasmine.fireMouseEvent(node, 'click');
                    event = spy.mostRecentCall.args[4];
                    expect(view.indexOf(event)).toBe(1);
                });

                it("should accept an HTMLElement that is the child of an item", function() {
                    createSimpleView({
                        itemTpl: '<div class="bleh">{name}</div>'
                    });
                    var node = view.getNodes()[1];

                    expect(view.indexOf(node.firstChild)).toBe(1);
                });
            });

            describe("in response to updates", function() {
                beforeEach(function() {
                    createSimpleView();
                });

                it("should return the correct index after an add", function() {
                    store.insert(0, {
                        name: 'X'
                    });
                    expect(view.indexOf(view.getNodes()[0])).toBe(0);
                });

                it("should return the correct node after an update", function() {
                    store.first().set('name', 'Foo');
                    expect(view.indexOf(view.getNodes()[1])).toBe(1);
                });

                it("should return the correct node after a remove", function() {
                    store.removeAt(0);
                    expect(view.indexOf(view.getNodes()[1])).toBe(1);
                });

                it("should return the correct node after a refresh", function() {
                    store.loadData([{
                        name: 'Foo'
                    }, {
                        name: 'Bar'
                    }]);
                    expect(view.indexOf(store.getAt(1))).toBe(1);
                });

                it("should return -1 after a removeAll", function() {
                    var rec = store.first();

                    store.removeAll();
                    expect(view.indexOf(rec)).toBe(-1);
                });
            });
        });
    });

    describe("cleanup", function() {
        it("should detach all store listeners", function() {
            function getKeys() {
                var items = store.hasListeners,
                    o = {},
                    key;

                for (key in items) {
                    if (items.hasOwnProperty(key)) {
                        o[key] = items[key];
                    }
                }

                return o;
            }

            store = makeStore();
            var hasListeners = getKeys();

            createView({
                renderTo: Ext.getBody(),
                itemTpl: '{name}',
                store: store
            });
            view.destroy();
            expect(getKeys()).toEqual(hasListeners);
        });

        it("should unbind from the store", function() {
            createView({
                renderTo: Ext.getBody(),
                itemTpl: '{name}'
            });
            expect(view.getStore()).toBe(store);
            view.destroy();
            expect(view.store).toBeNull();
        });

        it("should destroy a load mask", function() {
            var CM = Ext.ComponentManager,
                count = CM.getCount();

            createView({
                renderTo: Ext.getBody(),
                itemTpl: '{name}'
            });

            view.destroy();

            expect(CM.getCount()).toBe(count);
        });
    });

    describe("modifying the store", function() {
        var spy, args;

        function createSimpleView(data) {
            createView({
                renderTo: Ext.getBody(),
                itemTpl: '{name}'
            }, data);
        }

        function createListView(data) {
            createView({
                renderTo: Ext.getBody(),
                itemSelector: 'li',
                tpl: [
                    '<ul>',
                        '<tpl for=".">',
                            '<li>{name}</li>',
                        '</tpl>',
                    '</ul>'
                ]
            }, data);
        }

        function getUL() {
            return view.getEl().down('ul', true);
        }

        beforeEach(function() {
            spy = jasmine.createSpy();
        });

        describe("adding", function() {
            describe("a single record", function() {
                describe("with a simple view", function() {
                    it("should be able to add to an empty view", function() {
                        createSimpleView([]);
                        store.add(createModel('Item1'));
                        var nodes = view.getNodes();

                        expect(nodes.length).toBe(1);
                        expect(nodes[0]).hasHTML('Item1');
                    });

                    it("should be able to add to the end of a view", function() {
                        createSimpleView();
                        store.add(createModel('Item2'));
                        var nodes = view.getNodes();

                        expect(nodes.length).toBe(2);
                        expect(nodes[1]).hasHTML('Item2');
                    });

                    it("should be able to insert a node at the start of the view", function() {
                        createSimpleView();
                        store.insert(0, createModel('Item2'));
                        var nodes = view.getNodes();

                        expect(nodes.length).toBe(2);
                        expect(nodes[0]).hasHTML('Item2');
                    });

                    it("should be able to insert a node in the middle of the view", function() {
                        createSimpleView([{
                            name: 'Item1'
                        }, {
                            name: 'Item2'
                        }, {
                            name: 'Item3'
                        }, {
                            name: 'Item4'
                        }]);
                        store.insert(2, createModel('new'));
                        var nodes = view.getNodes();

                        expect(nodes.length).toBe(5);
                        expect(nodes[2]).hasHTML('new');
                    });
                });

                describe("with a container element", function() {
                    it("should be able to add to an empty view", function() {
                        createListView([]);
                        store.add(createModel('Item1'));
                        var nodes = view.getNodes();

                        expect(nodes.length).toBe(1);
                        expect(nodes[0]).hasHTML('Item1');
                        expect(nodes[0].parentNode).toBe(getUL());
                    });

                    it("should be able to add to the end of a view", function() {
                        createListView();
                        store.add(createModel('Item2'));
                        var nodes = view.getNodes();

                        expect(nodes.length).toBe(2);
                        expect(nodes[1]).hasHTML('Item2');
                        expect(nodes[1].parentNode).toBe(getUL());
                    });

                    it("should be able to insert a node at the start of the view", function() {
                        createListView();
                        store.insert(0, createModel('Item2'));
                        var nodes = view.getNodes();

                        expect(nodes.length).toBe(2);
                        expect(nodes[0]).hasHTML('Item2');
                        expect(nodes[0].parentNode).toBe(getUL());
                    });

                    it("should be able to insert a node in the middle of the view", function() {
                        createListView([{
                            name: 'Item1'
                        }, {
                            name: 'Item2'
                        }, {
                            name: 'Item3'
                        }, {
                            name: 'Item4'
                        }]);
                        store.insert(2, createModel('new'));
                        var nodes = view.getNodes();

                        expect(nodes.length).toBe(5);
                        expect(nodes[2]).hasHTML('new');
                        expect(nodes[2].parentNode).toBe(getUL());
                    });
                });

                describe("events", function() {
                    it("should fire the itemadd event and pass the records, the index & the nodes", function() {
                        createSimpleView();
                        view.on('itemadd', spy);
                        store.add(createModel('foo'));
                        expect(spy.callCount).toBe(1);
                        args = spy.mostRecentCall.args;
                        expect(args[0]).toEqual([store.getAt(1)]);
                        expect(args[1]).toBe(1);
                        expect(args[2]).toEqual([view.getNodes()[1]]);
                    });

                    it("should fire the itemadd event when adding to an empty view", function() {
                        createSimpleView([]);
                        view.on('itemadd', spy);
                        store.add(createModel('foo'));
                        expect(spy.callCount).toBe(1);
                    });
                });
            });

            describe("multiple records", function() {
                describe("contiguous range", function() {
                    describe("with a simple view", function() {
                        it("should be able to add to an empty view", function() {
                            createSimpleView([]);
                            store.add(createModel('Item1'), createModel('Item2'));
                            var nodes = view.getNodes();

                            expect(nodes.length).toBe(2);
                            expect(nodes[0]).hasHTML('Item1');
                            expect(nodes[1]).hasHTML('Item2');
                        });

                        it("should be able to add to the end of a view", function() {
                            createSimpleView();
                            store.add(createModel('Item2'), createModel('Item3'));
                            var nodes = view.getNodes();

                            expect(nodes.length).toBe(3);
                            expect(nodes[1]).hasHTML('Item2');
                            expect(nodes[2]).hasHTML('Item3');
                        });

                        it("should be able to insert at the start of the view", function() {
                            createSimpleView();
                            store.insert(0, [createModel('Item2'), createModel('Item3')]);
                            var nodes = view.getNodes();

                            expect(nodes.length).toBe(3);
                            expect(nodes[0]).hasHTML('Item2');
                            expect(nodes[1]).hasHTML('Item3');
                        });

                        it("should be able to insert in the middle of the view", function() {
                            createSimpleView([{
                                name: 'Item1'
                            }, {
                                name: 'Item2'
                            }, {
                                name: 'Item3'
                            }, {
                                name: 'Item4'
                            }]);
                            store.insert(2, [createModel('new1'), createModel('new2')]);
                            var nodes = view.getNodes();

                            expect(nodes.length).toBe(6);
                            expect(nodes[2]).hasHTML('new1');
                            expect(nodes[3]).hasHTML('new2');
                        });
                    });

                    describe("with a container element", function() {
                        it("should be able to add to an empty view", function() {
                            createListView([]);
                            store.add(createModel('Item1'), createModel('Item2'));
                            var nodes = view.getNodes();

                            expect(nodes.length).toBe(2);
                            expect(nodes[0]).hasHTML('Item1');
                            expect(nodes[1]).hasHTML('Item2');
                            expect(nodes[0].parentNode).toBe(getUL());
                            expect(nodes[1].parentNode).toBe(getUL());
                        });

                        it("should be able to add to the end of a view", function() {
                            createListView();
                            store.add(createModel('Item2'), createModel('Item3'));
                            var nodes = view.getNodes();

                            expect(nodes.length).toBe(3);
                            expect(nodes[1]).hasHTML('Item2');
                            expect(nodes[2]).hasHTML('Item3');
                            expect(nodes[1].parentNode).toBe(getUL());
                            expect(nodes[2].parentNode).toBe(getUL());
                        });

                        it("should be able to insert at the start of the view", function() {
                            createListView();
                            store.insert(0, [createModel('Item2'), createModel('Item3')]);
                            var nodes = view.getNodes();

                            expect(nodes.length).toBe(3);
                            expect(nodes[0]).hasHTML('Item2');
                            expect(nodes[1]).hasHTML('Item3');
                            expect(nodes[0].parentNode).toBe(getUL());
                            expect(nodes[1].parentNode).toBe(getUL());
                        });

                        it("should be able to insert in the middle of the view", function() {
                            createListView([{
                                name: 'Item1'
                            }, {
                                name: 'Item2'
                            }, {
                                name: 'Item3'
                            }, {
                                name: 'Item4'
                            }]);
                            store.insert(2, [createModel('new1'), createModel('new2')]);
                            var nodes = view.getNodes();

                            expect(nodes.length).toBe(6);
                            expect(nodes[2]).hasHTML('new1');
                            expect(nodes[3]).hasHTML('new2');
                            expect(nodes[2].parentNode).toBe(getUL());
                            expect(nodes[3].parentNode).toBe(getUL());
                        });
                    });

                    describe("events", function() {
                        it("should fire the itemadd event and pass the records, the index & the nodes", function() {
                            createSimpleView();
                            view.on('itemadd', spy);
                            store.add(createModel('foo1'), createModel('foo2'), createModel('foo3'));
                            expect(spy.callCount).toBe(1);
                            args = spy.mostRecentCall.args;
                            expect(args[0]).toEqual([store.getAt(1), store.getAt(2), store.getAt(3)]);
                            expect(args[1]).toBe(1);
                            var nodes = view.getNodes();

                            expect(args[2]).toEqual([nodes[1], nodes[2], nodes[3]]);
                        });
                    });
                });

                describe("discontiguous range", function() {
                    describe("with a simple view", function() {
                        it("should be able to add nodes", function() {
                            createSimpleView([
                                createModel('e'),
                                createModel('j'),
                                createModel('o')
                            ]);
                            store.sort('name');

                            store.add(
                                createModel('a'),
                                createModel('b'),
                                createModel('f'),
                                createModel('g'),
                                createModel('k'),
                                createModel('l'),
                                createModel('m'),
                                createModel('p')
                            );
                            var nodes = view.getNodes();

                            expect(nodes.length).toBe(11);
                            expect(nodes[0]).hasHTML('a');
                            expect(nodes[1]).hasHTML('b');
                            expect(nodes[2]).hasHTML('e');
                            expect(nodes[3]).hasHTML('f');
                            expect(nodes[4]).hasHTML('g');
                            expect(nodes[5]).hasHTML('j');
                            expect(nodes[6]).hasHTML('k');
                            expect(nodes[7]).hasHTML('l');
                            expect(nodes[8]).hasHTML('m');
                            expect(nodes[9]).hasHTML('o');
                            expect(nodes[10]).hasHTML('p');
                        });
                    });

                    describe("with a container element", function() {
                        it("should be able to add nodes", function() {
                            createListView([
                                createModel('e'),
                                createModel('j'),
                                createModel('o')
                            ]);
                            store.sort('name');

                            store.add(
                                createModel('a'),
                                createModel('b'),
                                createModel('f'),
                                createModel('g'),
                                createModel('k'),
                                createModel('l'),
                                createModel('m'),
                                createModel('p')
                            );

                            var nodes = view.getNodes(),
                                ul = getUL(),
                                i;

                            expect(nodes.length).toBe(11);
                            expect(nodes[0]).hasHTML('a');
                            expect(nodes[1]).hasHTML('b');
                            expect(nodes[2]).hasHTML('e');
                            expect(nodes[3]).hasHTML('f');
                            expect(nodes[4]).hasHTML('g');
                            expect(nodes[5]).hasHTML('j');
                            expect(nodes[6]).hasHTML('k');
                            expect(nodes[7]).hasHTML('l');
                            expect(nodes[8]).hasHTML('m');
                            expect(nodes[9]).hasHTML('o');
                            expect(nodes[10]).hasHTML('p');

                            for (i = 0; i < nodes.length; ++i) {
                                expect(nodes[i].parentNode).toBe(ul);
                            }
                        });
                    });

                    describe("events", function() {
                        it("should fire the itemadd event for each chunk", function() {
                            createSimpleView([
                                createModel('e'),
                                createModel('j'),
                                createModel('o')
                            ]);
                            store.sort('name');

                            view.on('itemadd', spy);
                            store.add(
                                createModel('a'),
                                createModel('b'),
                                createModel('f'),
                                createModel('g'),
                                createModel('k'),
                                createModel('l'),
                                createModel('m'),
                                createModel('p')
                            );
                            var nodes = view.getNodes();

                            args = spy.calls[0].args;
                            expect(args[0]).toEqual([store.getAt(0), store.getAt(1)]);
                            expect(args[1]).toBe(0);
                            expect(args[2]).toEqual([nodes[0], nodes[1]]);

                            args = spy.calls[1].args;
                            expect(args[0]).toEqual([store.getAt(3), store.getAt(4)]);
                            expect(args[1]).toBe(3);
                            expect(args[2]).toEqual([nodes[3], nodes[4]]);

                            args = spy.calls[2].args;
                            expect(args[0]).toEqual([store.getAt(6), store.getAt(7), store.getAt(8)]);
                            expect(args[1]).toBe(6);
                            expect(args[2]).toEqual([nodes[6], nodes[7], nodes[8]]);

                            args = spy.calls[3].args;
                            expect(args[0]).toEqual([store.getAt(10)]);
                            expect(args[1]).toBe(10);
                            expect(args[2]).toEqual([nodes[10]]);
                        });
                    });
                });
            });
        });

        describe("updating", function() {
            var rec;

            beforeEach(function() {
                createSimpleView();
                rec = store.first();
            });
            it("should update the node content", function() {
                rec.set('name', 'foo');
                var nodes = view.getNodes();

                expect(nodes.length).toBe(1);
                expect(nodes[0]).hasHTML('foo');
            });

            describe("events", function() {
                it("should fire the itemupdate event and pass the record, index & node", function() {
                    var spy = jasmine.createSpy();

                    view.on('itemupdate', spy);
                    rec.set('name', 'foo');
                    expect(spy.callCount).toBe(1);
                    var args = spy.mostRecentCall.args;

                    expect(args[0]).toBe(rec);
                    expect(args[1]).toBe(0);
                    expect(args[2]).toEqual(view.getNodes()[0]);
                });
            });
        });

        describe("removing", function() {
            describe("a single record", function() {
                it("should be able to remove the only node in the view", function() {
                    createSimpleView();
                    store.removeAt(0);
                    var nodes = view.getNodes();

                    expect(nodes.length).toBe(0);
                });
                it("should be remove a node from the end of a view", function() {
                    createSimpleView([
                        createModel('a'),
                        createModel('b'),
                        createModel('c'),
                        createModel('d')
                    ]);
                    store.removeAt(3);
                    var nodes = view.getNodes();

                    expect(nodes.length).toBe(3);
                    expect(nodes[0]).hasHTML('a');
                    expect(nodes[1]).hasHTML('b');
                    expect(nodes[2]).hasHTML('c');
                });

                it("should be able to remove a node from the start of the view", function() {
                    createSimpleView([
                        createModel('a'),
                        createModel('b'),
                        createModel('c'),
                        createModel('d')
                    ]);
                    store.removeAt(0);
                    var nodes = view.getNodes();

                    expect(nodes.length).toBe(3);
                    expect(nodes[0]).hasHTML('b');
                    expect(nodes[1]).hasHTML('c');
                    expect(nodes[2]).hasHTML('d');
                });

                it("should be able to remove a node from the middle of the view", function() {
                    createSimpleView([
                        createModel('a'),
                        createModel('b'),
                        createModel('c'),
                        createModel('d')
                    ]);
                    store.removeAt(1);
                    var nodes = view.getNodes();

                    expect(nodes.length).toBe(3);
                    expect(nodes[0]).hasHTML('a');
                    expect(nodes[1]).hasHTML('c');
                    expect(nodes[2]).hasHTML('d');
                });

                describe("events", function() {
                    it("should fire the itemremove event and pass the records, the index & the nodes", function() {
                        createSimpleView([
                            createModel('a'),
                            createModel('b'),
                            createModel('c'),
                            createModel('d')
                        ]);
                        var node = view.getNode(1),
                            rec = store.getAt(1);

                        view.on('itemremove', spy);
                        store.removeAt(1);
                        expect(spy.callCount).toBe(1);
                        args = spy.mostRecentCall.args;
                        expect(args[0]).toEqual([rec]);
                        expect(args[1]).toBe(1);
                        expect(args[2]).toEqual([node]);
                    });

                    it("should fire the itemremove event when removing the last record", function() {
                        createSimpleView([createModel('foo')]);
                        view.on('itemremove', spy);
                        store.removeAt(0);
                        expect(spy.callCount).toBe(1);
                    });

                    it("should fire the itemremove event when rereshing", function() {
                        createSimpleView([createModel('foo')]);
                        view.on('itemremove', spy);
                        store.fireEvent('refresh', store);
                        expect(spy.callCount).toBe(1);
                    });
                });
            });

            describe("multiple records", function() {
                beforeEach(function() {
                    createSimpleView([
                        createModel('a'),
                        createModel('b'),
                        createModel('c'),
                        createModel('d'),
                        createModel('e'),
                        createModel('f'),
                        createModel('g'),
                        createModel('h'),
                        createModel('i'),
                        createModel('j'),
                        createModel('k'),
                        createModel('l'),
                        createModel('m'),
                        createModel('n'),
                        createModel('o'),
                        createModel('p')
                    ]);
                });

                describe("contiguous range", function() {
                    it("should be able to remove the only nodes in the view", function() {
                        store.remove(store.getRange());
                        expect(view.getNodes().length).toBe(0);
                    });

                    it("should be able to remove at the end of a view", function() {
                        store.remove([byName('n'), byName('o'), byName('p')]);
                        var nodes = view.getNodes();

                        expect(nodes.length).toBe(13);
                        expect(nodes[11]).hasHTML('l');
                        expect(nodes[12]).hasHTML('m');
                    });

                    it("should be able to remove at the start of the view", function() {
                        store.remove([byName('a'), byName('b')]);
                        var nodes = view.getNodes();

                        expect(nodes.length).toBe(14);
                        expect(nodes[0]).hasHTML('c');
                        expect(nodes[1]).hasHTML('d');
                    });

                    it("should be able to remove in the middle of the view", function() {
                        store.remove([byName('f'), byName('g'), byName('h'), byName('i')]);
                        var nodes = view.getNodes();

                        expect(nodes.length).toBe(12);
                        expect(nodes[4]).hasHTML('e');
                        expect(nodes[5]).hasHTML('j');
                    });

                    describe("events", function() {
                        it("should fire the itemremove event and pass the records, the index & the nodes", function() {
                            view.on('itemremove', spy);
                            var c = byName('c'),
                                d = byName('d'),
                                e = byName('e'),
                                nodes = view.getNodes();

                            store.remove([c, d, e]);
                            expect(spy.callCount).toBe(1);
                            args = spy.mostRecentCall.args;
                            expect(args[0]).toEqual([c, d, e]);
                            expect(args[1]).toBe(2);
                            expect(args[2]).toEqual([nodes[2], nodes[3], nodes[4]]);
                        });
                    });
                });

                describe("discontiguous range", function() {
                    it("should be able to remove  nodes", function() {
                        store.remove([
                            byName('b'),
                            byName('c'),
                            byName('f'),
                            byName('g'),
                            byName('h'),
                            byName('m'),
                            byName('n')
                        ]);
                        var nodes = view.getNodes();

                        expect(nodes.length).toBe(9);
                        expect(nodes[0]).hasHTML('a');
                        expect(nodes[1]).hasHTML('d');
                        expect(nodes[2]).hasHTML('e');
                        expect(nodes[3]).hasHTML('i');
                        expect(nodes[4]).hasHTML('j');
                        expect(nodes[5]).hasHTML('k');
                        expect(nodes[6]).hasHTML('l');
                        expect(nodes[7]).hasHTML('o');
                        expect(nodes[8]).hasHTML('p');
                    });

                    describe("events", function() {
                        it("should fire the itemremove event for each chunk in reverse order", function() {
                            view.on('itemremove', spy);
                            var nodes = view.getNodes(),
                                records = store.getRange();

                            store.remove([
                                byName('b'),
                                byName('c'),
                                byName('f'),
                                byName('g'),
                                byName('h'),
                                byName('m'),
                                byName('n')
                            ]);

                            args = spy.calls[0].args;
                            expect(args[0]).toEqual([records[12], records[13]]);
                            expect(args[1]).toBe(12);
                            expect(args[2]).toEqual([nodes[12], nodes[13]]);

                            args = spy.calls[1].args;
                            expect(args[0]).toEqual([records[5], records[6], records[7]]);
                            expect(args[1]).toBe(5);
                            expect(args[2]).toEqual([nodes[5], nodes[6], nodes[7]]);

                            args = spy.calls[2].args;
                            expect(args[0]).toEqual([records[1], records[2]]);
                            expect(args[1]).toBe(1);
                            expect(args[2]).toEqual([nodes[1], nodes[2]]);
                        });
                    });
                });
            });
        });

        describe("with a pending refresh, while in a hidden container after being visible", function() {
            var ct;

            beforeEach(function() {
                ct = new Ext.container.Container({
                    renderTo: Ext.getBody(),
                    width: 300,
                    height: 300,
                    items: createView({
                        itemTpl: '{name}'
                    }, 5)
                });
                ct.hide();
                view.refresh();
            });

            afterEach(function() {
                ct = Ext.destroy(ct);
            });

            it("should update after an add", function() {
                store.add([{
                    name: 'a'
                }]);
                ct.show();
                var nodes = view.getNodes();

                expect(nodes.length).toBe(6);
                expect(nodes[0]).hasHTML('Item 1');
                expect(nodes[1]).hasHTML('Item 2');
                expect(nodes[2]).hasHTML('Item 3');
                expect(nodes[3]).hasHTML('Item 4');
                expect(nodes[4]).hasHTML('Item 5');
                expect(nodes[5]).hasHTML('a');
            });

            it("should update after an edit", function() {
                store.first().set('name', 'foo');
                ct.show();
                var nodes = view.getNodes();

                expect(nodes.length).toBe(5);
                expect(nodes[0]).hasHTML('foo');
                expect(nodes[1]).hasHTML('Item 2');
                expect(nodes[2]).hasHTML('Item 3');
                expect(nodes[3]).hasHTML('Item 4');
                expect(nodes[4]).hasHTML('Item 5');
            });

            it("should update after a remove", function() {
                store.removeAt(0);
                ct.show();
                var nodes = view.getNodes();

                expect(nodes.length).toBe(4);
                expect(nodes[0]).hasHTML('Item 2');
                expect(nodes[1]).hasHTML('Item 3');
                expect(nodes[2]).hasHTML('Item 4');
                expect(nodes[3]).hasHTML('Item 5');
            });

            it("should update after a removeAll", function() {
                store.removeAll();
                ct.show();
                expect(view.getNodes().length).toBe(0);
            });

            it("should update after a sort", function() {
                store.sort('name', 'DESC');
                ct.show();
                var nodes = view.getNodes();

                expect(nodes.length).toBe(5);
                expect(nodes[0]).hasHTML('Item 5');
                expect(nodes[1]).hasHTML('Item 4');
                expect(nodes[2]).hasHTML('Item 3');
                expect(nodes[3]).hasHTML('Item 2');
                expect(nodes[4]).hasHTML('Item 1');
            });

            it("should update after a filter", function() {
                store.filter({
                    filterFn: function(rec) {
                        var n = parseInt(rec.get('name').replace('Item ', ''), 10);

                        return n % 2 === 0;
                    }
                });
                ct.show();
                var nodes = view.getNodes();

                expect(nodes.length).toBe(2);
                expect(nodes[0]).hasHTML('Item 2');
                expect(nodes[1]).hasHTML('Item 4');
            });

            it("should update to a series of actions", function() {
                var rec = store.add({
                    name: 'X'
                })[0];

                rec.set('name', 'Foo');
                store.removeAt(0);
                store.removeAt(1);
                store.removeAll();
                store.add({
                    name: 'A'
                });
                store.add(['Z'], ['Q']);
                store.sort('name');
                ct.show();

                var nodes = view.getNodes();

                expect(nodes.length).toBe(3);
                expect(nodes[0]).hasHTML('A');
                expect(nodes[1]).hasHTML('Q');
                expect(nodes[2]).hasHTML('Z');
            });
        });
    });

    describe("shrink wrap", function() {
        describe("width", function() {
            function makeShrinkWrapView(tpl, data) {
                createView({
                    renderTo: Ext.getBody(),
                    floating: true,
                    shrinkWrap: true,
                    tpl: tpl || '<tpl for="."><div class="x-tpl-item" style="float: left; width: 10px;">{name}</div></tpl>',
                    itemSelector: '.x-tpl-item'
                }, data || [
                    createModel('a'),
                    createModel('b'),
                    createModel('c'),
                    createModel('d'),
                    createModel('e'),
                    createModel('f'),
                    createModel('g'),
                    createModel('h'),
                    createModel('i'),
                    createModel('j')
                ]);
            }

            it("should set the width on refresh", function() {
                makeShrinkWrapView();
                var store = view.getStore();

                store.suspendEvents();
                store.removeAll();

                for (var i = 1; i <= 5; ++i) {
                    store.add({
                        name: 'Item ' + i
                    });
                }

                store.resumeEvents();
                view.refresh();
                expect(view.getWidth()).toBe(50);
            });

            it("should update the width when a new item is added", function() {
                makeShrinkWrapView();
                expect(view.getWidth()).toBe(100);
                view.getStore().add({
                    name: 'Item 2'
                });
                expect(view.getWidth()).toBe(110);
            });

            it("should update the width when an item is removed", function() {
                makeShrinkWrapView();
                view.getStore().removeAt(1);
                expect(view.getWidth()).toBe(90);
            });

            it("should update the width when an item is modified causing the width to change", function() {
                makeShrinkWrapView(
                    '<tpl for="."><div class="x-tpl-item" style="float: left; width: {size}px;">{name}</div></tpl>',
                [createModel({
                    name: 'a',
                    size: 10
                })]);
                expect(view.getWidth()).toBe(10);
                view.getStore().first().set('size', 100);
                expect(view.getWidth()).toBe(100);
            });
        });

        describe("height", function() {
            function makeShrinkWrapView(tpl, data) {
                createView({
                    renderTo: Ext.getBody(),
                    tpl: tpl || '<tpl for="."><div class="x-tpl-item" style="height: 10px;">{name}</div></tpl>',
                    itemSelector: '.x-tpl-item'
                }, data || [
                    createModel('a'),
                    createModel('b'),
                    createModel('c'),
                    createModel('d'),
                    createModel('e'),
                    createModel('f'),
                    createModel('g'),
                    createModel('h'),
                    createModel('i'),
                    createModel('j')
                ]);
            }

            it("should set the height on refresh", function() {
                makeShrinkWrapView();
                var store = view.getStore();

                store.suspendEvents();
                store.removeAll();

                for (var i = 1; i <= 5; ++i) {
                    store.add({
                        name: 'Item ' + i
                    });
                }

                store.resumeEvents();
                view.refresh();
                expect(view.getHeight()).toBe(50);
            });

            it("should update the height when a new item is added", function() {
                makeShrinkWrapView();
                expect(view.getHeight()).toBe(100);
                view.getStore().add({
                    name: 'Item 2'
                });
                expect(view.getHeight()).toBe(110);
            });

            it("should update the height when an item is removed", function() {
                makeShrinkWrapView();
                view.getStore().removeAt(1);
                expect(view.getHeight()).toBe(90);
            });

            it("should update the height when an item is modified causing the height to change", function() {
                makeShrinkWrapView(
                    '<tpl for="."><div class="x-tpl-item" style="height: {size}px;">{name}</div></tpl>',
                [createModel({
                    name: 'a',
                    size: 10
                })]);
                expect(view.getHeight()).toBe(10);
                view.getStore().first().set('size', 100);
                expect(view.getHeight()).toBe(100);
            });
        });

        it("should only trigger a single layout when adding multiple ranges", function() {
            createView({
                renderTo: Ext.getBody(),
                itemTpl: '{name}'
            }, [
                createModel('a'),
                createModel('b'),
                createModel('d'),
                createModel('e'),
                createModel('g'),
                createModel('h'),
                createModel('j')
            ]);
            view.getStore().sort('name');

            var store = view.getStore(),
                counter = view.componentLayoutCounter;

            store.add(
                createModel('c'),
                createModel('f'),
                createModel('i')
            );
            expect(view.componentLayoutCounter).toBe(counter + 1);
        });

        it("should only trigger a single layout when removing multiple ranges", function() {
            createView({
                renderTo: Ext.getBody(),
                itemTpl: '{name}'
            }, [
                createModel('a'),
                createModel('b'),
                createModel('c'),
                createModel('d'),
                createModel('e'),
                createModel('f'),
                createModel('g'),
                createModel('h'),
                createModel('i'),
                createModel('j')
            ]);

            var store = view.getStore(),
                counter = view.componentLayoutCounter;

            store.remove([
                byName('a'),
                byName('b'),
                byName('e'),
                byName('f'),
                byName('i')
            ]);
            expect(view.componentLayoutCounter).toBe(counter + 1);
        });
    });

    describe("emptyText", function() {
        function createSimpleView(deferEmptyText, data) {
            createView({
                renderTo: Ext.getBody(),
                deferEmptyText: deferEmptyText,
                itemTpl: '{name}',
                emptyText: 'Foo'
            }, data);
        }

        describe("with deferEmptyText: false", function() {
            it("should show the empty text immediately when the store is empty", function() {
                createSimpleView(false, null);
                expect(view.getEl().dom.childNodes.length).toBe(2);
                expect(view.getEl().dom.childNodes[0].data).toBe('Foo');
                expect(view.getEl().dom.childNodes[1] === view.tabGuardEl).toBe(true);
            });

            it("should not contain the empty text if there are nodes", function() {
                createSimpleView(false);
                expect(view.getEl().dom.childNodes.length).toBe(store.getCount() + 1);
                expect(view.getEl().dom.childNodes[store.getCount()] === view.tabGuardEl).toBe(true);
            });
        });

        describe("with deferEmptyText: true", function() {
            it("should not show the empty text immediately when the store is empty", function() {
                createSimpleView(true, null);
                expect(view.getEl().dom.childNodes.length).toBe(1);
                expect(view.getEl().dom.childNodes[0] === view.tabGuardEl).toBe(true);
            });

            it("should show the empty text after a second refresh if the store is empty", function() {
                createSimpleView(true, null);
                view.refresh();

                // Simple test for HTML content here. Subsequent refreshes wipe out the tabGuardEl.
                // It is only necessary one time.
                expect(view.getEl().dom).hasHTML('Foo');
            });

            it("should not contain the empty text if there are nodes", function() {
                createSimpleView(true);
                expect(view.getEl().dom.childNodes.length).toBe(store.getCount() + 1);
                expect(view.getEl().dom.childNodes[store.getCount()] === view.tabGuardEl).toBe(true);
            });

            it("should show the empty text if the store had loaded before render", function() {
                store = new Ext.data.Store({
                    model: TestModel,
                    proxy: {
                        type: 'ajax',
                        url: 'foo'
                    }
                });

                createView({
                    deferEmptyText: true,
                    itemTpl: '{name}',
                    emptyText: 'Foo',
                    store: store
                });
                store.load();
                completeRequest([]);
                view.render(Ext.getBody());
                expect(view.getEl().dom.childNodes.length).toBe(2);
                expect(view.getEl().dom.childNodes[0].data).toBe('Foo');
                expect(view.getEl().dom.childNodes[1] === view.tabGuardEl).toBe(true);
            });
        });

        describe("store modifications", function() {
            it("should clear the empty text when adding a record", function() {
                createSimpleView(false, []);
                store.add(createModel('Item1'));
                expect(view.getEl().dom).not.hasHTML('Foo');
            });

            it("should clear the empty text when loading several records", function() {
                createSimpleView(false, []);
                store.loadData([{
                    name: 'Item1'
                }, {
                    name: 'Item2'
                }, {
                    name: 'Item3'
                }]);
                expect(view.getEl().dom).not.hasHTML('Foo');
            });

            it("should add the empty text when removing the last element", function() {
                createSimpleView(false);
                store.removeAt(0);
                expect(view.getEl().dom).hasHTML('Foo');
            });

            it("should add the empty text when loading an empty data set", function() {
                createSimpleView(false);
                store.loadData([]);
                expect(view.getEl().dom).hasHTML('Foo');
            });
        });
    });

    describe("refreshNode", function() {
        var renderFn;

        function makeTplView(data) {
            createView({
                itemTpl: new Ext.XTemplate('{name:this.doRender}', {
                    doRender: function(v) {
                        return renderFn ? renderFn(v) : v;
                    }
                })
            }, data);
        }

        it("should not throw when the view is not rendered", function() {
            makeTplView();
            expect(function() {
                view.refreshNode(store.first());
            }).not.toThrow();
        });

        it("should not throw when the record is not in the store", function() {
            var rec = new TestModel();

            makeTplView();
            view.render(Ext.getBody());
            expect(function() {
                view.refreshNode(rec);
            }).not.toThrow();
        });

        it("should not throw if the index is not in the view", function() {
            makeTplView();
            view.render(Ext.getBody());
            expect(function() {
                view.refreshNode(100);
            }).not.toThrow();
        });

        it("should update the view contents when passing a model", function() {
            var someVar = 100;

            renderFn = function(v) {
                return someVar + v;
            };

            makeTplView();
            view.render(Ext.getBody());
            expect(view.getNodes()[0]).hasHTML('100Item1');
            // Change the closure var which should trigger a change on refresh
            someVar = 200;
            view.refreshNode(store.first());
            expect(view.getNodes()[0]).hasHTML('200Item1');
        });

        it("should only update the specified record", function() {
            var someVar = 100;

            renderFn = function(v) {
                return someVar + v;
            };

            makeTplView([{ name: 'Foo' }, { name: 'Bar' }, { name: 'Baz' }]);
            view.render(Ext.getBody());
            var nodes = view.getNodes();

            expect(nodes[0]).hasHTML('100Foo');
            expect(nodes[1]).hasHTML('100Bar');
            expect(nodes[2]).hasHTML('100Baz');
            someVar = 200;
            view.refreshNode(store.getAt(1));
            nodes = view.getNodes();
            expect(nodes[0]).hasHTML('100Foo');
            expect(nodes[1]).hasHTML('200Bar');
            expect(nodes[2]).hasHTML('100Baz');
        });

        it("should update the view contents when passing an index", function() {
            var someVar = 100;

            renderFn = function(v) {
                return someVar + v;
            };

            makeTplView();
            view.render(Ext.getBody());
            expect(view.getNodes()[0]).hasHTML('100Item1');
            // Change the closure var which should trigger a change on refresh
            someVar = 200;
            view.refreshNode(store.first());
            expect(view.getNodes()[0]).hasHTML('200Item1');
        });

        it("should only update the specified index", function() {
            var someVar = 100;

            renderFn = function(v) {
                return someVar + v;
            };

            makeTplView([{ name: 'Foo' }, { name: 'Bar' }, { name: 'Baz' }]);
            view.render(Ext.getBody());
            var nodes = view.getNodes();

            expect(nodes[0]).hasHTML('100Foo');
            expect(nodes[1]).hasHTML('100Bar');
            expect(nodes[2]).hasHTML('100Baz');
            someVar = 200;
            view.refreshNode(1);
            nodes = view.getNodes();
            expect(nodes[0]).hasHTML('100Foo');
            expect(nodes[1]).hasHTML('200Bar');
            expect(nodes[2]).hasHTML('100Baz');
        });
    });

    describe("selection:multi", function() {
        var sm, a, b, c, d, e, f, g;

        function makeSelectionView(render) {
            createView({
                renderTo: render ? Ext.getBody() : undefined,
                itemTpl: '{name}',
                multiSelect: true
            }, [
                a = createModel('a'),
                b = createModel('b'),
                c = createModel('c'),
                d = createModel('d'),
                e = createModel('e'),
                f = createModel('f'),
                g = createModel('g')
            ]);
            sm = view.getSelectionModel();
        }

        afterEach(function() {
            a = b = c = d = e = f = g = sm = null;
        });

        function isSelected(record) {
            var node = view.getNode(record),
                cls = view.selectedItemCls;

            return Ext.fly(node).hasCls(cls);
        }

        function expectSelected(record) {
            expect(isSelected(record)).toBe(true);
        }

        function expectNotSelected(record) {
            expect(isSelected(record)).toBe(false);
        }

        describe("before render", function() {
            beforeEach(function() {
                makeSelectionView(false);
            });

            it("should add the selected cls to a selected record", function() {
                sm.select(a);
                view.render(Ext.getBody());
                expectSelected(a);
                expectNotSelected(b);
                expectNotSelected(c);
                expectNotSelected(d);
                expectNotSelected(e);
                expectNotSelected(f);
                expectNotSelected(g);
            });

            it("should add the selected cls to multiple selected records", function() {
                sm.select([a, d, f, g]);
                view.render(Ext.getBody());
                expectSelected(a);
                expectNotSelected(b);
                expectNotSelected(c);
                expectSelected(d);
                expectNotSelected(e);
                expectSelected(f);
                expectSelected(g);
            });

            it("should not add the selected cls to deselected records", function() {
                sm.select(a);
                sm.deselect(a);
                view.render(Ext.getBody());
                expectNotSelected(a);
            });
        });

        describe("after render", function() {
            beforeEach(function() {
                makeSelectionView(true);
            });

            it("should add the selected cls to a selected record", function() {
                sm.select(a);
                expectSelected(a);
                expectNotSelected(b);
                expectNotSelected(c);
                expectNotSelected(d);
                expectNotSelected(e);
                expectNotSelected(f);
                expectNotSelected(g);
            });

            it("should add the selected cls to multiple selected records", function() {
                sm.select([a, d, f, g]);
                expectSelected(a);
                expectNotSelected(b);
                expectNotSelected(c);
                expectSelected(d);
                expectNotSelected(e);
                expectSelected(f);
                expectSelected(g);
            });

            it("should not add the selected cls to deselected records", function() {
                sm.select(a);
                sm.deselect(a);
                expectNotSelected(a);
            });
        });

        it("should maintain the selected cls after being updated", function() {
            makeSelectionView(true);
            sm.select(a);
            a.set('name', 'Foo');
            expectSelected(a);
        });

        it("should maintain the selected cls after being sorted", function() {
            makeSelectionView(true);
            sm.select(a);
            store.sort('name', 'ASC');
            expectSelected(a);
        });
    });

    describe("highlighting", function() {
        beforeEach(function() {
            createView({
                itemCls: 'foo',
                renderTo: Ext.getBody(),
                itemTpl: '{name}',
                overItemCls: 'over'
            }, makeData(10));
        });

        it("should apply the highlight class to a node", function() {
            view.highlightItem(view.getNode(0));
            var nodes = view.getEl().select('.foo');

            expect(nodes.item(0).hasCls(view.overItemCls)).toBe(true);
        });

        it("should remove the highlight on an item", function() {
            view.highlightItem(view.getNode(0));
            view.clearHighlight(view.getNode(0));
            var nodes = view.getEl().select('.foo');

            expect(nodes.item(0).hasCls(view.overItemCls)).toBe(false);
        });

        it("should only have at most one item highlighted", function() {
            view.highlightItem(view.getNode(0));
            view.highlightItem(view.getNode(1));
            var nodes = view.getEl().select('.foo');

            expect(nodes.item(0).hasCls(view.overItemCls)).toBe(false);
            expect(nodes.item(1).hasCls(view.overItemCls)).toBe(true);
        });

        it("should keep highlight on an item when updated", function() {
            view.highlightItem(view.getNode(0));
            view.getStore().getAt(0).set('name', 'New');
            var nodes = view.getEl().select('.foo');

            expect(nodes.item(0).hasCls(view.overItemCls)).toBe(true);
        });

        it("should clear all highlights on refresh", function() {
            view.highlightItem(view.getNode(0));
            view.refresh();
            var nodes = view.getEl().select('.foo');

            expect(nodes.item(0).hasCls(view.overItemCls)).toBe(false);
        });
    });

    describe('focusing a node within the view', function() {
        it('should not scroll to top of dataview when descendant node is selected and focused, dataview only', function() {
            var node;

            createView({
                tpl: new Ext.XTemplate(
                    '<tpl for=".">',
                        '<p style="margin: 0;" class="foo">{name}</p>',
                    '</tpl>'
                ),
                itemSelector: 'p.foo',
                height: 100,
                autoScroll: true,
                renderTo: Ext.getBody()
            }, makeData(50));

            node = view.getNode(49);

            // Scroll to the last node in the view and select it.
            view.scrollBy(Ext.fly(node).getXY());
            jasmine.fireMouseEvent(node, 'click');

            expect(view.el.dom.scrollTop).not.toBe(0);
        });

        it('should not scroll to top of dataview when descendant node is selected and focused, dataview in a parent container', function() {
            var container, node;

            createView({
                tpl: new Ext.XTemplate(
                    '<tpl for=".">',
                        '<p style="margin: 0;" class="foo">{name}</p>',
                    '</tpl>'
                ),
                itemSelector: 'p.foo'
            }, makeData(50));

            container = new Ext.container.Container({
                height: 300,
                autoScroll: true,
                items: view,
                renderTo: Ext.getBody()
            });

            node = view.getNode(49);

            // Scroll to the last node in the view and select it.
            container.scrollBy(Ext.fly(node).getXY());
            jasmine.fireMouseEvent(node, 'click');

            expect(container.el.dom.scrollTop).not.toBe(0);

            container.destroy();
            container = null;
        });
    });

    describe("bindStore", function() {
        var other;

        afterEach(function() {
            other = Ext.destroy(other);
        });

        it("should only refresh once when binding a new store", function() {
            createView({
                renderTo: Ext.getBody(),
                itemTpl: '{name}',
                store: null
            });

            expect(view.getNodes().length).toBe(0);
            var count = view.refreshCounter;

            store = new Ext.data.Store({
                model: TestModel,
                data: [{
                    name: 'NewItem'
                }]
            });
            view.bindStore(store);
            expect(view.refreshCounter).toBe(count + 1);
            expect(view.getNodes().length).toBe(1);
        });

        it("should only refresh once when binding over an existing store", function() {
            createView({
                renderTo: Ext.getBody(),
                itemTpl: '{name}'
            }, makeData(5));

            expect(view.getNodes().length).toBe(5);
            var count = view.refreshCounter;

            other = new Ext.data.Store({
                model: TestModel,
                data: [{
                    name: 'NewItem'
                }]
            });
            view.bindStore(other);
            expect(view.refreshCounter).toBe(count + 1);
            expect(view.getNodes().length).toBe(1);
        });

        it("should defer the refresh until the store loads", function() {
            createView({
                renderTo: Ext.getBody(),
                itemTpl: '{name}'
            }, makeData(10));

            expect(view.getNodes().length).toBe(10);
            var count = view.refreshCounter;

            other = new Ext.data.Store({
                model: TestModel,
                proxy: {
                    type: 'ajax',
                    url: 'fakeUrl'
                }
            });

            other.load();
            view.bindStore(other);
            expect(count).toBe(count);
            completeRequest(makeData(3));
            expect(view.refreshCounter).toBe(count + 1);
            expect(view.getNodes().length).toBe(3);
        });

        it("should not cause an exception with a selection", function() {
            createView({
                renderTo: Ext.getBody(),
                itemTpl: '{name}'
            }, makeData(3));

            view.getSelectionModel().select(store.getAt(0));

            other = new Ext.data.Store({
                model: TestModel,
                data: makeData(3)
            });

            expect(function() {
                view.bindStore(other);
            }).not.toThrow();
        });

        it("should remain selected with a matching record", function() {
            createView({
                renderTo: Ext.getBody(),
                itemTpl: '{name}'
            }, makeDataWithId(3));

            var selModel = view.getSelectionModel();

            selModel.select(store.getAt(0));

            other = new Ext.data.Store({
                model: TestModel,
                data: makeDataWithId(3)
            });

            view.bindStore(other);

            expect(selModel.isSelected(other.getAt(0))).toBe(true);
            expect(view.getNode(0)).toHaveCls(view.selectedItemCls);
        });
    });

    describe("setStore", function() {
        var other;

        afterEach(function() {
            other = Ext.destroy(other);
        });

        it("should only refresh once when binding a new store", function() {
            createView({
                renderTo: Ext.getBody(),
                itemTpl: '{name}',
                store: null
            });

            expect(view.getNodes().length).toBe(0);
            var count = view.refreshCounter;

            store = new Ext.data.Store({
                model: TestModel,
                data: [{
                    name: 'NewItem'
                }]
            });
            view.setStore(store);
            expect(view.refreshCounter).toBe(count + 1);
            expect(view.getNodes().length).toBe(1);
        });

        it("should only refresh once when binding over an existing store", function() {
            createView({
                renderTo: Ext.getBody(),
                itemTpl: '{name}'
            }, makeData(5));

            expect(view.getNodes().length).toBe(5);
            var count = view.refreshCounter;

            other = new Ext.data.Store({
                model: TestModel,
                data: [{
                    name: 'NewItem'
                }]
            });
            view.setStore(other);
            expect(view.refreshCounter).toBe(count + 1);
            expect(view.getNodes().length).toBe(1);
        });

        it("should defer the refresh until the store loads", function() {
            createView({
                renderTo: Ext.getBody(),
                itemTpl: '{name}'
            }, makeData(10));

            expect(view.getNodes().length).toBe(10);
            var count = view.refreshCounter;

            other = new Ext.data.Store({
                model: TestModel,
                proxy: {
                    type: 'ajax',
                    url: 'fakeUrl'
                }
            });

            other.load();
            view.setStore(other);
            expect(count).toBe(count);
            completeRequest(makeData(3));
            expect(view.refreshCounter).toBe(count + 1);
            expect(view.getNodes().length).toBe(3);
        });

        it("should not cause an exception with a selection", function() {
            createView({
                renderTo: Ext.getBody(),
                itemTpl: '{name}'
            }, makeData(3));

            view.getSelectionModel().select(store.getAt(0));

            other = new Ext.data.Store({
                model: TestModel,
                data: makeData(3)
            });

            expect(function() {
                view.setStore(other);
            }).not.toThrow();
        });

        it("should remain selected with a matching record", function() {
            createView({
                renderTo: Ext.getBody(),
                itemTpl: '{name}'
            }, makeDataWithId(3));

            var selModel = view.getSelectionModel();

            selModel.select(store.getAt(0));

            other = new Ext.data.Store({
                model: TestModel,
                data: makeDataWithId(3)
            });

            view.setStore(other);

            expect(selModel.isSelected(other.getAt(0))).toBe(true);
            expect(view.getNode(0)).toHaveCls(view.selectedItemCls);
        });
    });

    describe("viewmodel binding", function() {
        var viewModel;

        beforeEach(function() {
            viewModel = new Ext.app.ViewModel();
        });

        afterEach(function() {
            viewModel = Ext.destroy(viewModel);
        });

        describe("store", function() {
            it("should be able to bind the store", function() {
                viewModel.setStores({
                    things: {
                        model: TestModel,
                        data: makeData(5)
                    }
                });

                createView({
                    renderTo: Ext.getBody(),
                    itemTpl: '{name}',
                    store: null,
                    bind: '{things}',
                    viewModel: viewModel
                });
                expect(view.getNodes().length).toBe(0);
                viewModel.notify();
                expect(view.getNodes().length).toBe(5);
            });
        });

        describe("selection", function() {
            var spy, a, b, c, d, selModel;

            function makeViewModelView(cfg) {
                a = createModel({ id: 1, name: 'a' });
                b = createModel({ id: 2, name: 'b' });
                c = createModel({ id: 3, name: 'c' });
                d = createModel({ id: 4, name: 'd' });

                createView(Ext.apply({
                    renderTo: Ext.getBody(),
                    itemTpl: '{name}',
                    viewModel: viewModel
                }, cfg), [a, b, c, d]);
                selModel = view.getSelectionModel();
            }

            beforeEach(function() {
                spy = jasmine.createSpy();
            });

            afterEach(function() {
                a = b = c = d = spy = selModel = null;
            });

            function selectNotify(rec) {
                selModel.select(rec);
                viewModel.notify();
            }

            describe("reference", function() {
                beforeEach(function() {
                    makeViewModelView({
                        reference: 'userList'
                    });
                    viewModel.bind('{userList.selection}', spy);
                    viewModel.notify();
                });

                it("should publish null by default", function() {
                    var args = spy.mostRecentCall.args;

                    expect(args[0]).toBeNull();
                    expect(args[1]).toBeUndefined();
                });

                it("should publish the value when selected", function() {
                    selectNotify(b);
                    var args = spy.mostRecentCall.args;

                    expect(args[0]).toBe(b);
                    expect(args[1]).toBeNull();
                });

                it("should publish when the selection is changed", function() {
                    selectNotify(b);
                    spy.reset();
                    selectNotify(d);
                    var args = spy.mostRecentCall.args;

                    expect(args[0]).toBe(d);
                    expect(args[1]).toBe(b);
                });

                it("should publish when an item is deselected", function() {
                    selectNotify(b);
                    spy.reset();
                    selModel.deselect(b);
                    viewModel.notify();
                    var args = spy.mostRecentCall.args;

                    expect(args[0]).toBeNull();
                    expect(args[1]).toBe(b);
                });
            });

            describe("two way binding", function() {
                beforeEach(function() {
                    makeViewModelView({
                        bind: {
                            selection: '{foo}'
                        }
                    });
                    viewModel.bind('{foo}', spy);
                    viewModel.notify();
                });

                describe("changing the selection", function() {
                    it("should trigger the binding when adding a selection", function() {
                        selectNotify(c);
                        var args = spy.mostRecentCall.args;

                        expect(args[0]).toBe(c);
                        expect(args[1]).toBeUndefined();
                    });

                    it("should trigger the binding when changing the selection", function() {
                        selectNotify(c);
                        spy.reset();
                        selectNotify(a);
                        var args = spy.mostRecentCall.args;

                        expect(args[0]).toBe(a);
                        expect(args[1]).toBe(c);
                    });

                    it("should trigger the binding when an item is deselected", function() {
                        selectNotify(c);
                        spy.reset();
                        selModel.deselect(c);
                        viewModel.notify();
                        var args = spy.mostRecentCall.args;

                        expect(args[0]).toBeNull();
                        expect(args[1]).toBe(c);
                    });
                });

                describe("changing the viewmodel value", function() {
                    it("should select the record when setting the value", function() {
                        viewModel.set('foo', a);
                        viewModel.notify();
                        expect(selModel.isSelected(a)).toBe(true);
                    });

                    it("should select the record when updating the value", function() {
                        viewModel.set('foo', a);
                        viewModel.notify();
                        viewModel.set('foo', b);
                        viewModel.notify();
                        expect(selModel.isSelected(a)).toBe(false);
                        expect(selModel.isSelected(b)).toBe(true);
                    });

                    it("should deselect when clearing the value", function() {
                        viewModel.set('foo', a);
                        viewModel.notify();
                        viewModel.set('foo', null);
                        viewModel.notify();
                        expect(selModel.isSelected(a)).toBe(false);
                    });
                });

                describe("reloading the store", function() {
                    beforeEach(function() {
                        selectNotify(a);
                        spy.reset();

                        store.setProxy({
                            type: 'ajax',
                            url: 'fake'
                        });
                        store.load();
                    });

                    describe("when the selected record is in the result set", function() {
                        it("should trigger the selection binding", function() {
                            completeRequest(makeDataWithId(2));
                            viewModel.notify();
                            expect(spy.callCount).toBe(1);
                            expect(spy.mostRecentCall.args[0]).toBe(store.getAt(0));
                        });
                    });

                    describe("when the selected record is not in the result set", function() {
                        it("should trigger the selection binding", function() {
                            completeRequest([]);
                            viewModel.notify();
                            expect(spy.callCount).toBe(1);
                            expect(spy.mostRecentCall.args[0]).toBeNull();
                        });
                    });
                });
            });
        });
    });

    describe("masking", function() {
        // TODO:
        describe("mask configurations", function() {

        });

        describe("mask visibility", function() {
            describe("static stores", function() {
                var mask;

                function makeLoadView(loadStore) {
                    store = makeStore([]);

                    if (loadStore) {
                        store.load();
                    }

                    createView({
                        renderTo: Ext.getBody(),
                        mask: true,
                        itemTpl: '{name}',
                        store: store
                    }, []);
                    mask = view.loadMask;
                }

                afterEach(function() {
                    mask = null;
                });

                it("should show a mask when the configured store is loading", function() {
                    makeLoadView(true);
                    expect(mask.isVisible()).toBe(true);
                    completeRequest();
                    expect(mask.isVisible()).toBe(false);
                });

                it("should show the mask when beforeload fires", function() {
                    makeLoadView();
                    store.load();
                    expect(mask.isVisible()).toBe(true);
                    completeRequest();
                });

                it("should hide the mask when a successful request returns", function() {
                    makeLoadView();
                    store.load();
                    expect(mask.isVisible()).toBe(true);
                    completeRequest();
                    expect(mask.isVisible()).toBe(false);
                });

                it("should hide the mask when an unsuccessful request returns", function() {
                    makeLoadView();
                    store.load();
                    expect(mask.isVisible()).toBe(true);
                    completeRequest(null, 500);
                    expect(mask.isVisible()).toBe(false);
                });

                it("should show a mask when using a chained store with a source that loads", function() {
                    makeLoadView();
                    var chained = new Ext.data.ChainedStore({
                        source: store
                    });

                    view.bindStore(chained);
                    store.load();
                    expect(mask.isVisible()).toBe(true);
                    completeRequest();
                    expect(mask.isVisible()).toBe(false);
                });
            });

            describe("binding store dynamically", function() {
                beforeEach(function() {
                    createView({
                        renderTo: Ext.getBody(),
                        mask: true,
                        itemTpl: '{name}',
                        store: null
                    });
                    store = makeStore([]);
                });

                it("should show a mask when a new store is bound", function() {
                    store.load();
                    view.bindStore(store);
                    var mask = view.loadMask;

                    expect(mask.isVisible()).toBe(true);
                    completeRequest();
                    expect(mask.isVisible()).toBe(false);
                });

                it("should show a mask when the store is loading when bound", function() {
                    view.bindStore(store);
                    store.load();
                    var mask = view.loadMask;

                    expect(mask.isVisible()).toBe(true);
                    completeRequest();
                    expect(mask.isVisible()).toBe(false);
                });

                it("should show a mask when a source is loading when a chained store is bound", function() {
                    var chained = new Ext.data.ChainedStore({
                        source: store
                    });

                    store.load();
                    view.bindStore(chained);
                    var mask = view.loadMask;

                    expect(mask.isVisible()).toBe(true);
                    completeRequest();
                    expect(mask.isVisible()).toBe(false);
                });
            });
        });

        describe("as a child reference", function() {
            it("should include the mask in the ref items", function() {
                createView({
                    renderTo: Ext.getBody(),
                    mask: true,
                    itemTpl: '{name}'
                });
                var mask = view.getRefItems()[0];

                expect(mask instanceof Ext.LoadMask);
            });

            it("should not return the mask if not created", function() {
                 createView({
                    mask: true,
                    itemTpl: '{name}'
                });
                expect(view.getRefItems().length).toBe(0);
            });
        });
    });

    describe('focusing', function() {
        beforeEach(function() {
            createView({
                renderTo: Ext.getBody(),
                itemTpl: '{name}'
            }, 10);
        });

        it("should restore focus when the view is refreshed", function() {
            var itemBeforeRefresh,
                itemAfterRefresh;

            navModel.setPosition(1);

            // Navigation conditions must be met.
            itemBeforeRefresh = Ext.get(view.all.item(1, true));
            expect(view.el.query('.' + navModel.focusCls).length).toBe(1);
            expect(itemBeforeRefresh.hasCls(navModel.focusCls)).toBe(true);

            store.fireEvent('refresh', store);

            // The DOM has changed, but focus conditions must be restored
            itemAfterRefresh = view.all.item(1);
            expect(itemAfterRefresh.dom !== itemBeforeRefresh.dom).toBe(true);

            // Navigation conditions must be restored after the refresh.
            expect(view.el.query('.' + navModel.focusCls).length).toBe(1);
            expect(itemAfterRefresh.hasCls(navModel.focusCls)).toBe(true);

            itemBeforeRefresh.destroy();
        });
    });

    describe("destruction", function() {
        it("should leave the layout counter intact if destroyed during a begin/endUpdate", function() {
            var count = Ext.Component.layoutSuspendCount;

            createView({
                renderTo: Ext.getBody(),
                itemTpl: '{name}'
            });
            store.beginUpdate();
            view.destroy();
            expect(Ext.Component.layoutSuspendCount).toBe(count);
        });
    });
});

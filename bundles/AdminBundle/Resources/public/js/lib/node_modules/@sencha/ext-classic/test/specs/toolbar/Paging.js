topSuite("Ext.toolbar.Paging",
    ['Ext.grid.Panel', 'Ext.Button', 'Ext.grid.feature.Grouping'],
function() {
    var keyEvent = Ext.supports.SpecialKeyDownRepeat ? 'keydown' : 'keypress',
        tb, store, store2,
        describeNotIE9_10 = Ext.isIE9 || Ext.isIE10 ? xdescribe : describe,
        synchronousLoad = true,
        proxyStoreLoad = Ext.data.ProxyStore.prototype.load,
        loadStore = function() {
            proxyStoreLoad.apply(this, arguments);

            if (synchronousLoad) {
                this.flushLoad.apply(this, arguments);
            }

            return this;
        };

    function makeToolbar(cfg, preventRender) {
        cfg = cfg || {};

        if (!preventRender) {
            cfg.renderTo = Ext.getBody();
        }

        if (cfg.store === undefined) {
            cfg.store = makeStore();
        }

        tb = new Ext.toolbar.Paging(cfg);
    }

    function makeStore(pageSize) {
        store = new Ext.data.Store({
            model: 'spec.PagingToolbarModel',
            storeId: 'pagingToolbarStore',
            pageSize: pageSize != null ? pageSize : 5,
            proxy: {
                type: 'ajax',
                url: 'fakeUrl',
                reader: {
                    type: 'json',
                    rootProperty: 'data',
                    totalProperty: 'total'
                }
            }
        });

        return store;
    }

    function makeData(total, start, limit) {
        var data = [],
            i;

        if (limit === undefined) {
            limit = start + store.pageSize;
        }

        for (i = start; i < limit; ++i) {
            data.push({
                name: 'Item ' + (i + 1)
            });
        }

        return Ext.encode({
            data: data,
            total: total
        });
    }

    function mockComplete(responseText, status) {
        Ext.Ajax.mockComplete({
            status: status || 200,
            responseText: responseText
        });
    }

    beforeEach(function() {
        // Override so that we can control asynchronous loading
        Ext.data.ProxyStore.prototype.load = loadStore;

        Ext.define('spec.PagingToolbarModel', {
            extend: 'Ext.data.Model',
            fields: ['name']
        });
        MockAjaxManager.addMethods();
    });

    afterEach(function() {
        // Undo the overrides.
        Ext.data.ProxyStore.prototype.load = proxyStoreLoad;

        MockAjaxManager.removeMethods();

        tb = Ext.destroy(tb);
        store = Ext.destroy(store);
        store2 = Ext.destroy(store2);

        Ext.undefine('spec.PagingToolbarModel');
        Ext.data.Model.schema.clear();
    });

    describe("alternate class name", function() {
        it("should have Ext.PagingToolbar as the alternate class name", function() {
            expect(Ext.toolbar.Paging.prototype.alternateClassName).toEqual("Ext.PagingToolbar");
        });

        it("should allow the use of Ext.PagingToolbar", function() {
            expect(Ext.PagingToolbar).toBeDefined();
        });
    });

    describe("auto store", function() {
        var view;

        beforeEach(function() {
            view = Ext.create({
                xtype: 'grid',
                store: makeStore(20),
                renderTo: Ext.getBody(),
                width: 500,
                height: 400,

                columns: [{
                    text: 'Name',
                    dataIndex: 'name'
                }],

                bbar: {
                    xtype: 'pagingtoolbar'
                }
            });
        });

        afterEach(function() {
            view = Ext.destroy(view);
        });

        it('should associate to owner store', function() {
            store.load();
            mockComplete(makeData(200, 0));

            var c = view.down('pagingtoolbar');

            expect(c.store).toBe(view.store);
            expect(c.store).toBe(store);

            store2 = store;

            view.setStore(makeStore(10));
            store.load();
            mockComplete(makeData(200, 0));

            expect(c.store).toBe(view.store);
            expect(c.store).toBe(store);
        });
    });

    describe("store", function() {
        it("should be able to create without a store", function() {
            expect(function() {
                makeToolbar({
                    store: null
                });
            }).not.toThrow();
        });

        it("should accept a store instance", function() {
            store = makeStore();
            makeToolbar({
                store: store
            });
            expect(tb.getStore()).toBe(store);
        });

        it("should accept a store config", function() {
            makeToolbar({
                store: {
                    model: 'spec.PagingToolbarModel'
                }
            });
            expect(tb.getStore().model).toBe(spec.PagingToolbarModel);
        });

        it("should accept a store id", function() {
            store = makeStore();
            makeToolbar({
                store: 'pagingToolbarStore'
            });
            expect(tb.getStore()).toBe(store);
        });

        it("should update the toolbar info if the store is already loaded at render time", function() {
            store = makeStore();
            store.loadPage(2);
            mockComplete(makeData(20, 5));
            makeToolbar({
                store: store
            });
            expect(tb.down('#inputItem').getValue()).toBe(2);
        });

        it("should display the correct number of total pages", function() {
            store = makeStore();
            store.loadPage(1);
            mockComplete(makeData(20, 10));
            makeToolbar({
                store: store
            });
            expect(tb.down('#afterTextItem').el.dom.innerHTML).toBe('of 4');
        });

        it("should update the toolbar info when binding a new store", function() {
            makeToolbar();
            store = makeStore();
            store.loadPage(3);
            mockComplete(makeData(20, 10));
            tb.bindStore(store);
            expect(tb.down('#inputItem').getValue()).toBe(3);
        });

        it("should display the correct info for pageSize 0", function() {
            store = makeStore(0);
            store.load();
            mockComplete(makeData(20, 10));
            makeToolbar({
                store: store
            });

            expect(tb.getPageData()).toEqual({
                total: 20,
                currentPage: 1,
                pageCount: 1,
                fromRecord: 1,
                toRecord: 20
            });
        });
    });

    describe("child items", function() {
        it("should add items after the default buttons", function() {
            makeToolbar({
                items: [{
                    xtype: 'button',
                    itemId: 'foo'
                }]
            });
            expect(tb.items.last().getItemId()).toBe('foo');
        });

        it("should add items before the default buttons with prependButtons: true", function() {
            makeToolbar({
                prependButtons: true,
                items: [{
                    xtype: 'button',
                    itemId: 'foo'
                }]
            });
            expect(tb.items.first().getItemId()).toBe('foo');
        });

        it("should add the info display if displayInfo is true", function() {
            makeToolbar({
                displayInfo: true
            });
            var items = tb.items;

            expect(items.getAt(items.getCount() - 2).isXType('tbfill')).toBe(true);
            expect(items.last().getItemId()).toBe('displayItem');
        });
    });

    describe("disabling/enabling items", function() {
        function expectEnabled(id) {
            expectState(id, false);
        }

        function expectDisabled(id) {
            expectState(id, true);
        }

        function expectState(id, state) {
            expect(tb.child('#' + id).disabled).toBe(state);
        }

        it("should disable everything except refresh when the store hasn't been loaded", function() {
            makeToolbar();
            expectDisabled('first');
            expectDisabled('prev');
            expectDisabled('inputItem');
            expectDisabled('next');
            expectDisabled('last');
            expectEnabled('refresh');
        });

        describe("store loads before render", function() {
            it("should set the state if the store is loaded", function() {
                makeToolbar({}, true);
                store.load();
                mockComplete(makeData(20, 0));
                tb.render(Ext.getBody());
                expectDisabled('first');
                expectDisabled('prev');
                expectEnabled('inputItem');
                expectEnabled('next');
                expectEnabled('last');
                expectEnabled('refresh');
            });
        });

        describe("store loads after render", function() {
            it("should set the state if the store is loaded", function() {
                makeToolbar();
                store.load();
                mockComplete(makeData(20, 0));
                expectDisabled('first');
                expectDisabled('prev');
                expectEnabled('inputItem');
                expectEnabled('next');
                expectEnabled('last');
                expectEnabled('refresh');
            });
        });

        describe("based on current page", function() {
            it("should disable first/prev buttons on the first page", function() {
                makeToolbar();
                store.loadPage(1);
                mockComplete(makeData(20, 0));
                expectDisabled('first');
                expectDisabled('prev');
                expectEnabled('inputItem');
                expectEnabled('next');
                expectEnabled('last');
                expectEnabled('refresh');
            });

            it("should disable next/last buttons on the last page", function() {
                makeToolbar();
                store.loadPage(4);
                mockComplete(makeData(20, 0));
                expectEnabled('first');
                expectEnabled('prev');
                expectEnabled('inputItem');
                expectDisabled('next');
                expectDisabled('last');
                expectEnabled('refresh');
            });

            it("should enable all buttons when the page is not first or last", function() {
                makeToolbar();
                store.loadPage(2);
                mockComplete(makeData(20, 0));
                expectEnabled('first');
                expectEnabled('prev');
                expectEnabled('inputItem');
                expectEnabled('next');
                expectEnabled('last');
                expectEnabled('refresh');
            });
        });

        describe("refresh icon", function() {
            it("should disable the refresh icon if the store is loading during construction", function() {
                makeStore();
                store.load();
                makeToolbar({
                    store: store
                });
                expectDisabled('refresh');
            });

            it("should disable the refresh icon during a load", function() {
                makeToolbar();
                store.load();
                expectDisabled('refresh');
            });
        });

        describe("empty store", function() {
            it("should disable the inputItem & buttons", function() {
                makeToolbar();
                store.load();
                mockComplete(makeData(0, 0, 0));
                expectDisabled('first');
                expectDisabled('prev');
                expectDisabled('inputItem');
                expectDisabled('next');
                expectDisabled('last');
                expectEnabled('refresh');
            });
        });
    });

    describe("move/refresh methods", function() {
        var spy;

        beforeEach(function() {
            makeToolbar();
            store.load();
            mockComplete(makeData(20, 0));
            spy = jasmine.createSpy();
        });

        afterEach(function() {
            spy = null;
        });

        describe("moveFirst", function() {
            it("should fire the beforechange event with the toolbar & the new page", function() {
                tb.on('beforechange', spy);
                tb.moveFirst();
                expect(spy.mostRecentCall.args[0]).toBe(tb);
                expect(spy.mostRecentCall.args[1]).toBe(1);
            });

            it("should return false if the beforechange event is vetoed", function() {
                tb.on('beforechange', spy.andReturn(false));
                expect(tb.moveFirst()).toBe(false);
            });

            it("should return true & load the store with the first page", function() {
                spyOn(store, 'loadPage');
                expect(tb.moveFirst()).toBe(true);
                expect(store.loadPage.mostRecentCall.args[0]).toBe(1);
            });
        });

        describe("movePrevious", function() {
            it("should fire the beforechange event with the toolbar & the new page", function() {
                tb.on('beforechange', spy);
                store.loadPage(3);
                mockComplete(makeData(20, 10));
                tb.movePrevious();
                expect(spy.mostRecentCall.args[0]).toBe(tb);
                expect(spy.mostRecentCall.args[1]).toBe(2);
            });

            it("should return false if moving to the previous page is not valid, the change event should not fire", function() {
                tb.on('beforechange', spy);
                expect(tb.movePrevious()).toBe(false);
                expect(spy).not.toHaveBeenCalled();
            });

            it("should return false if the beforechange event is vetoed", function() {
                tb.on('beforechange', spy.andReturn(false));
                expect(tb.movePrevious()).toBe(false);
            });

            it("should return true & load the store with the previous page", function() {
                spyOn(store, 'previousPage');
                store.loadPage(3);
                mockComplete(makeData(20, 10));
                expect(tb.movePrevious()).toBe(true);
                expect(store.previousPage).toHaveBeenCalled();
            });
        });

        describe("moveNext", function() {
            it("should fire the beforechange event with the toolbar & the new page", function() {
                tb.on('beforechange', spy);
                tb.moveNext();
                expect(spy.mostRecentCall.args[0]).toBe(tb);
                expect(spy.mostRecentCall.args[1]).toBe(2);
            });

            it("should return false if moving to the next page is not valid, the change event should not fire", function() {
                tb.on('beforechange', spy);
                store.loadPage(4);
                mockComplete(makeData(20, 15));
                expect(tb.moveNext()).toBe(false);
                expect(spy).not.toHaveBeenCalled();
            });

            it("should return false if the beforechange event is vetoed", function() {
                tb.on('beforechange', spy.andReturn(false));
                expect(tb.moveNext()).toBe(false);
            });

            it("should return true & load the store with the next page", function() {
                spyOn(store, 'nextPage');
                expect(tb.moveNext()).toBe(true);
                expect(store.nextPage).toHaveBeenCalled();
            });
        });

        describe("moveLast", function() {
            it("should fire the beforechange event with the toolbar & the new page", function() {
                tb.on('beforechange', spy);
                tb.moveLast();
                expect(spy.mostRecentCall.args[0]).toBe(tb);
                expect(spy.mostRecentCall.args[1]).toBe(4);
            });

            it("should return false if the beforechange event is vetoed", function() {
                tb.on('beforechange', spy.andReturn(false));
                expect(tb.moveLast()).toBe(false);
            });

            it("should return true & load the store with the last page", function() {
                spyOn(store, 'loadPage');
                expect(tb.moveLast()).toBe(true);
                expect(store.loadPage.mostRecentCall.args[0]).toBe(4);
            });
        });

        describe("doRefresh", function() {
            it("should fire the beforechange event with the toolbar & the current page", function() {
                tb.on('beforechange', spy);
                tb.doRefresh();
                expect(spy.mostRecentCall.args[0]).toBe(tb);
                expect(spy.mostRecentCall.args[1]).toBe(1);
            });

            it("should return false if the beforechange event is vetoed", function() {
                tb.on('beforechange', spy.andReturn(false));
                expect(tb.doRefresh()).toBe(false);
            });

            it("should return true & load the store with the last page", function() {
                spyOn(store, 'loadPage');
                expect(tb.doRefresh()).toBe(true);
                expect(store.loadPage.mostRecentCall.args[0]).toBe(1);
            });
        });
    });

    describe("change event", function() {
        var spy;

        beforeEach(function() {
            spy = jasmine.createSpy();
        });

        afterEach(function() {
            spy = null;
        });

        it("should fire the change event on load with the toolbar & pageData", function() {
            makeToolbar();
            tb.on('change', spy);
            store.loadPage(3);
            mockComplete(makeData(20, 10));
            expect(spy.mostRecentCall.args[0]).toBe(tb);
            expect(spy.mostRecentCall.args[1]).toEqual({
                total: 20,
                currentPage: 3,
                pageCount: 4,
                fromRecord: 11,
                toRecord: 15
            });
        });

        it("should not fire if configured with an empty store", function() {
            makeToolbar(undefined, true);
            tb.on('change', spy);
            tb.render(Ext.getBody());
            expect(spy).not.toHaveBeenCalled();
        });

        it("should provide empty pageData when a store loads empty", function() {
            makeToolbar();
            tb.on('change', spy);
            store.load();
            mockComplete('[]');
            expect(spy.mostRecentCall.args[0]).toBe(tb);
            expect(spy.mostRecentCall.args[1]).toEqual({
                total: 0,
                currentPage: 0,
                pageCount: 0,
                fromRecord: 0,
                toRecord: 0
            });
        });
    });

    // Opera has a problem handling key specs
    (Ext.isOpera ? xdescribe : describe)("inputItem", function() {
        var TAB = 9,
            ENTER = 13,
            ESC = 27,
            PAGE_UP = 33,
            PAGE_DOWN = 34,
            END = 35,
            HOME = 36,
            LEFT = 37,
            UP = 38,
            RIGHT = 39,
            DOWN = 40;

        function triggerKeyEvent(key) {
            var dom = tb.down('#inputItem').inputEl.dom;

            dom.focus();
            jasmine.fireKeyEvent(dom, keyEvent, key);
        }

        it("should set the value to the new page on load", function() {
            makeToolbar();
            store.loadPage(3);
            mockComplete(makeData(20, 10));
            expect(tb.getInputItem().getValue()).toBe(3);
        });

        it("should set the value to the current page on blur", function() {
            makeToolbar();
            var input = tb.getInputItem();

            // Will auto disable if not attached to a Store. Programatically enable so that it will focus and blur.
            input.enable();
            input.focus();

            waitsFor(function() {
                return input.hasFocus;
            });

            runs(function() {
                input.setValue(4);
                input.blur();
            });

            // After the blur events gets done, it should have reverted to the current page
            waitsFor(function() {
                return input.getValue() === 1;
            });
        });

        describe('reconfiguring a grid using buffered rendering and grouping', function() {
            // This test demonstrates that the paging toolbar will update its input item when the grid
            // is configured in a very specific way.
            //
            // This bug only is reproducible when reconfigure is called on a grid with the buffered
            // renderer plugin and grouping feature. The bug was that the buffered renderer plugin
            // would bind the data store to the plugin rather than the group store (created when
            // there's a grouping feature). See Ext.grid.plugin.BufferedRenderer:bindStore().
            //
            // See EXTJSIV-11860 and EXTJSIV-11892.
            var grid;

            afterEach(function() {
                grid.destroy();
                grid = null;
            });

            it('should update the input item when paging', function() {
                grid = Ext.create('Ext.grid.Panel', {
                    width: 100,
                    height: 100,
                    store: makeStore(),
                    features: [{ ftype: 'grouping' }],
                    columns: [{
                        text: 'Name',
                        dataIndex: 'name',
                        width: 100
                    }],
                    bbar: makeToolbar(undefined, true),
                    renderTo: Ext.getBody()
                });

                grid.reconfigure(store);
                store.loadPage(3);
                mockComplete(makeData(20, 10));

                expect(tb.getInputItem().getValue()).toBe(3);
            });
        });

        describe("keypress", function() {
            it("should set the value to the first page on home", function() {
                makeToolbar();
                store.loadPage(3);
                mockComplete(makeData(100, 10));
                triggerKeyEvent(HOME);
                expect(tb.getInputItem().getValue()).toBe(1);
            });

            it("should set the value to the last page on end", function() {
                makeToolbar();
                store.loadPage(1);
                mockComplete(makeData(20, 0));
                triggerKeyEvent(END);
                expect(tb.getInputItem().getValue()).toBe(4);
            });

            describe("down", function() {
                it("should set the value to the previous page on pagedown", function() {
                    makeToolbar();
                    store.loadPage(3);
                    mockComplete(makeData(20, 10));
                    triggerKeyEvent(PAGE_DOWN);
                    expect(tb.getInputItem().getValue()).toBe(2);
                });

                it("should set the value to the previous page on down", function() {
                    makeToolbar();
                    store.loadPage(3);
                    mockComplete(makeData(20, 10));
                    triggerKeyEvent(DOWN);
                    expect(tb.getInputItem().getValue()).toBe(2);
                });

                describe("shift", function() {
                    it("should not change the page if it will go over the limit with pagedown", function() {
                        makeToolbar();
                        store.loadPage(3);
                        mockComplete(makeData(20, 10));
                        var spy = spyOn(tb, 'processKeyEvent').andCallFake(function(field, e) {
                            e.shiftKey = true;
                            Ext.toolbar.Paging.prototype.processKeyEvent.call(tb, field, e);
                        });

                        triggerKeyEvent(PAGE_DOWN);
                        expect(tb.getInputItem().getValue()).toBe(3);
                    });

                    it("should not change the page if it will go over the limit with down", function() {
                        makeToolbar();
                        store.loadPage(3);
                        mockComplete(makeData(20, 10));
                        spyOn(tb, 'processKeyEvent').andCallFake(function(field, e) {
                            e.shiftKey = true;
                            Ext.toolbar.Paging.prototype.processKeyEvent.call(tb, field, e);
                        });
                        triggerKeyEvent(DOWN);
                        expect(tb.getInputItem().getValue()).toBe(3);
                    });

                    it("should decrement by 10 when using shift + pagedown", function() {
                        makeToolbar();
                        store.loadPage(15);
                        mockComplete(makeData(100, 75));
                        spyOn(tb, 'processKeyEvent').andCallFake(function(field, e) {
                            e.shiftKey = true;
                            Ext.toolbar.Paging.prototype.processKeyEvent.call(tb, field, e);
                        });
                        triggerKeyEvent(PAGE_DOWN);
                        expect(tb.getInputItem().getValue()).toBe(5);
                    });

                    it("should decrement by 10 when using shift + down", function() {
                        makeToolbar();
                        store.loadPage(15);
                        mockComplete(makeData(100, 75));
                        spyOn(tb, 'processKeyEvent').andCallFake(function(field, e) {
                            e.shiftKey = true;
                            Ext.toolbar.Paging.prototype.processKeyEvent.call(tb, field, e);
                        });
                        triggerKeyEvent(DOWN);
                        expect(tb.getInputItem().getValue()).toBe(5);
                    });
                });
            });

            describe("up", function() {
                it("should set the value to the next page on pageup", function() {
                    makeToolbar();
                    store.loadPage(3);
                    mockComplete(makeData(20, 10));
                    triggerKeyEvent(PAGE_UP);
                    expect(tb.getInputItem().getValue()).toBe(4);
                });

                it("should set the value to the next page on up", function() {
                    makeToolbar();
                    store.loadPage(3);
                    mockComplete(makeData(20, 10));
                    triggerKeyEvent(UP);
                    expect(tb.getInputItem().getValue()).toBe(4);
                });

                describe("shift", function() {
                    it("should not change the page if it will go over the limit with pageup", function() {
                        makeToolbar();
                        store.loadPage(1);
                        mockComplete(makeData(20, 0));
                        var spy = spyOn(tb, 'processKeyEvent').andCallFake(function(field, e) {
                            e.shiftKey = true;
                            Ext.toolbar.Paging.prototype.processKeyEvent.call(tb, field, e);
                        });

                        triggerKeyEvent(PAGE_UP);
                        expect(tb.getInputItem().getValue()).toBe(1);
                    });

                    it("should not change the page if it will go over the limit with up", function() {
                        makeToolbar();
                        store.loadPage(1);
                        mockComplete(makeData(20, 0));
                        spyOn(tb, 'processKeyEvent').andCallFake(function(field, e) {
                            e.shiftKey = true;
                            Ext.toolbar.Paging.prototype.processKeyEvent.call(tb, field, e);
                        });
                        triggerKeyEvent(UP);
                        expect(tb.getInputItem().getValue()).toBe(1);
                    });

                    it("should increment by 10 when using shift + pageup", function() {
                        makeToolbar();
                        store.loadPage(1);
                        mockComplete(makeData(100, 0));
                        spyOn(tb, 'processKeyEvent').andCallFake(function(field, e) {
                            e.shiftKey = true;
                            Ext.toolbar.Paging.prototype.processKeyEvent.call(tb, field, e);
                        });
                        triggerKeyEvent(PAGE_UP);
                        expect(tb.getInputItem().getValue()).toBe(11);
                    });

                    it("should increment by 10 when using shift + up", function() {
                        makeToolbar();
                        store.loadPage(1);
                        mockComplete(makeData(100, 0));
                        spyOn(tb, 'processKeyEvent').andCallFake(function(field, e) {
                            e.shiftKey = true;
                            Ext.toolbar.Paging.prototype.processKeyEvent.call(tb, field, e);
                        });
                        triggerKeyEvent(UP);
                        expect(tb.getInputItem().getValue()).toBe(11);
                    });
                });
            });

            // These tests fails unreliably on IE9 and 10 on a VM
            describeNotIE9_10("enter", function() {
                it("should load the page in the field", function() {
                    makeToolbar();
                    store.loadPage(1);
                    mockComplete(makeData(20, 0));
                    tb.getInputItem().setRawValue(3);
                    spyOn(store, 'loadPage');
                    triggerKeyEvent(ENTER);
                    expect(store.loadPage.mostRecentCall.args[0]).toBe(3);
                });

                it("should do nothing if the value isn't valid", function() {
                    makeToolbar();
                    store.loadPage(1);
                    mockComplete(makeData(20, 0));
                    tb.getInputItem().setRawValue('foo');
                    spyOn(store, 'loadPage');
                    triggerKeyEvent(ENTER);
                    expect(store.loadPage).not.toHaveBeenCalled();
                });

                it("should do nothing if the page hasn't changed", function() {
                    makeToolbar();
                    store.loadPage(1);
                    mockComplete(makeData(20, 0));
                    tb.getInputItem().setRawValue(1);
                    spyOn(store, 'loadPage');
                    triggerKeyEvent(ENTER);
                    expect(store.loadPage).not.toHaveBeenCalled();
                });

                // This test fails unreliably on IE9 and 10 on a VM
                it("should pull the value up to the minimum", function() {
                    makeToolbar();
                    store.loadPage(2);
                    mockComplete(makeData(20, 5));
                    tb.getInputItem().setRawValue(-2);
                    spyOn(store, 'loadPage');
                    triggerKeyEvent(ENTER);
                    expect(store.loadPage.mostRecentCall.args[0]).toBe(1);
                });

                it("should limit the value up to the maximum", function() {
                    makeToolbar();
                    store.loadPage(1);
                    mockComplete(makeData(20, store.pageSize));
                    tb.getInputItem().setRawValue(50);
                    spyOn(store, 'loadPage');
                    triggerKeyEvent(ENTER);
                    expect(store.loadPage.mostRecentCall.args[0]).toBe(4);
                });

                it("should fire the beforechange event with the toolbar & the new page", function() {
                    makeToolbar();
                    store.loadPage(1);
                    mockComplete(makeData(20, 0));
                    tb.getInputItem().setRawValue(3);

                    var spy = jasmine.createSpy();

                    tb.on('beforechange', spy);
                    triggerKeyEvent(ENTER);
                    expect(spy.mostRecentCall.args[0]).toBe(tb);
                    expect(spy.mostRecentCall.args[1]).toBe(3);
                });

                it("should not call load if vetoing the event", function() {
                    makeToolbar();
                    store.loadPage(1);
                    mockComplete(makeData(20, 0));
                    tb.getInputItem().setRawValue(3);

                    spyOn(store, 'loadPage');
                    tb.on('beforechange', function() {
                        return false;
                    });
                    triggerKeyEvent(ENTER);
                    expect(store.loadPage).not.toHaveBeenCalled();
                });
            });
        });
    });

    describe("after invalid load", function() {
        it("should load the largest available page when we've gone outside the dataset", function() {
            var spy = jasmine.createSpy();

            makeToolbar();
            store.loadPage(5);
            mockComplete(makeData(25, 20));
            tb.on('change', spy);
            tb.doRefresh();
            spyOn(store, 'loadPage');
            mockComplete(makeData(10, 5));
            expect(spy).not.toHaveBeenCalled();
            expect(store.loadPage.mostRecentCall.args[0]).toBe(2);
        });
    });
});

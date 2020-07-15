topSuite("Ext.list.Tree", ['Ext.data.TreeStore'], function() {
    var root, list, store, sampleData;

    var Model = Ext.define(null, {
        extend: 'Ext.data.TreeModel',
        fields: ['customField']
    });

    function makeList(cfg, noStore) {
        if (!store && !noStore) {
            store = new Ext.data.TreeStore({
                model: Model,
                root: {
                    expanded: true,
                    children: sampleData
                }
            });
        }

        list = new Ext.list.Tree(Ext.apply({
            store: store,
            animation: false
        }, cfg));
        list.render(Ext.getBody());

        if (list.getStore()) {
            root = list.getStore().getRoot();
        }
    }

    beforeEach(function() {
        sampleData = [{
            id: 'i1',
            text: 'Item 1',
            children: [{
                id: 'i11',
                text: 'Item 1.1',
                leaf: true
            }, {
                id: 'i12',
                text: 'Item 1.2',
                leaf: true
            }]
        }, {
            id: 'i2',
            text: 'Item 2',
            expandable: false
        }, {
            id: 'i3',
            text: 'Item 3'
        }, {
            id: 'i4',
            text: 'Item 4',
            expanded: true,
            children: [{
                id: 'i41',
                text: 'Item 4.1',
                leaf: true
            }, {
                id: 'i42',
                text: 'Item 4.2'
            }, {
                id: 'i43',
                text: 'Item 4.3',
                leaf: true
            }]
        }, {
            id: 'i5',
            text: 'Item 5',
            leaf: true
        }];
    });

    afterEach(function() {
        root = sampleData = store = list = Ext.destroy(list, store);
    });

    function byId(id) {
        return store.getNodeById(id);
    }

    function getItem(id) {
        return list.getItem(byId(id));
    }

    describe("store", function() {
        function getListeners() {
            var listeners = {},
                hasListeners = store.hasListeners,
                key;

            for (key in hasListeners) {
                if (key !== '_decr_' && key !== '_incr_') {
                    listeners[key] = hasListeners[key];
                }
            }

            return listeners;
        }

        describe("configuration", function() {
            it("should accept a store id", function() {
                store = new Ext.data.TreeStore({
                    id: 'storeWithId',
                    model: Model
                });

                makeList({
                    store: 'storeWithId'
                });
                expect(list.getStore()).toBe(store);
            });

            it("should accept a store config and default the type to Ext.data.TreeStore", function() {
                makeList({
                    store: {
                        storeId: 'storeWithId',
                        model: Model
                    }
                }, true);

                // Make sure the store gets destroyed
                store = list.getStore();

                expect(store.$className).toBe('Ext.data.TreeStore');
                expect(store.getStoreId()).toBe('storeWithId');
            });

            it("should accept a store config with a type", function() {
                Ext.define('spec.CustomTreeStore', {
                    extend: 'Ext.data.TreeStore',
                    alias: 'store.customtree'
                });

                makeList({
                    store: {
                        type: 'customtree',
                        storeId: 'storeWithId',
                        model: Model
                    }
                }, true);

                // Ditto
                store = list.getStore();

                expect(store.$className).toBe('spec.CustomTreeStore');
                expect(store.getStoreId()).toBe('storeWithId');

                Ext.undefine('spec.CustomTreeStore');
            });

            it("should accept a store instance", function() {
                store = new Ext.data.TreeStore({
                    model: Model
                });

                makeList({
                    store: store
                });
                expect(list.getStore()).toBe(store);
            });
        });

        describe("setting after creation", function() {
            describe("with no existing store", function() {
                beforeEach(function() {
                    makeList(null, true);
                });

                it("should accept a store id", function() {
                    store = new Ext.data.TreeStore({
                        id: 'storeWithId',
                        model: Model
                    });
                    list.setStore('storeWithId');
                    expect(list.getStore()).toBe(store);
                });

                it("should accept a store config and default the type to Ext.data.TreeStore", function() {
                    list.setStore({
                        storeId: 'storeWithId',
                        model: Model
                    });

                    store = list.getStore();

                    expect(store.$className).toBe('Ext.data.TreeStore');
                    expect(store.getStoreId()).toBe('storeWithId');
                });

                it("should accept a store config with a type", function() {
                    Ext.define('spec.CustomTreeStore', {
                        extend: 'Ext.data.TreeStore',
                        alias: 'store.customtree'
                    });

                    list.setStore({
                        type: 'customtree',
                        storeId: 'storeWithId',
                        model: Model
                    });

                    store = list.getStore();

                    expect(store.$className).toBe('spec.CustomTreeStore');
                    expect(store.getStoreId()).toBe('storeWithId');

                    Ext.undefine('spec.CustomTreeStore');
                });

                it("should accept a store instance", function() {
                    store = new Ext.data.TreeStore({
                        model: Model
                    });
                    list.setStore(store);
                    expect(list.getStore()).toBe(store);
                });
            });

            describe("with an existing store", function() {
                var listeners;

                beforeEach(function() {
                    store = new Ext.data.TreeStore({
                        model: Model
                    });
                    listeners = getListeners();
                    makeList();
                });

                afterEach(function() {
                    listeners = null;
                });

                it("should accept a store id and unbind old store listeners", function() {
                    var newStore = new Ext.data.TreeStore({
                        id: 'storeWithId',
                        model: Model
                    });

                    list.setStore('storeWithId');
                    expect(list.getStore()).toBe(newStore);
                    expect(getListeners()).toEqual(listeners);
                    newStore.destroy();
                });

                it("should accept a store config and default the type to Ext.data.TreeStore and unbind old store listeners", function() {
                    list.setStore({
                        storeId: 'storeWithId',
                        model: Model
                    });
                    expect(list.getStore().$className).toBe('Ext.data.TreeStore');
                    expect(list.getStore().getStoreId()).toBe('storeWithId');
                    expect(getListeners()).toEqual(listeners);

                    // Stores with ID are not destroyed automatically
                    list.getStore().destroy();
                });

                it("should accept a store config with a type and unbind old store listeners", function() {
                    Ext.define('spec.CustomTreeStore', {
                        extend: 'Ext.data.TreeStore',
                        alias: 'store.customtree'
                    });

                    list.setStore({
                        type: 'customtree',
                        storeId: 'storeWithId',
                        model: Model
                    });
                    expect(list.getStore().$className).toBe('spec.CustomTreeStore');
                    expect(list.getStore().getStoreId()).toBe('storeWithId');
                    expect(getListeners()).toEqual(listeners);

                    list.getStore().destroy();
                    Ext.undefine('spec.CustomTreeStore');
                });

                it("should accept a store instance and unbind old store listeners", function() {
                    var newStore = new Ext.data.TreeStore({
                        model: Model
                    });

                    list.setStore(newStore);
                    expect(list.getStore()).toBe(newStore);
                    expect(getListeners()).toEqual(listeners);
                    newStore.destroy();
                });

                it("should not destroy the old store with autoDestroy: false", function() {
                    list.setStore(null);
                    expect(store.destroyed).toBe(false);
                });

                it("should destroy the old store with autoDestroy: true", function() {
                    store.setAutoDestroy(true);
                    list.setStore(null);
                    expect(store.destroyed).toBe(true);
                });
            });
        });

        describe("destruction", function() {
            beforeEach(function() {
                store = new Ext.data.TreeStore({
                    model: Model
                });
            });

            it("should set the store to null", function() {
                makeList();
                list.destroy();
                expect(list._store).toBeNull();
            });

            it("should unbind any listeners", function() {
                var listeners = getListeners();

                makeList();
                list.destroy();
                expect(getListeners()).toEqual(listeners);
            });

            it("should not destroy the store if autoDestroy: false", function() {
                makeList();
                list.destroy();
                expect(store.destroyed).toBe(false);
            });

            it("should destroy the store if autoDestroy: true", function() {
                store.setAutoDestroy(true);
                makeList();
                list.destroy();
                expect(store.destroyed).toBe(true);
            });
        });
    });

    // The purpose for these tests is to test the API between the list and the item.
    // Because the item class is expected to be subclassed, there's not much point testing
    // the UI portion default class here. This is why these tests seem a little abstract.
    describe("items", function() {
        var insertSpy, removeSpy, expandSpy, collapseSpy;

        function makeCustomList(cfg, noStore) {
            makeList(Ext.merge({
                defaults: {
                    xtype: 'spec_treelist_customitem'
                }
            }, cfg), noStore);

            list.on({
                iteminsert: insertSpy,
                itemremove: removeSpy,
                itemexpand: expandSpy,
                itemcollapse: collapseSpy
            });
        }

        beforeAll(function() {
            // We create this first to prevent the inconsistency with the way configs behave
            // when using cached: true. After the first instance, the behaviour will remain the same
            Ext.define('spec.treelist.CustomItem', {
                extend: 'Ext.list.AbstractTreeItem',

                xtype: 'spec_treelist_customitem',

                config: {
                    testConfig: null,
                    floated: false
                },

                constructor: function(config) {
                    this.$noClearOnDestroy = (this.$noClearOnDestroy || {});
                    this.$noClearOnDestroy.logs = true;

                    this.logs = {
                        expandable: [],
                        expanded: [],
                        iconCls: [],
                        leaf: [],
                        loading: [],
                        text: [],

                        onNodeCollapse: [],
                        onNodeExpand: [],
                        onNodeInsert: [],
                        onNodeRemove: [],
                        onNodeUpdate: [],

                        insertItem: [],
                        removeItem: []
                    };
                    this.callParent([config]);
                },

                doDestroy: function() {
                    if (this.toolElement) {
                        this.toolElement.destroy();
                    }

                    this.callParent();
                },

                getToolElement: function() {
                    if (!this.toolElement) {
                        this.toolElement = this.element.createChild();
                    }

                    return this.toolElement;
                },

                insertItem: function(item, refItem) {
                    this.logs.insertItem.push([item, refItem]);
                },

                removeItem: function(item) {
                    this.logs.removeItem.push(item);
                },

                nodeCollapse: function(node) {
                    this.logs.onNodeCollapse.push(node);
                    this.callParent(arguments);
                },

                nodeExpand: function(node) {
                    this.logs.onNodeExpand.push(node);
                    this.callParent(arguments);
                },

                nodeInsert: function(node, refNode) {
                    this.logs.onNodeInsert.push([node, refNode]);
                    this.callParent(arguments);
                },

                nodeRemove: function(node) {
                    this.logs.onNodeRemove.push(node);
                    this.callParent(arguments);
                },

                nodeUpdate: function(node, modifiedFieldNames) {
                    this.logs.onNodeUpdate.push([node, modifiedFieldNames]);
                    this.callParent(arguments);
                },

                updateExpandable: function(expandable) {
                    this.logs.expandable.push(expandable);
                },

                updateExpanded: function(expanded) {
                    this.logs.expanded.push(expanded);
                },

                updateIconCls: function(iconCls) {
                    this.logs.iconCls.push(iconCls);
                },

                updateLeaf: function(leaf) {
                    this.logs.leaf.push(leaf);
                },

                updateLoading: function(loading) {
                    this.logs.loading.push(loading);
                },

                updateText: function(text) {
                    this.logs.text.push(text);
                }
            });

            var temp = new spec.treelist.CustomItem();

            temp.destroy();
        });

        beforeEach(function() {
            insertSpy = jasmine.createSpy();
            removeSpy = jasmine.createSpy();
            expandSpy = jasmine.createSpy();
            collapseSpy = jasmine.createSpy();
        });

        afterEach(function() {
            insertSpy = removeSpy = expandSpy = collapseSpy = null;
        });

        describe("configuration of items", function() {
            it("should use the default item type", function() {
                makeList();

                list.getStore().each(function(node) {
                    expect(list.getItem(node).xtype).toBe(list.getDefaults().xtype);
                });
            });

            it("should use a specified type", function() {
                makeList({
                    defaults: {
                        xtype: 'spec_treelist_customitem'
                    }
                });

                list.getStore().each(function(node) {
                    expect(list.getItem(node).xtype).toBe('spec_treelist_customitem');
                });
            });
        });

        describe("at creation", function() {
            describe("top level nodes", function() {
                describe("expanded: false, with children", function() {
                    it("should set expanded", function() {
                        makeCustomList();
                        var item = getItem('i1');

                        expect(item.logs.expanded).toEqual([]);
                        expect(item.getExpanded()).toBe(false);
                    });

                    it("should set expandable", function() {
                        makeCustomList();
                        var item = getItem('i1');

                        expect(item.logs.expandable).toEqual([true]);
                        expect(item.getExpandable()).toBe(true);
                    });

                    it("should set leaf", function() {
                        makeCustomList();
                        var item = getItem('i1');

                        expect(item.logs.leaf).toEqual([false]);
                        expect(item.getLeaf()).toBe(false);
                    });

                    it("should set the icon if iconClsProperty is specified", function() {
                        sampleData[0].iconCls = 'iconA';
                        makeCustomList();
                        var item = getItem('i1');

                        expect(item.logs.iconCls).toEqual(['iconA']);
                        expect(item.getIconCls()).toBe('iconA');
                    });

                    it("should not set the icon if an iconClsProperty is not specified", function() {
                        sampleData[0].iconCls = 'iconA';
                        makeCustomList({
                            defaults: {
                                iconClsProperty: ''
                            }
                        });
                        var item = getItem('i1');

                        expect(item.logs.iconCls).toEqual([]);
                        expect(item.getIconCls()).toBe('');
                    });

                    it("should set the text if a textProperty is specified", function() {
                        makeCustomList();
                        var item = getItem('i1');

                        expect(item.logs.text).toEqual(['Item 1']);
                        expect(item.getText()).toBe('Item 1');
                    });

                    it("should not set the text if an textProperty is not specified", function() {
                        makeCustomList({
                            defaults: {
                                textProperty: ''
                            }
                        });
                        var item = getItem('i1');

                        expect(item.logs.text).toEqual([]);
                        expect(item.getText()).toBe('');
                    });

                    it("should insert the child nodes", function() {
                        makeCustomList();
                        var item = getItem('i1');

                        expect(item.logs.insertItem).toEqual([
                            [getItem('i11'), null],
                            [getItem('i12'), null]
                        ]);
                    });

                    it("should not call any template methods", function() {
                        makeCustomList();
                        var item = getItem('i1');

                        expect(item.logs.onNodeCollapse).toEqual([]);
                        expect(item.logs.onNodeExpand).toEqual([]);
                        expect(item.logs.onNodeInsert).toEqual([]);
                        expect(item.logs.onNodeRemove).toEqual([]);
                        expect(item.logs.onNodeUpdate).toEqual([]);
                    });

                    it("should not fire events", function() {
                        expect(insertSpy).not.toHaveBeenCalled();
                        expect(removeSpy).not.toHaveBeenCalled();
                        expect(expandSpy).not.toHaveBeenCalled();
                        expect(collapseSpy).not.toHaveBeenCalled();
                    });

                    it("should have the node, list and parent set", function() {
                        makeCustomList();
                        var item = getItem('i1');

                        expect(item.getNode()).toBe(byId('i1'));
                        expect(item.getParentItem()).toBeNull();
                        expect(item.getOwner()).toBe(list);
                    });

                    it("should have the itemConfig set", function() {
                        makeCustomList({
                            defaults: {
                                testConfig: 12
                            }
                        });
                        var item = getItem('i1');

                        expect(item.getTestConfig()).toBe(12);
                    });
                });

                describe("expandable: false, no children", function() {
                    it("should set expanded", function() {
                        makeCustomList();
                        var item = getItem('i2');

                        expect(item.logs.expanded).toEqual([]);
                        expect(item.getExpanded()).toBe(false);
                    });

                    it("should set expandable", function() {
                        makeCustomList();
                        var item = getItem('i2');

                        expect(item.logs.expandable).toEqual([]);
                        expect(item.getExpandable()).toBe(false);
                    });

                    it("should set leaf", function() {
                        makeCustomList();
                        var item = getItem('i2');

                        expect(item.logs.leaf).toEqual([false]);
                        expect(item.getLeaf()).toBe(false);
                    });

                    it("should set the icon if iconClsProperty is specified", function() {
                        sampleData[1].iconCls = 'iconA';
                        makeCustomList();
                        var item = getItem('i2');

                        expect(item.logs.iconCls).toEqual(['iconA']);
                        expect(item.getIconCls()).toBe('iconA');
                    });

                    it("should not set the icon if an iconClsProperty is not specified", function() {
                        sampleData[1].iconCls = 'iconA';
                        makeCustomList({
                            defaults: {
                                iconClsProperty: ''
                            }
                        });
                        var item = getItem('i2');

                        expect(item.logs.iconCls).toEqual([]);
                        expect(item.getIconCls()).toBe('');
                    });

                    it("should set the text if a textProperty is specified", function() {
                        makeCustomList();
                        var item = getItem('i2');

                        expect(item.logs.text).toEqual(['Item 2']);
                        expect(item.getText()).toBe('Item 2');
                    });

                    it("should not set the text if an textProperty is not specified", function() {
                        makeCustomList({
                            defaults: {
                                textProperty: ''
                            }
                        });
                        var item = getItem('i2');

                        expect(item.logs.text).toEqual([]);
                        expect(item.getText()).toBe('');
                    });

                    it("should not insert child nodes", function() {
                        makeCustomList();
                        var item = getItem('i2');

                        expect(item.logs.insertItem).toEqual([]);
                    });

                    it("should not call any template methods", function() {
                        makeCustomList();
                        var item = getItem('i2');

                        expect(item.logs.onNodeCollapse).toEqual([]);
                        expect(item.logs.onNodeExpand).toEqual([]);
                        expect(item.logs.onNodeInsert).toEqual([]);
                        expect(item.logs.onNodeRemove).toEqual([]);
                        expect(item.logs.onNodeUpdate).toEqual([]);
                    });

                    it("should not fire events", function() {
                        expect(insertSpy).not.toHaveBeenCalled();
                        expect(removeSpy).not.toHaveBeenCalled();
                        expect(expandSpy).not.toHaveBeenCalled();
                        expect(collapseSpy).not.toHaveBeenCalled();
                    });

                    it("should have the node, list and parent set", function() {
                        makeCustomList();
                        var item = getItem('i2');

                        expect(item.getNode()).toBe(byId('i2'));
                        expect(item.getParentItem()).toBeNull();
                        expect(item.getOwner()).toBe(list);
                    });

                    it("should have the itemConfig set", function() {
                        makeCustomList({
                            defaults: {
                                testConfig: 12
                            }
                        });
                        var item = getItem('i2');

                        expect(item.getTestConfig()).toBe(12);
                    });
                });

                describe("leaf: true", function() {
                    beforeEach(function() {
                        sampleData[2].leaf = true;
                    });

                    it("should set expanded", function() {
                        makeCustomList();
                        var item = getItem('i3');

                        expect(item.logs.expanded).toEqual([]);
                        expect(item.getExpanded()).toBe(false);
                    });

                    it("should set expandable", function() {
                        makeCustomList();
                        var item = getItem('i3');

                        expect(item.logs.expandable).toEqual([]);
                        expect(item.getExpandable()).toBe(false);
                    });

                    it("should set leaf", function() {
                        makeCustomList();
                        var item = getItem('i3');

                        expect(item.logs.leaf).toEqual([]);
                        expect(item.getLeaf()).toBe(true);
                    });

                    it("should set the icon if iconClsProperty is specified", function() {
                        sampleData[2].iconCls = 'iconA';
                        makeCustomList();
                        var item = getItem('i3');

                        expect(item.logs.iconCls).toEqual(['iconA']);
                        expect(item.getIconCls()).toBe('iconA');
                    });

                    it("should not set the icon if an iconClsProperty is not specified", function() {
                        sampleData[2].iconCls = 'iconA';
                        makeCustomList({
                            defaults: {
                                iconClsProperty: ''
                            }
                        });
                        var item = getItem('i3');

                        expect(item.logs.iconCls).toEqual([]);
                        expect(item.getIconCls()).toBe('');
                    });

                    it("should set the text if a textProperty is specified", function() {
                        makeCustomList();
                        var item = getItem('i3');

                        expect(item.logs.text).toEqual(['Item 3']);
                        expect(item.getText()).toBe('Item 3');
                    });

                    it("should not set the text if an textProperty is not specified", function() {
                        makeCustomList({
                            defaults: {
                                textProperty: ''
                            }
                        });
                        var item = getItem('i3');

                        expect(item.logs.text).toEqual([]);
                        expect(item.getText()).toBe('');
                    });

                    it("should not insert child nodes", function() {
                        makeCustomList();
                        var item = getItem('i3');

                        expect(item.logs.insertItem).toEqual([]);
                    });

                    it("should not call any template methods", function() {
                        makeCustomList();
                        var item = getItem('i3');

                        expect(item.logs.onNodeCollapse).toEqual([]);
                        expect(item.logs.onNodeExpand).toEqual([]);
                        expect(item.logs.onNodeInsert).toEqual([]);
                        expect(item.logs.onNodeRemove).toEqual([]);
                        expect(item.logs.onNodeUpdate).toEqual([]);
                    });

                    it("should not fire events", function() {
                        expect(insertSpy).not.toHaveBeenCalled();
                        expect(removeSpy).not.toHaveBeenCalled();
                        expect(expandSpy).not.toHaveBeenCalled();
                        expect(collapseSpy).not.toHaveBeenCalled();
                    });

                    it("should have the node, list and parent set", function() {
                        makeCustomList();
                        var item = getItem('i3');

                        expect(item.getNode()).toBe(byId('i3'));
                        expect(item.getParentItem()).toBeNull();
                        expect(item.getOwner()).toBe(list);
                    });

                    it("should have the itemConfig set", function() {
                        makeCustomList({
                            defaults: {
                                testConfig: 12
                            }
                        });
                        var item = getItem('i3');

                        expect(item.getTestConfig()).toBe(12);
                    });
                });

                describe("expanded: true, with children", function() {
                    it("should set expanded", function() {
                        makeCustomList();
                        var item = getItem('i4');

                        expect(item.logs.expanded).toEqual([true]);
                        expect(item.getExpanded()).toBe(true);
                    });

                    it("should set expandable", function() {
                        makeCustomList();
                        var item = getItem('i4');

                        expect(item.logs.expandable).toEqual([true]);
                        expect(item.getExpandable()).toBe(true);
                    });

                    it("should set leaf", function() {
                        makeCustomList();
                        var item = getItem('i4');

                        expect(item.logs.leaf).toEqual([false]);
                        expect(item.getLeaf()).toBe(false);
                    });

                    it("should set the icon if iconClsProperty is specified", function() {
                        sampleData[3].iconCls = 'iconA';
                        makeCustomList();
                        var item = getItem('i4');

                        expect(item.logs.iconCls).toEqual(['iconA']);
                        expect(item.getIconCls()).toBe('iconA');
                    });

                    it("should not set the icon if an iconClsProperty is not specified", function() {
                        sampleData[3].iconCls = 'iconA';
                        makeCustomList({
                            defaults: {
                                iconClsProperty: ''
                            }
                        });
                        var item = getItem('i4');

                        expect(item.logs.iconCls).toEqual([]);
                        expect(item.getIconCls()).toBe('');
                    });

                    it("should set the text if a textProperty is specified", function() {
                        makeCustomList();
                        var item = getItem('i4');

                        expect(item.logs.text).toEqual(['Item 4']);
                        expect(item.getText()).toBe('Item 4');
                    });

                    it("should not set the text if an textProperty is not specified", function() {
                        makeCustomList({
                            defaults: {
                                textProperty: ''
                            }
                        });
                        var item = getItem('i4');

                        expect(item.logs.text).toEqual([]);
                        expect(item.getText()).toBe('');
                    });

                    it("should insert the child nodes", function() {
                        makeCustomList();
                        var item = getItem('i4');

                        expect(item.logs.insertItem).toEqual([
                            [getItem('i41'), null],
                            [getItem('i42'), null],
                            [getItem('i43'), null]
                        ]);
                    });

                    it("should not call any template methods", function() {
                        makeCustomList();
                        var item = getItem('i4');

                        expect(item.logs.onNodeCollapse).toEqual([]);
                        expect(item.logs.onNodeExpand).toEqual([]);
                        expect(item.logs.onNodeInsert).toEqual([]);
                        expect(item.logs.onNodeRemove).toEqual([]);
                        expect(item.logs.onNodeUpdate).toEqual([]);
                    });

                    it("should not fire events", function() {
                        expect(insertSpy).not.toHaveBeenCalled();
                        expect(removeSpy).not.toHaveBeenCalled();
                        expect(expandSpy).not.toHaveBeenCalled();
                        expect(collapseSpy).not.toHaveBeenCalled();
                    });

                    it("should have the node, list and parent set", function() {
                        makeCustomList();
                        var item = getItem('i4');

                        expect(item.getNode()).toBe(byId('i4'));
                        expect(item.getParentItem()).toBeNull();
                        expect(item.getOwner()).toBe(list);
                    });

                    it("should have the itemConfig set", function() {
                        makeCustomList({
                            defaults: {
                                testConfig: 12
                            }
                        });
                        var item = getItem('i4');

                        expect(item.getTestConfig()).toBe(12);
                    });
                });
            });

            describe("child level nodes", function() {
                describe("parent expanded: false", function() {
                    it("should set expanded", function() {
                        makeCustomList();
                        var item1 = getItem('i11'),
                            item2 = getItem('i12');

                        expect(item1.logs.expanded).toEqual([]);
                        expect(item2.logs.expanded).toEqual([]);

                        expect(item1.getExpanded()).toBe(false);
                        expect(item2.getExpanded()).toBe(false);
                    });

                    it("should set expandable", function() {
                        makeCustomList();
                        var item1 = getItem('i11'),
                            item2 = getItem('i12');

                        expect(item1.logs.expandable).toEqual([]);
                        expect(item2.logs.expandable).toEqual([]);

                        expect(item1.getExpandable()).toBe(false);
                        expect(item2.getExpanded()).toBe(false);
                    });

                    it("should set leaf", function() {
                        makeCustomList();
                        var item1 = getItem('i11'),
                            item2 = getItem('i12');

                        expect(item1.logs.leaf).toEqual([]);
                        expect(item2.logs.leaf).toEqual([]);

                        expect(item1.getLeaf()).toBe(true);
                        expect(item2.getLeaf()).toBe(true);
                    });

                    it("should set the icon if iconClsProperty is specified", function() {
                        sampleData[0].children[0].iconCls = 'iconA';
                        sampleData[0].children[1].iconCls = 'iconB';
                        makeCustomList();
                        var item1 = getItem('i11'),
                            item2 = getItem('i12');

                        expect(item1.logs.iconCls).toEqual(['iconA']);
                        expect(item2.logs.iconCls).toEqual(['iconB']);

                        expect(item1.getIconCls()).toBe('iconA');
                        expect(item2.getIconCls()).toBe('iconB');
                    });

                    it("should not set the icon if an iconClsProperty is not specified", function() {
                        sampleData[0].children[0].iconCls = 'iconA';
                        sampleData[0].children[1].iconCls = 'iconB';
                        makeCustomList({
                            defaults: {
                                iconClsProperty: ''
                            }
                        });
                        var item1 = getItem('i11'),
                            item2 = getItem('i12');

                        expect(item1.logs.iconCls).toEqual([]);
                        expect(item2.logs.iconCls).toEqual([]);

                        expect(item1.getIconCls()).toBe('');
                        expect(item2.getIconCls()).toBe('');
                    });

                    it("should set the text if a textProperty is specified", function() {
                        makeCustomList();
                        var item1 = getItem('i11'),
                            item2 = getItem('i12');

                        expect(item1.logs.text).toEqual(['Item 1.1']);
                        expect(item2.logs.text).toEqual(['Item 1.2']);

                        expect(item1.getText()).toBe('Item 1.1');
                        expect(item2.getText()).toBe('Item 1.2');
                    });

                    it("should not set the text if an textProperty is not specified", function() {
                        makeCustomList({
                            defaults: {
                                textProperty: ''
                            }
                        });
                        var item1 = getItem('i11'),
                            item2 = getItem('i12');

                        expect(item1.logs.text).toEqual([]);
                        expect(item2.logs.text).toEqual([]);

                        expect(item1.getText()).toBe('');
                        expect(item2.getText()).toBe('');
                    });

                    it("should not call any template methods", function() {
                        makeCustomList();
                        var item1 = getItem('i11'),
                            item2 = getItem('i12');

                        expect(item1.logs.onNodeCollapse).toEqual([]);
                        expect(item1.logs.onNodeExpand).toEqual([]);
                        expect(item1.logs.onNodeInsert).toEqual([]);
                        expect(item1.logs.onNodeRemove).toEqual([]);
                        expect(item1.logs.onNodeUpdate).toEqual([]);

                        expect(item2.logs.onNodeCollapse).toEqual([]);
                        expect(item2.logs.onNodeExpand).toEqual([]);
                        expect(item2.logs.onNodeInsert).toEqual([]);
                        expect(item2.logs.onNodeRemove).toEqual([]);
                        expect(item2.logs.onNodeUpdate).toEqual([]);
                    });

                    it("should not fire events", function() {
                        expect(insertSpy).not.toHaveBeenCalled();
                        expect(removeSpy).not.toHaveBeenCalled();
                        expect(expandSpy).not.toHaveBeenCalled();
                        expect(collapseSpy).not.toHaveBeenCalled();
                    });

                    it("should have the node, list and parent set", function() {
                        makeCustomList();
                        var item1 = getItem('i11'),
                            item2 = getItem('i12');

                        expect(item1.getNode()).toBe(byId('i11'));
                        expect(item1.getParentItem()).toBe(getItem('i1'));
                        expect(item1.getOwner()).toBe(list);

                        expect(item2.getNode()).toBe(byId('i12'));
                        expect(item2.getParentItem()).toBe(getItem('i1'));
                        expect(item2.getOwner()).toBe(list);
                    });

                    it("should have the itemConfig set", function() {
                        makeCustomList({
                            defaults: {
                                testConfig: 12
                            }
                        });
                        var item1 = getItem('i11'),
                            item2 = getItem('i12');

                        expect(item1.getTestConfig()).toBe(12);
                        expect(item2.getTestConfig()).toBe(12);
                    });
                });

                describe("parent expanded: true", function() {
                    it("should set expanded", function() {
                        makeCustomList();
                        var item1 = getItem('i41'),
                            item2 = getItem('i42'),
                            item3 = getItem('i43');

                        expect(item1.logs.expanded).toEqual([]);
                        expect(item2.logs.expanded).toEqual([]);
                        expect(item3.logs.expanded).toEqual([]);

                        expect(item1.getExpanded()).toBe(false);
                        expect(item2.getExpanded()).toBe(false);
                        expect(item3.getExpanded()).toBe(false);
                    });

                    it("should set expandable", function() {
                        makeCustomList();
                        var item1 = getItem('i41'),
                            item2 = getItem('i42'),
                            item3 = getItem('i43');

                        expect(item1.logs.expandable).toEqual([]);
                        expect(item2.logs.expandable).toEqual([true]);
                        expect(item3.logs.expandable).toEqual([]);

                        expect(item1.getExpandable()).toBe(false);
                        expect(item2.getExpandable()).toBe(true);
                        expect(item3.getExpandable()).toBe(false);
                    });

                    it("should set leaf", function() {
                        makeCustomList();
                        var item1 = getItem('i41'),
                            item2 = getItem('i42'),
                            item3 = getItem('i43');

                        expect(item1.logs.leaf).toEqual([]);
                        expect(item2.logs.leaf).toEqual([false]);
                        expect(item3.logs.leaf).toEqual([]);

                        expect(item1.getLeaf()).toBe(true);
                        expect(item2.getLeaf()).toBe(false);
                        expect(item3.getLeaf()).toBe(true);
                    });

                    it("should set the icon if iconClsProperty is specified", function() {
                        sampleData[3].children[0].iconCls = 'iconA';
                        sampleData[3].children[1].iconCls = 'iconB';
                        sampleData[3].children[2].iconCls = 'iconC';
                        makeCustomList();
                        var item1 = getItem('i41'),
                            item2 = getItem('i42'),
                            item3 = getItem('i43');

                        expect(item1.logs.iconCls).toEqual(['iconA']);
                        expect(item2.logs.iconCls).toEqual(['iconB']);
                        expect(item3.logs.iconCls).toEqual(['iconC']);

                        expect(item1.getIconCls()).toBe('iconA');
                        expect(item2.getIconCls()).toBe('iconB');
                        expect(item3.getIconCls()).toBe('iconC');
                    });

                    it("should not set the icon if an iconClsProperty is not specified", function() {
                        sampleData[3].children[0].iconCls = 'iconA';
                        sampleData[3].children[1].iconCls = 'iconB';
                        sampleData[3].children[2].iconCls = 'iconC';
                        makeCustomList({
                            defaults: {
                                iconClsProperty: ''
                            }
                        });
                        var item1 = getItem('i41'),
                            item2 = getItem('i42'),
                            item3 = getItem('i43');

                        expect(item1.logs.iconCls).toEqual([]);
                        expect(item2.logs.iconCls).toEqual([]);
                        expect(item3.logs.iconCls).toEqual([]);

                        expect(item1.getIconCls()).toBe('');
                        expect(item2.getIconCls()).toBe('');
                        expect(item3.getIconCls()).toBe('');
                    });

                    it("should set the text if a textProperty is specified", function() {
                        makeCustomList();
                        var item1 = getItem('i41'),
                            item2 = getItem('i42'),
                            item3 = getItem('i43');

                        expect(item1.logs.text).toEqual(['Item 4.1']);
                        expect(item2.logs.text).toEqual(['Item 4.2']);
                        expect(item3.logs.text).toEqual(['Item 4.3']);

                        expect(item1.getText()).toBe('Item 4.1');
                        expect(item2.getText()).toBe('Item 4.2');
                        expect(item3.getText()).toBe('Item 4.3');
                    });

                    it("should not set the text if an textProperty is not specified", function() {
                        makeCustomList({
                            defaults: {
                                textProperty: ''
                            }
                        });
                        var item1 = getItem('i41'),
                            item2 = getItem('i42'),
                            item3 = getItem('i43');

                        expect(item1.logs.text).toEqual([]);
                        expect(item2.logs.text).toEqual([]);
                        expect(item3.logs.text).toEqual([]);

                        expect(item1.getText()).toBe('');
                        expect(item2.getText()).toBe('');
                        expect(item3.getText()).toBe('');
                    });

                    it("should not call any template methods", function() {
                        makeCustomList();
                        var item1 = getItem('i41'),
                            item2 = getItem('i42'),
                            item3 = getItem('i43');

                        expect(item1.logs.onNodeCollapse).toEqual([]);
                        expect(item1.logs.onNodeExpand).toEqual([]);
                        expect(item1.logs.onNodeInsert).toEqual([]);
                        expect(item1.logs.onNodeRemove).toEqual([]);
                        expect(item1.logs.onNodeUpdate).toEqual([]);

                        expect(item2.logs.onNodeCollapse).toEqual([]);
                        expect(item2.logs.onNodeExpand).toEqual([]);
                        expect(item2.logs.onNodeInsert).toEqual([]);
                        expect(item2.logs.onNodeRemove).toEqual([]);
                        expect(item2.logs.onNodeUpdate).toEqual([]);

                        expect(item3.logs.onNodeCollapse).toEqual([]);
                        expect(item3.logs.onNodeExpand).toEqual([]);
                        expect(item3.logs.onNodeInsert).toEqual([]);
                        expect(item3.logs.onNodeRemove).toEqual([]);
                        expect(item3.logs.onNodeUpdate).toEqual([]);
                    });

                    it("should not fire events", function() {
                        expect(insertSpy).not.toHaveBeenCalled();
                        expect(removeSpy).not.toHaveBeenCalled();
                        expect(expandSpy).not.toHaveBeenCalled();
                        expect(collapseSpy).not.toHaveBeenCalled();
                    });

                    it("should have the node, list and parent set", function() {
                        makeCustomList();
                        var item1 = getItem('i41'),
                            item2 = getItem('i42'),
                            item3 = getItem('i43');

                        expect(item1.getNode()).toBe(byId('i41'));
                        expect(item1.getParentItem()).toBe(getItem('i4'));
                        expect(item1.getOwner()).toBe(list);

                        expect(item2.getNode()).toBe(byId('i42'));
                        expect(item2.getParentItem()).toBe(getItem('i4'));
                        expect(item2.getOwner()).toBe(list);

                        expect(item3.getNode()).toBe(byId('i43'));
                        expect(item3.getParentItem()).toBe(getItem('i4'));
                        expect(item3.getOwner()).toBe(list);
                    });

                    it("should have the itemConfig set", function() {
                        makeCustomList({
                            defaults: {
                                testConfig: 12
                            }
                        });
                        var item1 = getItem('i41'),
                            item2 = getItem('i42'),
                            item3 = getItem('i43');

                        expect(item1.getTestConfig()).toBe(12);
                        expect(item2.getTestConfig()).toBe(12);
                        expect(item3.getTestConfig()).toBe(12);
                    });
                });
            });
        });

        describe("Load store", function() {

            beforeEach(function() {
                var cfg;

                store = Ext.create('Ext.data.TreeStore', {
                    data: [{
                        text: 'node',
                        leaf: true
                    }]
                });

                list = new Ext.list.Tree(Ext.apply({
                    store: store,
                    animation: false
                }, cfg));
                list.render(Ext.getBody());

                if (list.getStore()) {
                    root = list.getStore().getRoot();
                }
            });

            it("should not append items on store load", function() {
                var itemsLen = Object.keys(list.rootItem.itemMap).length;

                store.load();

                // Length of items should be equal before and after load
                expect(Object.keys(list.rootItem.itemMap).length).toEqual(itemsLen);
            });
        });

        describe("dynamic store modifications", function() {
            describe("filtering", function() {
                it("should react to the store being filtered", function() {
                    makeList();
                    store.filterer = 'bottomup';
                    store.filterBy(function(rec) {
                        var s = rec.data.text;

                        return s === 'Item 1.1' || s === 'Item 4.2';
                    });
                    byId('i1').expand();

                    expect(getItem('i1')).not.toBeNull();
                    expect(getItem('i11')).not.toBeNull();
                    expect(getItem('i12')).toBeNull();

                    expect(getItem('i2')).toBeNull();

                    expect(getItem('i3')).toBeNull();

                    expect(getItem('i4')).not.toBeNull();
                    expect(getItem('i41')).toBeNull();
                    expect(getItem('i42')).not.toBeNull();
                    expect(getItem('i43')).toBeNull();
                });

                it("should react to filters being cleared", function() {
                    makeList();
                    store.filterer = 'bottomup';
                    byId('i1').expand();
                    store.filterBy(function(rec) {
                        var s = rec.data.text;

                        return s === 'Item 1.1' || s === 'Item 4.2';
                    });
                    store.getFilters().removeAll();

                    expect(getItem('i1')).not.toBeNull();
                    expect(getItem('i11')).not.toBeNull();
                    expect(getItem('i12')).not.toBeNull();

                    expect(getItem('i2')).not.toBeNull();

                    expect(getItem('i3')).not.toBeNull();

                    expect(getItem('i4')).not.toBeNull();
                    expect(getItem('i41')).not.toBeNull();
                    expect(getItem('i42')).not.toBeNull();
                    expect(getItem('i43')).not.toBeNull();
                });

                describe("events", function() {
                    it("should fire the refresh event after filtering/clearing", function() {
                        var spy = jasmine.createSpy();

                        makeList({
                            listeners: {
                                refresh: spy
                            }
                        });
                        store.filterer = 'bottomup';
                        byId('i1').expand();
                        store.filterBy(function(rec) {
                            var s = rec.data.text;

                            return s === 'Item 1.1' || s === 'Item 4.2';
                        });
                        expect(spy.callCount).toBe(1);
                        store.getFilters().removeAll();
                        expect(spy.callCount).toBe(2);
                    });

                    it("should not fire iteminsert/itemremove events", function() {
                        var spy = jasmine.createSpy();

                        makeList({
                            listeners: {
                                iteminsert: spy,
                                itemremove: spy
                            }
                        });
                        store.filterer = 'bottomup';
                        store.filterBy(function(rec) {
                            var s = rec.data.text;

                            return s === 'Item 1.1' || s === 'Item 4.2';
                        });
                        expect(spy).not.toHaveBeenCalled();
                        byId('i1').expand();
                        store.getFilters().removeAll();
                        expect(spy).not.toHaveBeenCalled();
                    });
                });
            });

            describe("adding nodes", function() {
                describe("via insert", function() {
                    describe("to the root", function() {
                        var node;

                        beforeEach(function() {
                            makeCustomList({
                                defaults: {
                                    testConfig: 200
                                }
                            });
                            node = root.insertBefore({
                                id: 'i9',
                                text: 'Item 9'
                            }, byId('i1'));
                        });

                        afterEach(function() {
                            node = null;
                        });

                        it("should create the item type", function() {
                            var item = getItem('i9');

                            expect(item.xtype).toBe('spec_treelist_customitem');
                        });

                        it("should set the itemConfig", function() {
                            var item = getItem('i9');

                            expect(item.getTestConfig()).toBe(200);
                        });

                        it("should have the node, list and parent set", function() {
                            var item = getItem('i9');

                            expect(item.getNode()).toBe(node);
                            expect(item.getParentItem()).toBeNull();
                            expect(item.getOwner()).toBe(list);
                        });

                        // We can test the DOM here because root is a special subclass
                        it("should insert the item before the passed item", function() {
                            var item = getItem('i9');

                            expect(item.el.next()).toBe(getItem('i1').el);
                        });

                        describe("events", function() {
                            it("should fire iteminsert", function() {
                                expect(insertSpy.callCount).toBe(1);
                                expect(insertSpy.mostRecentCall.args[0]).toBe(list);
                                expect(insertSpy.mostRecentCall.args[1]).toBe(getItem('root'));
                                expect(insertSpy.mostRecentCall.args[2]).toBe(getItem('i9'));
                                expect(insertSpy.mostRecentCall.args[3]).toBe(getItem('i1'));
                            });
                        });

                        describe("child nodes", function() {
                            var item, item1, item2;

                            beforeEach(function() {
                                node = new Model({
                                    id: 'i8'
                                });

                                insertSpy.reset();
                                node.appendChild([{
                                    id: 'i81'
                                }, {
                                    id: 'i82'
                                }]);

                                root.insertBefore(node, byId('i9'));

                                item = getItem('i8');
                                item1 = getItem('i81');
                                item2 = getItem('i82');
                            });

                            afterEach(function() {
                                item = item1 = item2 = null;
                            });

                            it("should have the node, list and parent set", function() {
                                expect(item1.getNode()).toBe(node.childNodes[0]);
                                expect(item1.getParentItem()).toBe(item);
                                expect(item1.getOwner()).toBe(list);

                                expect(item2.getNode()).toBe(node.childNodes[1]);
                                expect(item2.getParentItem()).toBe(item);
                                expect(item2.getOwner()).toBe(list);
                            });

                            it("should call insertItem", function() {
                                expect(item.logs.insertItem).toEqual([
                                    [item1, null],
                                    [item2, null]
                                ]);
                            });

                            it("should not call onNodeInsert", function() {
                                expect(item.logs.onNodeInsert).toEqual([]);
                            });

                            describe("events", function() {
                                it("should fire iteminsert for the top level item only", function() {
                                    expect(insertSpy.callCount).toBe(1);
                                    expect(insertSpy.mostRecentCall.args[0]).toBe(list);
                                    expect(insertSpy.mostRecentCall.args[1]).toBe(getItem('root'));
                                    expect(insertSpy.mostRecentCall.args[2]).toBe(getItem('i8'));
                                    expect(insertSpy.mostRecentCall.args[3]).toBe(getItem('i9'));
                                });
                            });
                        });
                    });

                    describe("non-root node", function() {
                        describe("node that is collapsed", function() {
                            var node;

                            beforeEach(function() {
                                makeCustomList({
                                    defaults: {
                                        testConfig: 200
                                    }
                                });
                                getItem('i1').logs.insertItem = [];
                                node = byId('i1').insertBefore({
                                    id: 'i9',
                                    text: 'Item 9'
                                }, byId('i12'));
                            });

                            afterEach(function() {
                                node = null;
                            });

                            it("should create the item type", function() {
                                var item = getItem('i9');

                                expect(item.xtype).toBe('spec_treelist_customitem');
                            });

                            it("should set the itemConfig", function() {
                                var item = getItem('i9');

                                expect(item.getTestConfig()).toBe(200);
                            });

                            it("should have the node, list and parent set", function() {
                                var item = getItem('i9');

                                expect(item.getNode()).toBe(node);
                                expect(item.getParentItem()).toBe(getItem('i1'));
                                expect(item.getOwner()).toBe(list);
                            });

                            it("should leave the parent as collapsed", function() {
                                expect(getItem('i1').isExpanded()).toBe(false);
                            });

                            describe("template methods", function() {
                                it("should call insertItem", function() {
                                    var item = getItem('i1');

                                    expect(item.logs.insertItem).toEqual([[getItem('i9'), getItem('i12')]]);
                                });

                                it("should call onNodeInsert", function() {
                                    var item = getItem('i1');

                                    expect(item.logs.onNodeInsert).toEqual([[byId('i9'), byId('i12')]]);
                                });
                            });

                            describe("events", function() {
                                it("should fire iteminsert", function() {
                                    expect(insertSpy.callCount).toBe(1);
                                    expect(insertSpy.mostRecentCall.args[0]).toBe(list);
                                    expect(insertSpy.mostRecentCall.args[1]).toBe(getItem('i1'));
                                    expect(insertSpy.mostRecentCall.args[2]).toBe(getItem('i9'));
                                    expect(insertSpy.mostRecentCall.args[3]).toBe(getItem('i12'));
                                });
                            });

                            describe("child nodes", function() {
                                var item, item1, item2;

                                beforeEach(function() {
                                    node = new Model({
                                        id: 'i8'
                                    });

                                    insertSpy.reset();
                                    node.appendChild([{
                                        id: 'i81'
                                    }, {
                                        id: 'i82'
                                    }]);

                                    byId('i1').insertBefore(node, byId('i9'));

                                    item = getItem('i8');
                                    item1 = getItem('i81');
                                    item2 = getItem('i82');
                                });

                                afterEach(function() {
                                    item = item1 = item2 = null;
                                });

                                it("should have the node, list and parent set", function() {
                                    expect(item1.getNode()).toBe(node.childNodes[0]);
                                    expect(item1.getParentItem()).toBe(item);
                                    expect(item1.getOwner()).toBe(list);

                                    expect(item2.getNode()).toBe(node.childNodes[1]);
                                    expect(item2.getParentItem()).toBe(item);
                                    expect(item2.getOwner()).toBe(list);
                                });

                                it("should call insertItem", function() {
                                    expect(item.logs.insertItem).toEqual([
                                        [item1, null],
                                        [item2, null]
                                    ]);
                                });

                                it("should not call onNodeInsert", function() {
                                    expect(item.logs.onNodeInsert).toEqual([]);
                                });

                                describe("events", function() {
                                    it("should fire iteminsert for the top level item only", function() {
                                        expect(insertSpy.callCount).toBe(1);
                                        expect(insertSpy.mostRecentCall.args[0]).toBe(list);
                                        expect(insertSpy.mostRecentCall.args[1]).toBe(getItem('i1'));
                                        expect(insertSpy.mostRecentCall.args[2]).toBe(getItem('i8'));
                                        expect(insertSpy.mostRecentCall.args[3]).toBe(getItem('i9'));
                                    });
                                });
                            });
                        });

                        describe("node that is expanded", function() {
                            var node;

                            beforeEach(function() {
                                makeCustomList({
                                    defaults: {
                                        testConfig: 200
                                    }
                                });
                                getItem('i4').logs.insertItem = [];
                                node = byId('i4').insertBefore({
                                    id: 'i9',
                                    text: 'Item 9'
                                }, byId('i43'));
                            });

                            afterEach(function() {
                                node = null;
                            });

                            it("should create the item type", function() {
                                var item = getItem('i9');

                                expect(item.xtype).toBe('spec_treelist_customitem');
                            });

                            it("should set the itemConfig", function() {
                                var item = getItem('i9');

                                expect(item.getTestConfig()).toBe(200);
                            });

                            it("should have the node, list and parent set", function() {
                                var item = getItem('i9');

                                expect(item.getNode()).toBe(node);
                                expect(item.getParentItem()).toBe(getItem('i4'));
                                expect(item.getOwner()).toBe(list);
                            });

                            it("should leave the parent as expanded", function() {
                                expect(getItem('i4').isExpanded()).toBe(true);
                            });

                            describe("template methods", function() {
                                it("should call insertItem", function() {
                                    var item = getItem('i4');

                                    expect(item.logs.insertItem).toEqual([[getItem('i9'), getItem('i43')]]);
                                });

                                it("should call onNodeInsert", function() {
                                    var item = getItem('i4');

                                    expect(item.logs.onNodeInsert).toEqual([[byId('i9'), byId('i43')]]);
                                });
                            });

                            describe("events", function() {
                                it("should fire iteminsert", function() {
                                    expect(insertSpy.callCount).toBe(1);
                                    expect(insertSpy.mostRecentCall.args[0]).toBe(list);
                                    expect(insertSpy.mostRecentCall.args[1]).toBe(getItem('i4'));
                                    expect(insertSpy.mostRecentCall.args[2]).toBe(getItem('i9'));
                                    expect(insertSpy.mostRecentCall.args[3]).toBe(getItem('i43'));
                                });
                            });

                            describe("child nodes", function() {
                                var item, item1, item2;

                                beforeEach(function() {
                                    node = new Model({
                                        id: 'i8'
                                    });

                                    insertSpy.reset();
                                    node.appendChild([{
                                        id: 'i81'
                                    }, {
                                        id: 'i82'
                                    }]);

                                    byId('i4').insertBefore(node, byId('i9'));

                                    item = getItem('i8');
                                    item1 = getItem('i81');
                                    item2 = getItem('i82');
                                });

                                afterEach(function() {
                                    item = item1 = item2 = null;
                                });

                                it("should have the node, list and parent set", function() {
                                    expect(item1.getNode()).toBe(node.childNodes[0]);
                                    expect(item1.getParentItem()).toBe(item);
                                    expect(item1.getOwner()).toBe(list);

                                    expect(item2.getNode()).toBe(node.childNodes[1]);
                                    expect(item2.getParentItem()).toBe(item);
                                    expect(item2.getOwner()).toBe(list);
                                });

                                it("should call insertItem", function() {
                                    expect(item.logs.insertItem).toEqual([
                                        [item1, null],
                                        [item2, null]
                                    ]);
                                });

                                it("should not call onNodeInsert", function() {
                                    expect(item.logs.onNodeInsert).toEqual([]);
                                });

                                describe("events", function() {
                                    it("should fire iteminsert for the top level item only", function() {
                                        expect(insertSpy.callCount).toBe(1);
                                        expect(insertSpy.mostRecentCall.args[0]).toBe(list);
                                        expect(insertSpy.mostRecentCall.args[1]).toBe(getItem('i4'));
                                        expect(insertSpy.mostRecentCall.args[2]).toBe(getItem('i8'));
                                        expect(insertSpy.mostRecentCall.args[3]).toBe(getItem('i9'));
                                    });
                                });
                            });
                        });
                    });

                    it("should update the expandable state when adding the first node", function() {
                        sampleData[2].children = [];
                        makeCustomList();
                        var item = getItem('i3');

                        expect(item.logs.expandable).toEqual([]);
                        byId('i3').insertBefore({
                            id: 'i9',
                            text: 'Item 9'
                        }, null);
                        expect(item.logs.expandable).toEqual([true]);
                    });

                    describe("existing nodes", function() {
                        var existing;

                        afterEach(function() {
                            existing = null;
                        });

                        describe("in the same container", function() {
                            beforeEach(function() {
                                makeCustomList();
                                existing = getItem('i43');
                                getItem('i4').logs.insertItem = [];
                                byId('i4').insertBefore(byId('i43'), byId('i41'));
                            });

                            it("should use the same item", function() {
                                expect(getItem('i43')).toBe(existing);
                            });

                            it("should use the same el", function() {
                                expect(getItem('i43').el).toBe(existing.el);
                            });

                            it("should set the parent", function() {
                                expect(getItem('i43').getParentItem()).toBe(getItem('i4'));
                            });

                            describe("template methods", function() {
                                it("should call onNodeInsert", function() {
                                    var item = getItem('i4');

                                    expect(item.logs.onNodeInsert).toEqual([[byId('i43'), byId('i41')]]);
                                });

                                it("should call insertItem", function() {
                                    var item = getItem('i4');

                                    expect(item.logs.insertItem).toEqual([[getItem('i43'), getItem('i41')]]);
                                });

                                it("should call removeItem", function() {
                                    var item = getItem('i4');

                                    expect(item.logs.removeItem).toEqual([getItem('i43')]);
                                });

                                it("should not call onNodeRemove", function() {
                                    var item = getItem('i4');

                                    expect(item.logs.onNodeRemove).toEqual([]);
                                });
                            });

                            describe("events", function() {
                                it("should fire iteminsert", function() {
                                    expect(insertSpy.callCount).toBe(1);
                                    expect(insertSpy.mostRecentCall.args[0]).toBe(list);
                                    expect(insertSpy.mostRecentCall.args[1]).toBe(getItem('i4'));
                                    expect(insertSpy.mostRecentCall.args[2]).toBe(getItem('i43'));
                                    expect(insertSpy.mostRecentCall.args[3]).toBe(getItem('i41'));
                                });

                                it("should not fire itemremove", function() {
                                    expect(removeSpy).not.toHaveBeenCalled();
                                });
                            });
                        });

                        describe("from a different container", function() {
                            beforeEach(function() {
                                sampleData[2].children = [];
                                makeCustomList();
                                existing = getItem('i43');
                                getItem('i1').logs.insertItem = [];
                                byId('i1').insertBefore(byId('i43'), byId('i11'));
                            });

                            it("should use the same item", function() {
                                expect(getItem('i43')).toBe(existing);
                            });

                            it("should use the same el", function() {
                                expect(getItem('i43').el).toBe(existing.el);
                            });

                            it("should set the parent", function() {
                                expect(getItem('i43').getParentItem()).toBe(getItem('i1'));
                            });

                            it("should update the expandable state when adding the first node and moving the last", function() {
                                var item1 = getItem('i1'),
                                    item3 = getItem('i3');

                                byId('i1').removeChild(byId('i12'));
                                byId('i1').removeChild(byId('i43'));

                                expect(item1.logs.expandable).toEqual([true]);
                                expect(item3.logs.expandable).toEqual([]);
                                byId('i3').insertBefore(byId('i11'), null);

                                expect(item1.logs.expandable).toEqual([true, false]);
                                expect(item3.logs.expandable).toEqual([true]);
                            });

                            describe("template methods", function() {
                                it("should call onNodeInsert", function() {
                                    var item = getItem('i1');

                                    expect(item.logs.onNodeInsert).toEqual([[byId('i43'), byId('i11')]]);
                                });

                                it("should call insertItem", function() {
                                    var item = getItem('i1');

                                    expect(item.logs.insertItem).toEqual([[getItem('i43'), getItem('i11')]]);
                                });

                                it("should call removeItem", function() {
                                    var item = getItem('i4');

                                    expect(item.logs.removeItem).toEqual([getItem('i43')]);
                                });

                                it("should not call onNodeRemove", function() {
                                    var item = getItem('i4');

                                    expect(item.logs.onNodeRemove).toEqual([]);
                                });
                            });

                            describe("events", function() {
                                it("should fire iteminsert", function() {
                                    expect(insertSpy.callCount).toBe(1);
                                    expect(insertSpy.mostRecentCall.args[0]).toBe(list);
                                    expect(insertSpy.mostRecentCall.args[1]).toBe(getItem('i1'));
                                    expect(insertSpy.mostRecentCall.args[2]).toBe(getItem('i43'));
                                    expect(insertSpy.mostRecentCall.args[3]).toBe(getItem('i11'));
                                });

                                it("should not fire itemremove", function() {
                                    expect(removeSpy).not.toHaveBeenCalled();
                                });
                            });
                        });
                    });
                });

                describe("via append", function() {
                    describe("to the root", function() {
                        var node;

                        beforeEach(function() {
                            makeCustomList({
                                defaults: {
                                    testConfig: 200
                                }
                            });
                            node = root.appendChild({
                                id: 'i9',
                                text: 'Item 9'
                            });
                        });

                        afterEach(function() {
                            node = null;
                        });

                        it("should create the item type", function() {
                            var item = getItem('i9');

                            expect(item.xtype).toBe('spec_treelist_customitem');
                        });

                        it("should set the itemConfig", function() {
                            var item = getItem('i9');

                            expect(item.getTestConfig()).toBe(200);
                        });

                        it("should have the node, list and parent set", function() {
                            var item = getItem('i9');

                            expect(item.getNode()).toBe(node);
                            expect(item.getParentItem()).toBeNull();
                            expect(item.getOwner()).toBe(list);
                        });

                        // We can test the DOM here because root is a special subclass
                        it("should insert the item at the end", function() {
                            var item = getItem('i9');

                            expect(item.el.prev()).toBe(getItem('i5').el);
                        });

                        describe("events", function() {
                            it("should fire iteminsert", function() {
                                expect(insertSpy.callCount).toBe(1);
                                expect(insertSpy.mostRecentCall.args[0]).toBe(list);
                                expect(insertSpy.mostRecentCall.args[1]).toBe(getItem('root'));
                                expect(insertSpy.mostRecentCall.args[2]).toBe(getItem('i9'));
                                expect(insertSpy.mostRecentCall.args[3]).toBeNull();
                            });
                        });

                        describe("child nodes", function() {
                            var item, item1, item2;

                            beforeEach(function() {
                                node = new Model({
                                    id: 'i8'
                                });

                                insertSpy.reset();
                                node.appendChild([{
                                    id: 'i81'
                                }, {
                                    id: 'i82'
                                }]);

                                root.appendChild(node);

                                item = getItem('i8');
                                item1 = getItem('i81');
                                item2 = getItem('i82');
                            });

                            afterEach(function() {
                                item = item1 = item2 = null;
                            });

                            it("should have the node, list and parent set", function() {
                                expect(item1.getNode()).toBe(node.childNodes[0]);
                                expect(item1.getParentItem()).toBe(item);
                                expect(item1.getOwner()).toBe(list);

                                expect(item2.getNode()).toBe(node.childNodes[1]);
                                expect(item2.getParentItem()).toBe(item);
                                expect(item2.getOwner()).toBe(list);
                            });

                            it("should call insertItem", function() {
                                expect(item.logs.insertItem).toEqual([
                                    [item1, null],
                                    [item2, null]
                                ]);
                            });

                            it("should not call onNodeInsert", function() {
                                expect(item.logs.onNodeInsert).toEqual([]);
                            });

                            describe("events", function() {
                                it("should fire iteminsert for the top level item only", function() {
                                    expect(insertSpy.callCount).toBe(1);
                                    expect(insertSpy.mostRecentCall.args[0]).toBe(list);
                                    expect(insertSpy.mostRecentCall.args[1]).toBe(getItem('root'));
                                    expect(insertSpy.mostRecentCall.args[2]).toBe(getItem('i8'));
                                    expect(insertSpy.mostRecentCall.args[3]).toBeNull();
                                });
                            });
                        });
                    });

                    describe("non-root node", function() {
                        describe("node that is collapsed", function() {
                            var node;

                            beforeEach(function() {
                                makeCustomList({
                                    defaults: {
                                        testConfig: 200
                                    }
                                });
                                getItem('i1').logs.insertItem = [];
                                node = byId('i1').appendChild({
                                    id: 'i9',
                                    text: 'Item 9'
                                });
                            });

                            afterEach(function() {
                                node = null;
                            });

                            it("should create the item type", function() {
                                var item = getItem('i9');

                                expect(item.xtype).toBe('spec_treelist_customitem');
                            });

                            it("should set the itemConfig", function() {
                                var item = getItem('i9');

                                expect(item.getTestConfig()).toBe(200);
                            });

                            it("should have the node, list and parent set", function() {
                                var item = getItem('i9');

                                expect(item.getNode()).toBe(node);
                                expect(item.getParentItem()).toBe(getItem('i1'));
                                expect(item.getOwner()).toBe(list);
                            });

                            it("should leave the parent as collapsed", function() {
                                expect(getItem('i1').isExpanded()).toBe(false);
                            });

                            describe("template methods", function() {
                                it("should call insertItem", function() {
                                    var item = getItem('i1');

                                    expect(item.logs.insertItem).toEqual([[getItem('i9'), null]]);
                                });

                                it("should call onNodeInsert", function() {
                                    var item = getItem('i1');

                                    expect(item.logs.onNodeInsert).toEqual([[byId('i9'), null]]);
                                });
                            });

                            describe("events", function() {
                                it("should fire iteminsert", function() {
                                    expect(insertSpy.callCount).toBe(1);
                                    expect(insertSpy.mostRecentCall.args[0]).toBe(list);
                                    expect(insertSpy.mostRecentCall.args[1]).toBe(getItem('i1'));
                                    expect(insertSpy.mostRecentCall.args[2]).toBe(getItem('i9'));
                                    expect(insertSpy.mostRecentCall.args[3]).toBeNull();
                                });
                            });

                            describe("child nodes", function() {
                                var item, item1, item2;

                                beforeEach(function() {
                                    node = new Model({
                                        id: 'i8'
                                    });

                                    insertSpy.reset();
                                    node.appendChild([{
                                        id: 'i81'
                                    }, {
                                        id: 'i82'
                                    }]);

                                    byId('i1').appendChild(node);

                                    item = getItem('i8');
                                    item1 = getItem('i81');
                                    item2 = getItem('i82');
                                });

                                afterEach(function() {
                                    item = item1 = item2 = null;
                                });

                                it("should have the node, list and parent set", function() {
                                    expect(item1.getNode()).toBe(node.childNodes[0]);
                                    expect(item1.getParentItem()).toBe(item);
                                    expect(item1.getOwner()).toBe(list);

                                    expect(item2.getNode()).toBe(node.childNodes[1]);
                                    expect(item2.getParentItem()).toBe(item);
                                    expect(item2.getOwner()).toBe(list);
                                });

                                it("should call insertItem", function() {
                                    expect(item.logs.insertItem).toEqual([
                                        [item1, null],
                                        [item2, null]
                                    ]);
                                });

                                it("should not call onNodeInsert", function() {
                                    expect(item.logs.onNodeInsert).toEqual([]);
                                });

                                describe("events", function() {
                                    it("should fire iteminsert for the top level item only", function() {
                                        expect(insertSpy.callCount).toBe(1);
                                        expect(insertSpy.mostRecentCall.args[0]).toBe(list);
                                        expect(insertSpy.mostRecentCall.args[1]).toBe(getItem('i1'));
                                        expect(insertSpy.mostRecentCall.args[2]).toBe(getItem('i8'));
                                        expect(insertSpy.mostRecentCall.args[3]).toBeNull();
                                    });
                                });
                            });
                        });

                        describe("node that is expanded", function() {
                            var node;

                            beforeEach(function() {
                                makeCustomList({
                                    defaults: {
                                        testConfig: 200
                                    }
                                });
                                getItem('i4').logs.insertItem = [];
                                node = byId('i4').appendChild({
                                    id: 'i9',
                                    text: 'Item 9'
                                });
                            });

                            afterEach(function() {
                                node = null;
                            });

                            it("should create the item type", function() {
                                var item = getItem('i9');

                                expect(item.xtype).toBe('spec_treelist_customitem');
                            });

                            it("should set the itemConfig", function() {
                                var item = getItem('i9');

                                expect(item.getTestConfig()).toBe(200);
                            });

                            it("should have the node, list and parent set", function() {
                                var item = getItem('i9');

                                expect(item.getNode()).toBe(node);
                                expect(item.getParentItem()).toBe(getItem('i4'));
                                expect(item.getOwner()).toBe(list);
                            });

                            it("should leave the parent as expanded", function() {
                                expect(getItem('i4').isExpanded()).toBe(true);
                            });

                            describe("template methods", function() {
                                it("should call insertItem", function() {
                                    var item = getItem('i4');

                                    expect(item.logs.insertItem).toEqual([[getItem('i9'), null]]);
                                });

                                it("should call onNodeInsert", function() {
                                    var item = getItem('i4');

                                    expect(item.logs.onNodeInsert).toEqual([[byId('i9'), null]]);
                                });
                            });

                            describe("events", function() {
                                it("should fire iteminsert", function() {
                                    expect(insertSpy.callCount).toBe(1);
                                    expect(insertSpy.mostRecentCall.args[0]).toBe(list);
                                    expect(insertSpy.mostRecentCall.args[1]).toBe(getItem('i4'));
                                    expect(insertSpy.mostRecentCall.args[2]).toBe(getItem('i9'));
                                    expect(insertSpy.mostRecentCall.args[3]).toBeNull();
                                });
                            });

                            describe("child nodes", function() {
                                var item, item1, item2;

                                beforeEach(function() {
                                    node = new Model({
                                        id: 'i8'
                                    });

                                    insertSpy.reset();
                                    node.appendChild([{
                                        id: 'i81'
                                    }, {
                                        id: 'i82'
                                    }]);

                                    byId('i4').appendChild(node);

                                    item = getItem('i8');
                                    item1 = getItem('i81');
                                    item2 = getItem('i82');
                                });

                                afterEach(function() {
                                    item = item1 = item2 = null;
                                });

                                it("should have the node, list and parent set", function() {
                                    expect(item1.getNode()).toBe(node.childNodes[0]);
                                    expect(item1.getParentItem()).toBe(item);
                                    expect(item1.getOwner()).toBe(list);

                                    expect(item2.getNode()).toBe(node.childNodes[1]);
                                    expect(item2.getParentItem()).toBe(item);
                                    expect(item2.getOwner()).toBe(list);
                                });

                                it("should call insertItem", function() {
                                    expect(item.logs.insertItem).toEqual([
                                        [item1, null],
                                        [item2, null]
                                    ]);
                                });

                                it("should not call onNodeInsert", function() {
                                    expect(item.logs.onNodeInsert).toEqual([]);
                                });

                                describe("events", function() {
                                    it("should fire iteminsert for the top level item only", function() {
                                        expect(insertSpy.callCount).toBe(1);
                                        expect(insertSpy.mostRecentCall.args[0]).toBe(list);
                                        expect(insertSpy.mostRecentCall.args[1]).toBe(getItem('i4'));
                                        expect(insertSpy.mostRecentCall.args[2]).toBe(getItem('i8'));
                                        expect(insertSpy.mostRecentCall.args[3]).toBeNull();
                                    });
                                });
                            });
                        });
                    });

                    it("should update the expandable state when adding the first node", function() {
                        sampleData[2].children = [];
                        makeCustomList();
                        var item = getItem('i3');

                        expect(item.logs.expandable).toEqual([]);
                        byId('i3').appendChild({
                            id: 'i9',
                            text: 'Item 9'
                        });
                        expect(item.logs.expandable).toEqual([true]);
                    });

                    describe("existing nodes", function() {
                        var existing;

                        afterEach(function() {
                            existing = null;
                        });

                        describe("in the same container", function() {
                            beforeEach(function() {
                                makeCustomList();
                                existing = getItem('i41');
                                getItem('i4').logs.insertItem = [];
                                byId('i4').appendChild(byId('i41'));
                            });

                            it("should use the same item", function() {
                                expect(getItem('i41')).toBe(existing);
                            });

                            it("should use the same el", function() {
                                expect(getItem('i41').el).toBe(existing.el);
                            });

                            it("should set the parent", function() {
                                expect(getItem('i41').getParentItem()).toBe(getItem('i4'));
                            });

                            describe("template methods", function() {
                                it("should call onNodeInsert", function() {
                                    var item = getItem('i4');

                                    expect(item.logs.onNodeInsert).toEqual([[byId('i41'), null]]);
                                });

                                it("should call insertItem", function() {
                                    var item = getItem('i4');

                                    expect(item.logs.insertItem).toEqual([[getItem('i41'), null]]);
                                });

                                it("should call removeItem", function() {
                                    var item = getItem('i4');

                                    expect(item.logs.removeItem).toEqual([getItem('i41')]);
                                });

                                it("should not call onNodeRemove", function() {
                                    var item = getItem('i4');

                                    expect(item.logs.onNodeRemove).toEqual([]);
                                });
                            });

                            describe("events", function() {
                                it("should fire iteminsert", function() {
                                    expect(insertSpy.callCount).toBe(1);
                                    expect(insertSpy.mostRecentCall.args[0]).toBe(list);
                                    expect(insertSpy.mostRecentCall.args[1]).toBe(getItem('i4'));
                                    expect(insertSpy.mostRecentCall.args[2]).toBe(getItem('i41'));
                                    expect(insertSpy.mostRecentCall.args[3]).toBeNull();
                                });

                                it("should not fire itemremove", function() {
                                    expect(removeSpy).not.toHaveBeenCalled();
                                });
                            });
                        });

                        describe("from a different container", function() {
                            beforeEach(function() {
                                sampleData[2].children = [];
                                makeCustomList();
                                existing = getItem('i43');
                                getItem('i1').logs.insertItem = [];
                                byId('i1').appendChild(byId('i43'));
                            });

                            it("should use the same item", function() {
                                expect(getItem('i43')).toBe(existing);
                            });

                            it("should use the same el", function() {
                                expect(getItem('i43').el).toBe(existing.el);
                            });

                            it("should set the parent", function() {
                                expect(getItem('i43').getParentItem()).toBe(getItem('i1'));
                            });

                            it("should update the expandable state when adding the first node and moving the last", function() {
                                var item1 = getItem('i1'),
                                    item3 = getItem('i3');

                                byId('i1').removeChild(byId('i12'));
                                byId('i1').removeChild(byId('i43'));

                                expect(item1.logs.expandable).toEqual([true]);
                                expect(item3.logs.expandable).toEqual([]);
                                byId('i3').appendChild(byId('i11'));

                                expect(item1.logs.expandable).toEqual([true, false]);
                                expect(item3.logs.expandable).toEqual([true]);
                            });

                            describe("template methods", function() {
                                it("should call onNodeInsert", function() {
                                    var item = getItem('i1');

                                    expect(item.logs.onNodeInsert).toEqual([[byId('i43'), null]]);
                                });

                                it("should call insertItem", function() {
                                    var item = getItem('i1');

                                    expect(item.logs.insertItem).toEqual([[getItem('i43'), null]]);
                                });

                                it("should call removeItem", function() {
                                    var item = getItem('i4');

                                    expect(item.logs.removeItem).toEqual([getItem('i43')]);
                                });

                                it("should not call onNodeRemove", function() {
                                    var item = getItem('i4');

                                    expect(item.logs.onNodeRemove).toEqual([]);
                                });
                            });

                            describe("events", function() {
                                it("should fire iteminsert", function() {
                                    expect(insertSpy.callCount).toBe(1);
                                    expect(insertSpy.mostRecentCall.args[0]).toBe(list);
                                    expect(insertSpy.mostRecentCall.args[1]).toBe(getItem('i1'));
                                    expect(insertSpy.mostRecentCall.args[2]).toBe(getItem('i43'));
                                    expect(insertSpy.mostRecentCall.args[3]).toBeNull();
                                });

                                it("should not fire itemremove", function() {
                                    expect(removeSpy).not.toHaveBeenCalled();
                                });
                            });
                        });
                    });
                });
            });

            describe("removing nodes", function() {
                beforeEach(function() {
                    makeCustomList();
                });

                it("should destroy the item", function() {
                    var item = getItem('i3');

                    root.removeChild(byId('i3'));
                    expect(item.destroyed).toBe(true);
                });

                it("should not be accessible via getItem", function() {
                    var node = byId('i3');

                    root.removeChild(node);
                    expect(list.getItem(node)).toBeNull();
                });

                it("should destroy nested items", function() {
                    var item1 = getItem('i41'),
                        item2 = getItem('i42'),
                        item3 = getItem('i43');

                    root.removeChild(byId('i4'));
                    expect(item1.destroyed).toBe(true);
                    expect(item2.destroyed).toBe(true);
                    expect(item3.destroyed).toBe(true);
                });

                it("should call setExpandable: false if removing the last child item", function() {
                    var item = getItem('i4');

                    byId('i4').removeChild(byId('i41'));
                    expect(item.logs.expandable).toEqual([true]);
                    byId('i4').removeChild(byId('i42'));
                    expect(item.logs.expandable).toEqual([true]);
                    byId('i4').removeChild(byId('i43'));
                    expect(item.logs.expandable).toEqual([true, false]);
                });

                describe("template methods", function() {
                    it("should call removeItem", function() {
                        var item = getItem('i4'),
                            oldItem = getItem('i41');

                        byId('i4').removeChild(byId('i41'));
                        expect(item.logs.removeItem).toEqual([oldItem]);
                    });

                    it("should call onNodeRemove", function() {
                        var item = getItem('i4'),
                            node = byId('i41');

                        byId('i4').removeChild(node);
                        expect(item.logs.onNodeRemove).toEqual([node]);
                    });

                    it("should not call template methods for nested children", function() {
                        var item = getItem('i4');

                        root.removeChild(byId('i4'));

                        expect(item.logs.removeItem).toEqual([]);
                        expect(item.logs.onNodeRemove).toEqual([]);
                    });
                });

                describe("events", function() {
                    it("should fire the remove event", function() {
                        var item = getItem('i41');

                        byId('i4').removeChild(item.getNode());
                        expect(removeSpy.callCount).toBe(1);
                        expect(removeSpy.mostRecentCall.args[0]).toBe(list);
                        expect(removeSpy.mostRecentCall.args[1]).toBe(getItem('i4'));
                        expect(removeSpy.mostRecentCall.args[2]).toBe(item);
                    });

                    it("should only for the remove event for the top level item", function() {
                        var item = getItem('i4');

                        root.removeChild(item.getNode());
                        expect(removeSpy.callCount).toBe(1);
                        expect(removeSpy.mostRecentCall.args[0]).toBe(list);
                        expect(removeSpy.mostRecentCall.args[1]).toBe(getItem('root'));
                        expect(removeSpy.mostRecentCall.args[2]).toBe(item);
                    });
                });
            });

            describe("collapse", function() {
                beforeEach(function() {
                    makeCustomList();
                });

                describe("when expanded", function() {
                    it("should call onNodeCollapsed", function() {
                        var item = getItem('i4'),
                            node = byId('i4');

                        expect(item.logs.onNodeCollapse).toEqual([]);
                        node.collapse();
                        expect(item.logs.onNodeCollapse).toEqual([node]);
                    });

                    it("should call setExpanded(false)", function() {
                        var item = getItem('i4'),
                            node = byId('i4');

                        expect(item.logs.expanded).toEqual([true]);
                        node.collapse();
                        expect(item.logs.expanded).toEqual([true, false]);
                    });

                    it("should fire the itemcollapse event", function() {
                        var item = getItem('i4'),
                            node = byId('i4');

                        node.collapse();
                        expect(collapseSpy.callCount).toBe(1);
                        expect(collapseSpy.mostRecentCall.args[0]).toBe(list);
                        expect(collapseSpy.mostRecentCall.args[1]).toBe(item);
                    });
                });

                describe("when collapsed", function() {
                    it("should not call onNodeCollapse", function() {
                        var item = getItem('i1'),
                            node = byId('i1');

                        expect(item.logs.onNodeCollapse).toEqual([]);
                        node.collapse();
                        expect(item.logs.onNodeCollapse).toEqual([]);
                    });

                    it("should not call setExpanded(false)", function() {
                        var item = getItem('i1'),
                            node = byId('i1');

                        expect(item.logs.expanded).toEqual([]);
                        node.collapse();
                        expect(item.logs.expanded).toEqual([]);
                    });

                    it("should not fire the itemcollapse event", function() {
                        var item = getItem('i1'),
                            node = byId('i1');

                        node.collapse();
                        expect(collapseSpy).not.toHaveBeenCalled();
                    });
                });
            });

            describe("expand", function() {
                beforeEach(function() {
                    makeCustomList();
                });

                describe("when collapsed", function() {
                    it("should call onNodeExpand", function() {
                        var item = getItem('i1'),
                            node = byId('i1');

                        expect(item.logs.onNodeExpand).toEqual([]);
                        node.expand();
                        expect(item.logs.onNodeExpand).toEqual([node]);
                    });

                    it("should call setExpanded(true)", function() {
                        var item = getItem('i1'),
                            node = byId('i1');

                        expect(item.logs.expanded).toEqual([]);
                        node.expand();
                        expect(item.logs.expanded).toEqual([true]);
                    });

                    it("should fire the itemexpand event", function() {
                        var item = getItem('i1'),
                            node = byId('i1');

                        node.expand();
                        expect(expandSpy.callCount).toBe(1);
                        expect(expandSpy.mostRecentCall.args[0]).toBe(list);
                        expect(expandSpy.mostRecentCall.args[1]).toBe(item);
                    });

                    describe("loading", function() {
                        beforeEach(function() {
                            MockAjaxManager.addMethods();
                        });

                        afterEach(function() {
                            MockAjaxManager.removeMethods();
                        });

                        function complete(data) {
                            Ext.Ajax.mockComplete({
                                status: 200,
                                responseText: Ext.encode(data)
                            });
                        }

                        it("should set loaded when the node is expanding", function() {
                            var item = getItem('i3');

                            expect(item.logs.loading).toEqual([]);
                            expect(item.getLoading()).toBe(false);

                            byId('i3').expand();

                            expect(item.logs.loading).toEqual([true]);
                            expect(item.getLoading()).toBe(true);

                            complete([]);

                            expect(item.logs.loading).toEqual([true, false]);
                            expect(item.getLoading()).toBe(false);
                        });

                        it("should not fire the itemexpand event until loading completes", function() {
                            var item = getItem('i3');

                            expect(expandSpy).not.toHaveBeenCalled();
                            byId('i3').expand();
                            expect(expandSpy).not.toHaveBeenCalled();
                            complete([]);
                            expect(expandSpy.callCount).toBe(1);
                            expect(expandSpy.mostRecentCall.args[0]).toBe(list);
                            expect(expandSpy.mostRecentCall.args[1]).toBe(item);
                        });
                    });
                });

                describe("when expanded", function() {
                    it("should not call onNodeExpand", function() {
                        var item = getItem('i4'),
                            node = byId('i4');

                        expect(item.logs.onNodeExpand).toEqual([]);
                        node.expand();
                        expect(item.logs.onNodeExpand).toEqual([]);
                    });

                    it("should not call setExpanded(true)", function() {
                        var item = getItem('i4'),
                            node = byId('i4');

                        expect(item.logs.expanded).toEqual([true]);
                        node.expand();
                        expect(item.logs.expanded).toEqual([true]);
                    });

                    it("should not fire the itemexpand event", function() {
                        var item = getItem('i4'),
                            node = byId('i4');

                        node.expand();
                        expect(expandSpy).not.toHaveBeenCalled();
                    });
                });
            });

            describe("updating node fields", function() {
                describe("text", function() {
                    it("should call setText when updating the text with a textProperty", function() {
                        makeCustomList();
                        var item = getItem('i2');

                        expect(item.logs.text).toEqual(['Item 2']);
                        byId('i2').set('text', 'Foo');
                        expect(item.logs.text).toEqual(['Item 2', 'Foo']);
                    });

                    it("should not call setText with no textProperty", function() {
                        makeCustomList({
                            defaults: {
                                textProperty: ''
                            }
                        });
                        var item = getItem('i2');

                        expect(item.logs.text).toEqual([]);
                        byId('i2').set('text', 'Foo');
                        expect(item.logs.text).toEqual([]);
                    });

                    it("should call onNodeUpdate", function() {
                        makeCustomList();
                        var item = getItem('i2'),
                            node = byId('i2');

                        expect(item.logs.onNodeUpdate).toEqual([]);
                        node.set('text', 'Foo');
                        expect(item.logs.onNodeUpdate).toEqual([[node, ['text']]]);
                    });
                });

                describe("iconCls", function() {
                    it("should call setIconCls when updating the iconCls with an iconClsProperty", function() {
                        makeCustomList();
                        var item = getItem('i2');

                        expect(item.logs.iconCls).toEqual([]);
                        byId('i2').set('iconCls', 'foo');
                        expect(item.logs.iconCls).toEqual(['foo']);
                    });

                    it("should not call setIconCls with no iconClsProperty", function() {
                        makeCustomList({
                            defaults: {
                                iconClsProperty: ''
                            }
                        });
                        var item = getItem('i2');

                        expect(item.logs.iconCls).toEqual([]);
                        byId('i2').set('iconCls', 'foo');
                        expect(item.logs.iconCls).toEqual([]);
                    });

                    it("should call onNodeUpdate", function() {
                        makeCustomList();
                        var item = getItem('i2'),
                            node = byId('i2');

                        expect(item.logs.onNodeUpdate).toEqual([]);
                        node.set('iconCls', 'foo');
                        expect(item.logs.onNodeUpdate).toEqual([[node, ['iconCls']]]);
                    });
                });

                describe("expandable", function() {
                    describe("expandable: false", function() {
                        it("should call setExpandable(false)", function() {
                            makeCustomList();
                            var item = getItem('i4');

                            expect(item.logs.expandable).toEqual([true]);
                            byId('i4').set('expandable', false);
                            expect(item.logs.expandable).toEqual([true, false]);
                        });

                        it("should call onNodeUpdate", function() {
                            makeCustomList();
                            var item = getItem('i4'),
                                node = byId('i4');

                            node.set('expandable', false);
                            expect(item.logs.onNodeUpdate).toEqual([[node, ['expandable']]]);
                        });
                    });

                    describe("expandable: true", function() {
                        it("should call setExpandable(true)", function() {
                            sampleData[0].expandable = false;
                            makeCustomList();
                            var item = getItem('i1');

                            expect(item.logs.expandable).toEqual([]);
                            byId('i1').set('expandable', true);
                            expect(item.logs.expandable).toEqual([true]);
                        });

                        it("should call onNodeUpdate", function() {
                            sampleData[0].expandable = false;
                            makeCustomList();
                            var item = getItem('i1'),
                                node = byId('i1');

                            node.set('expandable', true);
                            expect(item.logs.onNodeUpdate).toEqual([[node, ['expandable']]]);
                        });
                    });
                });

                describe("other fields", function() {
                    it("should call onNodeUpdate", function() {
                        makeCustomList();
                        var item = getItem('i1'),
                            node = byId('i1');

                        node.set('customField', 100);
                        expect(item.logs.onNodeUpdate).toEqual([[node, ['customField']]]);
                    });
                });

                it("should call onNodeUpdate when setting multiple fields", function() {
                    makeCustomList();
                    var item = getItem('i1'),
                        node = byId('i1');

                    node.set({
                        customField: 100,
                        text: 'Foo'
                    });
                    expect(item.logs.onNodeUpdate).toEqual([[node, ['customField', 'text']]]);
                });
            });

            // This is essentially the same as setting a new store
            describe("changing the root node", function() {
                describe("cleanup", function() {
                    it("should destroy the old items", function() {
                        sampleData = [{
                            id: 'i1',
                            text: 'Item 1',
                            children: [{
                                id: 'i11',
                                text: 'Item 1.1'
                            }]
                        }, {
                            id: 'i2',
                            text: 'Item 2',
                            expanded: true,
                            children: [{
                                id: 'i21',
                                text: 'Item 2.1'
                            }, {
                                id: 'i22',
                                text: 'Item 2.2'
                            }]
                        }];
                        makeCustomList();
                        var items = [
                            getItem('i1'),
                            getItem('i11'),
                            getItem('i2'),
                            getItem('i21'),
                            getItem('i22')
                        ];

                        store.setRoot({
                            children: []
                        });

                        Ext.Array.forEach(items, function(item) {
                            expect(item.destroyed).toBe(true);
                        });

                        expect(list.getItem('i1')).toBeNull();
                        expect(list.getItem('i11')).toBeNull();
                        expect(list.getItem('i2')).toBeNull();
                        expect(list.getItem('i21')).toBeNull();
                        expect(list.getItem('i22')).toBeNull();
                    });
                });

                describe("adding new items", function() {
                    var newData;

                    beforeEach(function() {
                        newData = [{
                            id: 'j1',
                            text: 'XItem 1',
                            iconCls: 'iconA',
                            children: [{
                                id: 'j11',
                                text: 'XItem 1.1',
                                leaf: true
                            }]
                        }, {
                            id: 'j2',
                            text: 'XItem 2',
                            expanded: true,
                            children: [{
                                id: 'j21',
                                text: 'XItem 2.1',
                                leaf: true
                            }, {
                                id: 'j22',
                                text: 'XItem 2.2'
                            }]
                        }];
                    });

                    afterEach(function() {
                        newData = null;
                    });

                    function makeAndSetRoot(cfg, data) {
                        makeCustomList(cfg);
                        store.setRoot({
                            expanded: true,
                            children: data || newData
                        });
                    }

                    describe("top level nodes", function() {
                        describe("expanded: false, with children", function() {
                            it("should set expanded", function() {
                                makeAndSetRoot();
                                var item = getItem('j1');

                                expect(item.logs.expanded).toEqual([]);
                                expect(item.getExpanded()).toBe(false);
                            });

                            it("should set expandable", function() {
                                makeAndSetRoot();
                                var item = getItem('j1');

                                expect(item.logs.expandable).toEqual([true]);
                                expect(item.getExpandable()).toBe(true);
                            });

                            it("should set leaf", function() {
                                makeAndSetRoot();
                                var item = getItem('j1');

                                expect(item.logs.leaf).toEqual([false]);
                                expect(item.getLeaf()).toBe(false);
                            });

                            it("should set the icon if iconClsProperty is specified", function() {
                                newData[0].iconCls = 'iconA';
                                makeAndSetRoot();
                                var item = getItem('j1');

                                expect(item.logs.iconCls).toEqual(['iconA']);
                                expect(item.getIconCls()).toBe('iconA');
                            });

                            it("should not set the icon if an iconClsProperty is not specified", function() {
                                newData[0].iconCls = 'iconA';
                                makeAndSetRoot({
                                    defaults: {
                                        iconClsProperty: ''
                                    }
                                });
                                var item = getItem('j1');

                                expect(item.logs.iconCls).toEqual([]);
                                expect(item.getIconCls()).toBe('');
                            });

                            it("should set the text if a textProperty is specified", function() {
                                makeAndSetRoot();
                                var item = getItem('j1');

                                expect(item.logs.text).toEqual(['XItem 1']);
                                expect(item.getText()).toBe('XItem 1');
                            });

                            it("should not set the text if an textProperty is not specified", function() {
                                makeAndSetRoot({
                                    defaults: {
                                        textProperty: ''
                                    }
                                });
                                var item = getItem('j1');

                                expect(item.logs.text).toEqual([]);
                                expect(item.getText()).toBe('');
                            });

                            it("should insert the child nodes", function() {
                                makeAndSetRoot();
                                var item = getItem('j1');

                                expect(item.logs.insertItem).toEqual([
                                    [getItem('j11'), null]
                                ]);
                            });

                            it("should not call any template methods", function() {
                                makeAndSetRoot();
                                var item = getItem('j1');

                                expect(item.logs.onNodeCollapse).toEqual([]);
                                expect(item.logs.onNodeExpand).toEqual([]);
                                expect(item.logs.onNodeInsert).toEqual([]);
                                expect(item.logs.onNodeRemove).toEqual([]);
                                expect(item.logs.onNodeUpdate).toEqual([]);
                            });

                            it("should not fire events", function() {
                                expect(insertSpy).not.toHaveBeenCalled();
                                expect(removeSpy).not.toHaveBeenCalled();
                                expect(expandSpy).not.toHaveBeenCalled();
                                expect(collapseSpy).not.toHaveBeenCalled();
                            });

                            it("should have the node, list and parent set", function() {
                                makeAndSetRoot();
                                var item = getItem('j1');

                                expect(item.getNode()).toBe(byId('j1'));
                                expect(item.getParentItem()).toBeNull();
                                expect(item.getOwner()).toBe(list);
                            });

                            it("should have the itemConfig set", function() {
                                makeAndSetRoot({
                                    defaults: {
                                        testConfig: 12
                                    }
                                });
                                var item = getItem('j1');

                                expect(item.getTestConfig()).toBe(12);
                            });
                        });

                        describe("expanded: true, with children", function() {
                            it("should set expanded", function() {
                                makeAndSetRoot();
                                var item = getItem('j2');

                                expect(item.logs.expanded).toEqual([true]);
                                expect(item.getExpanded()).toBe(true);
                            });

                            it("should set expandable", function() {
                                makeAndSetRoot();
                                var item = getItem('j2');

                                expect(item.logs.expandable).toEqual([true]);
                                expect(item.getExpandable()).toBe(true);
                            });

                            it("should set leaf", function() {
                                makeAndSetRoot();
                                var item = getItem('j2');

                                expect(item.logs.leaf).toEqual([false]);
                                expect(item.getLeaf()).toBe(false);
                            });

                            it("should set the icon if iconClsProperty is specified", function() {
                                newData[1].iconCls = 'iconA';
                                makeAndSetRoot();
                                var item = getItem('j2');

                                expect(item.logs.iconCls).toEqual(['iconA']);
                                expect(item.getIconCls()).toBe('iconA');
                            });

                            it("should not set the icon if an iconClsProperty is not specified", function() {
                                newData[1].iconCls = 'iconA';
                                makeAndSetRoot({
                                    defaults: {
                                        iconClsProperty: ''
                                    }
                                });
                                var item = getItem('j2');

                                expect(item.logs.iconCls).toEqual([]);
                                expect(item.getIconCls()).toBe('');
                            });

                            it("should set the text if a textProperty is specified", function() {
                                makeAndSetRoot();
                                var item = getItem('j2');

                                expect(item.logs.text).toEqual(['XItem 2']);
                                expect(item.getText()).toBe('XItem 2');
                            });

                            it("should not set the text if an textProperty is not specified", function() {
                                makeAndSetRoot({
                                    defaults: {
                                        textProperty: ''
                                    }
                                });
                                var item = getItem('j2');

                                expect(item.logs.text).toEqual([]);
                                expect(item.getText()).toBe('');
                            });

                            it("should insert the child nodes", function() {
                                makeAndSetRoot();
                                var item = getItem('j2');

                                expect(item.logs.insertItem).toEqual([
                                    [getItem('j21'), null],
                                    [getItem('j22'), null]
                                ]);
                            });

                            it("should not call any template methods", function() {
                                makeAndSetRoot();
                                var item = getItem('j2');

                                expect(item.logs.onNodeCollapse).toEqual([]);
                                expect(item.logs.onNodeExpand).toEqual([]);
                                expect(item.logs.onNodeInsert).toEqual([]);
                                expect(item.logs.onNodeRemove).toEqual([]);
                                expect(item.logs.onNodeUpdate).toEqual([]);
                            });

                            it("should not fire events", function() {
                                expect(insertSpy).not.toHaveBeenCalled();
                                expect(removeSpy).not.toHaveBeenCalled();
                                expect(expandSpy).not.toHaveBeenCalled();
                                expect(collapseSpy).not.toHaveBeenCalled();
                            });

                            it("should have the node, list and parent set", function() {
                                makeAndSetRoot();
                                var item = getItem('j2');

                                expect(item.getNode()).toBe(byId('j2'));
                                expect(item.getParentItem()).toBeNull();
                                expect(item.getOwner()).toBe(list);
                            });

                            it("should have the itemConfig set", function() {
                                makeAndSetRoot({
                                    defaults: {
                                        testConfig: 12
                                    }
                                });
                                var item = getItem('j2');

                                expect(item.getTestConfig()).toBe(12);
                            });
                        });
                    });

                    describe("child level nodes", function() {
                        describe("parent expanded: false", function() {
                            it("should set expanded", function() {
                                makeAndSetRoot();
                                var item = getItem('j11');

                                expect(item.logs.expanded).toEqual([]);
                                expect(item.getExpanded()).toBe(false);
                            });

                            it("should set expandable", function() {
                                makeAndSetRoot();
                                var item = getItem('j11');

                                expect(item.logs.expandable).toEqual([]);
                                expect(item.getExpandable()).toBe(false);
                            });

                            it("should set leaf", function() {
                                makeAndSetRoot();
                                var item = getItem('j11');

                                expect(item.logs.leaf).toEqual([]);
                                expect(item.getLeaf()).toBe(true);
                            });

                            it("should set the icon if iconClsProperty is specified", function() {
                                newData[0].children[0].iconCls = 'iconB';
                                makeAndSetRoot();
                                var item = getItem('j11');

                                expect(item.logs.iconCls).toEqual(['iconB']);
                                expect(item.getIconCls()).toBe('iconB');
                            });

                            it("should not set the icon if an iconClsProperty is not specified", function() {
                                newData[0].children[0].iconCls = 'iconB';
                                makeAndSetRoot({
                                    defaults: {
                                        iconClsProperty: ''
                                    }
                                });
                                var item = getItem('j11');

                                expect(item.logs.iconCls).toEqual([]);
                                expect(item.getIconCls()).toBe('');
                            });

                            it("should set the text if a textProperty is specified", function() {
                                makeAndSetRoot();
                                var item = getItem('j11');

                                expect(item.logs.text).toEqual(['XItem 1.1']);
                                expect(item.getText()).toBe('XItem 1.1');
                            });

                            it("should not set the text if an textProperty is not specified", function() {
                                makeAndSetRoot({
                                    defaults: {
                                        textProperty: ''
                                    }
                                });
                                var item = getItem('j11');

                                expect(item.logs.text).toEqual([]);
                                expect(item.getText()).toBe('');
                            });

                            it("should not call any template methods", function() {
                                makeAndSetRoot();
                                var item = getItem('j11');

                                expect(item.logs.onNodeCollapse).toEqual([]);
                                expect(item.logs.onNodeExpand).toEqual([]);
                                expect(item.logs.onNodeInsert).toEqual([]);
                                expect(item.logs.onNodeRemove).toEqual([]);
                                expect(item.logs.onNodeUpdate).toEqual([]);
                            });

                            it("should not fire events", function() {
                                expect(insertSpy).not.toHaveBeenCalled();
                                expect(removeSpy).not.toHaveBeenCalled();
                                expect(expandSpy).not.toHaveBeenCalled();
                                expect(collapseSpy).not.toHaveBeenCalled();
                            });

                            it("should have the node, list and parent set", function() {
                                makeAndSetRoot();
                                var item = getItem('j11');

                                expect(item.getNode()).toBe(byId('j11'));
                                expect(item.getParentItem()).toBe(getItem('j1'));
                                expect(item.getOwner()).toBe(list);
                            });

                            it("should have the itemConfig set", function() {
                                makeAndSetRoot({
                                    defaults: {
                                        testConfig: 12
                                    }
                                });
                                var item = getItem('j11');

                                expect(item.getTestConfig()).toBe(12);
                            });
                        });

                        describe("parent expanded: true", function() {
                            it("should set expanded", function() {
                                makeAndSetRoot();
                                var item1 = getItem('j21'),
                                    item2 = getItem('j22');

                                expect(item1.logs.expanded).toEqual([]);
                                expect(item2.logs.expanded).toEqual([]);

                                expect(item1.getExpanded()).toBe(false);
                                expect(item2.getExpanded()).toBe(false);
                            });

                            it("should set expandable", function() {
                                makeAndSetRoot();
                                var item1 = getItem('j21'),
                                    item2 = getItem('j22');

                                expect(item1.logs.expandable).toEqual([]);
                                expect(item2.logs.expandable).toEqual([true]);

                                expect(item1.getExpandable()).toBe(false);
                                expect(item2.getExpandable()).toBe(true);
                            });

                            it("should set leaf", function() {
                                makeAndSetRoot();
                                var item1 = getItem('j21'),
                                    item2 = getItem('j22');

                                expect(item1.logs.leaf).toEqual([]);
                                expect(item2.logs.leaf).toEqual([false]);

                                expect(item1.getLeaf()).toBe(true);
                                expect(item2.getLeaf()).toBe(false);
                            });

                            it("should set the icon if iconClsProperty is specified", function() {
                                newData[1].children[0].iconCls = 'iconB';
                                newData[1].children[1].iconCls = 'iconC';
                                makeAndSetRoot();
                                var item1 = getItem('j21'),
                                    item2 = getItem('j22');

                                expect(item1.logs.iconCls).toEqual(['iconB']);
                                expect(item2.logs.iconCls).toEqual(['iconC']);

                                expect(item1.getIconCls()).toBe('iconB');
                                expect(item2.getIconCls()).toBe('iconC');
                            });

                            it("should not set the icon if an iconClsProperty is not specified", function() {
                                newData[1].children[0].iconCls = 'iconB';
                                newData[1].children[1].iconCls = 'iconC';
                                makeAndSetRoot({
                                    defaults: {
                                        iconClsProperty: ''
                                    }
                                });
                                var item1 = getItem('j21'),
                                    item2 = getItem('j22');

                                expect(item1.logs.iconCls).toEqual([]);
                                expect(item2.logs.iconCls).toEqual([]);

                                expect(item1.getIconCls()).toBe('');
                                expect(item2.getIconCls()).toBe('');
                            });

                            it("should set the text if a textProperty is specified", function() {
                                makeAndSetRoot();
                                var item1 = getItem('j21'),
                                    item2 = getItem('j22');

                                expect(item1.logs.text).toEqual(['XItem 2.1']);
                                expect(item2.logs.text).toEqual(['XItem 2.2']);

                                expect(item1.getText()).toBe('XItem 2.1');
                                expect(item2.getText()).toBe('XItem 2.2');
                            });

                            it("should not set the text if an textProperty is not specified", function() {
                                makeAndSetRoot({
                                    defaults: {
                                        textProperty: ''
                                    }
                                });
                                var item1 = getItem('j21'),
                                    item2 = getItem('j22');

                                expect(item1.logs.text).toEqual([]);
                                expect(item2.logs.text).toEqual([]);

                                expect(item1.getText()).toBe('');
                                expect(item2.getText()).toBe('');
                            });

                            it("should not call any template methods", function() {
                                makeAndSetRoot();
                                var item1 = getItem('j21'),
                                    item2 = getItem('j22');

                                expect(item1.logs.onNodeCollapse).toEqual([]);
                                expect(item1.logs.onNodeExpand).toEqual([]);
                                expect(item1.logs.onNodeInsert).toEqual([]);
                                expect(item1.logs.onNodeRemove).toEqual([]);
                                expect(item1.logs.onNodeUpdate).toEqual([]);

                                expect(item2.logs.onNodeCollapse).toEqual([]);
                                expect(item2.logs.onNodeExpand).toEqual([]);
                                expect(item2.logs.onNodeInsert).toEqual([]);
                                expect(item2.logs.onNodeRemove).toEqual([]);
                                expect(item2.logs.onNodeUpdate).toEqual([]);
                            });

                            it("should not fire events", function() {
                                expect(insertSpy).not.toHaveBeenCalled();
                                expect(removeSpy).not.toHaveBeenCalled();
                                expect(expandSpy).not.toHaveBeenCalled();
                                expect(collapseSpy).not.toHaveBeenCalled();
                            });

                            it("should have the node, list and parent set", function() {
                                makeAndSetRoot();
                                var item1 = getItem('j21'),
                                    item2 = getItem('j22');

                                expect(item1.getNode()).toBe(byId('j21'));
                                expect(item1.getParentItem()).toBe(getItem('j2'));
                                expect(item1.getOwner()).toBe(list);

                                expect(item2.getNode()).toBe(byId('j22'));
                                expect(item2.getParentItem()).toBe(getItem('j2'));
                                expect(item2.getOwner()).toBe(list);
                            });

                            it("should have the itemConfig set", function() {
                                makeAndSetRoot({
                                    defaults: {
                                        testConfig: 12
                                    }
                                });
                                var item1 = getItem('j21'),
                                    item2 = getItem('j22');

                                expect(item1.getTestConfig()).toBe(12);
                                expect(item2.getTestConfig()).toBe(12);
                            });
                        });
                    });
                });
            });

            // Essentially the same as setting a new root node
            describe("setting a new store", function() {
                describe("cleanup", function() {
                    it("should destroy the old items", function() {
                        sampleData = [{
                            id: 'i1',
                            text: 'Item 1',
                            children: [{
                                id: 'i11',
                                text: 'Item 1.1'
                            }]
                        }, {
                            id: 'i2',
                            text: 'Item 2',
                            expanded: true,
                            children: [{
                                id: 'i21',
                                text: 'Item 2.1'
                            }, {
                                id: 'i22',
                                text: 'Item 2.2'
                            }]
                        }];
                        makeCustomList();
                        var items = [
                            getItem('i1'),
                            getItem('i11'),
                            getItem('i2'),
                            getItem('i21'),
                            getItem('i22')
                        ];

                        list.setStore({
                            root: {
                                children: []
                            }
                        });

                        Ext.Array.forEach(items, function(item) {
                            expect(item.destroyed).toBe(true);
                        });

                        expect(list.getItem('i1')).toBeNull();
                        expect(list.getItem('i11')).toBeNull();
                        expect(list.getItem('i2')).toBeNull();
                        expect(list.getItem('i21')).toBeNull();
                        expect(list.getItem('i22')).toBeNull();
                    });
                });

                describe("adding new items", function() {
                    var newData;

                    beforeEach(function() {
                        newData = [{
                            id: 'j1',
                            text: 'XItem 1',
                            iconCls: 'iconA',
                            children: [{
                                id: 'j11',
                                text: 'XItem 1.1',
                                leaf: true
                            }]
                        }, {
                            id: 'j2',
                            text: 'XItem 2',
                            expanded: true,
                            children: [{
                                id: 'j21',
                                text: 'XItem 2.1',
                                leaf: true
                            }, {
                                id: 'j22',
                                text: 'XItem 2.2'
                            }]
                        }];
                    });

                    afterEach(function() {
                        newData = null;
                    });

                    function makeAndSetStore(cfg, data) {
                        makeCustomList(cfg);
                        list.getStore().setAutoDestroy(true);
                        list.setStore({
                            root: {
                                expanded: true,
                                children: data || newData
                            }
                        });
                        store = list.getStore();
                    }

                    describe("top level nodes", function() {
                        describe("expanded: false, with children", function() {
                            it("should set expanded", function() {
                                makeAndSetStore();
                                var item = getItem('j1');

                                expect(item.logs.expanded).toEqual([]);
                                expect(item.getExpanded()).toBe(false);
                            });

                            it("should set expandable", function() {
                                makeAndSetStore();
                                var item = getItem('j1');

                                expect(item.logs.expandable).toEqual([true]);
                                expect(item.getExpandable()).toBe(true);
                            });

                            it("should set leaf", function() {
                                makeAndSetStore();
                                var item = getItem('j1');

                                expect(item.logs.leaf).toEqual([false]);
                                expect(item.getLeaf()).toBe(false);
                            });

                            it("should set the icon if iconClsProperty is specified", function() {
                                newData[0].iconCls = 'iconA';
                                makeAndSetStore();
                                var item = getItem('j1');

                                expect(item.logs.iconCls).toEqual(['iconA']);
                                expect(item.getIconCls()).toBe('iconA');
                            });

                            it("should not set the icon if an iconClsProperty is not specified", function() {
                                newData[0].iconCls = 'iconA';
                                makeAndSetStore({
                                    defaults: {
                                        iconClsProperty: ''
                                    }
                                });
                                var item = getItem('j1');

                                expect(item.logs.iconCls).toEqual([]);
                                expect(item.getIconCls()).toBe('');
                            });

                            it("should set the text if a textProperty is specified", function() {
                                makeAndSetStore();
                                var item = getItem('j1');

                                expect(item.logs.text).toEqual(['XItem 1']);
                                expect(item.getText()).toBe('XItem 1');
                            });

                            it("should not set the text if an textProperty is not specified", function() {
                                makeAndSetStore({
                                    defaults: {
                                        textProperty: ''
                                    }
                                });
                                var item = getItem('j1');

                                expect(item.logs.text).toEqual([]);
                                expect(item.getText()).toBe('');
                            });

                            it("should insert the child nodes", function() {
                                makeAndSetStore();
                                var item = getItem('j1');

                                expect(item.logs.insertItem).toEqual([
                                    [getItem('j11'), null]
                                ]);
                            });

                            it("should not call any template methods", function() {
                                makeAndSetStore();
                                var item = getItem('j1');

                                expect(item.logs.onNodeCollapse).toEqual([]);
                                expect(item.logs.onNodeExpand).toEqual([]);
                                expect(item.logs.onNodeInsert).toEqual([]);
                                expect(item.logs.onNodeRemove).toEqual([]);
                                expect(item.logs.onNodeUpdate).toEqual([]);
                            });

                            it("should not fire events", function() {
                                expect(insertSpy).not.toHaveBeenCalled();
                                expect(removeSpy).not.toHaveBeenCalled();
                                expect(expandSpy).not.toHaveBeenCalled();
                                expect(collapseSpy).not.toHaveBeenCalled();
                            });

                            it("should have the node, list and parent set", function() {
                                makeAndSetStore();
                                var item = getItem('j1');

                                expect(item.getNode()).toBe(byId('j1'));
                                expect(item.getParentItem()).toBeNull();
                                expect(item.getOwner()).toBe(list);
                            });

                            it("should have the itemConfig set", function() {
                                makeAndSetStore({
                                    defaults: {
                                        testConfig: 12
                                    }
                                });
                                var item = getItem('j1');

                                expect(item.getTestConfig()).toBe(12);
                            });
                        });

                        describe("expanded: true, with children", function() {
                            it("should be set expanded", function() {
                                makeAndSetStore();
                                var item = getItem('j2');

                                expect(item.logs.expanded).toEqual([true]);
                                expect(item.getExpanded()).toBe(true);
                            });

                            it("should set expandable", function() {
                                makeAndSetStore();
                                var item = getItem('j2');

                                expect(item.logs.expandable).toEqual([true]);
                                expect(item.getExpandable()).toBe(true);
                            });

                            it("should set leaf", function() {
                                makeAndSetStore();
                                var item = getItem('j2');

                                expect(item.logs.leaf).toEqual([false]);
                                expect(item.getLeaf()).toBe(false);
                            });

                            it("should set the icon if iconClsProperty is specified", function() {
                                newData[1].iconCls = 'iconA';
                                makeAndSetStore();
                                var item = getItem('j2');

                                expect(item.logs.iconCls).toEqual(['iconA']);
                                expect(item.getIconCls()).toBe('iconA');
                            });

                            it("should not set the icon if an iconClsProperty is not specified", function() {
                                newData[1].iconCls = 'iconA';
                                makeAndSetStore({
                                    defaults: {
                                        iconClsProperty: ''
                                    }
                                });
                                var item = getItem('j2');

                                expect(item.logs.iconCls).toEqual([]);
                                expect(item.getIconCls()).toBe('');
                            });

                            it("should set the text if a textProperty is specified", function() {
                                makeAndSetStore();
                                var item = getItem('j2');

                                expect(item.logs.text).toEqual(['XItem 2']);
                                expect(item.getText()).toBe('XItem 2');
                            });

                            it("should not set the text if an textProperty is not specified", function() {
                                makeAndSetStore({
                                    defaults: {
                                        textProperty: ''
                                    }
                                });
                                var item = getItem('j2');

                                expect(item.logs.text).toEqual([]);
                                expect(item.getText()).toBe('');
                            });

                            it("should insert the child nodes", function() {
                                makeAndSetStore();
                                var item = getItem('j2');

                                expect(item.logs.insertItem).toEqual([
                                    [getItem('j21'), null],
                                    [getItem('j22'), null]
                                ]);
                            });

                            it("should not call any template methods", function() {
                                makeAndSetStore();
                                var item = getItem('j2');

                                expect(item.logs.onNodeCollapse).toEqual([]);
                                expect(item.logs.onNodeExpand).toEqual([]);
                                expect(item.logs.onNodeInsert).toEqual([]);
                                expect(item.logs.onNodeRemove).toEqual([]);
                                expect(item.logs.onNodeUpdate).toEqual([]);
                            });

                            it("should not fire events", function() {
                                expect(insertSpy).not.toHaveBeenCalled();
                                expect(removeSpy).not.toHaveBeenCalled();
                                expect(expandSpy).not.toHaveBeenCalled();
                                expect(collapseSpy).not.toHaveBeenCalled();
                            });

                            it("should have the node, list and parent set", function() {
                                makeAndSetStore();
                                var item = getItem('j2');

                                expect(item.getNode()).toBe(byId('j2'));
                                expect(item.getParentItem()).toBeNull();
                                expect(item.getOwner()).toBe(list);
                            });

                            it("should have the itemConfig set", function() {
                                makeAndSetStore({
                                    defaults: {
                                        testConfig: 12
                                    }
                                });
                                var item = getItem('j2');

                                expect(item.getTestConfig()).toBe(12);
                            });
                        });
                    });

                    describe("child level nodes", function() {
                        describe("parent expanded: false", function() {
                            it("should set expanded", function() {
                                makeAndSetStore();
                                var item = getItem('j11');

                                expect(item.logs.expanded).toEqual([]);
                                expect(item.getExpanded()).toBe(false);
                            });

                            it("should set expandable", function() {
                                makeAndSetStore();
                                var item = getItem('j11');

                                expect(item.logs.expandable).toEqual([]);
                                expect(item.getExpandable()).toBe(false);
                            });

                            it("should set leaf", function() {
                                makeAndSetStore();
                                var item = getItem('j11');

                                expect(item.logs.leaf).toEqual([]);
                                expect(item.getLeaf()).toBe(true);
                            });

                            it("should set the icon if iconClsProperty is specified", function() {
                                newData[0].children[0].iconCls = 'iconB';
                                makeAndSetStore();
                                var item = getItem('j11');

                                expect(item.logs.iconCls).toEqual(['iconB']);
                                expect(item.getIconCls()).toBe('iconB');
                            });

                            it("should not set the icon if an iconClsProperty is not specified", function() {
                                newData[0].children[0].iconCls = 'iconB';
                                makeAndSetStore({
                                    defaults: {
                                        iconClsProperty: ''
                                    }
                                });
                                var item = getItem('j11');

                                expect(item.logs.iconCls).toEqual([]);
                                expect(item.getIconCls()).toBe('');
                            });

                            it("should set the text if a textProperty is specified", function() {
                                makeAndSetStore();
                                var item = getItem('j11');

                                expect(item.logs.text).toEqual(['XItem 1.1']);
                                expect(item.getText()).toBe('XItem 1.1');
                            });

                            it("should not set the text if an textProperty is not specified", function() {
                                makeAndSetStore({
                                    defaults: {
                                        textProperty: ''
                                    }
                                });
                                var item = getItem('j11');

                                expect(item.logs.text).toEqual([]);
                                expect(item.getText()).toBe('');
                            });

                            it("should not call any template methods", function() {
                                makeAndSetStore();
                                var item = getItem('j11');

                                expect(item.logs.onNodeCollapse).toEqual([]);
                                expect(item.logs.onNodeExpand).toEqual([]);
                                expect(item.logs.onNodeInsert).toEqual([]);
                                expect(item.logs.onNodeRemove).toEqual([]);
                                expect(item.logs.onNodeUpdate).toEqual([]);
                            });

                            it("should not fire events", function() {
                                expect(insertSpy).not.toHaveBeenCalled();
                                expect(removeSpy).not.toHaveBeenCalled();
                                expect(expandSpy).not.toHaveBeenCalled();
                                expect(collapseSpy).not.toHaveBeenCalled();
                            });

                            it("should have the node, list and parent set", function() {
                                makeAndSetStore();
                                var item = getItem('j11');

                                expect(item.getNode()).toBe(byId('j11'));
                                expect(item.getParentItem()).toBe(getItem('j1'));
                                expect(item.getOwner()).toBe(list);
                            });

                            it("should have the itemConfig set", function() {
                                makeAndSetStore({
                                    defaults: {
                                        testConfig: 12
                                    }
                                });
                                var item = getItem('j11');

                                expect(item.getTestConfig()).toBe(12);
                            });
                        });

                        describe("parent expanded: true", function() {
                            it("should set expanded", function() {
                                makeAndSetStore();
                                var item1 = getItem('j21'),
                                    item2 = getItem('j22');

                                expect(item1.logs.expanded).toEqual([]);
                                expect(item2.logs.expanded).toEqual([]);

                                expect(item1.getExpanded()).toBe(false);
                                expect(item2.getExpanded()).toBe(false);
                            });

                            it("should set expandable", function() {
                                makeAndSetStore();
                                var item1 = getItem('j21'),
                                    item2 = getItem('j22');

                                expect(item1.logs.expandable).toEqual([]);
                                expect(item2.logs.expandable).toEqual([true]);

                                expect(item1.getExpandable()).toBe(false);
                                expect(item2.getExpandable()).toBe(true);
                            });

                            it("should set leaf", function() {
                                makeAndSetStore();
                                var item1 = getItem('j21'),
                                    item2 = getItem('j22');

                                expect(item1.logs.leaf).toEqual([]);
                                expect(item2.logs.leaf).toEqual([false]);

                                expect(item1.getLeaf()).toBe(true);
                                expect(item2.getLeaf()).toBe(false);
                            });

                            it("should set the icon if iconClsProperty is specified", function() {
                                newData[1].children[0].iconCls = 'iconB';
                                newData[1].children[1].iconCls = 'iconC';
                                makeAndSetStore();
                                var item1 = getItem('j21'),
                                    item2 = getItem('j22');

                                expect(item1.logs.iconCls).toEqual(['iconB']);
                                expect(item2.logs.iconCls).toEqual(['iconC']);

                                expect(item1.getIconCls()).toBe('iconB');
                                expect(item2.getIconCls()).toBe('iconC');
                            });

                            it("should not set the icon if an iconClsProperty is not specified", function() {
                                newData[1].children[0].iconCls = 'iconB';
                                newData[1].children[1].iconCls = 'iconC';
                                makeAndSetStore({
                                    defaults: {
                                        iconClsProperty: ''
                                    }
                                });
                                var item1 = getItem('j21'),
                                    item2 = getItem('j22');

                                expect(item1.logs.iconCls).toEqual([]);
                                expect(item2.logs.iconCls).toEqual([]);

                                expect(item1.getIconCls()).toBe('');
                                expect(item2.getIconCls()).toBe('');
                            });

                            it("should set the text if a textProperty is specified", function() {
                                makeAndSetStore();
                                var item1 = getItem('j21'),
                                    item2 = getItem('j22');

                                expect(item1.logs.text).toEqual(['XItem 2.1']);
                                expect(item2.logs.text).toEqual(['XItem 2.2']);

                                expect(item1.getText()).toBe('XItem 2.1');
                                expect(item2.getText()).toBe('XItem 2.2');
                            });

                            it("should not set the text if an textProperty is not specified", function() {
                                makeAndSetStore({
                                    defaults: {
                                        textProperty: ''
                                    }
                                });
                                var item1 = getItem('j21'),
                                    item2 = getItem('j22');

                                expect(item1.logs.text).toEqual([]);
                                expect(item2.logs.text).toEqual([]);

                                expect(item1.getText()).toBe('');
                                expect(item2.getText()).toBe('');
                            });

                            it("should not call any template methods", function() {
                                makeAndSetStore();
                                var item1 = getItem('j21'),
                                    item2 = getItem('j22');

                                expect(item1.logs.onNodeCollapse).toEqual([]);
                                expect(item1.logs.onNodeExpand).toEqual([]);
                                expect(item1.logs.onNodeInsert).toEqual([]);
                                expect(item1.logs.onNodeRemove).toEqual([]);
                                expect(item1.logs.onNodeUpdate).toEqual([]);

                                expect(item2.logs.onNodeCollapse).toEqual([]);
                                expect(item2.logs.onNodeExpand).toEqual([]);
                                expect(item2.logs.onNodeInsert).toEqual([]);
                                expect(item2.logs.onNodeRemove).toEqual([]);
                                expect(item2.logs.onNodeUpdate).toEqual([]);
                            });

                            it("should not fire events", function() {
                                expect(insertSpy).not.toHaveBeenCalled();
                                expect(removeSpy).not.toHaveBeenCalled();
                                expect(expandSpy).not.toHaveBeenCalled();
                                expect(collapseSpy).not.toHaveBeenCalled();
                            });

                            it("should have the node, list and parent set", function() {
                                makeAndSetStore();
                                var item1 = getItem('j21'),
                                    item2 = getItem('j22');

                                expect(item1.getNode()).toBe(byId('j21'));
                                expect(item1.getParentItem()).toBe(getItem('j2'));
                                expect(item1.getOwner()).toBe(list);

                                expect(item2.getNode()).toBe(byId('j22'));
                                expect(item2.getParentItem()).toBe(getItem('j2'));
                                expect(item2.getOwner()).toBe(list);
                            });

                            it("should have the itemConfig set", function() {
                                makeAndSetStore({
                                    defaults: {
                                        testConfig: 12
                                    }
                                });
                                var item1 = getItem('j21'),
                                    item2 = getItem('j22');

                                expect(item1.getTestConfig()).toBe(12);
                                expect(item2.getTestConfig()).toBe(12);
                            });
                        });
                    });
                });
            });
        });

        describe("micro mode", function() {
            describe("at construction", function() {
                it("should have root level items in the toolElement", function() {
                    makeCustomList({
                        micro: true
                    });
                    var toolNodes = list.toolsElement.dom.childNodes;

                    expect(toolNodes[0]).toBe(getItem('i1').getToolElement().dom);
                    expect(toolNodes[1]).toBe(getItem('i2').getToolElement().dom);
                    expect(toolNodes[2]).toBe(getItem('i3').getToolElement().dom);
                    expect(toolNodes[3]).toBe(getItem('i4').getToolElement().dom);
                });

                it("should be empty if there is no data", function() {
                    store = new Ext.data.TreeStore({
                        model: Model,
                        root: {
                            expanded: true,
                            children: []
                        }
                    });
                    makeCustomList({
                        micro: true
                    });
                    expect(list.toolsElement.dom.childNodes.length).toBe(0);
                });
            });

            describe("dynamic", function() {
                describe("starting empty", function() {
                    it("should add nodes", function() {
                        store = new Ext.data.TreeStore({
                            model: Model,
                            root: {
                                expanded: true,
                                children: []
                            }
                        });
                        makeCustomList({
                            micro: true
                        });
                        store.getRoot().appendChild({
                            id: 'foo'
                        });
                        var toolNodes = list.toolsElement.dom.childNodes;

                        expect(toolNodes.length).toBe(1);
                        expect(toolNodes[0]).toBe(getItem('foo').getToolElement().dom);
                    });
                });

                describe("starting with nodes", function() {
                    beforeEach(function() {
                        makeCustomList({
                            micro: true
                        });
                    });

                    it("should handle appending", function() {
                        store.getRoot().appendChild({
                            id: 'foo'
                        });
                        var toolNodes = list.toolsElement.dom.childNodes;

                        expect(toolNodes.length).toBe(6);
                        expect(toolNodes[5]).toBe(getItem('foo').getToolElement().dom);
                    });

                    it("should handle insertion", function() {
                        store.getRoot().insertChild(0, {
                            id: 'foo'
                        });

                        var toolNodes = list.toolsElement.dom.childNodes;

                        expect(toolNodes.length).toBe(6);
                        expect(toolNodes[0]).toBe(getItem('foo').getToolElement().dom);

                        store.getRoot().insertChild(2, {
                            id: 'foo'
                        });

                        expect(toolNodes.length).toBe(7);
                        expect(toolNodes[2]).toBe(getItem('foo').getToolElement().dom);
                    });

                    it("should handle removal", function() {
                        var root = store.getRoot();

                        root.removeChild(root.getChildAt(1));

                        var toolNodes = list.toolsElement.dom.childNodes;

                        expect(toolNodes.length).toBe(4);

                        expect(toolNodes[0]).toBe(getItem('i1').getToolElement().dom);
                        expect(toolNodes[1]).toBe(getItem('i3').getToolElement().dom);
                        expect(toolNodes[2]).toBe(getItem('i4').getToolElement().dom);
                        expect(toolNodes[3]).toBe(getItem('i5').getToolElement().dom);
                    });
                });
            });
        });
    });

    describe("micro mode", function() {
        it("should default to micro: false", function() {
            makeList();
            expect(list.getMicro()).toBe(false);
        });

        describe("at construction", function() {
            describe("starting as micro: true", function() {
                var itNotTouch = Ext.supports.Touch ? xit : it;

                beforeEach(function() {
                    makeList({
                        micro: true
                    });
                    Ext.event.publisher.Dom.instance.reset();
                });

                it("should have the microCls", function() {
                    expect(list.element).toHaveCls(list.microCls);
                });

                it("should have the toolsElement be visible", function() {
                    expect(list.toolsElement.isVisible()).toBe(true);
                });

                // https://sencha.jira.com/browse/EXTJS-20210
                itNotTouch('should hide the icon on float', function() {
                    var rec0 = store.getAt(0),
                        item0 = list.getItem(rec0);

                    // Icon element begins visible
                    expect(item0.iconElement.isVisible()).toBe(true);

                    jasmine.fireMouseEvent(item0.toolElement, 'mouseover');

                    // When floated, it should be hidden
                    expect(item0.iconElement.isVisible()).toBe(false);
                });

                // https://sencha.jira.com/browse/EXTJS-27536
                // This test case is written only to satify the above mentioned JIRA ticket
                itNotTouch('should not throw error on leaf node click', function() {
                    var rec0 = store.getAt(0),
                        leafRec0 = rec0.childNodes[0],
                        item0 = list.getItem(rec0),
                        leafItem0 = list.getItem(leafRec0);

                    jasmine.fireMouseEvent(item0.toolElement, 'mouseover');

                    expect(leafItem0.el.isVisible()).toBe(true);

                    runs(function() {
                        expect(function() {
                            jasmine.fireMouseEvent(leafItem0.el, 'click');
                        }).not.toThrow();
                     });
                 });
            });

            describe("starting micro: false", function() {
                beforeEach(function() {
                    makeList({
                        micro: false
                    });
                });

                it("should not have the microCls", function() {
                    expect(list.element).not.toHaveCls(list.microCls);
                });

                it("should have the toolsElement be not visible", function() {
                    expect(list.toolsElement.isVisible()).toBe(false);
                });
            });
        });

        describe("dynamic", function() {
            describe("starting as micro: true", function() {
                beforeEach(function() {
                    makeList({
                        micro: true
                    });
                    list.setMicro(false);
                });

                it("should remove the microCls", function() {
                    expect(list.element).not.toHaveCls(list.microCls);
                });

                it("should have the toolsElement be not visible", function() {
                    expect(list.toolsElement.isVisible()).toBe(false);
                });
            });

            describe("starting micro: false", function() {
                beforeEach(function() {
                    makeList({
                        micro: false
                    });
                    list.setMicro(true);
                });

                it("should add the microCls", function() {
                    expect(list.element).toHaveCls(list.microCls);
                });

                it("should have the toolsElement be visible", function() {
                    expect(list.toolsElement.isVisible()).toBe(true);
                });
            });
        });

        describe('menu', function() {
            beforeEach(function() {
                makeList({
                    micro: true
                });
            });

            function makeShowMenuSpecs(event) {
                describe(event, function() {
                    var isClick = event === 'click';

                    it('should show menu of items', function() {
                        var node = byId('i1'),
                            item = list.getItem(node);

                        jasmine.fireMouseEvent(item.toolElement, event);

                        expect(list.activeFloater).toBe(item);
                        expect(item.getFloated()).toBe(true);
                    });

                    it('should ' + (isClick ? 'not ' : '') + 'show menu for leaf node', function() {
                        var node = byId('i5'),
                            item = list.getItem(node);

                        jasmine.fireMouseEvent(item.toolElement, event);

                        if (isClick) {
                            expect(list.activeFloater).toBeFalsy();
                            expect(item.getFloated()).toBeFalsy();
                        }
                        else {
                            expect(list.activeFloater).toBeTruthy();
                            expect(item.getFloated()).toBe(true);
                        }
                    });

                    if (isClick) {
                        // only click event can prevent floating leaf items
                        it('should show menu for leaf node', function() {
                            var node = byId('i5'),
                                item = list.getItem(node);

                            list.setFloatLeafItems(true);

                            jasmine.fireMouseEvent(item.toolElement, event);

                            expect(list.activeFloater).toBeTruthy();
                            expect(item.getFloated()).toBe(true);
                        });
                    }
                });
            }

            makeShowMenuSpecs('click');

            if (!jasmine.supportsTouch) {
                makeShowMenuSpecs('mouseover');
            }
        });
    });

    describe("list methods", function() {
        describe("getItem", function() {
            beforeEach(function() {
                makeList();
            });

            it("should return the item matching the node", function() {
                var node = byId('i2'),
                    item = list.getItem(node);

                expect(item.getNode()).toBe(node);
                expect(item.xtype).toBe(list.getDefaults().xtype);
            });

            it("should return null if the item does not exist", function() {
                var node = new Model(),
                    item = list.getItem(node);

                expect(list.getItem(node)).toBeNull();
            });

            it("should return null if no node is passed", function() {
                expect(list.getItem(null)).toBeNull();
            });

            it("should return null after an item was removed", function() {
                var node = byId('i4');

                root.removeChild(node);
                expect(list.getItem(node)).toBeNull();
            });

            it("should return null for a child when the parent was removed", function() {
                var node = byId('i41');

                root.removeChild(byId('i4'));
                expect(list.getItem(node)).toBeNull();
            });

            it("should return newly added items", function() {
                var node = root.appendChild({
                        id: 'i9'
                    }),
                    item = list.getItem(node);

                expect(item.getNode()).toBe(node);
                expect(item.xtype).toBe(list.getDefaults().xtype);
            });

            it("should return children of newly added items", function() {
                var node = root.appendChild({
                        id: 'i9',
                        children: [{
                            id: 'i91'
                        }]
                    }),
                    item = list.getItem(node.firstChild);

                expect(item.getNode()).toBe(node.firstChild);
                expect(item.xtype).toBe(list.getDefaults().xtype);
            });
        });
    });

    describe("item methods", function() {
        describe("expand", function() {
            it("should call through to the node expand", function() {
                makeList();
                var node = byId('i1');

                spyOn(node, 'expand');

                getItem('i1').expand();
                expect(node.expand.callCount).toBe(1);
            });
        });

        describe("collapse", function() {
            it("should call through to the node collapse", function() {
                makeList();
                var node = byId('i1');

                spyOn(node, 'collapse');

                getItem('i1').collapse();
                expect(node.collapse.callCount).toBe(1);
            });
        });

        describe("isExpanded", function() {
            it("should return true if the node is expanded", function() {
                makeList();
                expect(getItem('i4').isExpanded()).toBe(true);
            });

            it("should return false if the node is collapsed", function() {
                makeList();
                expect(getItem('i1').isExpanded()).toBe(false);
            });

            it("should return false if the node is a leaf", function() {
                sampleData[2].leaf = true;
                makeList();
                expect(getItem('i3').isExpanded()).toBe(false);
            });
        });
    });

    describe("singleExpand", function() {
        beforeEach(function() {
            sampleData = [{
                id: 'i1',
                children: [{
                    id: 'i11',
                    children: [{
                        id: 'i111',
                        leaf: true
                    }]
                }, {
                    id: 'i12',
                    children: [{
                        id: 'i121',
                        leaf: true
                    }]
                }, {
                    id: 'i13',
                    children: [{
                        id: 'i131',
                        leaf: true
                    }]
                }]
            }, {
                id: 'i2',
                children: [{
                    id: 'i21',
                    children: [{
                        id: 'i211',
                        leaf: true
                    }]
                }, {
                    id: 'i22',
                    children: [{
                        id: 'i221',
                        leaf: true
                    }]
                }, {
                    id: 'i23',
                    children: [{
                        id: 'i231',
                        leaf: true
                    }]
                }]
            }, {
                id: 'i3',
                children: [{
                    id: 'i31',
                    children: [{
                        id: 'i311',
                        leaf: true
                    }]
                }, {
                    id: 'i32',
                    children: [{
                        id: 'i321',
                        leaf: true
                    }]
                }, {
                    id: 'i33',
                    children: [{
                        id: 'i331',
                        leaf: true
                    }]
                }]
            }];
        });

        function expectExpanded(parent, state) {
            var node = byId(parent),
                childNodes = node.childNodes,
                len = childNodes.length,
                i;

            for (i = 0; i < len; ++i) {
                expect(getItem(childNodes[i].id).isExpanded()).toBe(state[i]);
            }
        }

        describe("with singleExpand: false", function() {
            beforeEach(function() {
                makeList();
            });

            it("should not collapse other nodes when expanding", function() {
                getItem('i1').expand();
                expectExpanded('root', [true, false, false]);
                expectExpanded('i1', [false, false, false]);
                expectExpanded('i2', [false, false, false]);
                expectExpanded('i3', [false, false, false]);

                getItem('i2').expand();
                expectExpanded('root', [true, true, false]);
                expectExpanded('i1', [false, false, false]);
                expectExpanded('i2', [false, false, false]);
                expectExpanded('i3', [false, false, false]);

                getItem('i3').expand();
                expectExpanded('root', [true, true, true]);
                expectExpanded('i1', [false, false, false]);
                expectExpanded('i2', [false, false, false]);
                expectExpanded('i3', [false, false, false]);

                getItem('i11').expand();
                expectExpanded('root', [true, true, true]);
                expectExpanded('i1', [true, false, false]);
                expectExpanded('i2', [false, false, false]);
                expectExpanded('i3', [false, false, false]);

                getItem('i12').expand();
                expectExpanded('root', [true, true, true]);
                expectExpanded('i1', [true, true, false]);
                expectExpanded('i2', [false, false, false]);
                expectExpanded('i3', [false, false, false]);

                getItem('i13').expand();
                expectExpanded('root', [true, true, true]);
                expectExpanded('i1', [true, true, true]);
                expectExpanded('i2', [false, false, false]);
                expectExpanded('i3', [false, false, false]);

                getItem('i21').expand();
                expectExpanded('root', [true, true, true]);
                expectExpanded('i1', [true, true, true]);
                expectExpanded('i2', [true, false, false]);
                expectExpanded('i3', [false, false, false]);

                getItem('i22').expand();
                expectExpanded('root', [true, true, true]);
                expectExpanded('i1', [true, true, true]);
                expectExpanded('i2', [true, true, false]);
                expectExpanded('i3', [false, false, false]);

                getItem('i23').expand();
                expectExpanded('root', [true, true, true]);
                expectExpanded('i1', [true, true, true]);
                expectExpanded('i2', [true, true, true]);
                expectExpanded('i3', [false, false, false]);

                getItem('i31').expand();
                expectExpanded('root', [true, true, true]);
                expectExpanded('i1', [true, true, true]);
                expectExpanded('i2', [true, true, true]);
                expectExpanded('i3', [true, false, false]);

                getItem('i32').expand();
                expectExpanded('root', [true, true, true]);
                expectExpanded('i1', [true, true, true]);
                expectExpanded('i2', [true, true, true]);
                expectExpanded('i3', [true, true, false]);

                getItem('i33').expand();
                expectExpanded('root', [true, true, true]);
                expectExpanded('i1', [true, true, true]);
                expectExpanded('i2', [true, true, true]);
                expectExpanded('i3', [true, true, true]);
            });
        });

        describe("with singleExpand: true", function() {
            beforeEach(function() {
                makeList({
                    singleExpand: true
                });
            });

            it("should only allow 1 item to be expanded per level", function() {
                getItem('i1').expand();
                expectExpanded('root', [true, false, false]);
                expectExpanded('i1', [false, false, false]);

                getItem('i2').expand();
                expectExpanded('root', [false, true, false]);
                expectExpanded('i2', [false, false, false]);

                getItem('i3').expand();
                expectExpanded('root', [false, false, true]);
                expectExpanded('i3', [false, false, false]);

                getItem('i1').expand();
                getItem('i11').expand();
                expectExpanded('root', [true, false, false]);
                expectExpanded('i1', [true, false, false]);

                getItem('i12').expand();
                expectExpanded('root', [true, false, false]);
                expectExpanded('i1', [false, true, false]);

                getItem('i13').expand();
                expectExpanded('root', [true, false, false]);
                expectExpanded('i1', [false, false, true]);

                getItem('i2').expand();
                getItem('i21').expand();
                expectExpanded('root', [false, true, false]);
                expectExpanded('i2', [true, false, false]);

                getItem('i22').expand();
                expectExpanded('root', [false, true, false]);
                expectExpanded('i2', [false, true, false]);

                getItem('i23').expand();
                expectExpanded('root', [false, true, false]);
                expectExpanded('i2', [false, false, true]);

                getItem('i3').expand();
                getItem('i31').expand();
                expectExpanded('root', [false, false, true]);
                expectExpanded('i3', [true, false, false]);

                getItem('i32').expand();
                expectExpanded('root', [false, false, true]);
                expectExpanded('i3', [false, true, false]);

                getItem('i33').expand();
                expectExpanded('root', [false, false, true]);
                expectExpanded('i3', [false, false, true]);
            });

            it("should collapse nodes before expanding", function() {
                var order = [];

                list.on('itemexpand', function(list, item) {
                    order.push(['e', item.getNode().id]);
                });
                list.on('itemcollapse', function(list, item) {
                    order.push(['c', item.getNode().id]);
                });

                getItem('i1').expand();
                expect(order).toEqual([['e', 'i1']]);
                order = [];

                getItem('i2').expand();
                expect(order).toEqual([['c', 'i1'], ['e', 'i2']]);
                order = [];

                getItem('i3').expand();
                expect(order).toEqual([['c', 'i2'], ['e', 'i3']]);
                order = [];

                getItem('i1').expand();
                expect(order).toEqual([['c', 'i3'], ['e', 'i1']]);
            });
        });
    });

    // NB: These are CLASSIC SPECIFIC TESTS
    (Ext.isModern ? xdescribe : describe)("sizing", function() {
        var ct, c, count;

        beforeEach(function() {
            makeList({
                renderTo: null
            });

            ct = new Ext.container.Container({
                renderTo: Ext.getBody(),
                width: 400,
                height: 600,
                layout: {
                    type: 'vbox',
                    align: 'stretch'
                },
                items: [{
                    xtype: 'component',
                    flex: 1
                }, list]
            });

            c = ct.items.first();

            count = ct.componentLayoutCounter;
        });

        afterEach(function() {
            c = ct = Ext.destroy(ct);
            count = 0;
        });

        function listHeight() {
            return list.element.getHeight();
        }

        it("should provide an initial size", function() {
            expect(c.getHeight()).toBe(600 - listHeight());
        });

        it("should update layout when a node is collapsed", function() {
            var h = c.getHeight();

            byId('i4').collapse();
            expect(c.getHeight()).toBeGreaterThan(h);
            expect(c.getHeight()).toBe(600 - listHeight());
            expect(ct.componentLayoutCounter).toBe(count + 1);
        });

        it("should update layout when a node is expanded", function() {
            var h = c.getHeight();

            byId('i1').expand();
            expect(c.getHeight()).toBeLessThan(h);
            expect(c.getHeight()).toBe(600 - listHeight());
            expect(ct.componentLayoutCounter).toBe(count + 1);
        });

        it("should update when a node is added", function() {
            var h = c.getHeight();

            root.appendChild({
                id: 'i9',
                text: 'Foo'
            });
            expect(c.getHeight()).toBeLessThan(h);
            expect(c.getHeight()).toBe(600 - listHeight());
            expect(ct.componentLayoutCounter).toBe(count + 1);
        });

        it("should update when a node is removed", function() {
            var h = c.getHeight();

            root.removeChild(byId('i1'));
            expect(c.getHeight()).toBeGreaterThan(h);
            expect(c.getHeight()).toBe(600 - listHeight());
            expect(ct.componentLayoutCounter).toBe(count + 1);
        });
    });

    describe("destruction", function() {
        beforeEach(function() {
            makeList();
        });

        it("should not fire itemremove events", function() {
            var spy = jasmine.createSpy();

            list.on('itemremove', spy);
            list.destroy();
            expect(spy).not.toHaveBeenCalled();
        });

        it("should unbind the store", function() {
            list.destroy();
            expect(list._store).toBeNull();
        });
    });

    describe('selection', function() {
        beforeEach(function() {
            makeList({
                selection: 'i11'
            });
        });

        it('should select leaf node', function() {
            var node = byId('i11'),
                item = list.getItem(node);

            expect(item.getSelected()).toBe(true);
            expect(node.parentNode.isExpanded()).toBe(true);
        });

        it('should collapse parent of selected leaf', function() {
            var node = byId('i11'),
                parentNode = node.parentNode,
                item = list.getItem(node),
                parentItem = list.getItem(parentNode),
                el = parentItem.expanderElement.dom;

            expect(item.getSelected()).toBe(true);
            expect(parentNode.isExpanded()).toBe(true);

            jasmine.fireMouseEvent(el, 'click');

            expect(parentNode.isExpanded()).toBe(false);
        });
    });

});

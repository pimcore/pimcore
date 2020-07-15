topSuite("Ext.data.ChainedStore", [
    'Ext.data.Session',
    'Ext.data.summary.*'
], function() {
    var fakeScope = {},
        abeRec, aaronRec, edRec, tommyRec,
        abeRaw, aaronRaw, edRaw, tommyRaw,
        source, store, User;

    function addSourceData() {
        source.add(edRaw, abeRaw, aaronRaw, tommyRaw);
        edRec    = source.getAt(0);
        abeRec   = source.getAt(1);
        aaronRec = source.getAt(2);
        tommyRec = source.getAt(3);
    }

    function makeUser(email, data) {
        if (Ext.isObject(email)) {
            data = email;
        }
        else {
            data = data || {};

            if (!data.email) {
                data.email = email;
            }
        }

        return new User(data);
    }

    function createSource(cfg) {
        cfg = cfg || {};
        source = new Ext.data.Store(Ext.applyIf(cfg, {
            asynchronousLoad: false,
            model: 'spec.User'
        }));
    }

    function createStore(cfg) {
        store = new Ext.data.ChainedStore(Ext.apply({
            source: source
        }, cfg));
    }

    function completeWithData(data) {
        Ext.Ajax.mockCompleteWithData(data);
    }

    function expectOrder(recs, s) {
        var len = recs.length,
            i;

        for (i = 0; i < len; ++i) {
            expect(s.getAt(i)).toBe(recs[i]);
        }
    }

    function getRecValues(store, fieldName) {
        var ret = [];

        store.each(function(r) {
            ret.push(r.data[fieldName]);
        });

        return ret;
    }

    function spyOnEvent(object, eventName, fn) {
        var obj = { fn: fn || Ext.emptyFn };

        var spy = spyOn(obj, "fn");

        object.addListener(eventName, obj.fn);

        return spy;
    }

    beforeEach(function() {
        Ext.data.Model.schema.setNamespace('spec');
        MockAjaxManager.addMethods();
        edRaw = { name: 'Ed Spencer',   email: 'ed@sencha.com',    evilness: 100, group: 'code',  old: false, age: 25, valid: 'yes' };
        abeRaw = { name: 'Abe Elias',    email: 'abe@sencha.com',   evilness: 70,  group: 'admin', old: false, age: 20, valid: 'yes' };
        aaronRaw = { name: 'Aaron Conran', email: 'aaron@sencha.com', evilness: 5,   group: 'admin', old: true, age: 26, valid: 'yes' };
        tommyRaw = { name: 'Tommy Maintz', email: 'tommy@sencha.com', evilness: -15, group: 'code',  old: true, age: 70, valid: 'yes' };

        User = Ext.define('spec.User', {
            extend: 'Ext.data.Model',
            idProperty: 'email',

            fields: [
                { name: 'name',      type: 'string' },
                { name: 'email',     type: 'string' },
                { name: 'evilness',  type: 'int' },
                { name: 'group',     type: 'string' },
                { name: 'old',       type: 'boolean' },
                { name: 'valid',     type: 'string' },
                { name: 'age',       type: 'int' }
            ]
        });

        createSource();
        addSourceData();
    });

    afterEach(function() {
        MockAjaxManager.removeMethods();
        Ext.data.Model.schema.clear();
        Ext.undefine('spec.User');
        User = source = store = Ext.destroy(source, store);
        Ext.data.Model.schema.clear(true);
    });

    describe("constructing", function() {
        it("should inherit the model from the backing store", function() {
            createStore();
            expect(store.getModel()).toBe(User);
        });

        it("should have the data from the backing store", function() {
            createStore();

            var sourceData = source.getRange(),
                storeData = store.getRange(),
                len = sourceData.length,
                i;

            expect(storeData.length).toBe(sourceData.length);

            for (i = 0; i < len; ++i) {
                expect(storeData[i]).toBe(sourceData[i]);
            }
        });

        it("should not fire a refresh or datachanged event", function() {
            var called = 0;

            createStore({
                listeners: {
                    refresh: function() {
                        called += 1;
                    },
                    datachanged: function() {
                        called += 2;
                    }
                }
            });

            expect(called).toBe(0);
        });

        it("should accept an id of a store as the source", function() {
            var idSource = new Ext.data.Store({
                model: 'spec.User',
                storeId: 'sourceId'
            });

            source = 'sourceId';
            createStore();
            source = null;
            expect(store.getSource()).toBe(idSource);
            idSource.destroy();
        });

        it("should accept a chained store as the source", function() {
            createStore();
            var child = new Ext.data.ChainedStore({
                source: store
            });

            expect(child.getCount()).toBe(4);
            expect(child.getModel()).toBe(User);
            child.destroy();
        });
    });

    it("should not join the records to the store", function() {
        createStore();
        var joined = edRec.joined;

        expect(joined.length).toBe(1);
        expect(joined[0]).toBe(source);
    });

    describe("beginUpdate/endUpdate", function() {
        var beginSpy, endSpy;

        beforeEach(function() {
            beginSpy = jasmine.createSpy();
            endSpy = jasmine.createSpy();

            createStore();
        });

        afterEach(function() {
            beginSpy = endSpy = null;
        });

        function setup() {
            createStore();
            store.on('beginupdate', beginSpy);
            store.on('endupdate', endSpy);
        }

        describe("calls to methods directly", function() {
            it("should fire beginupdate on the first call to beginUpdate", function() {
                setup();
                store.beginUpdate();
                expect(beginSpy.callCount).toBe(1);
                store.beginUpdate();
                store.beginUpdate();
                expect(beginSpy.callCount).toBe(1);
            });

            it("should fire the endupdate on the last matching call to endUpdate", function() {
                setup();
                store.beginUpdate();
                store.beginUpdate();
                store.beginUpdate();
                store.endUpdate();
                store.endUpdate();
                expect(endSpy).not.toHaveBeenCalled();
                store.endUpdate();
                expect(endSpy.callCount).toBe(1);
            });
        });

        describe("in reaction to store changes", function() {
            beforeEach(function() {
                setup();
            });

            // TODO: this could be fleshed out further to include more functionality
            describe("add", function() {
                it("should fire begin/end for adding a single record", function() {
                    source.add({});
                    expect(beginSpy.callCount).toBe(1);
                    expect(endSpy.callCount).toBe(1);
                });

                it("should fire begin/end for adding multiple records in contiguous range", function() {
                    source.add([{}, {}, {}, {}]);
                    expect(beginSpy.callCount).toBe(1);
                    expect(endSpy.callCount).toBe(1);
                });

                it("should fire begin/end for adding multiple records over a discontiguous range", function() {
                    store.sort('age');
                    source.add([{
                        age: 1
                    }, {
                        age: 1000
                    }]);
                    expect(beginSpy.callCount).toBe(1);
                    expect(endSpy.callCount).toBe(1);
                });
            });

            describe("remove", function() {
                it("should fire begin/end for removing a single record", function() {
                    source.removeAt(0);
                    expect(beginSpy.callCount).toBe(1);
                    expect(endSpy.callCount).toBe(1);
                });

                it("should fire begin/end for removing multiple records in contiguous range", function() {
                    source.remove([edRec, abeRec]);
                    expect(beginSpy.callCount).toBe(1);
                    expect(endSpy.callCount).toBe(1);
                });

                it("should fire begin/end for removing multiple records over a discontiguous range", function() {
                    source.remove([edRec, tommyRec]);
                    expect(beginSpy.callCount).toBe(1);
                    expect(endSpy.callCount).toBe(1);
                });
            });

            describe("update", function() {
                it("should fire begin/end for a record update", function() {
                    edRec.set('name', 'foo');
                    expect(beginSpy.callCount).toBe(1);
                    expect(endSpy.callCount).toBe(1);
                });
            });
        });
    });

    describe("getting records", function() {
        beforeEach(function() {
            createStore();
            addSourceData();
        });

        describe("first", function() {
            it("should return the first record", function() {
                expect(store.first()).toBe(edRec);
            });

            it("should return the record if there is only 1", function() {
                store.remove([edRec, abeRec, tommyRec]);
                expect(store.first()).toBe(aaronRec);
            });

            it("should return null with an empty store", function() {
                store.removeAll();
                expect(store.first()).toBeNull();
            });

            it("should be affected by filters", function() {
                store.getFilters().add({
                    property: 'group',
                    value: 'admin'
                });
                expect(store.first()).toBe(abeRec);
            });
        });

        describe("last", function() {
            it("should return the last record", function() {
                expect(store.last()).toBe(tommyRec);
            });

            it("should return the record if there is only 1", function() {
                store.remove([edRec, abeRec, tommyRec]);
                expect(store.last()).toBe(aaronRec);
            });

            it("should return null with an empty store", function() {
                store.removeAll();
                expect(store.last()).toBeNull();
            });

            it("should be affected by filters", function() {
                store.getFilters().add({
                    property: 'group',
                    value: 'admin'
                });
                expect(store.last()).toBe(aaronRec);
            });
        });

        describe("getAt", function() {
            it("should return the record at the specified index", function() {
                expect(store.getAt(1)).toBe(abeRec);
            });

            it("should return null when the index is outside the store bounds", function() {
                expect(store.getAt(100)).toBe(null);
            });

            it("should return null when the store is empty", function() {
                store.removeAll();
                expect(store.getAt(0)).toBe(null);
            });
        });

        describe("getById", function() {
            it("should return the record with the matching id", function() {
                expect(store.getById('tommy@sencha.com')).toBe(tommyRec);
            });

            it("should return null if a matching id is not found", function() {
                expect(store.getById('foo@sencha.com')).toBe(null);
            });

            it("should return null when the store is empty", function() {
                store.removeAll();
                expect(store.getById('ed@sencha.com')).toBe(null);
            });

            it("should ignore filters", function() {
                store.filter('email', 'ed@sencha.com');
                expect(store.getById('aaron@sencha.com')).toBe(aaronRec);
            });
        });

        describe("getByInternalId", function() {
            it("should return the record with the matching id", function() {
                expect(store.getByInternalId(tommyRec.internalId)).toBe(tommyRec);
            });

            it("should return null if a matching id is not found", function() {
                expect(store.getByInternalId('foo@sencha.com')).toBe(null);
            });

            it("should return null when the store is empty", function() {
                store.removeAll();
                expect(store.getByInternalId('ed@sencha.com')).toBe(null);
            });

            it("should ignore filters", function() {
                store.filter('email', 'ed@sencha.com');
                expect(store.getByInternalId(aaronRec.internalId)).toBe(aaronRec);
            });

            it("should work correctly if not called before filtering", function() {
                store.filter('email', 'ed@sencha.com');
                expect(store.getByInternalId(aaronRec.internalId)).toBe(aaronRec);
            });

            it("should work correctly if called before & after filtering", function() {
                expect(store.getByInternalId(aaronRec.internalId)).toBe(aaronRec);
                store.filter('email', 'ed@sencha.com');
                expect(store.getByInternalId(aaronRec.internalId)).toBe(aaronRec);
            });
        });

        describe("getRange", function() {
            it("should default to the full store range", function() {
                expect(store.getRange()).toEqual([edRec, abeRec, aaronRec, tommyRec]);
            });

            it("should return from the start index", function() {
                expect(store.getRange(2)).toEqual([aaronRec, tommyRec]);
            });

            it("should use the end index, and include it", function() {
                expect(store.getRange(0, 2)).toEqual([edRec, abeRec, aaronRec]);
            });

            it("should ignore an end index greater than the store range", function() {
                expect(store.getRange(1, 100)).toEqual([abeRec, aaronRec, tommyRec]);
            });
        });

        describe("query", function() {
            var coders,
                slackers;

            it("should return records with group: 'coder'", function() {
                coders = store.query('group', 'code');
                expect(coders.length).toBe(2);
                expect(coders.contains(edRec)).toBe(true);
                expect(coders.contains(tommyRec)).toBe(true);
                expect(coders.contains(aaronRec)).toBe(false);
                expect(coders.contains(abeRec)).toBe(false);
            });

            it("should return null if a matching id is not found", function() {
                slackers = store.query('group', 'slackers');
                expect(slackers.length).toBe(0);
            });

            it("should return null when the store is empty", function() {
                store.removeAll();
                coders = store.query('group', 'code');
                expect(coders.length).toBe(0);
            });

            it("should ignore filters", function() {
                store.filter('email', 'ed@sencha.com');
                expect(store.getCount()).toBe(1);
                coders = store.query('group', 'code');
                expect(coders.length).toBe(2);
                expect(coders.contains(edRec)).toBe(true);
                expect(coders.contains(tommyRec)).toBe(true);
                expect(coders.contains(aaronRec)).toBe(false);
                expect(coders.contains(abeRec)).toBe(false);
            });
        });
    });

    describe("sorting", function() {
        describe("initial values", function() {
            it("should default to having no sorters", function() {
                createStore();
                expect(store.getSorters().getCount()).toBe(0);
            });

            it("should not inherit sorters from the source store", function() {
                source.sort('age', 'DESC');
                createStore();
                expect(store.getSorters().getCount()).toBe(0);
            });

            it("should have the data in order of the source store by default", function() {
                source.sort('age', 'DESC');
                createStore();
                expect(store.getAt(0)).toBe(source.getAt(0));
                expect(store.getAt(1)).toBe(source.getAt(1));
                expect(store.getAt(2)).toBe(source.getAt(2));
                expect(store.getAt(3)).toBe(source.getAt(3));
            });
        });

        describe("sorting the source", function() {
            it("should not change the sort order in the store", function() {
                createStore();
                source.sort('name', 'DESC');
                expectOrder([tommyRec, edRec, abeRec, aaronRec], source);
                expectOrder([edRec, abeRec, aaronRec, tommyRec], store);
            });
        });

        describe("sorting the store", function() {
            it("should not change the sort order in the source store", function() {
                createStore();
                store.sort('name', 'DESC');
                expectOrder([tommyRec, edRec, abeRec, aaronRec], store);
                expectOrder([edRec, abeRec, aaronRec, tommyRec], source);
            });
        });
    });

    describe("filtering", function() {
        describe("filtering the source", function() {
            it("should also filter the store", function() {
                createStore();
                source.filter('group', 'code');
                expect(store.getCount()).toBe(2);
                expectOrder(source.getRange(), store);
            });

            it("should not affect the store filter collection", function() {
                createStore();
                source.filter('group', 'code');
                expect(store.getFilters().getCount()).toBe(0);
            });

            it("should also unfilter the store", function() {
                createStore();
                source.filter('group', 'code');
                source.getFilters().removeAll();
                expect(store.getCount()).toBe(4);
                expectOrder(source.getRange(), store);
            });

            it("should have record in store when added to source but filtered out", function() {
                createStore();
                source.filter('group', 'code');

                var rec = makeUser('foo@sencha.com', {
                    group: 'admin'
                });

                source.add(rec);
                source.getFilters().removeAll();
                expect(store.indexOf(rec)).toBe(4);
            });

            describe("events", function() {
                var spy;

                beforeEach(function() {
                    spy = jasmine.createSpy();
                    createStore();
                });

                afterEach(function() {
                    spy = null;
                });

                it("should fire the refresh event on the store", function() {
                    var called = 0;

                    store.on('refresh', function() {
                        ++called;
                    });

                    source.filter('group', 'code');

                    expect(called).toBe(1);
                });

                it("should fire the datachanged event on the store", function() {
                    store.on('datachanged', spy);
                    source.filter('group', 'code');
                    expect(spy).toHaveBeenCalled();
                    expect(spy.callCount).toBe(1);
                });

                it("should not fire the filterchange event", function() {
                    store.on('filterchange', spy);
                    source.filter('group', 'code');
                    expect(spy).not.toHaveBeenCalled();
                });

                describe("when the source is a chained store", function() {
                    var child;

                    beforeEach(function() {
                        child = new Ext.data.ChainedStore({
                            source: store
                        });
                    });

                    afterEach(function() {
                        child.destroy();
                        child = null;
                    });

                    it("should fire the refresh event on the store", function() {
                        child.on('refresh', spy);
                        store.filter('group', 'code');
                        expect(spy).toHaveBeenCalled();
                        expect(spy.callCount).toBe(1);
                    });

                    it("should fire the datachanged event on the store", function() {
                        child.on('datachanged', spy);
                        store.filter('group', 'code');
                        expect(spy).toHaveBeenCalled();
                        expect(spy.callCount).toBe(1);
                    });

                    it("should not fire the filterchange event", function() {
                        child.on('filterchange', spy);
                        store.filter('group', 'code');
                        expect(spy).not.toHaveBeenCalled();
                    });
                });
            });
        });

        describe("filtering the store", function() {
            it("should not filter the source", function() {
                createStore();
                store.filter('group', 'code');
                expect(store.getCount()).toBe(2);
                expect(source.getCount()).toBe(4);
            });

            it("should not affect the source filter collection", function() {
                createStore();
                store.filter('group', 'code');
                expect(source.getFilters().getCount()).toBe(0);
            });

            it("should filter based off source filters when the source is filtered", function() {
                createStore();
                source.filter('group', 'code');
                store.filter('name', 'Tommy');
                expect(store.getCount()).toBe(1);
                expect(store.getAt(0)).toBe(tommyRec);
            });

            it("should apply source filters over current filters", function() {
                createStore();
                store.getFilters().add({
                    property: 'age',
                    value: 70,
                    operator: '<'
                });
                expect(store.getCount()).toBe(3);
                source.filter('group', 'admin');
                expect(store.getCount()).toBe(2);
                expectOrder([abeRec, aaronRec], store);
            });

            describe("events", function() {
                it("should fire the refresh event", function() {
                    var called = 0;

                    createStore();

                    store.on('refresh', function() {
                        ++called;
                    });

                    store.filter('group', 'code');

                    expect(called).toBe(1);
                });

                it("should fire the datachanged event", function() {
                    var called = 0;

                    createStore();
                    store.on('datachanged', function() {
                        ++called;
                    });

                    store.filter('group', 'code');

                    expect(called).toBe(1);
                });

                it("should fire the filterchange event", function() {
                    var spy = jasmine.createSpy();

                    createStore();
                    store.on('filterchange', spy);
                    store.filter('group', 'code');
                    expect(spy).toHaveBeenCalled();
                    expect(spy.callCount).toBe(1);
                });

                it('should fire the update event on both source and chained Stores', function() {
                    store = new Ext.data.ArrayStore({
                        fields: ['f1'],
                        data: [['f1value']]
                    });
                    var chained = new Ext.data.ChainedStore({
                            source: store
                        }),
                        sourceFiredUpdate,
                        chainedFiredUpdate,
                        rec = store.getAt(0);

                    store.on('update', function()  {
                        sourceFiredUpdate = true;
                    });
                    chained.on('update', function() {
                        chainedFiredUpdate = true;
                    });

                    // There's one record in each store
                    expect(store.getCount()).toBe(1);
                    expect(chained.getCount()).toBe(1);

                    rec.set('f1', 'f1 updated');

                    // Should be no change
                    expect(store.getCount()).toBe(1);
                    expect(chained.getCount()).toBe(1);

                    // Both stores fire the update event
                    expect(sourceFiredUpdate).toBe(true);
                    expect(chainedFiredUpdate).toBe(true);

                    chained.destroy();
                });

                it('should NOT fire the update event on the chained Store if the record is filtered out of the source', function() {
                    store = new Ext.data.ArrayStore({
                        fields: ['f1'],
                        data: [['f1value']],
                        filters: {
                            property: 'f1',
                            value: 'f1Value'
                        }
                    });
                    var chained = new Ext.data.ChainedStore({
                            source: store
                        }),
                        sourceFiredUpdate,
                        chainedFiredUpdate,
                        rec = store.getAt(0);

                    store.on('update', function() {
                        sourceFiredUpdate = true;
                    });
                    chained.on('update', function() {
                        chainedFiredUpdate = true;
                    });

                    // There's one record in each store
                    expect(store.getCount()).toBe(1);
                    expect(chained.getCount()).toBe(1);

                    // Will filter the record out of source because "f1 updated" won't match "f1value"
                    rec.set('f1', 'f1 updated');

                    // The only record is filtered out
                    expect(store.getCount()).toBe(0);
                    expect(chained.getCount()).toBe(0);

                    // Source should have fired the update event
                    expect(sourceFiredUpdate).toBe(true);

                    // Chained store no longer contains the filtered record,
                    // so should not have fired the update event
                    expect(chainedFiredUpdate).toBeFalsy();

                    chained.destroy();
                });
            });
        });
    });

    describe("loading", function() {
        describe("via load", function() {
            it("should populate the store", function() {
                source.removeAll();
                createStore();
                source.load();
                completeWithData([abeRaw, tommyRaw, edRaw, aaronRaw]);
                expectOrder(source.getRange(), store);
            });

            it("should clear any existing data", function() {
                createStore();
                source.load();
                completeWithData([{
                    id: 'foo@sencha.com'
                }]);
                expect(store.getCount()).toBe(1);
                expect(store.getAt(0)).toBe(source.getAt(0));
            });

            describe("events", function() {
                it("should not fire the add, remove or clear events", function() {
                    source.removeAll();
                    createStore();
                    var spy = jasmine.createSpy();

                    store.on('add', spy);
                    store.on('remove', spy);
                    store.on('clear', spy);
                    source.load();
                    completeWithData([abeRaw, tommyRaw, edRaw, aaronRaw]);
                    expect(spy).not.toHaveBeenCalled();
                });

                it("should relay the beforeload event", function() {
                    var readSpy = spyOn(User.getProxy(), 'read').andCallThrough();

                    source.removeAll();
                    createStore();
                    var spy = jasmine.createSpy();

                    store.on('beforeload', spy);
                    source.load();
                    completeWithData([abeRaw, tommyRaw, edRaw, aaronRaw]);
                    expect(spy).toHaveBeenCalled();
                    expect(spy.mostRecentCall.args[0]).toBe(store);
                    expect(spy.mostRecentCall.args[1]).toBe(readSpy.mostRecentCall.args[0]);
                });

                it("should relay the load event", function() {
                    var readSpy = spyOn(User.getProxy(), 'read').andCallThrough();

                    source.removeAll();
                    createStore();
                    var spy = jasmine.createSpy();

                    store.on('load', spy);
                    source.load();
                    completeWithData([abeRaw, tommyRaw, edRaw, aaronRaw]);
                    expect(spy).toHaveBeenCalled();
                    expect(spy.mostRecentCall.args[0]).toBe(store);
                    expect(spy.mostRecentCall.args[1]).toEqual([source.getAt(0), source.getAt(1), source.getAt(2), source.getAt(3)]);
                    expect(spy.mostRecentCall.args[2]).toBe(true);
                    expect(spy.mostRecentCall.args[3]).toBe(readSpy.mostRecentCall.args[0]);
                });

                it("should fire the refresh and datachanged event", function() {
                    createStore();
                    var dataChangedSpy = jasmine.createSpy(),
                        refreshSpy = jasmine.createSpy();

                    store.on('refresh', refreshSpy);
                    store.on('datachanged', dataChangedSpy);

                    source.load();
                    completeWithData([abeRaw, tommyRaw, edRaw, aaronRaw]);
                    expect(refreshSpy).toHaveBeenCalled();
                    expect(refreshSpy.mostRecentCall.args[0]).toBe(store);

                    expect(dataChangedSpy).toHaveBeenCalled();
                    expect(dataChangedSpy.mostRecentCall.args[0]).toBe(store);
                });

                it("should forward on beforeload/load events when the source is chained", function() {
                    createStore();

                    var readSpy = spyOn(User.getProxy(), 'read').andCallThrough();

                    var beforeLoadSpy = jasmine.createSpy(),
                        loadSpy = jasmine.createSpy(),
                        child = new Ext.data.ChainedStore({
                            source: store,
                            listeners: {
                                beforeload: beforeLoadSpy,
                                load: loadSpy
                            }
                        });

                    source.load();
                    expect(beforeLoadSpy.callCount).toBe(1);
                    expect(beforeLoadSpy.mostRecentCall.args[0]).toBe(child);
                    expect(beforeLoadSpy.mostRecentCall.args[1]).toBe(readSpy.mostRecentCall.args[0]);

                    completeWithData([abeRaw, tommyRaw, edRaw, aaronRaw]);
                    expect(loadSpy.callCount).toBe(1);

                    expect(loadSpy.mostRecentCall.args[0]).toBe(child);
                    expect(loadSpy.mostRecentCall.args[1]).toEqual([source.getAt(0), source.getAt(1), source.getAt(2), source.getAt(3)]);
                    expect(loadSpy.mostRecentCall.args[2]).toBe(true);
                    expect(loadSpy.mostRecentCall.args[3]).toBe(readSpy.mostRecentCall.args[0]);

                    child.destroy();
                });
            });
        });

        describe("via loadData", function() {
            it("should populate the store", function() {
                source.removeAll();
                createStore();
                source.loadData([edRaw, tommyRaw, aaronRaw, abeRaw]);
                expectOrder(source.getRange(), store);
            });

            it("should clear any existing data", function() {
                createStore();
                source.loadData([{
                    id: 'foo@sencha.com'
                }]);
                expect(store.getCount()).toBe(1);
                expect(store.getAt(0)).toBe(source.getAt(0));
            });

            it("should not fire the add event", function() {
                source.removeAll();
                createStore();
                var spy = jasmine.createSpy();

                store.on('add', spy);
                source.loadData([edRaw, tommyRaw, aaronRaw, abeRaw]);
                expect(spy).not.toHaveBeenCalled();
            });

            it("should not fire the remove event", function() {
                createStore();
                var spy = jasmine.createSpy();

                store.on('remove', spy);
                source.loadData([edRaw, tommyRaw, aaronRaw, abeRaw]);
                expect(spy).not.toHaveBeenCalled();
            });

            it("should fire the refresh and datachanged event", function() {
                createStore();
                var dataChangedSpy = jasmine.createSpy(),
                    refreshSpy = jasmine.createSpy();

                store.on('refresh', refreshSpy);
                store.on('datachanged', dataChangedSpy);

                source.loadData([edRaw, tommyRaw, aaronRaw, abeRaw]);
                expect(refreshSpy).toHaveBeenCalled();
                expect(refreshSpy.mostRecentCall.args[0]).toBe(store);

                expect(dataChangedSpy).toHaveBeenCalled();
                expect(dataChangedSpy.mostRecentCall.args[0]).toBe(store);
            });
        });

        describe("via loadRawData", function() {
            it("should populate the store", function() {
                source.removeAll();
                createStore();
                source.loadRawData([edRaw, tommyRaw, aaronRaw, abeRaw]);
                expectOrder(source.getRange(), store);
            });

            it("should clear any existing data", function() {
                createStore();
                source.loadRawData([{
                    id: 'foo@sencha.com'
                }]);
                expect(store.getCount()).toBe(1);
                expect(store.getAt(0)).toBe(source.getAt(0));
            });

            it("should not fire the add event", function() {
                source.removeAll();
                createStore();
                var spy = jasmine.createSpy();

                store.on('add', spy);
                source.loadRawData([edRaw, tommyRaw, aaronRaw, abeRaw]);
                expect(spy).not.toHaveBeenCalled();
            });

            it("should not fire the remove event", function() {
                createStore();
                var spy = jasmine.createSpy();

                store.on('remove', spy);
                source.loadRawData([edRaw, tommyRaw, aaronRaw, abeRaw]);
                expect(spy).not.toHaveBeenCalled();
            });

            it("should fire the refresh and datachanged event", function() {
                createStore();
                var dataChangedSpy = jasmine.createSpy(),
                    refreshSpy = jasmine.createSpy();

                store.on('refresh', refreshSpy);
                store.on('datachanged', dataChangedSpy);

                source.loadRawData([edRaw, tommyRaw, aaronRaw, abeRaw]);
                expect(refreshSpy).toHaveBeenCalled();
                expect(refreshSpy.mostRecentCall.args[0]).toBe(store);

                expect(dataChangedSpy).toHaveBeenCalled();
                expect(dataChangedSpy.mostRecentCall.args[0]).toBe(store);
            });
        });

        describe("with sorters", function() {
            it("should apply sorters from the store", function() {
                source.removeAll();
                createStore();
                store.sort('name', 'DESC');
                source.load();
                completeWithData([abeRaw, edRaw, tommyRaw, aaronRaw]);
                expect(store.getAt(0).id).toBe('tommy@sencha.com');
                expect(store.getAt(1).id).toBe('ed@sencha.com');
                expect(store.getAt(2).id).toBe('abe@sencha.com');
                expect(store.getAt(3).id).toBe('aaron@sencha.com');
            });
        });

        describe("filters", function() {
            it("should apply filters from the store", function() {
                source.removeAll();
                createStore();
                store.getFilters().add({
                    property: 'group',
                    value: 'code'
                });
                source.load();
                completeWithData([abeRaw, edRaw, tommyRaw, aaronRaw]);
                expect(store.getCount()).toBe(2);
                expect(store.getAt(0).id).toBe('ed@sencha.com');
                expect(store.getAt(1).id).toBe('tommy@sencha.com');
            });
        });
    });

    describe("adding", function() {
        beforeEach(function() {
            createStore();
        });

        describe("adding to the source", function() {
            it("should also add to the store", function() {
                var rec = source.add({
                    id: 'new@sencha.com'
                })[0];

                expect(store.getAt(4)).toBe(rec);
            });

            describe("events", function() {
                it("should fire the add/datachanged event on the store", function() {
                    var addSpy = jasmine.createSpy(),
                        datachangedSpy = jasmine.createSpy(),
                        rec, args;

                    store.on('add', addSpy);
                    store.on('datachanged', datachangedSpy);

                    rec = source.add({
                        id: 'new@sencha.com'
                    })[0];

                    expect(addSpy).toHaveBeenCalled();
                    args = addSpy.mostRecentCall.args;
                    expect(args[0]).toBe(store);
                    expect(args[1]).toEqual([rec]);
                    expect(args[2]).toBe(4);
                    expect(datachangedSpy).toHaveBeenCalled();
                    expect(datachangedSpy.mostRecentCall.args[0]).toBe(store);
                });

                it("should fire add on the source, then the store", function() {
                    var order = [];

                    source.on('add', function() {
                        order.push('source');
                    });
                    store.on('add', function() {
                        order.push('store');
                    });
                    source.add({
                        id: 'foo@sencha.com'
                    });
                    expect(order).toEqual(['source', 'store']);
                });
            });

            describe("with sorting", function() {
                describe("with the source sorted", function() {
                    it("should use the position from the source", function() {
                        source.sort('email');

                        source.add({
                            email: 'aaaa@sencha.com'
                        });

                        expect(getRecValues(source, 'email')).toEqual([
                            'aaaa@sencha.com', 'aaron@sencha.com', 'abe@sencha.com',
                            'ed@sencha.com', 'tommy@sencha.com'
                        ]);

                        expect(getRecValues(store, 'email')).toEqual([
                            'ed@sencha.com', 'abe@sencha.com',
                            'aaaa@sencha.com', // add before aaron
                            'aaron@sencha.com', 'tommy@sencha.com'
                        ]);
                    });
                });

                describe("with the store sorted", function() {
                    it("should add to source and insert in sorted position in store", function() {
                        store.sort('email');

                        var rec = source.add({
                            email: 'bbb@sencha.com'
                        })[0];

                        expect(source.getAt(4)).toBe(rec);
                        expect(store.getAt(2)).toBe(rec);
                    });
                });

                describe("with both sorted", function() {
                    it("should insert into the correct sorted position", function() {
                        store.sort('email');
                        source.sort('email', 'desc');

                        var rec = source.add({
                            email: 'aazzon@sencha.com'
                        })[0];

                        expect(source.getAt(3)).toBe(rec);
                        expect(store.getAt(1)).toBe(rec);
                    });
                });
            });

            describe("with filtering", function() {
                it("should filter out non-matching records", function() {
                    store.filter('group', 'admin');
                    var rec = source.add({
                        email: 'new@sencha.com',
                        group: 'code'
                    })[0];

                    expect(store.indexOf(rec)).toBe(-1);
                });

                it("should include the filtered out record when filters are cleared", function() {
                    store.filter('group', 'admin');
                    var rec = source.add({
                        email: 'new@sencha.com',
                        group: 'code'
                    })[0];

                    store.getFilters().removeAll();
                    expect(store.getAt(4)).toBe(rec);
                });
            });
        });

        describe("adding to the store", function() {
            it("should also add the record to the source", function() {
                var rec = store.add({
                    id: 'new@sencha.com'
                })[0];

                expect(source.getAt(4)).toBe(rec);
            });

            describe("events", function() {
                it("should fire the add/datachanged event on the source", function() {
                    var addSpy = jasmine.createSpy(),
                        datachangedSpy = jasmine.createSpy();

                    source.on('add', addSpy);
                    source.on('datachanged', datachangedSpy);

                    var rec = store.add({
                        id: 'new@sencha.com'
                    })[0],
                    args;

                    expect(addSpy).toHaveBeenCalled();
                    args = addSpy.mostRecentCall.args;
                    expect(args[0]).toBe(source);
                    expect(args[1]).toEqual([rec]);
                    expect(args[2]).toBe(4);
                    expect(datachangedSpy).toHaveBeenCalled();
                    expect(datachangedSpy.mostRecentCall.args[0]).toBe(source);
                });

                it("should fire add on the source, then the store", function() {
                    var order = [];

                    source.on('add', function() {
                        order.push('source');
                    });
                    store.on('add', function() {
                        order.push('store');
                    });
                    store.add({
                        id: 'foo@sencha.com'
                    });
                    expect(order).toEqual(['source', 'store']);
                });
            });

            describe("with sorting", function() {
                describe("with the source sorted", function() {
                    it("should append to the store and add to the sorted position in the source", function() {
                        source.sort('email');
                        var rec = store.add({
                            email: 'aaaa@sencha.com'
                        })[0];

                        expect(source.getAt(0)).toBe(rec);
                        expect(store.getAt(4)).toBe(rec);
                    });
                });

                describe("with the store sorted", function() {
                    it("should append to the source and add to the sorted position in the store", function() {
                        store.sort('email');
                        var rec = source.add({
                            email: 'aaaa@sencha.com'
                        })[0];

                        expect(store.getAt(0)).toBe(rec);
                    });
                });

                describe("with both sorted", function() {
                    it("should insert into the correct sorted position", function() {
                        store.sort('email');
                        source.sort('email', 'desc');
                        var rec = source.add({
                            email: 'aazzon@sencha.com'
                        })[0];

                        expect(source.getAt(3)).toBe(rec);
                        expect(store.getAt(1)).toBe(rec);
                    });
                });
            });
        });
    });

    describe("inserting", function() {
        beforeEach(function() {
            createStore();
        });

        describe("inserting in the source", function() {
            it("should also add to the store", function() {
                var rec = source.insert(0, {
                    id: 'new@sencha.com'
                })[0];

                expect(source.getAt(0)).toBe(rec);
                expect(store.getAt(0)).toBe(rec);
            });

            describe("events", function() {
                it("should fire the add/datachanged event on the store", function() {
                    var addSpy = jasmine.createSpy(),
                        datachangedSpy = jasmine.createSpy(),
                        rec, args;

                    store.on('add', addSpy);
                    store.on('datachanged', datachangedSpy);

                    rec = source.insert(0, {
                        id: 'new@sencha.com'
                    })[0];

                    expect(addSpy).toHaveBeenCalled();
                    args = addSpy.mostRecentCall.args;
                    expect(args[0]).toBe(store);
                    expect(args[1]).toEqual([rec]);
                    expect(args[2]).toBe(0);
                    expect(datachangedSpy).toHaveBeenCalled();
                    expect(datachangedSpy.mostRecentCall.args[0]).toBe(store);
                });

                it("should fire add on the source, then the store", function() {
                    var order = [];

                    source.on('add', function() {
                        order.push('source');
                    });
                    store.on('add', function() {
                        order.push('store');
                    });
                    source.insert(0, {
                        id: 'foo@sencha.com'
                    });
                    expect(order).toEqual(['source', 'store']);
                });
            });

            describe("with sorting", function() {
                describe("with the source sorted", function() {
                    it("should use the position from the source", function() {
                        source.sort('email');

                        source.insert(2, {
                            email: 'aaaa@sencha.com'
                        });

                        expect(getRecValues(source, 'email')).toEqual([
                            'aaaa@sencha.com', 'aaron@sencha.com', 'abe@sencha.com',
                            'ed@sencha.com', 'tommy@sencha.com'
                        ]);

                        expect(getRecValues(store, 'email')).toEqual([
                            'ed@sencha.com', 'abe@sencha.com',
                            'aaaa@sencha.com', // before aaron
                            'aaron@sencha.com', 'tommy@sencha.com'
                        ]);
                    });
                });

                describe("with the store sorted", function() {
                    it("should insert into the specified position in the source and the sorted position in the store", function() {
                        store.sort('email');
                        var rec = source.insert(3, {
                            email: 'aaaa@sencha.com'
                        })[0];

                        expect(source.getAt(3)).toBe(rec);
                        expect(store.getAt(0)).toBe(rec);
                    });
                });

                describe("with both sorted", function() {
                    it("should insert into the sorted position in both stores", function() {
                        store.sort('email');
                        source.sort('email', 'desc');

                        var rec = source.insert(3, {
                            email: 'aazzon@sencha.com'
                        })[0];

                        expect(source.getAt(3)).toBe(rec);
                        expect(store.getAt(1)).toBe(rec);
                    });
                });
            });

            describe("with filtering", function() {
                it("should filter out non-matching records", function() {
                    store.filter('group', 'admin');
                    var rec = source.insert(0, {
                        email: 'new@sencha.com',
                        group: 'code'
                    })[0];

                    expect(store.indexOf(rec)).toBe(-1);
                });

                it("should include the filtered out record when filters are cleared", function() {
                    store.filter('group', 'admin');
                    var rec = source.insert(0, {
                        email: 'new@sencha.com',
                        group: 'code'
                    })[0];

                    store.getFilters().removeAll();
                    expect(source.getAt(0)).toBe(rec);
                });

                it("should position the item correctly when filtered out", function() {
                    store.filter('group', 'admin');
                    var rec = source.insert(2, {
                        email: 'new@sencha.com',
                        group: 'code'
                    })[0];

                    store.getFilters().removeAll();
                    expect(store.getAt(2)).toBe(rec);
                });
            });
        });

        describe("inserting in the store", function() {
            it("should also add the record to the source", function() {
                var rec = store.insert(0, {
                    id: 'new@sencha.com'
                })[0];

                expect(source.getAt(0)).toBe(rec);
            });

            describe("events", function() {
                it("should fire the add/datachanged event on the source", function() {
                    var addSpy = jasmine.createSpy(),
                        datachangedSpy = jasmine.createSpy();

                    source.on('add', addSpy);
                    source.on('datachanged', datachangedSpy);

                    var rec = store.insert(2, {
                        id: 'new@sencha.com'
                    })[0],
                    args;

                    expect(addSpy).toHaveBeenCalled();
                    args = addSpy.mostRecentCall.args;
                    expect(args[0]).toBe(source);
                    expect(args[1]).toEqual([rec]);
                    expect(args[2]).toBe(2);
                    expect(datachangedSpy).toHaveBeenCalled();
                    expect(datachangedSpy.mostRecentCall.args[0]).toBe(source);
                });

                it("should fire add on the source, then the store", function() {
                    var order = [];

                    source.on('add', function() {
                        order.push('source');
                    });
                    store.on('add', function() {
                        order.push('store');
                    });
                    store.insert(1, {
                        id: 'foo@sencha.com'
                    });
                    expect(order).toEqual(['source', 'store']);
                });
            });

            describe("with sorting", function() {
                describe("with the source sorted", function() {
                    it("should insert into the correct sorted position in the source and use the specified position in the store", function() {
                        source.sort('email');
                        var rec = store.insert(2, {
                            email: 'aaaa@sencha.com'
                        })[0];

                        expect(source.getAt(0)).toBe(rec);
                        expect(store.getAt(2)).toBe(rec);
                    });
                });

                describe("with the store sorted", function() {
                    it("should insert into the specified position in the source and the sorted position in the store", function() {
                        store.sort('email');
                        var rec = store.insert(3, {
                            email: 'aaaa@sencha.com'
                        })[0];

                        expect(source.getAt(3)).toBe(rec);
                        expect(store.getAt(0)).toBe(rec);
                    });
                });

                describe("with both sorted", function() {
                    it("should insert into the sorted position in both stores", function() {
                        store.sort('email');
                        source.sort('email', 'desc');

                        var rec = store.insert(3, {
                            email: 'aazzon@sencha.com'
                        })[0];

                        expect(source.getAt(3)).toBe(rec);
                        expect(store.getAt(1)).toBe(rec);
                    });
                });
            });
        });
    });

    describe("removing", function() {
        beforeEach(function() {
            createStore();
        });

        describe("remove", function() {
            describe("removing from the source", function() {
                it("should also remove from the store", function() {
                    source.removeAt(0);
                    expect(store.indexOf(edRec)).toBe(-1);
                });

                it("should fire the remove/datachanged event on the store", function() {
                    var removeSpy = jasmine.createSpy(),
                        datachangedSpy = jasmine.createSpy(),
                        args;

                    store.on('remove', removeSpy);
                    store.on('datachanged', datachangedSpy);

                    store.remove(edRec);

                    expect(removeSpy).toHaveBeenCalled();
                    args = removeSpy.mostRecentCall.args;
                    expect(args[0]).toBe(store);
                    expect(args[1]).toEqual([edRec]);
                    expect(args[2]).toBe(0);
                    expect(datachangedSpy).toHaveBeenCalled();
                    expect(datachangedSpy.mostRecentCall.args[0]).toBe(store);
                });

                it("should fire remove on the source, then the store", function() {
                    var order = [];

                    source.on('remove', function() {
                        order.push('source');
                    });
                    store.on('remove', function() {
                        order.push('store');
                    });
                    source.removeAt(0);
                    expect(order).toEqual(['source', 'store']);
                });

                describe("with filtering", function() {
                    it("should remove from the store when record is filtered out", function() {
                        store.filter('group', 'admin');
                        source.remove(edRec);
                        store.getFilters().removeAll();
                        expect(store.indexOf(edRec)).toBe(-1);
                    });
                });
            });

            describe("removing from the store", function() {
                it("should also remove the record from the source", function() {
                    store.remove(edRec);
                    expect(source.indexOf(edRec)).toBe(-1);
                });

                it("should fire the add/datachanged event on the source", function() {
                    var removeSpy = jasmine.createSpy(),
                        datachangedSpy = jasmine.createSpy(),
                        args;

                    source.on('remove', removeSpy);
                    source.on('datachanged', datachangedSpy);

                    store.remove(edRec);

                    expect(removeSpy).toHaveBeenCalled();
                    args = removeSpy.mostRecentCall.args;
                    expect(args[0]).toBe(source);
                    expect(args[1]).toEqual([edRec]);
                    expect(args[2]).toBe(0);
                    expect(datachangedSpy).toHaveBeenCalled();
                    expect(datachangedSpy.mostRecentCall.args[0]).toBe(source);
                });

                it("should fire add on the source, then the store", function() {
                    var order = [];

                    source.on('remove', function() {
                        order.push('source');
                    });
                    store.on('remove', function() {
                        order.push('store');
                    });
                    store.remove(edRec);
                    expect(order).toEqual(['source', 'store']);
                });
            });
        });

        describe("removeAll", function() {
            it("should not fire a remove event", function() {
                var spy = jasmine.createSpy();

                store.on('remove', spy);
                source.removeAll();
                expect(spy).not.toHaveBeenCalled();
            });

            it("should fire the clear event", function() {
                var spy = jasmine.createSpy();

                store.on('clear', spy);
                source.removeAll();
                expect(spy).toHaveBeenCalled();
                expect(spy.mostRecentCall.args[0]).toBe(store);
            });

            it("should fire the datachanged event", function() {
                var spy = jasmine.createSpy();

                store.on('datachanged', spy);
                source.removeAll();
                expect(spy).toHaveBeenCalled();
                expect(spy.mostRecentCall.args[0]).toBe(store);
            });

            describe("with silent: true", function() {
                it("should not fire the clear event", function() {
                    var spy = jasmine.createSpy();

                    store.on('clear', spy);
                    source.removeAll(true);
                    expect(spy).not.toHaveBeenCalled();
                });

                it("should not fire the datachanged event", function() {
                    var spy = jasmine.createSpy();

                    store.on('datachanged', spy);
                    source.removeAll(true);
                    expect(spy).not.toHaveBeenCalled();
                });
            });
        });
    });

    describe("updating", function() {
        var spy;

        beforeEach(function() {
            createStore();
            spy = jasmine.createSpy();
        });

        describe("via set", function() {
            it("should fire the update event on the source & pass the store, record, type & modified fields", function() {
                var args;

                source.on('update', spy);
                abeRec.set('name', 'foo');
                expect(spy).toHaveBeenCalled();
                expect(spy.callCount).toBe(1);

                args = spy.mostRecentCall.args;
                expect(args[0]).toBe(source);
                expect(args[1]).toBe(abeRec);
                expect(args[2]).toBe(Ext.data.Model.EDIT);
                expect(args[3]).toEqual(['name']);
            });

            it("should fire the update event on the store & pass the store, record, type & modified fields", function() {
                var args;

                store.on('update', spy);
                abeRec.set('name', 'foo');
                expect(spy).toHaveBeenCalled();
                expect(spy.callCount).toBe(1);

                args = spy.mostRecentCall.args;
                expect(args[0]).toBe(store);
                expect(args[1]).toBe(abeRec);
                expect(args[2]).toBe(Ext.data.Model.EDIT);
                expect(args[3]).toEqual(['name']);
            });

            it("should fire the event on the source first, then the store", function() {
                var order = [];

                source.on('update', function() {
                    order.push('source');
                });
                store.on('update', function() {
                    order.push('store');
                });
                edRec.set('name', 'foo');
                expect(order).toEqual(['source', 'store']);
            });

            it("should not fire the event if the record is filtered out of the store", function() {
                source.filter('name', 'Aaron');
                store.on('update', spy);
                abeRec.set('name', 'Foo');
                expect(spy).not.toHaveBeenCalled();
            });
        });

        describe("via commit", function() {
            it("should fire the update event on the source & pass the store, record, type & modified fields", function() {
                var args;

                abeRec.set('name', 'foo');
                source.on('update', spy);
                abeRec.commit();
                expect(spy).toHaveBeenCalled();
                expect(spy.callCount).toBe(1);

                args = spy.mostRecentCall.args;
                expect(args[0]).toBe(source);
                expect(args[1]).toBe(abeRec);
                expect(args[2]).toBe(Ext.data.Model.COMMIT);
                expect(args[3]).toBeNull();
            });

            it("should fire the update event on the store & pass the store, record, type & modified fields", function() {
                var args;

                abeRec.set('name', 'foo');
                store.on('update', spy);
                abeRec.commit();
                expect(spy).toHaveBeenCalled();
                expect(spy.callCount).toBe(1);

                args = spy.mostRecentCall.args;
                expect(args[0]).toBe(store);
                expect(args[1]).toBe(abeRec);
                expect(args[2]).toBe(Ext.data.Model.COMMIT);
                expect(args[3]).toBeNull();
            });

            it("should fire the event on the source first, then the store", function() {
                var order = [];

                edRec.set('name', 'foo');
                source.on('update', function() {
                    order.push('source');
                });
                store.on('update', function() {
                    order.push('store');
                });
                edRec.commit();
                expect(order).toEqual(['source', 'store']);
            });

            it("should not fire the event if the record is filtered out of the store", function() {
                source.filter('name', 'Aaron');
                abeRec.set('name', 'Foo');
                store.on('update', spy);
                abeRec.commit();
                expect(spy).not.toHaveBeenCalled();
            });
        });

        describe("via reject", function() {
            it("should fire the update event on the source & pass the store, record, type & modified fields", function() {
                var args;

                abeRec.set('name', 'foo');
                source.on('update', spy);
                abeRec.reject();
                expect(spy).toHaveBeenCalled();
                expect(spy.callCount).toBe(1);

                args = spy.mostRecentCall.args;
                expect(args[0]).toBe(source);
                expect(args[1]).toBe(abeRec);
                expect(args[2]).toBe(Ext.data.Model.REJECT);
                expect(args[3]).toBeNull();
            });

            it("should fire the update event on the store & pass the store, record, type & modified fields", function() {
                var args;

                abeRec.set('name', 'foo');
                store.on('update', spy);
                abeRec.reject();
                expect(spy).toHaveBeenCalled();
                expect(spy.callCount).toBe(1);

                args = spy.mostRecentCall.args;
                expect(args[0]).toBe(store);
                expect(args[1]).toBe(abeRec);
                expect(args[2]).toBe(Ext.data.Model.REJECT);
                expect(args[3]).toBeNull();
            });

            it("should fire the event on the source first, then the store", function() {
                var order = [];

                edRec.set('name', 'foo');
                source.on('update', function() {
                    order.push('source');
                });
                store.on('update', function() {
                    order.push('store');
                });
                edRec.reject();
                expect(order).toEqual(['source', 'store']);
            });

            it("should not fire the event if the record is filtered out of the store", function() {
                source.filter('name', 'Aaron');
                abeRec.set('name', 'Foo');
                store.on('update', spy);
                abeRec.reject();
                expect(spy).not.toHaveBeenCalled();
            });
        });
    });

    describe("misc", function() {
        var Order, OrderItem, orders;

        beforeEach(function() {
            Order = Ext.define('spec.Order', {
                extend: 'Ext.data.Model',
                fields: ['id']
            });

            OrderItem = Ext.define('spec.OrderItem', {
                extend: 'Ext.data.Model',
                fields: ['id', {
                    name: 'orderId',
                    reference: 'Order'
                }]
            });

            orders = new Ext.data.Store({
                model: Order
            });

            orders.loadRawData([{
                id: 1,
                orderItems: [{
                    id: 1,
                    orderId: 1
                }, {
                    id: 3,
                    orderId: 1
                }]
            }]);

            source = orders.first().orderItems();

            createStore({
                sorters: ['id']
            });
        });

        afterEach(function() {
            orders.destroy();
            Ext.undefine('spec.Order');
            Ext.undefine('spec.OrderItem');
        });

        it("should add records from source in sorted order of chained store", function() {
            source.add({
                id: 2
            });

            // chained store maintains itself in sorted order
            expect(store.getAt(0)).toBe(source.getById(1));
            expect(store.getAt(1)).toBe(source.getById(2));
            expect(store.getAt(2)).toBe(source.getById(3));
        });

        it("should prepend records from source if chained store is not sorted", function() {
            store.setAutoSort(false);

            source.add({
                id: 2
            });

            // The new id:2 record should have been appended
            expect(getRecValues(store, 'id')).toEqual([ 1, 3, 2 ]);

            expect(getRecValues(source, 'id')).toEqual([ 1, 3, 2 ]);
        });

        it('should fire idchanged when the source collection updates a key', function() {
            var spy = spyOnEvent(store, 'idchanged');

            var rec = source.getAt(0);

            rec.set('id', 'foobar');

            // Downstream store must pass on the idchanged event.
            expect(spy.callCount).toBe(1);
            expect(spy.mostRecentCall.args).toEqual([store, rec, 1, 'foobar']);
        });

        it("should maintain synchronization with source store when observers are added", function() {
            source = new Ext.data.Store({
                fields: ['id', 'name'],
                data: [{
                    id: 1,
                    name: 'foo'
                }]
            });

            createStore();

            var spy = spyOnEvent(store, 'remove');

            source.on('remove', function() {
                // the initialization of the collectionkey will call addObserver() in the underlying collection
                // if this happens while we're in the middle of notify(), chained store will be left out
                source.setExtraKeys({
                    byFoo: { property: 'name', root: '' }
                });
            });
            // remove the last record
            source.removeAt(0);
            // if all went as planned, the chained store's collection should have been notified of the removal as well
            expect(store.getCount()).toBe(0);
            expect(spy).toHaveBeenCalled();
        });
    });

    describe("session", function() {
        beforeEach(function() {
            source = Ext.destroy(source);
        });

        it("should return the session from the source", function() {
            source = Ext.destroy(source);

            var s = new Ext.data.Session();

            createSource({
                session: s
            });
            createStore();
            expect(store.getSession()).toBe(s);
        });

        it("should return null when there is no source", function() {
            createStore();
            expect(store.getSession()).toBeNull();
        });
    });

    describe("getModel", function() {
        it("should return the model from the source", function() {
            createStore();
            expect(store.getModel()).toBe(User);
        });

        it("should return null when there is no source", function() {
            source = Ext.destroy(source);
            createStore();
            expect(store.getModel()).toBeNull();
        });
    });

    describe("hasPendingLoad", function() {
        it("should return the hasPendingLoad from the source", function() {
            createStore();
            source.load();
            expect(store.hasPendingLoad()).toBe(true);
            completeWithData([]);
        });

        it("should return false when there is no source", function() {
            source = Ext.destroy(source);
            createStore();
            expect(store.hasPendingLoad()).toBe(false);
        });
    });

    describe("isLoaded", function() {
        it("should return the isLoaded from the source", function() {
            createStore();
            source.load();
            completeWithData([]);
            expect(store.isLoaded()).toBe(true);
        });

        it("should return false when there is no source", function() {
            source = Ext.destroy(source);
            createStore();
            expect(store.isLoaded()).toBe(false);
        });
    });

    describe("isLoading", function() {
        it("should return the isLoading from the source", function() {
            createStore();
            source.load();
            expect(store.isLoading()).toBe(true);
            completeWithData([]);
        });

        it("should return false when there is no source", function() {
            source = Ext.destroy(source);
            createStore();
            expect(store.isLoading()).toBe(false);
        });
    });

    describe("summaries", function() {
        var M = Ext.define(null, {
            extend: 'Ext.data.Model',
            fields: ['group', {
                name: 'rate',
                summary: 'average'
            }],
            summary: {
                maxRate: {
                    summary: 'max',
                    field: 'rate'
                }
            }
        }),
        data;

        beforeEach(function() {
            data = [
                { group: 'g1', rate: 8 },
                { group: 'g1', rate: 12 },
                { group: 'g2', rate: 15 },
                { group: 'g2', rate: 13 }
            ];

            source.destroy();

            createSource({
                model: M,
                data: data
            });
        });

        afterEach(function() {
            data = null;
        });

        describe("group summaries", function() {
            function getSummaryFor(group) {
                return store.getGroups().get(group).getSummaryRecord();
            }

            beforeEach(function() {
                createStore({
                    model: M,
                    groupField: 'group'
                }, false);
            });

            it("should return the calculated values", function() {
                var r = getSummaryFor('g1');

                expect(r.get('rate')).toBe(10);
                expect(r.get('maxRate')).toBe(12);

                r = getSummaryFor('g2');
                expect(r.get('rate')).toBe(14);
                expect(r.get('maxRate')).toBe(15);
            });

            describe("dynamic", function() {
                beforeEach(function() {
                    // Force creation
                    getSummaryFor('g1');
                    getSummaryFor('g2');
                });

                describe("adding", function() {
                    it("should react to a new group being created", function() {
                        source.add({ group: 'g3', rate: 100 });

                        var r = getSummaryFor('g1');

                        expect(r.get('rate')).toBe(10);
                        expect(r.get('maxRate')).toBe(12);

                        r = getSummaryFor('g2');
                        expect(r.get('rate')).toBe(14);
                        expect(r.get('maxRate')).toBe(15);

                        r = getSummaryFor('g3');
                        expect(r.get('rate')).toBe(100);
                        expect(r.get('maxRate')).toBe(100);
                    });

                    it("should react to an add in an existing group", function() {
                        source.add({ group: 'g1', rate: 100 });

                        var r = getSummaryFor('g1');

                        expect(r.get('rate')).toBe(40);
                        expect(r.get('maxRate')).toBe(100);

                        r = getSummaryFor('g2');
                        expect(r.get('rate')).toBe(14);
                        expect(r.get('maxRate')).toBe(15);
                    });
                });

                describe("updating", function() {
                    it("should react to an update", function() {
                        store.getAt(0).set('rate', 200);

                        var r = getSummaryFor('g1');

                        expect(r.get('rate')).toBe(106);
                        expect(r.get('maxRate')).toBe(200);

                        r = getSummaryFor('g2');
                        expect(r.get('rate')).toBe(14);
                        expect(r.get('maxRate')).toBe(15);
                    });

                    it("should react to an update that forces a group change", function() {
                        store.getAt(0).set('group', 'g2');

                        var r = getSummaryFor('g1');

                        expect(r.get('rate')).toBe(12);
                        expect(r.get('maxRate')).toBe(12);

                        r = getSummaryFor('g2');
                        expect(r.get('rate')).toBe(12);
                        expect(r.get('maxRate')).toBe(15);
                    });
                });

                it("should react to a remove", function() {
                    source.removeAt(1);

                    var r = getSummaryFor('g1');

                    expect(r.get('rate')).toBe(8);
                    expect(r.get('maxRate')).toBe(8);

                    r = getSummaryFor('g2');
                    expect(r.get('rate')).toBe(14);
                    expect(r.get('maxRate')).toBe(15);
                });

                it("should react to filtering", function() {
                    store.getFilters().add({
                        filterFn: function(rec) {
                            var rate = rec.get('rate');

                            return rate === 12 || rate === 13;
                        }
                    });

                    var r = getSummaryFor('g1');

                    expect(r.get('rate')).toBe(12);
                    expect(r.get('maxRate')).toBe(12);

                    r = getSummaryFor('g2');
                    expect(r.get('rate')).toBe(13);
                    expect(r.get('maxRate')).toBe(13);

                    store.getFilters().removeAll();

                    r = getSummaryFor('g1');
                    expect(r.get('rate')).toBe(10);
                    expect(r.get('maxRate')).toBe(12);

                    r = getSummaryFor('g2');
                    expect(r.get('rate')).toBe(14);
                    expect(r.get('maxRate')).toBe(15);
                });

                it("should react to a loadData", function() {
                    source.loadData([
                        { group: 'g1', rate: 82 },
                        { group: 'g1', rate: 81 },
                        { group: 'g2', rate: 99 },
                        { group: 'g2', rate: 100 }
                    ]);

                    var r = getSummaryFor('g1');

                    expect(r.get('rate')).toBe(81.5);
                    expect(r.get('maxRate')).toBe(82);

                    r = getSummaryFor('g2');
                    expect(r.get('rate')).toBe(99.5);
                    expect(r.get('maxRate')).toBe(100);
                });
            });
        });

        describe("total summaries", function() {
            beforeEach(function() {
                createStore({
                    model: M
                }, false);
            });

            it("should return the calculated values", function() {
                var r = store.getSummaryRecord();

                expect(r.get('rate')).toBe(12);
                expect(r.get('maxRate')).toBe(15);
            });

            describe("dynamic changes", function() {
                beforeEach(function() {
                    // Force creation
                    store.getSummaryRecord();
                });

                it("should react to an add", function() {
                    source.add({ rate: 20 });
                    var r = store.getSummaryRecord();

                    expect(r.get('rate')).toBe(13.6);
                    expect(r.get('maxRate')).toBe(20);
                });

                it("should react to a remove", function() {
                    source.removeAt(2);
                    var r = store.getSummaryRecord();

                    expect(r.get('rate')).toBe(11);
                    expect(r.get('maxRate')).toBe(13);
                });

                it("should react to a removeAll", function() {
                    source.removeAll();
                    var r = store.getSummaryRecord();

                    expect(r.get('rate')).toBeUndefined();
                    expect(r.get('maxRate')).toBeUndefined();
                });

                it("should react to an update", function() {
                    source.getAt(2).set('rate', 1);
                    var r = store.getSummaryRecord();

                    expect(r.get('rate')).toBe(8.5);
                    expect(r.get('maxRate')).toBe(13);
                });

                it("should react to filtering", function() {
                    store.getFilters().add({
                        filterFn: function(rec) {
                            return rec.get('rate') < 13;
                        }
                    });
                    var r = store.getSummaryRecord();

                    expect(r.get('rate')).toBe(10);
                    expect(r.get('maxRate')).toBe(12);

                    store.getFilters().removeAll();
                    r = store.getSummaryRecord();
                    expect(r.get('rate')).toBe(12);
                    expect(r.get('maxRate')).toBe(15);
                });

                it("should react to a loadData", function() {
                    source.loadData([
                        { rate: 20 },
                        { rate: 30 }
                    ]);
                    var r = store.getSummaryRecord();

                    expect(r.get('rate')).toBe(25);
                    expect(r.get('maxRate')).toBe(30);
                });
            });
        });
    });
});

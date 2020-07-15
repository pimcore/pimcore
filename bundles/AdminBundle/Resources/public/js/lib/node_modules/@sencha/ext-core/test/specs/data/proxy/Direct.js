topSuite("Ext.data.proxy.Direct", ['Ext.data.ArrayStore', 'Ext.direct.RemotingProvider'], function() {
    var proxy, provider, spies, Writer, writer, Model,
        readSpy, createSpy, updateSpy, destroySpy, directSpy, namedSpy, orderedSpy;

    function makeApi(cfg) {
        cfg = Ext.apply({
            "namespace": "spec",
            type: "remoting",
            url: "fake",
            enableBuffer: false
        }, cfg);

        provider = Ext.direct.Manager.addProvider(cfg);
    }

    function makeProxy(cfg, ctor) {
        var writerCfg = cfg && cfg.writer;

        writer = new Writer(writerCfg || {});

        cfg = Ext.apply({
            writer: writer
        }, cfg);

        proxy = new (ctor || Ext.data.proxy.Direct)(cfg);
    }

    function makeSpy(name) {
        var directCfg = spec.DirectSpecs[name].directCfg,
            spy = spyOn(spec.DirectSpecs, name);

        spy.directCfg = directCfg;

        return spy;
    }

    function returnData(spy, data) {
        spy.andCallFake(function(params, callback, scope) {
            Ext.callback(callback, scope, [
                null,
                {
                    status: true,
                    result: data
                }
            ]);
        });
    }

    function makeOperation(op, action) {
        op = Ext.apply({}, op);

        var records = op.records,
            rec, i, len;

        if (records) {
            for (i = 0, len = records.length; i < len; i++) {
                records[i] = new Model(records[i]);
            }
        }

        return op;
    }

    function readSome(proxyObject, operation) {
        proxyObject = proxyObject || proxy;
        operation = makeOperation(operation);

        proxyObject.read(new Ext.data.operation.Read(operation));
    }

    function createSome(proxyObject, operation) {
        proxyObject = proxyObject || proxy;
        operation = makeOperation(operation);

        proxyObject.create(new Ext.data.operation.Create(operation || {}));
    }

    function updateSome(proxyObject, operation) {
        proxyObject = proxyObject || proxy;
        operation = makeOperation(operation);

        proxyObject.update(new Ext.data.operation.Update(operation || {}));
    }

    function destroySome(proxyObject, operation) {
        proxyObject = proxyObject || proxy;
        operation = makeOperation(operation);

        proxyObject.erase(new Ext.data.operation.Destroy(operation || {}));
    }

    function expectArgs(spy, expected) {
        var args = spy.mostRecentCall.args;

        expect(args[0]).toEqual(expected);
        expect(typeof args[1]).toBe('function');
    }

    beforeEach(function() {
        Model = Ext.define(null, {
            extend: 'Ext.data.Model',

            fields: [
                { name: 'id', type: 'integer' },
                { name: 'name', type: 'string' }
            ],

            idProperty: 'id'
        });

        Writer = Ext.define(null, {
            extend: 'Ext.data.writer.Json',

            write: function(request) {
                var op = request.getOperation(),
                    data = op.data;

                if (data) {
                    request.setJsonData(data);
                }
                else {
                    return this.callParent(arguments);
                }

                return request;
            }
        });

        MockAjaxManager.addMethods();
    });

    afterEach(function() {
        if (proxy) {
            Ext.destroy(proxy);
        }

        if (writer) {
            Ext.destroy(writer);
        }

        if (provider) {
            Ext.direct.Manager.removeProvider(provider);
            provider.destroy();
        }

        provider = proxy = Writer = writer = Model = null;
        readSpy = createSpy = updateSpy = destroySpy = directSpy = null;
        namedSpy = orderedSpy = spies = window.spec = null;

        MockAjaxManager.removeMethods();
    });

    describe("API declaration", function() {
        beforeEach(function() {
            makeApi({
                actions: {
                    'DirectSpecs': [{
                        len: 0,
                        name: 'read'
                    }, {
                        len: 0,
                        name: 'create'
                    }, {
                        len: 0,
                        name: 'update'
                    }, {
                        len: 0,
                        name: 'destroy'
                    }, {
                        len: 0,
                        name: 'directFn'
                    }]
                }
            });

            readSpy = makeSpy('read');
            createSpy = makeSpy('create');
            updateSpy = makeSpy('update');
            destroySpy = makeSpy('destroy');
            directSpy = makeSpy('directFn');

            spies = {
                read: readSpy,
                create: createSpy,
                update: updateSpy,
                destroy: destroySpy,
                directFn: directSpy
            };
        });

        describe("directFn", function() {
            beforeEach(function() {
                makeProxy({
                    directFn: directSpy
                });
            });

            it("should be used to read", function() {
                readSome();

                expect(directSpy).toHaveBeenCalled();
            });

            it("should be used to create", function() {
                createSome();

                expect(directSpy).toHaveBeenCalled();
            });

            it("should be used to update", function() {
                updateSome();

                expect(directSpy).toHaveBeenCalled();
            });

            it("should be used to destroy", function() {
                destroySome();

                expect(directSpy).toHaveBeenCalled();
            });
        });

        describe("api blob", function() {
            beforeEach(function() {
                makeProxy({
                    api: {
                        read: readSpy,
                        create: createSpy,
                        update: updateSpy,
                        destroy: destroySpy
                    }
                });
            });

            it("should be used to read", function() {
                readSome();

                expect(readSpy).toHaveBeenCalled();
            });

            it("should be used to create", function() {
                createSome();

                expect(createSpy).toHaveBeenCalled();
            });

            it("should be used to update", function() {
                updateSome();

                expect(updateSpy).toHaveBeenCalled();
            });

            it("should be used to destroy", function() {
                destroySome();

                expect(destroySpy).toHaveBeenCalled();
            });
        });

        describe("both directFn and api blob", function() {
            function makeSuite(name, wantCalled, wantNotCalled, opFn) {
                return describe(name, function() {
                    beforeEach(opFn);

                    it("should call " + wantCalled, function() {
                        var spy = spies[wantCalled];

                        expect(spy).toHaveBeenCalled();
                    });

                    it("should not call " + wantNotCalled, function() {
                        var spy = spies[wantNotCalled];

                        expect(spy).not.toHaveBeenCalled();
                    });
                });
            }

            beforeEach(function() {
                // This configuration is taken from customer use case,
                // see https://sencha.jira.com/browse/EXTJS-14843
                makeProxy({
                    api: {
                        create: createSpy,
                        update: updateSpy,
                        destroy: destroySpy
                    },
                    directFn: directSpy
                });
            });

            makeSuite('read',    'directFn', 'read',     function() { readSome(); });
            makeSuite('create',  'create',   'directFn', function() { createSome(); });
            makeSuite('update',  'update',   'directFn', function() { updateSome(); });
            makeSuite('destroy', 'destroy',  'directFn', function() { destroySome(); });
        });

        describe("string name resolving", function() {
            describe("directFn", function() {
                beforeEach(function() {
                    makeProxy({
                        directFn: 'spec.DirectSpecs.directFn'
                    });
                });

                it("should resolve directFn", function() {
                    readSome();

                    expect(directSpy.callCount).toBe(1);
                });

                it("should be able to resolve a new directFn after loading", function() {
                    readSome(); // To resolve the first time

                    proxy.setDirectFn('spec.DirectSpecs.read');

                    readSome();

                    expect(readSpy.callCount).toBe(1);
                });
            });

            describe("api blob", function() {
                describe("no prefix", function() {
                    describe("initial", function() {
                        beforeEach(function() {
                            makeProxy({
                                api: {
                                    read: 'spec.DirectSpecs.read',
                                    create: 'spec.DirectSpecs.create',
                                    update: 'spec.DirectSpecs.update',
                                    destroy: 'spec.DirectSpecs.destroy'
                                }
                            });
                        });

                        it("should resolve read fn", function() {
                            readSome();

                            expect(readSpy.callCount).toBe(1);
                        });

                        it("should resolve create fn", function() {
                            createSome();

                            expect(createSpy.callCount).toBe(1);
                        });

                        it("should resolve update fn", function() {
                            updateSome();

                            expect(updateSpy.callCount).toBe(1);
                        });

                        it("should resolve destroy fn", function() {
                            destroySome();

                            expect(destroySpy.callCount).toBe(1);
                        });
                    });

                    describe("re-initializing", function() {
                        beforeEach(function() {
                            makeProxy({
                                api: {
                                    read: 'spec.DirectSpecs.directFn',
                                    create: 'spec.DirectSpecs.directFn',
                                    update: 'spec.DirectSpecs.directFn',
                                    destroy: 'spec.DirectSpecs.directFn'
                                }
                            });

                            it("should resolve to directFn upfront", function() {
                                readSome();

                                expect(directSpy.callCount).toBe(1);
                            });

                            describe("after re-init", function() {
                                beforeEach(function() {
                                    proxy.setApi({
                                        read: 'spec.DirectSpecs.read',
                                        create: 'spec.DirectSpecs.create',
                                        update: 'spec.DirectSpecs.update',
                                        destroy: 'spec.DirectSpecs.destroy'
                                    });
                                });

                                it("should resolve read fn", function() {
                                    readSome();

                                    expect(readSpy.callCount).toBe(1);
                                });

                                it("should resolve create fn", function() {
                                    createSome();

                                    expect(createSpy.callCount).toBe(1);
                                });

                                it("should resolve update fn", function() {
                                    updateSome();

                                    expect(updateSpy.callCount).toBe(1);
                                });

                                it("should resolve destroy fn", function() {
                                    destroySome();

                                    expect(destroySpy.callCount).toBe(1);
                                });
                            });
                        });
                    });
                });

                describe("with prefix", function() {
                    describe("initial", function() {
                        beforeEach(function() {
                            makeProxy({
                                api: {
                                    prefix: 'spec.DirectSpecs',
                                    read: 'read',
                                    create: 'create',
                                    update: 'update',
                                    destroy: 'destroy'
                                }
                            });
                        });

                        it("should resolve read fn", function() {
                            readSome();

                            expect(readSpy.callCount).toBe(1);
                        });

                        it("should resolve create fn", function() {
                            createSome();

                            expect(createSpy.callCount).toBe(1);
                        });

                        it("should resolve update fn", function() {
                            updateSome();

                            expect(updateSpy.callCount).toBe(1);
                        });

                        it("should resolve destroy fn", function() {
                            destroySome();

                            expect(destroySpy.callCount).toBe(1);
                        });
                    });

                    describe("re-initializing", function() {
                        beforeEach(function() {
                            makeProxy({
                                api: {
                                    prefix: 'spec.DirectSpecs.',
                                    read: 'directFn',
                                    create: 'directFn',
                                    update: 'directFn',
                                    destroy: 'directFn'
                                }
                            });

                            it("should resolve to directFn upfront", function() {
                                readSome();

                                expect(directSpy.callCount).toBe(1);
                            });

                            describe("after re-init", function() {
                                beforeEach(function() {
                                    proxy.setApi({
                                        read: 'read',
                                        create: 'create',
                                        update: 'update',
                                        destroy: 'destroy'
                                    });
                                });

                                it("should resolve read fn", function() {
                                    readSome();

                                    expect(readSpy.callCount).toBe(1);
                                });

                                it("should resolve create fn", function() {
                                    createSome();

                                    expect(createSpy.callCount).toBe(1);
                                });

                                it("should resolve update fn", function() {
                                    updateSome();

                                    expect(updateSpy.callCount).toBe(1);
                                });

                                it("should resolve destroy fn", function() {
                                    destroySome();

                                    expect(destroySpy.callCount).toBe(1);
                                });
                            });

                            describe("after re-init with no prefix", function() {
                                beforeEach(function() {
                                    proxy.setApi({
                                        prefix: null,
                                        read: 'spec.DirectSpecs.read',
                                        create: 'spec.DirectSpecs.create',
                                        update: 'spec.DirectSpecs.update',
                                        destroy: 'spec.DirectSpecs.destroy'
                                    });
                                });

                                it("should resolve read fn", function() {
                                    readSome();

                                    expect(readSpy.callCount).toBe(1);
                                });

                                it("should resolve create fn", function() {
                                    createSome();

                                    expect(createSpy.callCount).toBe(1);
                                });

                                it("should resolve update fn", function() {
                                    updateSome();

                                    expect(updateSpy.callCount).toBe(1);
                                });

                                it("should resolve destroy fn", function() {
                                    destroySome();

                                    expect(destroySpy.callCount).toBe(1);
                                });
                            });
                        });
                    });

                    describe("merged through inheritance", function() {
                        var Foo, Bar;

                        beforeEach(function() {
                            Foo = Ext.define(null, {
                                extend: 'Ext.data.proxy.Direct',

                                api: {
                                    read: 'read',
                                    create: 'create',
                                    update: 'update',
                                    destroy: 'destroy'
                                }
                            });

                            Bar = Ext.define(null, {
                                extend: Foo,

                                api: {
                                    prefix: 'spec.DirectSpecs'
                                }
                            });

                            makeProxy(null, Bar);
                        });

                        afterEach(function() {
                            Foo = Bar = null;
                        });

                        it("should resolve read fn", function() {
                            readSome();

                            expect(readSpy.callCount).toBe(1);
                        });

                        it("should resolve create fn", function() {
                            createSome();

                            expect(createSpy.callCount).toBe(1);
                        });

                        it("should resolve update fn", function() {
                            updateSome();

                            expect(updateSpy.callCount).toBe(1);
                        });

                        it("should resolve destroy fn", function() {
                            destroySome();

                            expect(destroySpy.callCount).toBe(1);
                        });
                    });
                });
            });

            describe("both directFn and api blob", function() {
                beforeEach(function() {
                    makeProxy({
                        api: {
                            read: 'spec.DirectSpecs.read',
                            create: 'spec.DirectSpecs.create',
                            update: 'spec.DirectSpecs.update',
                            destroy: 'spec.DirectSpecs.destroy'
                        },
                        directFn: 'spec.DirectSpecs.directFn'
                    });

                    proxy.resolveMethods();
                });

                it("should resolve directFn", function() {
                    expect(proxy.directFn).toBe(directSpy);
                });

                it("should resolve api.read", function() {
                    expect(proxy.api.read).toBe(readSpy);
                });

                it("should resolve api.create", function() {
                    expect(proxy.api.create).toBe(createSpy);
                });

                it("should resolve api.update", function() {
                    expect(proxy.api.update).toBe(updateSpy);
                });

                it("should resolve api.destroy", function() {
                    expect(proxy.api.destroy).toBe(destroySpy);
                });
            });
        });
    });

    describe("invalid API", function() {
        // https://sencha.jira.com/browse/EXTJS-16255
        it("should not cause stack overflow when both params and len is declared", function() {
            makeApi({
                actions: {
                    "Proxy.Query": [
                        {
                            "name": "GetUserlog",
                            "strict": false,
                            "params": [
                                "username",
                                "start",
                                "limit",
                                "sort"
                            ],
                            "len": 4
                        }
                    ]
                }
            });

            makeProxy({ directFn: 'Proxy.Query.GetUserlog' });

            // Console error is expected
            spyOn(Ext, 'log');

            expect(function() {
                readSome();
            }).toThrow('Incorrect parameters for Direct proxy "read" operation');
        });
    });

    describe("store handling with canonical API", function() {
        var store, loadSpy;

        beforeEach(function() {
            makeApi({
                actions: {
                    DirectSpecs: [
                        { name: 'readNamed', params: [], strict: false },
                        { name: 'readOrdered', len: 1 },
                        { name: 'create', len: 1 },
                        { name: 'update', len: 1 },
                        { name: 'destroy', len: 1 }
                    ]
                }
            });

            namedSpy = makeSpy('readNamed');
            orderedSpy = makeSpy('readOrdered');
            createSpy = makeSpy('create');
            updateSpy = makeSpy('update');
            destroySpy = makeSpy('destroy');

            makeProxy({
                api: {
                    read: orderedSpy,
                    create: createSpy,
                    update: updateSpy,
                    destroy: destroySpy
                }
            });

            loadSpy = jasmine.createSpy('store load');

            store = new Ext.data.Store({
                model: Model,
                proxy: proxy,
                autoLoad: false,
                asynchronousLoad: false,
                listeners: {
                    load: loadSpy
                }
            });
        });

        afterEach(function() {
            store = Ext.destroy(store);
        });

        describe("read", function() {
            var result = [
                { id: 1, name: 'foo' },
                { id: 2, name: 'bar' },
                { id: 3, name: 'qux' }
            ];

            beforeEach(function() {
                returnData(namedSpy, result);
                returnData(orderedSpy, result);
            });

            describe("with named", function() {
                beforeEach(function() {
                    proxy.api.read = namedSpy;
                    store.load();
                    waitForSpy(loadSpy);
                });

                it("should load the records in the store", function() {
                    expect(store.getCount()).toBe(3);
                    expect(store.getAt(0).get('name')).toBe('foo');
                    expect(store.getAt(1).get('name')).toBe('bar');
                    expect(store.getAt(2).get('name')).toBe('qux');
                });

                it("should pass correct params to the fn", function() {
                    expectArgs(proxy.api.read, {
                        page: 1,
                        start: 0,
                        limit: 25
                    });
                });
            });

            describe("with ordered", function() {
                beforeEach(function() {
                    proxy.api.read = orderedSpy;
                });

                describe("no paramOrder", function() {
                    beforeEach(function() {
                        store.load();
                        waitForSpy(loadSpy);
                    });

                    it("should load the records in the store", function() {
                        expect(store.getCount()).toBe(3);
                        expect(store.getAt(0).get('name')).toBe('foo');
                        expect(store.getAt(1).get('name')).toBe('bar');
                        expect(store.getAt(2).get('name')).toBe('qux');
                    });

                    it("should pass correct params to the fn", function() {
                        expectArgs(proxy.api.read, {
                            page: 1,
                            start: 0,
                            limit: 25
                        });
                    });
                });

                describe("with paramOrder", function() {
                    beforeEach(function() {
                        proxy.setParamOrder('start,limit,page');
                        store.load();
                        waitForSpy(loadSpy);
                    });

                    it("should load the records in the store", function() {
                        expect(store.getCount()).toBe(3);
                        expect(store.getAt(0).get('name')).toBe('foo');
                        expect(store.getAt(1).get('name')).toBe('bar');
                        expect(store.getAt(2).get('name')).toBe('qux');
                    });

                    it("should pass correct params to the fn", function() {
                        expectArgs(proxy.api.read, [0, 25, 1]);
                    });
                });
            });
        });

        describe("create", function() {
            describe("single record, allowSingle == true", function() {
                beforeEach(function() {
                    store.add(
                        { name: 'xyzzy' }
                    );
                });

                describe("no paramOrder", function() {
                    beforeEach(function() {
                        store.sync();
                    });

                    it("should pass correct params to the fn", function() {
                        expectArgs(createSpy, {
                            id: store.getAt(0).id, name: 'xyzzy'
                        });
                    });
                });

                describe("with paramOrder", function() {
                    describe("matching number of arguments", function() {
                        beforeEach(function() {
                            proxy.setParamOrder('name');
                            store.sync();
                        });

                        it("should pass correct params to the fn", function() {
                            expectArgs(createSpy, 'xyzzy');
                        });
                    });

                    describe("paramOrder.length > method.len", function() {
                        beforeEach(function() {
                            proxy.setParamOrder('id,name');
                            store.sync();
                        });

                        it("should pass correct params to the fn", function() {
                            expectArgs(createSpy, [
                                store.getAt(0).id, 'xyzzy'
                            ]);
                        });
                    });
                });
            });

            describe("single record, allowSingle == false", function() {
                beforeEach(function() {
                    proxy.getWriter().setAllowSingle(false);
                    store.add(
                        { name: 'xyzzy' }
                    );
                });

                describe("no paramOrder", function() {
                    beforeEach(function() {
                        store.sync();
                    });

                    it("should pass correct params to the fn", function() {
                        expectArgs(createSpy, [
                            { id: store.getAt(0).id, name: 'xyzzy' }
                        ]);
                    });
                });

                describe("with paramOrder", function() {
                    describe("matching number of arguments", function() {
                        beforeEach(function() {
                            proxy.setParamOrder('name');
                            store.sync();
                        });

                        it("should pass correct params to the fn", function() {
                            expectArgs(createSpy, ['xyzzy']);
                        });
                    });

                    describe("paramOrder.length > method.len", function() {
                        beforeEach(function() {
                            proxy.setParamOrder('id,name');
                            store.sync();
                        });

                        it("should pass correct params to the fn", function() {
                            expectArgs(createSpy, [
                                [ store.getAt(0).id, 'xyzzy' ]
                            ]);
                        });
                    });
                });
            });

            describe("multiple records", function() {
                beforeEach(function() {
                    store.add(
                        { name: 'xyzzy' },
                        { name: 'zyxxy' }
                    );
                });

                describe("no paramOrder", function() {
                    beforeEach(function() {
                        store.sync();
                    });

                    it("should pass correct params to the fn", function() {
                        expectArgs(createSpy, [
                            { id: store.getAt(0).id, name: 'xyzzy' },
                            { id: store.getAt(1).id, name: 'zyxxy' }
                        ]);
                    });
                });

                describe("with paramOrder", function() {
                    describe("matching number of arguments", function() {
                        beforeEach(function() {
                            proxy.setParamOrder('name');
                            store.sync();
                        });

                        it("should pass correct params to the fn", function() {
                            expectArgs(createSpy, ['xyzzy', 'zyxxy']);
                        });
                    });

                    describe("paramOrder.length > method.len", function() {
                        beforeEach(function() {
                            proxy.setParamOrder('id,name');
                            store.sync();
                        });

                        it("should pass correct params to the fn", function() {
                            expectArgs(createSpy, [
                                [store.getAt(0).id, 'xyzzy'],
                                [store.getAt(1).id, 'zyxxy']
                            ]);
                        });
                    });
                });
            });
        });

        describe("update", function() {
            beforeEach(function() {
                store.loadRawData([
                    { id: 41, name: 'blergo' },
                    { id: 42, name: 'frobbe' }
                ]);

                store.getAt(1).set({ name: 'throbbe' });
            });

            describe("single record, allowSingle == true", function() {
                describe("no paramOrder", function() {
                    beforeEach(function() {
                        store.sync();
                    });

                    it("should pass correct params to the fn", function() {
                        expectArgs(updateSpy, {
                            id: 42,
                            name: 'throbbe'
                        });
                    });
                });

                describe("with paramOrder", function() {
                    describe("matching number of arguments", function() {
                        beforeEach(function() {
                            proxy.setParamOrder('name');
                            store.sync();
                        });

                        it("should pass correct params to the fn", function() {
                            expectArgs(updateSpy, 'throbbe');
                        });
                    });

                    describe("paramOrder.length > method.len", function() {
                        beforeEach(function() {
                            proxy.setParamOrder('id,name');
                            store.sync();
                        });

                        it("should pass correct params to the fn", function() {
                            expectArgs(updateSpy, [42, 'throbbe']);
                        });
                    });
                });
            });

            describe("single record, allowSingle == false", function() {
                beforeEach(function() {
                    proxy.getWriter().setAllowSingle(false);
                });

                describe("no paramOrder", function() {
                    beforeEach(function() {
                        store.sync();
                    });

                    it("should pass correct params to the fn", function() {
                        expectArgs(updateSpy, [
                            { id: 42, name: 'throbbe' }
                        ]);
                    });
                });

                describe("with paramOrder", function() {
                    describe("matching number of arguments", function() {
                        beforeEach(function() {
                            proxy.setParamOrder('name');
                            store.sync();
                        });

                        it("should pass correct params to the fn", function() {
                            expectArgs(updateSpy, ['throbbe']);
                        });
                    });

                    describe("paramOrder.length > method.len", function() {
                        beforeEach(function() {
                            proxy.setParamOrder('id,name');
                            store.sync();
                        });

                        it("should pass correct params to the fn", function() {
                            expectArgs(updateSpy, [ [42, 'throbbe'] ]);
                        });
                    });
                });
            });

            describe("multiple records", function() {
                beforeEach(function() {
                    store.getAt(0).set({ name: 'zingbong' });
                });

                describe("no paramOrder", function() {
                    beforeEach(function() {
                        store.sync();
                    });

                    it("should pass correct params to the fn", function() {
                        expectArgs(updateSpy, [
                            { id: 41, name: 'zingbong' },
                            { id: 42, name: 'throbbe' }
                        ]);
                    });
                });

                describe("with paramOrder", function() {
                    describe("matching number of arguments", function() {
                        beforeEach(function() {
                            proxy.setParamOrder('name');
                            store.sync();
                        });

                        it("should pass correct params to the fn", function() {
                            expectArgs(updateSpy, ['zingbong', 'throbbe']);
                        });
                    });

                    describe("paramOrder.length > method.len", function() {
                        beforeEach(function() {
                            proxy.setParamOrder('id,name');
                            store.sync();
                        });

                        it("should pass correct params to the fn", function() {
                            expectArgs(updateSpy, [
                                [41, 'zingbong'], [42, 'throbbe']
                            ]);
                        });
                    });
                });
            });
        });

        describe("destroy", function() {
            beforeEach(function() {
                store.loadRawData([
                    { id: 7, name: 'yin' },
                    { id: 8, name: 'yang' }
                ]);
            });

            describe("single record, allowSingle == true", function() {
                describe("no paramOrder", function() {
                    beforeEach(function() {
                        store.removeAt(0);
                        store.sync();
                    });

                    it("should pass correct params to the fn", function() {
                        expectArgs(destroySpy, { id: 7 });
                    });
                });

                describe("with paramOrder", function() {
                    beforeEach(function() {
                        proxy.setParamOrder('id');
                        store.removeAt(0);
                        store.sync();
                    });

                    it("should pass correct params to the fn", function() {
                        expectArgs(destroySpy, 7);
                    });
                });
            });

            describe("single record, allowSingle == false", function() {
                beforeEach(function() {
                    proxy.getWriter().setAllowSingle(false);
                });

                describe("no paramOrder", function() {
                    beforeEach(function() {
                        store.removeAt(1);
                        store.sync();
                    });

                    it("should pass correct params to the fn", function() {
                        expectArgs(destroySpy, [{ id: 8 }]);
                    });
                });

                describe("with paramOrder", function() {
                    beforeEach(function() {
                        proxy.setParamOrder('id');
                        store.removeAt(1);
                        store.sync();
                    });

                    it("should pass correct params to the fn", function() {
                        expectArgs(destroySpy, [8]);
                    });
                });
            });

            describe("multiple records", function() {
                describe("no paramOrder", function() {
                    beforeEach(function() {
                        store.removeAll();
                        store.sync();
                    });

                    it("should pass correct params to the fn", function() {
                        expectArgs(destroySpy, [
                            { id: 8 }, { id: 7 }
                        ]);
                    });
                });

                describe("with paramOrder", function() {
                    beforeEach(function() {
                        proxy.setParamOrder('id');
                        store.removeAll();
                        store.sync();
                    });

                    it("should pass correct params to the fn", function() {
                        expectArgs(destroySpy, [8, 7]);
                    });
                });
            });
        });
    });

    describe("params", function() {
        var ordered2Spy;

        beforeEach(function() {
            makeApi({
                actions: {
                    DirectSpecs: [{
                        name: 'named',
                        params: ['blerg']
                    }, {
                        name: 'ordered',
                        len: 1
                    }, {
                        name: 'ordered2',
                        len: 2
                    }]
                }
            });

            namedSpy = makeSpy('named');
            orderedSpy = makeSpy('ordered');
            ordered2Spy = makeSpy('ordered2');
        });

        afterEach(function() {
            ordered2Spy = null;
        });

        describe("with named fn", function() {
            beforeEach(function() {
                makeProxy({ directFn: namedSpy });
            });

            it("should pass params to read method", function() {
                readSome(proxy, { params: { blerg: -1 } });

                expectArgs(namedSpy, {
                    blerg: -1
                });
            });
        });

        describe("with ordered fn", function() {
            describe("paramOrder", function() {
                beforeEach(function() {
                    makeProxy({ directFn: ordered2Spy });
                    proxy.setParamOrder('foo,bar');
                });

                describe("read", function() {
                    it("should use paramOrder", function() {
                        readSome(proxy, { params: { foo: 1, bar: 101 } });

                        var args = ordered2Spy.mostRecentCall.args;

                        expect(args[0]).toBe(1);
                        expect(args[1]).toBe(101);
                    });
                });
            });

            describe("paramsAsHash == true", function() {
                beforeEach(function() {
                    makeProxy({ directFn: orderedSpy });
                    proxy.setParamsAsHash(true);
                });

                it("should pass an object to read method", function() {
                    readSome(proxy, { params: { foo: 'bar', blerg: 'throbbe' } });

                    expect(orderedSpy.mostRecentCall.args[0]).toEqual({
                        foo: 'bar',
                        blerg: 'throbbe'
                    });
                });
            });

            describe("paramsAsHash == false", function() {
                beforeEach(function() {
                    makeProxy({ directFn: orderedSpy });
                    proxy.setParamsAsHash(false);
                });

                it("should pass object as argument to read method", function() {
                    readSome(proxy, { params: { throbbe: 'knurl', bonzo: 'gurgle' } });

                    expect(orderedSpy.mostRecentCall.args[0]).toEqual({
                        throbbe: 'knurl',
                        bonzo: 'gurgle'
                    });
                });
            });
        });
    });

    describe("extraParams", function() {
        var ordered2Spy;

        beforeEach(function() {
            makeApi({
                actions: {
                    'DirectSpecs': [{
                        name: 'named',
                        params: ['blerg']
                    }, {
                        name: 'ordered',
                        len: 1
                    }, {
                        name: 'ordered2',
                        len: 2
                    }]
                }
            });

            namedSpy = makeSpy('named');
            orderedSpy = makeSpy('ordered');
            ordered2Spy = makeSpy('ordered2');
        });

        afterEach(function() {
            ordered2Spy = null;
        });

        describe("with named fn", function() {
            beforeEach(function() {
                makeProxy({
                    directFn: namedSpy,
                    extraParams: { foo: true, bar: false }
                });
            });

            it("should pass extraParams with read", function() {
                readSome(proxy, { params: { blerg: 42 } });

                expect(namedSpy.mostRecentCall.args[0]).toEqual({
                    foo: true,
                    bar: false,
                    blerg: 42
                });
            });

            it("should not pass extraParams with create", function() {
                createSome();

                expect(namedSpy.mostRecentCall.args[0]).toBe(undefined);
            });

            it("should not pass extraParams with update", function() {
                updateSome();

                expect(namedSpy.mostRecentCall.args[0]).toBe(undefined);
            });

            it("should not pass extraParams with destroy", function() {
                destroySome();

                expect(namedSpy.mostRecentCall.args[0]).toBe(undefined);
            });
        });

        describe("with ordered fn", function() {
            beforeEach(function() {
                makeProxy({
                    directFn: orderedSpy,
                    extraParams: { foo: true, bar: false }
                });
            });

            describe("read", function() {
                it("should pass an object by default", function() {
                    readSome(proxy, { params: { blerg: 43 } });

                    // This is a bug in Direct proxy that we want to keep
                    // for backwards compatibility
                    expect(orderedSpy.mostRecentCall.args[0]).toEqual({
                        blerg: 43,
                        foo: true,
                        bar: false
                    });
                });

                it("should pass an object with paramsAsHash", function() {
                    proxy.setParamsAsHash(true);

                    readSome(proxy, { params: { blerg: 44 } });

                    expect(orderedSpy.mostRecentCall.args[0]).toEqual({
                        blerg: 44,
                        foo: true,
                        bar: false
                    });
                });

                describe("paramOrder", function() {
                    beforeEach(function() {
                        proxy.setDirectFn(ordered2Spy);
                    });

                    it("should pass ordered args", function() {
                        proxy.setParamOrder(['blerg', 'foo', 'bar']);

                        readSome(proxy, { params: { blerg: 45 } });

                        var args = ordered2Spy.mostRecentCall.args;

                        expect(args[0]).toBe(45);
                        expect(args[1]).toBe(true);
                        expect(typeof args[2]).toBe('function');
                    });

                    it("should not discriminate extraParams", function() {
                        proxy.setParamOrder(['bar', 'blerg', 'foo']);

                        readSome(proxy, { params: { blerg: 46 } });

                        var args = ordered2Spy.mostRecentCall.args;

                        expect(args[0]).toBe(false);
                        expect(args[1]).toBe(46);
                        expect(typeof args[2]).toBe('function');
                    });
                });
            });

            describe("create/update/delete", function() {
                it("should not pass extraParams with create", function() {
                    createSome();

                    expect(orderedSpy.mostRecentCall.args[0]).toBe(undefined);
                });

                it("should not pass extraParams with update", function() {
                    updateSome();

                    expect(orderedSpy.mostRecentCall.args[0]).toBe(undefined);
                });

                it("should not pass extraParams with destroy", function() {
                    destroySome();

                    expect(orderedSpy.mostRecentCall.args[0]).toBe(undefined);
                });
            });
        });
    });

    describe("metadata", function() {
        describe("named", function() {
            beforeEach(function() {
                makeApi({
                    actions: {
                        'DirectSpecs': [{
                            name: 'named',
                            params: ['blerg'],
                            metadata: {
                                params: ['foo', 'bar']
                            }
                        }]
                    }
                });

                namedSpy = makeSpy('named');

                makeProxy({ directFn: namedSpy });
            });

            describe("read operation", function() {
                it("should not set options by default", function() {
                    readSome();

                    expect(namedSpy.mostRecentCall.args[3]).toBe(undefined);
                });

                it("should pass metadata when it is set", function() {
                    proxy.setMetadata({ foo: 42, bar: false });

                    readSome();

                    expect(namedSpy.mostRecentCall.args[3]).toEqual({
                        metadata: { foo: 42, bar: false }
                    });
                });
            });

            describe("create operation", function() {
                it("should not set options by default", function() {
                    createSome();

                    expect(namedSpy.mostRecentCall.args[3]).toBe(undefined);
                });

                it("should pass metadata when it is set", function() {
                    proxy.setMetadata({ foo: false, bar: null });

                    createSome();

                    expect(namedSpy.mostRecentCall.args[3]).toEqual({
                        metadata: { foo: false, bar: null }
                    });
                });
            });

            describe("update operation", function() {
                it("should not set options by default", function() {
                    updateSome();

                    expect(namedSpy.mostRecentCall.args[3]).toBe(undefined);
                });

                it("should pass metadata to update fn", function() {
                    proxy.setMetadata({ foo: { baz: 1 }, bar: ['foo'] });

                    updateSome();

                    expect(namedSpy.mostRecentCall.args[3]).toEqual({
                        metadata: { foo: { baz: 1 }, bar: ['foo'] }
                    });
                });
            });

            describe("destroy operation", function() {
                it("should not set options by default", function() {
                    destroySome();

                    expect(namedSpy.mostRecentCall.args[3]).toBe(undefined);
                });

                it("should pass metadata to destroy fn", function() {
                    proxy.setMetadata({ foo: { bar: { baz: 42 } }, bar: 'blerg' });

                    destroySome();

                    expect(namedSpy.mostRecentCall.args[3]).toEqual({
                        metadata: {
                            foo: { bar: { baz: 42 } }, bar: 'blerg'
                        }
                    });
                });
            });
        });

        describe("ordered", function() {
            beforeEach(function() {
                makeApi({
                    actions: {
                        'DirectSpecs': [{
                            name: 'ordered',
                            len: 0,
                            metadata: {
                                len: 1
                            }
                        }]
                    }
                });

                orderedSpy = makeSpy('ordered');

                makeProxy({ directFn: orderedSpy });
            });

            describe("read operation", function() {
                it("should not set options by default", function() {
                    readSome();

                    expect(orderedSpy.mostRecentCall.args[2]).toBe(undefined);
                });

                it("should pass metadata when it is set", function() {
                    proxy.setMetadata([42]);

                    readSome();

                    expect(orderedSpy.mostRecentCall.args[2]).toEqual({
                        metadata: [42]
                    });
                });
            });

            describe("create operation", function() {
                it("should not set options by default", function() {
                    createSome();

                    expect(orderedSpy.mostRecentCall.args[2]).toBe(undefined);
                });

                it("should pass metadata when it is set", function() {
                    proxy.setMetadata([43]);

                    createSome();

                    expect(orderedSpy.mostRecentCall.args[2]).toEqual({
                        metadata: [43]
                    });
                });
            });

            describe("update operation", function() {
                it("should not set options by default", function() {
                    updateSome();

                    expect(orderedSpy.mostRecentCall.args[2]).toBe(undefined);
                });

                it("should pass metadata when it is set", function() {
                    proxy.setMetadata([44]);

                    updateSome();

                    expect(orderedSpy.mostRecentCall.args[2]).toEqual({
                        metadata: [44]
                    });
                });
            });

            describe("destroy operation", function() {
                it("should not set options by default", function() {
                    destroySome();

                    expect(orderedSpy.mostRecentCall.args[2]).toBe(undefined);
                });

                it("should pass metadata when it is set", function() {
                    proxy.setMetadata([45]);

                    destroySome();

                    expect(orderedSpy.mostRecentCall.args[2]).toEqual({
                        metadata: [45]
                    });
                });
            });
        });
    });

    describe("aborting", function() {
        var operation, callback, directFn;

        beforeEach(function() {
            makeApi({
                actions: {
                    DirectSpecs: [{
                        len: 0,
                        name: 'directFn'
                    }]
                }
            });

            directFn = makeSpy('directFn').andCallFake(function(cb, proxy) {
                callback = cb;
            });

            makeProxy({
                directFn: directFn
            });

            spyOn(proxy, 'processResponse').andCallThrough();
            spyOn(proxy, 'doRequest').andCallThrough();
        });

        afterEach(function() {
            operation = callback = directFn = null;
        });

        describe("by operation", function() {
            it("should abort read operations", function() {
                readSome();

                operation = proxy.doRequest.mostRecentCall.args[0];
                var id = operation.id;

                proxy.abort(operation);
                expect(proxy.canceledOperations[id]).toBe(true);

                callback({}, { success: true });

                expect(proxy.processResponse).not.toHaveBeenCalled();
                expect(proxy.canceledOperations[id]).not.toBeDefined();
            });

            it("should abort create operations", function() {
                createSome();

                operation = proxy.doRequest.mostRecentCall.args[0];
                var id = operation.id;

                proxy.abort(operation);
                expect(proxy.canceledOperations[id]).toBe(true);

                callback({}, { success: true });

                expect(proxy.processResponse).not.toHaveBeenCalled();
                expect(proxy.canceledOperations[id]).not.toBeDefined();
            });

            it("should abort update operations", function() {
                updateSome();

                operation = proxy.doRequest.mostRecentCall.args[0];
                var id = operation.id;

                proxy.abort(operation);
                expect(proxy.canceledOperations[id]).toBe(true);

                callback({}, { success: true });

                expect(proxy.processResponse).not.toHaveBeenCalled();
                expect(proxy.canceledOperations[id]).not.toBeDefined();
            });

            it("should abort delete operations", function() {
                destroySome();

                operation = proxy.doRequest.mostRecentCall.args[0];
                var id = operation.id;

                proxy.abort(operation);
                expect(proxy.canceledOperations[id]).toBe(true);

                callback({}, { success: true });

                expect(proxy.processResponse).not.toHaveBeenCalled();
                expect(proxy.canceledOperations[id]).not.toBeDefined();
            });
        });

        describe("by request", function() {
            it("should abort read operations", function() {
                readSome();

                // doRequest() result is an Ext.data.Request
                operation = proxy.doRequest.mostRecentCall.result;
                var id = operation.getOperation().id;

                proxy.abort(operation);
                expect(proxy.canceledOperations[id]).toBe(true);

                callback({}, { success: true });

                expect(proxy.processResponse).not.toHaveBeenCalled();
                expect(proxy.canceledOperations[id]).not.toBeDefined();
            });

            it("should abort create operations", function() {
                createSome();

                // doRequest() result is an Ext.data.Request
                operation = proxy.doRequest.mostRecentCall.result;
                var id = operation.getOperation().id;

                proxy.abort(operation);
                expect(proxy.canceledOperations[id]).toBe(true);

                callback({}, { success: true });

                expect(proxy.processResponse).not.toHaveBeenCalled();
                expect(proxy.canceledOperations[id]).not.toBeDefined();
            });

            it("should abort update operations", function() {
                updateSome();

                // doRequest() result is an Ext.data.Request
                operation = proxy.doRequest.mostRecentCall.result;
                var id = operation.getOperation().id;

                proxy.abort(operation);
                expect(proxy.canceledOperations[id]).toBe(true);

                callback({}, { success: true });

                expect(proxy.processResponse).not.toHaveBeenCalled();
                expect(proxy.canceledOperations[id]).not.toBeDefined();
            });

            it("should abort delete operations", function() {
                destroySome();

                // doRequest() result is an Ext.data.Request
                operation = proxy.doRequest.mostRecentCall.result;
                var id = operation.getOperation().id;

                proxy.abort(operation);
                expect(proxy.canceledOperations[id]).toBe(true);

                callback({}, { success: true });

                expect(proxy.processResponse).not.toHaveBeenCalled();
                expect(proxy.canceledOperations[id]).not.toBeDefined();
            });
        });
    });
});

describe('Ext.data.BufferedStore', function() {
    var bufferedStore;

    function getData(start, limit) {
        var end = start + limit,
            recs = [],
            i;

        for (i = start; i < end; ++i) {
            recs.push({
                id: i,
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

    function createStore(cfg) {
        bufferedStore = new Ext.data.BufferedStore(Ext.apply({
            model: 'spec.ForumThread',
            pageSize: 100,
            proxy: {
                type: 'ajax',
                url: 'fakeUrl',
                reader: {
                    type: 'json',
                    rootProperty: 'data'
                }
            }
        }, cfg));
    }

    beforeEach(function() {
        Ext.define('spec.ForumThread', {
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

        MockAjaxManager.addMethods();
    });
    
    afterEach(function(){
        MockAjaxManager.removeMethods();
        bufferedStore.destroy();
        bufferedStore = null;
        Ext.data.Model.schema.clear();
        Ext.undefine('spec.ForumThread');
    });

    it('should be able to lookup a record by its inmternalId', function() {
        createStore();
        bufferedStore.loadPage(1);
        satisfyRequests();

        var rec0 = bufferedStore.getAt(0);

        // Lookup by the string version of internalId because that's how we get from DOM to record: https://sencha.jira.com/browse/EXTJS-15388
        expect(bufferedStore.getByInternalId(String(rec0.internalId))).toBe(rec0);
    });

    it('should be able to start from any page', function() {
        createStore();
        bufferedStore.loadPage(10);

        satisfyRequests();

        expect(bufferedStore.currentPage).toBe(10);
        var page10 = bufferedStore.getRange(900, 999);
        expect(page10.length).toBe(100);

        // Page 10 contains records 900 to 999.
        expect(page10[0].get('title')).toBe('Title900');
        expect(page10[99].get('title')).toBe('Title999');
    });

    it('should be able to find records in a buffered store', function() {
        createStore();
        bufferedStore.load();

        satisfyRequests();

        expect(bufferedStore.findBy(function(rec) {
            return rec.get('title') === 'Title10';
        })).toBe(10);

        expect(bufferedStore.findExact('title', 'Title10')).toBe(10);

        expect(bufferedStore.find('title', 'title10')).toBe(10);
    });

    it("should clear the data when calling sort with parameters when remote sorting", function() {
        createStore();
        bufferedStore.load();

        satisfyRequests();

        bufferedStore.sort();
        expect(bufferedStore.data.getCount()).toBe(0);
        satisfyRequests();
        expect(bufferedStore.data.getCount()).toBe(300);
    });

    it('should load the store when filtered', function() {
        var spy = jasmine.createSpy();

        createStore({
            listeners: {
                load: spy
            }
        });

        // Filter mutation shuold trigger a load
        bufferedStore.filter('title', 'panel');
        satisfyRequests();
        expect(spy).toHaveBeenCalled();
   });

    it('should load the store when sorted', function() {
         var spy = jasmine.createSpy();

        createStore({
            listeners: {
                load: spy
            }
        });

        // Sorter mutation shuold trigger a load
        bufferedStore.sort('title', 'ASC');
        satisfyRequests();
        expect(spy).toHaveBeenCalled();
   });

    it("should update the sorters when sorting by an existing key", function() {
        createStore({
            sorters: [{
                property: 'title'
            }]
        });

        bufferedStore.sort('title', 'DESC');
        var sorter = bufferedStore.getSorters().getAt(0);
        expect(sorter.getProperty()).toBe('title');
        expect(sorter.getDirection()).toBe('DESC');
    });

    // Test for https://sencha.jira.com/browse/EXTJSIV-10338
    // purgePageCount ensured that the viewSize could never be satisfied
    // by small pages because they would keep being pruned.
    it('should load the requested range when the pageSize is small', function() {
        var spy = jasmine.createSpy();
        createStore({
            pageSize: 5,
            listeners: {
                load: spy
            }
        });

        bufferedStore.load();

        satisfyRequests();
        expect(spy).toHaveBeenCalled();
    });

    describe('load', function () {
        it("should pass the records loaded, the operation & success to the callback", function() {
            var spy = jasmine.createSpy(),
                args;

            createStore();

            bufferedStore.load({
                // Called after first prefetch and first page has been added.
                callback: spy
            });
            satisfyRequests();

            args = spy.mostRecentCall.args;
            expect(Ext.isArray(args[0])).toBe(true);
            expect(args[0][0].isModel).toBe(true);

            expect(args[1].getAction()).toBe('read');
            expect(args[1].$className).toBe('Ext.data.operation.Read');

            expect(args[2]).toBe(true);

        });

        describe('should assign dataset index numbers to the records in the Store dependent upon configured pageSize', function () {
            it('should not exceed 100 records', function () {
                createStore();

                var spy = jasmine.createSpy();
                bufferedStore.load({
                    // Called after first prefetch and first page has been added.
                    callback: spy
                });

                satisfyRequests();

                expect(spy).toHaveBeenCalled();
                expect(bufferedStore.indexOf(bufferedStore.getAt(0))).toBe(0);
                expect(bufferedStore.indexOf(bufferedStore.getAt(99))).toBe(99);
                expect(spy.mostRecentCall.args[0].length).toBe(100);
            });

            it('should not exceed 50 records', function () {
                createStore({
                    pageSize: 50
                });

                var spy = jasmine.createSpy();
                bufferedStore.load({
                    // Called after first prefetch and first page has been added.
                    callback: spy
                });

                satisfyRequests(50);
                expect(spy).toHaveBeenCalled();

                expect(bufferedStore.indexOf(bufferedStore.getAt(0))).toBe(0);
                expect(bufferedStore.indexOf(bufferedStore.getAt(49))).toBe(49);
                expect(spy.mostRecentCall.args[0].length).toBe(50);
            });
        });
    });

    describe('reload', function () {
        it('should not increase the number of pages when reloading', function () {
            var refreshed = 0,
                count;

            createStore();
            bufferedStore.load();

            satisfyRequests();

            bufferedStore.on('refresh', function() {
                refreshed++;
            });
            bufferedStore.reload();
            satisfyRequests();
            
            waitsFor(function() {
                return refreshed === 1;
            });
            
            runs(function() {
                expect(refreshed).toBe(1);
                count = bufferedStore.getData().getCount();

                bufferedStore.reload();
                satisfyRequests();
            });

            waitsFor(function() {
                return refreshed === 2;
            });
            
            runs(function() {
                expect(bufferedStore.getData().getCount()).toBe(count);
            });
        });
    });
    
    describe('pruning', function() {
        it('should prune least recently used pages as new ones are added above the purgePageCount', function() {
            var keys;

            // Keep it simple
            createStore({
                pageSize: 10,
                viewSize: 10,
                leadingBufferZone: 0,
                trailingBufferZone: 0,
                purgePageCount: new Number(0)
            });
            bufferedStore.load();
            satisfyRequests();


            // The PageMap should contain page 1
            keys = [];
            bufferedStore.getData().forEach(function(rec){
                keys.push(String(rec.internalId));
            });
            expect(keys.length).toBe(10);
            expect(Ext.Object.getKeys(bufferedStore.getData().map)).toEqual(['1']);

            // The indexMap must contain only the keys to the records that are now there.
            expect(Ext.Object.getKeys(bufferedStore.getData().indexMap)).toEqual(keys);

            // This should evict page one because there are no buffer zones, and a non-falsy purgePageCount of zero
            bufferedStore.loadPage(2);
            satisfyRequests();

            // The PageMap should contain ONLY page 2
            keys = [];
            bufferedStore.getData().forEach(function(rec){
                keys.push(String(rec.internalId));
            });
            expect(keys.length).toBe(10);
            expect(Ext.Object.getKeys(bufferedStore.getData().map)).toEqual(['2']);

            // The indexMap must contain only the keys to the records that are now there.
            expect(Ext.Object.getKeys(bufferedStore.getData().indexMap)).toEqual(keys);
        });
    });
});
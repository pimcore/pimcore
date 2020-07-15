topSuite("Ext.util.Sorter", ['Ext.data.SortTypes', 'Ext.data.Model'], function() {
    var sorter;

    describe("instantiation", function() {
        var createSorter = function(config) {
            return function() {
                new Ext.util.Sorter(config);
            };
        };

        it("should require either a property or a function", function() {
            expect(createSorter({})).toThrow();
        });

        it("should accept a property config", function() {
            expect(createSorter({ property: 'test' })).not.toThrow();
        });

        it("should accept a sorter function", function() {
            expect(createSorter({ sorterFn: Ext.emptyFn })).not.toThrow();
        });

        it("should have no transform method", function() {
            expect(createSorter().transform).toBeUndefined();
        });
    });

    describe("building sorter functions", function() {
        it("should default to sorting ASC", function() {
            sorter = new Ext.util.Sorter({
                property: 'age'
            });

            var rec1   = { age: 24 },
                rec2   = { age: 25 },
                result = sorter.sort(rec1, rec2);

            expect(result).toEqual(-1);
        });

        it("should accept DESC direction", function() {
            sorter = new Ext.util.Sorter({
                property: 'age',
                direction: 'DESC'
            });

            var rec1   = { age: 24 },
                rec2   = { age: 25 },
                result = sorter.sort(rec1, rec2);

            expect(result).toEqual(1);
        });

        it("should allow specification of the root property", function() {
            sorter = new Ext.util.Sorter({
                root: 'data',
                property: 'age'
            });

            var rec1   = { data: { age: 24 } },
                rec2   = { data: { age: 25 } },
                result = sorter.sort(rec1, rec2);

            expect(result).toEqual(-1);
        });
    });

    it("should accept some custom transform function", function() {
        sorter = new Ext.util.Sorter({
            property: 'age',
            transform: function(v) {
                return v * -1;
            }
        });

        var rec1 = { age: 18 },
            rec2 = { age: 21 },
            result = sorter.sort(rec1, rec2);

        expect(result).toBe(1);
    });

    // https://sencha.jira.com/browse/EXTJS-18836
    it('should sort an array of Records by multiple sorters where the first returns equality', function() {
        var edRaw = { name: 'Ed Spencer',   email: 'ed@sencha.com',    evilness: 100, group: 'code',  old: false, age: 25, valid: 'yes' },
            abeRaw = { name: 'Abe Elias',    email: 'abe@sencha.com',   evilness: 70,  group: 'admin', old: false, age: 20, valid: 'yes' },
            aaronRaw = { name: 'Aaron Conran', email: 'aaron@sencha.com', evilness: 5,   group: 'admin', old: true, age: 26, valid: 'yes' },
            tommyRaw = { name: 'Tommy Maintz', email: 'tommy@sencha.com', evilness: -15, group: 'code',  old: true, age: 70, valid: 'yes' },
            User = Ext.define(null, {
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
            }),
            records = [
                new User(edRaw),
                new User(abeRaw),
                new User(aaronRaw),
                new User(tommyRaw)
            ],
            sorters = [
                new Ext.util.Sorter({
                    sorterFn: function() {
                        return 0;
                    }
                }),
                new Ext.util.Sorter({ root: 'data', property: 'age' })
            ];

        // Should not throw error.
        Ext.Array.sort(records, Ext.util.Sortable.createComparator(sorters));
    });

    describe('sorting null values', function() {
        // See EXTJS-13694.
        var SortTypes = Ext.data.SortTypes;

        function createComparator(candidate, nullFirst) {
            var sorter = new Ext.util.Sorter({
                nullFirst: nullFirst,
                sorterFn: defaultSorterFn,
                transform: candidate.transform
            });

            return function(v1, v2) {
                return sorter.sort(v1, v2);
            };
        }

        function nullFirstComparator(nullFirst, transform) {
            return function(v1, v2) {
                if (v1 === null) {
                    return nullFirst ? -1 : 1;
                }
                else if (v2 === null) {
                    return nullFirst ? 1 : -1;
                }
                else if (transform) {
                    v1 = transform(v1);
                    v2 = transform(v2);
                }

                return v1 > v2 ? 1 : (v1 < v2 ? -1 : 0);
            };
        }

        // NOTE that we're passing in a custom sorterFn, but it's almost the same as the default. We've
        // had to do this b/c we're testing simple arrays and didn't need to specify a `property` value.
        function defaultSorterFn(v1, v2) {
            var me = this,
                transform = me._transform;

            if (v1 === v2) {
                return 0;
            }

            if (v1 === null) {
                return me.nullFirst ? -1 : 1;
            }
            else if (v2 === null) {
                return me.nullFirst ? 1 : -1;
            }
            else if (transform) {
                v1 = transform(v1);
                v2 = transform(v2);
            }

            return v1 > v2 ? 1 : (v1 < v2 ? -1 : 0);
        }

        var candidates = {
            asFloat: {
                test: [5.3, null, 2.4, null],
                transform: SortTypes.asFloat
            },
            asInt: {
                test: [5, null, 2, null],
                transform: SortTypes.asInt
            },
            asText: {
                test: ['<p>hello, <span>world!</span></p>', null, '<div>i am <p>in</p>a block</div>', null],
                transform: SortTypes.asText
            },
            asUCString: {
                test: ['z', null, 'a', null],
                transform: SortTypes.asUCString
            },
            asUCText: {
                test: ['<p>hello, <span>world!</span></p>', null, '<div>i am <p>in</p>a block</div>', null],
                transform: SortTypes.asUCText
            }
        };

        function sortIt(method, nullFirst) {
            var candidate = candidates[method],
                testArr = candidate.test,
                compare = createComparator(candidate, nullFirst);

            describe(method + (nullFirst ? ' first' : ' last'), function() {
                it('should sort null values ' + (nullFirst ? 'first' : 'last'), function() {
                    expect(testArr.concat().sort(nullFirstComparator(nullFirst, candidate.transform))).toEqual(testArr.concat().sort(compare));
                });
            });
        }

        describe('asUCString', function() {
            sortIt('asUCString');
            sortIt('asUCString', true);
        });

        describe('numbers', function() {
            sortIt('asFloat');
            sortIt('asInt');

            sortIt('asFloat', true);
            sortIt('asInt', true);
        });

        describe('text', function() {
            sortIt('asText');
            sortIt('asUCText');

            sortIt('asText', true);
            sortIt('asUCText', true);
        });
    });
});


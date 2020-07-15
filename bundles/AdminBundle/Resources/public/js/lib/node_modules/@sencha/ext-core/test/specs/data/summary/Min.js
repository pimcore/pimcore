topSuite("Ext.data.summary.Min", ['Ext.data.Model'], function() {
    var aggregator;

    beforeEach(function() {
        aggregator = new Ext.data.summary.Min();
    });

    afterEach(function() {
        aggregator = null;
    });

    function makeSuite(withRecords) {
        var data, M, defaultData;

        function run(property, begin, end) {
            property = property || 'p1';
            begin = begin || 0;
            end = end || data.length;

            if (data.length) {
                if (typeof data[0] === 'number') {
                    data = Ext.Array.map(data, function(n) {
                        var o = {};

                        o[property] = n;

                        return o;
                    });
                }
            }

            if (withRecords) {
                data = Ext.Array.map(data, function(item) {
                    return new M(item);
                });
            }

            return aggregator.calculate(data, property, withRecords ? 'data' : '', begin, end);
        }

        if (withRecords) {
            M = Ext.define(null, {
                extend: 'Ext.data.Model',
                fields: ['p1', 'p2']
            });
        }

        afterEach(function() {
            data = null;
        });

        describe("value", function() {
            it("should return undefined when there are no items", function() {
                data = [];
                expect(run()).toBeUndefined();
            });

            it("should return the only item", function() {
                data = [3];
                expect(run()).toBe(3);
            });

            it("should return an item at the beginning", function() {
                data = [2, 8, 14, 9, 3, 15, 10, 19, 7, 13];
                expect(run()).toBe(2);
            });

            it("should return an item in the middle", function() {
                data = [8, 14, 9, 3, 15, 2, 10, 19, 7, 13];
                expect(run()).toBe(2);
            });

            it("should return an item at the end", function() {
                data = [8, 14, 9, 3, 15, 10, 19, 7, 13, 2];
                expect(run()).toBe(2);
            });

            it("should return the minimum when there are duplicates", function() {
                data = [8, 2, 9, 3, 2, 10, 2, 7, 2, 2];
                expect(run()).toBe(2);
            });

            it("should return the minimum correctly with negative numbers", function() {
                data = [-8, -14, -9, -3, -15, -2, -10, -19, -7, -13];
                expect(run()).toBe(-19);
            });

            it("should return a minimum of 0", function() {
                data = [8, 14, 9, 3, 15, 0, 10, 19, 7, 13];
                expect(run()).toBe(0);
            });
        });

        describe("property", function() {
            beforeEach(function() {
                var set1 = [16, 13, 3, 14, 6, 19, 4, 5, 1, 12],
                    set2 = [17, 9, 2, 14, 3, 12, 4, 6, 10, 15];

                data = [];

                Ext.Array.forEach(set1, function(v, idx) {
                    data.push({
                        p1: v,
                        p2: set2[idx]
                    });
                });
            });

            it("should run on the correct property", function() {
                expect(run('p1')).toBe(1);
            });

            it("should run on another property", function() {
                expect(run('p2')).toBe(2);
            });
        });

        describe("begin/end", function() {
            it("should allow a begin after 0", function() {
                data = [2, 8, 14, 9, 3, 15, 10, 19, 7, 13];
                expect(run('p1', 5)).toBe(7);
            });

            it("should allow an end before the length", function() {
                data = [8, 14, 9, 3, 15, 10, 19, 7, 13, 2];
                expect(run('p1', 0, 6)).toBe(3);
            });

            it("should allow both together", function() {
                data = [3, 8, 14, 9, 15, 10, 19, 7, 13, 2];
                expect(run('p1', 1, 7)).toBe(8);
            });
        });
    }

    describe("with records", function() {
        makeSuite(true);
    });

    describe("with objects", function() {
        makeSuite(false);
    });
});

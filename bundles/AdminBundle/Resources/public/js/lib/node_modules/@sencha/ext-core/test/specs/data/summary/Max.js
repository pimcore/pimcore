topSuite("Ext.data.summary.Max", ['Ext.data.Model'], function() {
    var aggregator;

    beforeEach(function() {
        aggregator = new Ext.data.summary.Max();
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
                data = [17, 8, 11, 14, 4, 2, 6, 9, 5, 13];
                expect(run()).toBe(17);
            });

            it("should return an item in the middle", function() {
                data = [8, 11, 14, 4, 2, 17, 6, 9, 5, 13];
                expect(run()).toBe(17);
            });

            it("should return an item at the end", function() {
                data = [8, 11, 14, 4, 2, 6, 9, 5, 13, 17];
                expect(run()).toBe(17);
            });

            it("should return the maximum when there are duplicates", function() {
                data = [8, 17, 14, 17, 2, 17, 6, 9, 17, 13];
                expect(run()).toBe(17);
            });

            it("should return the maximum correctly with negative numbers", function() {
                data = [-8, -11, -14, -4, -2, -17, -6, -9, -5, -13];
                expect(run()).toBe(-2);
            });

            it("should return a maximum of 0", function() {
                data = [-8, -11, -14, -4, 0, -17, -6, -9, -5, -13];
                expect(run()).toBe(0);
            });
        });

        describe("property", function() {
            beforeEach(function() {
                var set1 = [16, 7, 19, 2, 9, 10, 20, 5, 12, 1],
                    set2 = [6, 9, 19, 11, 13, 8, 1, 14, 5, 10];

                data = [];

                Ext.Array.forEach(set1, function(v, idx) {
                    data.push({
                        p1: v,
                        p2: set2[idx]
                    });
                });
            });

            it("should run on the correct property", function() {
                expect(run('p1')).toBe(20);
            });

            it("should run on another property", function() {
                expect(run('p2')).toBe(19);
            });
        });

        describe("begin/end", function() {
            it("should allow a begin after 0", function() {
                data = [8, 11, 14, 4, 2, 17, 6, 9, 5, 13];
                expect(run('p1', 6)).toBe(13);
            });

            it("should allow an end before the length", function() {
                data = [8, 11, 14, 4, 2, 17, 6, 9, 5, 13];
                expect(run('p1', 0, 5)).toBe(14);
            });

            it("should allow both together", function() {
                data = [8, 11, 14, 4, 2, 17, 6, 9, 5, 13];
                expect(run('p1', 1, 5)).toBe(14);
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

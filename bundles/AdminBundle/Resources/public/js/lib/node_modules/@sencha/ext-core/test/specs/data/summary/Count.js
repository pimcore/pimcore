topSuite("Ext.data.summary.Count", ['Ext.data.Model'], function() {
    var aggregator;

    beforeEach(function() {
        aggregator = new Ext.data.summary.Count();
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
            it("should return 0 when there are no items", function() {
                data = [];
                expect(run()).toBe(0);
            });

            it("should return the only item", function() {
                data = [3];
                expect(run()).toBe(1);
            });

            it("should return the correct count", function() {
                data = [17, 8, 11, 14, 4, 2, 6, 9, 5, 13];
                expect(run()).toBe(10);
            });

            it("should return the count when there are duplicates", function() {
                data = [8, 17, 14, 17, 2, 17, 6, 9, 17, 13];
                expect(run()).toBe(10);
            });

            it("should return the count correctly with negative numbers", function() {
                data = [-8, -11, -14, -4, -2, -17, -6, -9, -5, -13];
                expect(run()).toBe(10);
            });
        });

        describe("property", function() {
            it("should ignore the property", function() {
                data = [16, 7, 19, 2, 9, 10, 20, 5, 12, 1];
                expect(run('p2')).toBe(10);
            });
        });

        describe("begin/end", function() {
            it("should allow a begin after 0", function() {
                data = [8, 11, 14, 4, 2, 17, 6, 9, 5, 13];
                expect(run('p1', 6)).toBe(4);
            });

            it("should allow an end before the length", function() {
                data = [8, 11, 14, 4, 2, 17, 6, 9, 5, 13];
                expect(run('p1', 0, 5)).toBe(5);
            });

            it("should allow both together", function() {
                data = [8, 11, 14, 4, 2, 17, 6, 9, 5, 13];
                expect(run('p1', 2, 8)).toBe(6);
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

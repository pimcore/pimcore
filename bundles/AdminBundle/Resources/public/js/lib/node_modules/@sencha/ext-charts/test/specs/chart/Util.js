topSuite("Ext.chart.Util", ['Ext.chart.*'], function() {

    var defaultRange = [0, 1];

    describe('expandRange', function() {
        it('should handle nulls correctly', function() {
            var dataRange = [NaN, NaN];

            var data = [-800, -600, -400, null, null];

            Ext.chart.Util.expandRange(dataRange, data);

            expect(dataRange[0]).toBe(-800);
            expect(dataRange[1]).toBe(-400);
        });
    });

    describe('validateRange', function() {
        it('should work with zero ranges', function() {
            var range1 = [0, 0];

            var range2 = [5, 5];

            var range3 = [-5, -5];

            var range4 = [-Infinity, -Infinity];

            var range5 = [Infinity, Infinity];

            var range6 = [Ext.Number.MIN_SAFE_INTEGER, Ext.Number.MIN_SAFE_INTEGER];

            var range7 = [Ext.Number.MAX_SAFE_INTEGER, Ext.Number.MAX_SAFE_INTEGER];

            var range8 = [Ext.Number.MIN_SAFE_INTEGER - 1, Ext.Number.MIN_SAFE_INTEGER - 1];

            var range9 = [Ext.Number.MAX_SAFE_INTEGER + 1, Ext.Number.MAX_SAFE_INTEGER + 1];

            var range10 = [null, 5];

            var range11 = [5, null];

            var range12 = [NaN, 5];

            var range13 = [5, NaN];

            var range14 = [NaN, NaN];

            var range15 = [undefined, 5];

            var range16 = [5, undefined];

            var range17 = [undefined, undefined];

            var range18 = [5, Infinity];

            var range19 = [Infinity, 5];

            var range20 = [-Infinity, -5];

            var range21 = [-5, -Infinity];

            var range22 = [5, -Infinity];

            var range23 = [-5, Infinity];

            var result;

            result = Ext.chart.Util.validateRange(range1, defaultRange);
            expect(result[0]).toBe(-0.5);
            expect(result[1]).toBe(0.5);

            result = Ext.chart.Util.validateRange(range2, defaultRange);
            expect(result[0]).toBe(4.5);
            expect(result[1]).toBe(5.5);

            result = Ext.chart.Util.validateRange(range3, defaultRange);
            expect(result[0]).toBe(-5.5);
            expect(result[1]).toBe(-4.5);

            result = Ext.chart.Util.validateRange(range4, defaultRange);
            expect(result[0]).toBe(0);
            expect(result[1]).toBe(1);

            result = Ext.chart.Util.validateRange(range5, defaultRange);
            expect(result[0]).toBe(0);
            expect(result[1]).toBe(1);

            result = Ext.chart.Util.validateRange(range6, defaultRange);
            expect(result[0]).toBe(Ext.Number.MIN_SAFE_INTEGER - 1);
            expect(result[1]).toBe(Ext.Number.MIN_SAFE_INTEGER + 1);
            expect(isFinite(result[0])).toBe(true);
            expect(isFinite(result[1])).toBe(true);

            result = Ext.chart.Util.validateRange(range7, defaultRange);
            expect(result[0]).toBe(Ext.Number.MAX_SAFE_INTEGER - 1);
            expect(result[1]).toBe(Ext.Number.MAX_SAFE_INTEGER + 1);
            expect(isFinite(result[0])).toBe(true);
            expect(isFinite(result[1])).toBe(true);

            result = Ext.chart.Util.validateRange(range8, defaultRange);
            expect(result[0]).toBe(0);
            expect(result[1]).toBe(1);
            expect(isFinite(result[0])).toBe(true);
            expect(isFinite(result[1])).toBe(true);

            result = Ext.chart.Util.validateRange(range9, defaultRange);
            expect(result[0]).toBe(0);
            expect(result[1]).toBe(1);
            expect(isFinite(result[0])).toBe(true);
            expect(isFinite(result[1])).toBe(true);

            result = Ext.chart.Util.validateRange(range10, defaultRange);
            expect(result[0]).toBe(0);
            expect(result[1]).toBe(5);

            result = Ext.chart.Util.validateRange(range11, defaultRange);
            expect(result[0]).toBe(0);
            expect(result[1]).toBe(5);

            result = Ext.chart.Util.validateRange(range12, defaultRange);
            expect(result[0]).toBe(0);
            expect(result[1]).toBe(5);

            result = Ext.chart.Util.validateRange(range13, defaultRange);
            expect(result[0]).toBe(0);
            expect(result[1]).toBe(5);

            result = Ext.chart.Util.validateRange(range14, defaultRange);
            expect(result[0]).toBe(-0.5);
            expect(result[1]).toBe(0.5);

            result = Ext.chart.Util.validateRange(range15, defaultRange);
            expect(result[0]).toBe(0);
            expect(result[1]).toBe(5);

            result = Ext.chart.Util.validateRange(range16, defaultRange);
            expect(result[0]).toBe(0);
            expect(result[1]).toBe(5);

            result = Ext.chart.Util.validateRange(range17, defaultRange);
            expect(result[0]).toBe(-0.5);
            expect(result[1]).toBe(0.5);

            result = Ext.chart.Util.validateRange(range18, defaultRange);
            expect(result[0]).toBe(5);
            expect(result[1]).toBe(6);

            result = Ext.chart.Util.validateRange(range19, defaultRange);
            expect(result[0]).toBe(5);
            expect(result[1]).toBe(6);

            result = Ext.chart.Util.validateRange(range20, defaultRange);
            expect(result[0]).toBe(-6);
            expect(result[1]).toBe(-5);

            result = Ext.chart.Util.validateRange(range21, defaultRange);
            expect(result[0]).toBe(-6);
            expect(result[1]).toBe(-5);

            result = Ext.chart.Util.validateRange(range22, defaultRange);
            expect(result[0]).toBe(4);
            expect(result[1]).toBe(5);

            result = Ext.chart.Util.validateRange(range23, defaultRange);
            expect(result[0]).toBe(-5);
            expect(result[1]).toBe(-4);

            result = Ext.chart.Util.validateRange(range2, defaultRange, 0);
            expect(result[0]).toBe(5);
            expect(result[1]).toBe(5);

            result = Ext.chart.Util.validateRange(range7, defaultRange, 0);
            expect(result[0]).toBe(Ext.Number.MAX_SAFE_INTEGER);
            expect(result[1]).toBe(Ext.Number.MAX_SAFE_INTEGER);
        });
    });
});

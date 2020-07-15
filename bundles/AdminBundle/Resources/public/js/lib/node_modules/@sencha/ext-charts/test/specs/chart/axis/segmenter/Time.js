topSuite("Ext.chart.axis.segmenter.Time", ['Ext.chart.*', 'Ext.data.ArrayStore'], function() {
    var proto = Ext.chart.axis.segmenter.Time.prototype;

    describe('getTimeBucket', function() {
        it('should return the right bucket', function() {
            var result;

            // Note: formats like '2016-12-08', '2016-12-08T03:00:00' don't work in IE8 (will get NaN).

            result = proto.getTimeBucket(new Date(2016, 11, 8), new Date(2016, 11, 9));
            expect(result.unit).toBe(Ext.Date.DAY);
            expect(result.step).toBe(1);

            result = proto.getTimeBucket(new Date(2016, 11, 7), new Date(2016, 11, 9));
            expect(result.unit).toBe(Ext.Date.DAY);
            expect(result.step).toBe(7);

            result = proto.getTimeBucket(new Date(2016, 11, 2), new Date(2016, 11, 20));
            expect(result.unit).toBe(Ext.Date.DAY);
            expect(result.step).toBe(14);

            result = proto.getTimeBucket(new Date(2016, 11, 8, 3, 0, 0), new Date(2016, 11, 8, 5, 0, 0));
            expect(result.unit).toBe(Ext.Date.HOUR);
            expect(result.step).toBe(6);

            result = proto.getTimeBucket(new Date(1016, 11, 8), new Date(2016, 11, 8));
            expect(result.unit).toBe(Ext.Date.YEAR);
            expect(result.step).toBe(500);

            result = proto.getTimeBucket(new Date(0), new Date(1));
            expect(result.unit).toBe(Ext.Date.MILLI);
            expect(result.step).toBe(1);

            result = proto.getTimeBucket(new Date(0), new Date(0.5));
            expect(result.unit).toBe(Ext.Date.MILLI);
            expect(result.step).toBe(1);
        });
    });
});

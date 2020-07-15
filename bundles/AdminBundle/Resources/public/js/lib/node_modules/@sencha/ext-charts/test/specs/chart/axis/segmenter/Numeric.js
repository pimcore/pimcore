topSuite("Ext.chart.axis.segmenter.Numeric", ['Ext.chart.*', 'Ext.data.ArrayStore'], function() {
    var proto = Ext.chart.axis.segmenter.Numeric.prototype;

    describe("exactStep", function() {
        it("should calculate unit and step correctly for the [.01, .99] range with 5 steps", function() {
            var min = 0.01,
                max = 0.99,
                steps = 5,
                estStep = (max - min) / steps,
                result = proto.exactStep(min, estStep);

            expect(result.unit.fixes).toBe(2);
            expect(result.unit.scale).toBe(estStep);
            expect(result.step).toBe(1);
        });

        it("should calculate unit and step correctly for the [-.01, .99] range with 5 steps", function() {
            var min = -0.01,
                max = 0.99,
                steps = 5,
                estStep = (max - min) / steps,
                result = proto.exactStep(min, estStep);

            expect(result.unit.fixes).toBe(2);
            expect(result.unit.scale).toBe(0.2);
            expect(result.step).toBe(1);
        });

        it("should calculate unit and step correctly for the [0, 1] range with 10 steps", function() {
            var min = 0,
                max = 1,
                steps = 10,
                estStep = (max - min) / steps,
                result = proto.exactStep(min, estStep);

            expect(result.unit.fixes).toBe(2);
            expect(result.unit.scale).toBe(0.1);
            expect(result.step).toBe(1);
        });

        it("should calculate unit and step correctly for the [5, 10] range with 3 steps", function() {
            var min = 5,
                max = 10,
                steps = 3,
                estStep = (max - min) / steps,
                result = proto.exactStep(min, estStep);

            expect(result.unit.fixes).toBe(1);
            expect(result.unit.scale).toBe(1);
            expect(result.step).toBe(1.6666666666666667);
        });
    });

    describe('adjustByMajorUnit', function() {
        it('should round up min/max values both greater and less than 1 correctly', function() {
            var method = Ext.chart.axis.segmenter.Numeric.prototype.adjustByMajorUnit;

            var step = 0.01;

            var scale = 10;

            var range = [1, 2];

            var precision = 10;

            method(step, scale, range);

            expect(range[0]).toBeCloseTo(1, precision);
            expect(range[1]).toBeCloseTo(2, precision);

            range = [0.93, 2];
            method(step, scale, range);

            expect(range[0]).toBeCloseTo(0.9, precision);
            expect(range[1]).toBeCloseTo(2, precision);

            step = 0.005;
            range = [1, 1.93];
            method(step, scale, range);

            expect(range[0]).toBeCloseTo(1, precision);
            expect(range[1]).toBeCloseTo(1.95, precision);

            step = 0.005;
            range = [0, 1.93];
            method(step, scale, range);

            expect(range[0]).toBeCloseTo(0, precision);
            expect(range[1]).toBeCloseTo(1.95, precision);

            step = 0.01;
            range = [-0.52, 1.95];
            method(step, scale, range);

            expect(range[0]).toBeCloseTo(-0.6, precision);
            expect(range[1]).toBeCloseTo(2, precision);
        });
    });
});

topSuite("Ext.draw.Matrix", function() {

    describe('split', function() {
        it("should extract transformation components properly", function() {
            var elements = [1.76776695, 1.76776695, -5.30330086, 5.30330086, 3, 4],
                matrix = Ext.draw.Matrix.fly(elements),
                split = matrix.split(),
                precision = 8;

            expect(split.translateX).toEqual(3);
            expect(split.translateY).toEqual(4);
            expect(split.scaleX).toBeCloseTo(2.5, precision);
            expect(split.scaleY).toBeCloseTo(7.5, precision);
            expect(split.rotate).toBeCloseTo(Math.PI / 4, precision);
        });
    });

    describe("isEqual", function() {
        it("should return 'true' for matricies with same elements", function() {
            var m1 = new Ext.draw.Matrix(1, 2, 3, 4, 5, 6),
                m2 = new Ext.draw.Matrix(1, 2, 3, 4, 5, 6);

            expect(m1.isEqual(m2)).toBe(true);
            m1.scale(2, 3, 4, 5, true);
            m2.scale(2, 3, 4, 5, true);
            m1.rotate(Math.PI / 3, 8, 9, true);
            m2.rotate(Math.PI / 3, 8, 9, true);
            expect(m1.isEqual(m2)).toBe(true);
        });

        it("should return 'false' for matrices with different elements", function() {
            var m1 = new Ext.draw.Matrix(1, 2, 3, 1, 5, 6),
                m2 = new Ext.draw.Matrix(1, 2, 3, 4, 5, 6);

            expect(m1.isEqual(m2)).toBe(false);
            m1.reset();
            m2.reset();
            m1.scale(2, 3, 4, 5, true);
            m2.scale(2, 3, 4, 5, true);
            m1.rotate(Math.PI / 3, 7, 9, true);
            m2.rotate(Math.PI / 3, 8, 9, true);
            expect(m1.isEqual(m2)).toBe(false);
        });
    });

    describe("skewX", function() {
        it("should properly affect the matrix", function() {
            var matrix = new Ext.draw.Matrix(1, 2, 3, 4, 5, 6),
                precision = 8;

            matrix.skewX(Math.PI / 3);

            expect(matrix.elements[0]).toEqual(1);
            expect(matrix.elements[1]).toEqual(2);
            expect(matrix.elements[2]).toBeCloseTo(4.73205080756888, precision);
            expect(matrix.elements[3]).toBeCloseTo(7.46410161513775, precision);
            expect(matrix.elements[4]).toEqual(5);
            expect(matrix.elements[5]).toEqual(6);
        });
    });

    describe("skewY", function() {
        it("should properly affect the matrix", function() {
            var matrix = new Ext.draw.Matrix(1, 2, 3, 4, 5, 6),
                precision = 8;

            matrix.skewY(Math.PI / 3);

            expect(matrix.elements[0]).toBeCloseTo(6.19615242270663, precision);
            expect(matrix.elements[1]).toBeCloseTo(8.92820323027551, precision);
            expect(matrix.elements[2]).toEqual(3);
            expect(matrix.elements[3]).toEqual(4);
            expect(matrix.elements[4]).toEqual(5);
            expect(matrix.elements[5]).toEqual(6);
        });
    });

    describe("shearX", function() {
        it("should properly affect the matrix", function() {
            var matrix = new Ext.draw.Matrix(1, 2, 3, 4, 5, 6);

            matrix.shearX(3);
            expect(matrix.elements).toEqual([1, 2, 6, 10, 5, 6]);
        });
    });

    describe("shearY", function() {
        it("should properly affect the matrix", function() {
            var matrix = new Ext.draw.Matrix(1, 2, 3, 4, 5, 6);

            matrix.shearY(3);
            expect(matrix.elements).toEqual([10, 14, 3, 4, 5, 6]);
        });
    });

});

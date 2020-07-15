topSuite("Ext.draw.sprite.AnimationParser", function() {
    var parser = Ext.draw.sprite.AnimationParser;

    describe("'data' (array) parser", function() {
        var empty = [],
            nonEmpty1 = [4, 8, 16, 32],
            nonEmpty2 = [-4, -8, -16, -32],
            smaller = [4, 8, 16],
            bigger = [2, 4, 8, 16, 32],
            punctured = [2, NaN, 8, null];

        it('should be able to transition from an empty to a non-empty array', function() {
            // Since no initial values were given, the compute will
            // return the target array for any delta value.
            var delta = 0.25;

            var result = parser.data.compute(empty, nonEmpty1, delta);

            expect(result).toEqual([4, 8, 16, 32]);
        });

        it('should be able to transition from a non-empty to an empty array', function() {
            var delta = 0.25;

            // Since we are transitioning to nothing, no intermediate
            // value can be calculated. So the intermediate values
            // are computed as if the target values were zeros.
            var result = parser.data.compute(nonEmpty1, empty, delta);

            expect(result).toEqual([3, 6, 12, 24]);
        });

        it('should be able to transition between two equal size arrays', function() {
            var delta = 0.25;

            // This is simply an interpolated value of the corresponding
            // elements in respective arrays.
            var result = parser.data.compute(nonEmpty1, nonEmpty2, delta);

            expect(result).toEqual([2, 4, 8, 16]);
        });

        it('should be able to transition from a smaller to a bigger arrray', function() {
            var delta = 0.5;

            // This will interpolate between respective elements,
            // until the smaller array is out of elements, but the
            // bigger one still has some. In that case the interpolation
            // will be made between the last element of the smaller
            // array and the extra elements of the bigger array.
            var result = parser.data.compute(smaller, bigger, delta);

            expect(result).toEqual([3, 6, 12, 16, 24]);
        });

        it('should be able to transition from a bigger to a smaller array', function() {
            var delta = 0.5;

            // Behaves just like the interpolation from smaller to bigger.
            var result = parser.data.compute(bigger, smaller, delta);

            expect(result).toEqual([3, 6, 12, 16, 24]);
        });

        it('should be able to transition from a punctured to a normal array', function() {
            var delta = 0.5;

            // When invalid numbers in the punctured array are encountered,
            // a value with the same index in the target array will be used
            // for the resulting array.
            var result = parser.data.compute(punctured, nonEmpty1, delta);

            expect(result).toEqual([3, 8, 12, 32]);
        });

        it('should be able to transition from a normal to a punctured array', function() {
            var delta = 0.5;

            // Just like with transitioning to an empty array, if the value
            // can't be found in the target array, a zero value will be used
            // instead.
            var result = parser.data.compute(nonEmpty1, punctured, delta);

            expect(result).toEqual([3, 4, 12, 16]);
        });
    });

});

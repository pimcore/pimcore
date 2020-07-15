topSuite("Ext.util.Spans", function() {
    var spans;

    beforeEach(function() {
        spans = new Ext.util.Spans();
    });

    describe('add', function() {
        it('should be able to make a hole in a span resulting in two spans', function() {
            spans.add(0, 10);

            expect(spans.spans).toEqual([[0, 10]]);

            spans.remove(5, 6);

            expect(spans.spans).toEqual([[0, 5], [6, 10]]);
        });

        it('should coalesce adjacent added spans', function() {
            spans.add(0, 10);
            spans.add(10, 20);

            expect(spans.spans).toEqual([[0, 20]]);
        });

        it('should coalesce overlapping added spans', function() {
            spans.add(0, 10);
            spans.add(5, 11);

            expect(spans.spans).toEqual([[0, 11]]);
        });

        it('should add non-overlapping added spans', function() {
            spans.add(0, 10);
            spans.add(11, 20);

            expect(spans.spans).toEqual([[0, 10], [11, 20]]);
        });

        it('should coalesce adjacent added spans reverse order', function() {
            spans.add(10, 20);
            spans.add(0, 10);

            expect(spans.spans).toEqual([[0, 20]]);
        });

        it('should coalesce overlapping added spans reverse order', function() {
            spans.add(5, 20);
            spans.add(0, 10);

            expect(spans.spans).toEqual([[0, 20]]);
        });

        it('should add non-overlapping added spans reverse order', function() {
            spans.add(11, 20);
            spans.add(0, 10);

            expect(spans.spans).toEqual([[0, 10], [11, 20]]);
        });

        it('should ignore adds of fully contained spans', function() {
            spans.add(0, 20);
            spans.add(5, 15);

            expect(spans.spans).toEqual([[0, 20]]);

            spans.add(15, 20);

            expect(spans.spans).toEqual([[0, 20]]);

            spans.add(0, 10);

            expect(spans.spans).toEqual([[0, 20]]);
        });

        it('should coalesce multiple overlapping spans', function() {
            spans.add(0, 10);
            spans.add(20, 30);
            spans.add(40, 50);
            spans.add(60, 70);

            expect(spans.spans).toEqual([[0, 10], [20, 30], [40, 50], [60, 70]]);

            spans.add(0, 100);
            expect(spans.spans).toEqual([[0, 100]]);
        });
    });

    describe('remove', function() {
        beforeEach(function() {
            spans.add(0, 10);
            spans.add(20, 30);
            spans.add(40, 50);
            spans.add(60, 70);
        });

        it('should split first span and leave remaining spans', function() {
            spans.remove(5, 6);

            expect(spans.spans).toEqual([[0, 5], [6, 10], [20, 30], [40, 50], [60, 70]]);
        });

        it('should remove first span and leave remaining spans', function() {
            spans.remove(0, 10);

            expect(spans.spans).toEqual([[20, 30], [40, 50], [60, 70]]);
        });

        it('should do nothing if removing in a gap', function() {
            spans.remove(10, 20);

            expect(spans.spans).toEqual([[0, 10], [20, 30], [40, 50], [60, 70]]);
        });

        it('should remove the end of an first span', function() {
            spans.remove(9, 10);

            expect(spans.spans).toEqual([[0, 9], [20, 30], [40, 50], [60, 70]]);

            spans.remove(8, 20);

            expect(spans.spans).toEqual([[0, 8], [20, 30], [40, 50], [60, 70]]);
        });

        it('should remove the front of an internal span', function() {
            spans.remove(20, 21);

            expect(spans.spans).toEqual([[0, 10], [21, 30], [40, 50], [60, 70]]);

            spans.remove(10, 22);

            expect(spans.spans).toEqual([[0, 10], [22, 30], [40, 50], [60, 70]]);
        });

        it('should remove tail of one span and head of the adjacent span', function() {
            spans.remove(9, 21);

            expect(spans.spans).toEqual([[0, 9], [21, 30], [40, 50], [60, 70]]);
        });

        it('should remove multiple spans at the front', function() {
            spans.remove(0, 40);

            expect(spans.spans).toEqual([[40, 50], [60, 70]]);
        });

        it('should remove multiple spans at the end', function() {
            spans.remove(40, 80);

            expect(spans.spans).toEqual([[0, 10], [20, 30]]);
        });

        it('should remove multiple spans in the middle', function() {
            spans.remove(10, 60);

            expect(spans.spans).toEqual([[0, 10], [60, 70]]);
        });

        it('should remove tail of first overlapped span, all intervening spans, and head of last overlapped span', function() {
            spans.remove(9, 61);

            expect(spans.spans).toEqual([[0, 9], [61, 70]]);
        });

        it('should remove all spans', function() {
            spans.remove(0, 100);

            expect(spans.spans).toEqual([]);
        });

        it('should remove head of first span', function() {
            spans.remove(0, 5);

            expect(spans.spans).toEqual([[5, 10], [20, 30], [40, 50], [60, 70]]);
        });

        it('should remove tail of last span', function() {
            spans.remove(65, 70);

            expect(spans.spans).toEqual([[0, 10], [20, 30], [40, 50], [60, 65]]);
        });
    });

    describe('contains', function() {
        beforeEach(function() {
            spans.add(0, 10);
            spans.add(20, 30);
            spans.add(40, 50);
            spans.add(60, 70);
        });

        it('should report true correctly', function() {
            expect(spans.contains(0)).toBe(true);
            expect(spans.contains(9)).toBe(true);
            expect(spans.contains(20)).toBe(true);
            expect(spans.contains(29)).toBe(true);
            expect(spans.contains(40)).toBe(true);
            expect(spans.contains(49)).toBe(true);
            expect(spans.contains(60)).toBe(true);
            expect(spans.contains(69)).toBe(true);

            expect(spans.contains(0, 10)).toBe(true);
            expect(spans.contains(20, 30)).toBe(true);
            expect(spans.contains(40, 50)).toBe(true);
            expect(spans.contains(60, 70)).toBe(true);

            expect(spans.contains(0, 5)).toBe(true);
            expect(spans.contains(1, 4)).toBe(true);
            expect(spans.contains(5, 10)).toBe(true);

            expect(spans.contains(40, 45)).toBe(true);
            expect(spans.contains(41, 44)).toBe(true);
            expect(spans.contains(45, 50)).toBe(true);

            expect(spans.contains(60, 65)).toBe(true);
            expect(spans.contains(61, 64)).toBe(true);
            expect(spans.contains(65, 70)).toBe(true);
        });

        it('should report false correctly', function() {
            expect(spans.contains(-1)).toBe(false);
            expect(spans.contains(10)).toBe(false);
            expect(spans.contains(19)).toBe(false);
            expect(spans.contains(30)).toBe(false);
            expect(spans.contains(39)).toBe(false);
            expect(spans.contains(50)).toBe(false);
            expect(spans.contains(59)).toBe(false);
            expect(spans.contains(70)).toBe(false);

            expect(spans.contains(10, 20)).toBe(false);
            expect(spans.contains(30, 40)).toBe(false);
            expect(spans.contains(50, 60)).toBe(false);
            expect(spans.contains(70, 80)).toBe(false);

            expect(spans.contains(0, 30)).toBe(false);
        });
    });

    describe('intersects', function() {
        beforeEach(function() {
            spans.add(0, 10);
            spans.add(20, 30);
            spans.add(40, 50);
            spans.add(60, 70);
        });

        it('should report true correctly', function() {
            expect(spans.intersects(0)).toBe(true);
            expect(spans.intersects(9)).toBe(true);
            expect(spans.intersects(20)).toBe(true);
            expect(spans.intersects(29)).toBe(true);
            expect(spans.intersects(40)).toBe(true);
            expect(spans.intersects(49)).toBe(true);
            expect(spans.intersects(60)).toBe(true);
            expect(spans.intersects(69)).toBe(true);

            expect(spans.intersects(0, 10)).toBe(true);
            expect(spans.intersects(20, 30)).toBe(true);
            expect(spans.intersects(40, 50)).toBe(true);
            expect(spans.intersects(60, 70)).toBe(true);

            expect(spans.intersects(0, 5)).toBe(true);
            expect(spans.intersects(1, 4)).toBe(true);
            expect(spans.intersects(5, 10)).toBe(true);

            expect(spans.intersects(40, 45)).toBe(true);
            expect(spans.intersects(41, 44)).toBe(true);
            expect(spans.intersects(45, 50)).toBe(true);

            expect(spans.intersects(60, 65)).toBe(true);
            expect(spans.intersects(61, 64)).toBe(true);
            expect(spans.intersects(65, 70)).toBe(true);
            expect(spans.intersects(0, 30)).toBe(true);

            expect(spans.intersects(10, 21)).toBe(true);
            expect(spans.intersects(9, 20)).toBe(true);
            expect(spans.intersects(20, 21)).toBe(true);
            expect(spans.intersects(69, 70)).toBe(true);
        });

        it('should report false correctly', function() {
            expect(spans.intersects(-1)).toBe(false);
            expect(spans.intersects(10)).toBe(false);
            expect(spans.intersects(19)).toBe(false);
            expect(spans.intersects(30)).toBe(false);
            expect(spans.intersects(39)).toBe(false);
            expect(spans.intersects(50)).toBe(false);
            expect(spans.intersects(59)).toBe(false);
            expect(spans.intersects(70)).toBe(false);

            expect(spans.intersects(10, 20)).toBe(false);
            expect(spans.intersects(30, 40)).toBe(false);
            expect(spans.intersects(50, 60)).toBe(false);
            expect(spans.intersects(70, 80)).toBe(false);

        });
    });

});

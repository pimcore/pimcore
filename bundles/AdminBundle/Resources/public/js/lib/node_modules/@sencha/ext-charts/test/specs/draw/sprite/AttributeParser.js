topSuite("Ext.draw.sprite.AttributeParser", function() {
    var parser = Ext.draw.sprite.AttributeParser;

    describe('angle', function() {
        it("should normalize -Math.PI and Math.PI to the same value", function() {
            var a = parser.angle(-Math.PI),
                b = parser.angle(Math.PI);

            expect(a).toEqual(b);
        });
        it("should make normalized values lie within [-Math.PI, Math.PI) interval", function() {
            var a = parser.angle(-Math.PI),
                b = parser.angle(Math.PI),
                c = parser.angle(-Math.PI * 3),
                d = parser.angle(Math.PI * 4),
                e = parser.angle(-Math.PI * 2.75),
                f = parser.angle(Math.PI * 3.25),
                g = parser.angle(Math.PI * 0.25),
                h = parser.angle(-Math.PI * 0.75);

            expect(a).toBeGreaterThanOrEqual(-Math.PI);
            expect(a).toBeLessThan(Math.PI);

            expect(b).toBeGreaterThanOrEqual(-Math.PI);
            expect(b).toBeLessThan(Math.PI);

            expect(c).toBeGreaterThanOrEqual(-Math.PI);
            expect(c).toBeLessThan(Math.PI);

            expect(d).toBeGreaterThanOrEqual(-Math.PI);
            expect(d).toBeLessThan(Math.PI);

            expect(e).toBeGreaterThanOrEqual(-Math.PI);
            expect(e).toBeLessThan(Math.PI);

            expect(f).toBeGreaterThanOrEqual(-Math.PI);
            expect(f).toBeLessThan(Math.PI);

            expect(g).toBeGreaterThanOrEqual(-Math.PI);
            expect(g).toBeLessThan(Math.PI);

            expect(h).toBeGreaterThanOrEqual(-Math.PI);
            expect(h).toBeLessThan(Math.PI);
        });
    });
});

topSuite("Ext.draw.Point", function() {
    var proto = Ext.draw.Point.prototype,
        precision = 12; // first 12 decimal points should match

    it('should be isPoint', function() {
        expect(proto.isPoint).toBeTruthy();
    });

    it('should default to using degrees', function() {
        expect(proto.angleUnits).toBe('degrees');
    });

    describe('constructor', function() {
        it('should take two numbers', function() {
            var p = new Ext.draw.Point(3, 4);

            expect(p.x).toEqual(3);
            expect(p.y).toEqual(4);
        });
        it('should take a single number', function() {
            var p = new Ext.draw.Point(3);

            expect(p.x).toEqual(3);
            expect(p.y).toEqual(3);
        });
        it('should take an array', function() {
            var p = new Ext.draw.Point([3, 4]);

            expect(p.x).toEqual(3);
            expect(p.y).toEqual(4);
        });
        it('should take an object', function() {
            var p = new Ext.draw.Point({
                x: 3,
                y: 4
            });

            expect(p.x).toEqual(3);
            expect(p.y).toEqual(4);
        });
        it('should take a point', function() {
            var p = new Ext.draw.Point(new Ext.draw.Point(3, 4));

            expect(p.x).toEqual(3);
            expect(p.y).toEqual(4);
        });
        it('should calculate polar coordinates', function() {
            var p = new Ext.draw.Point(5, 5);

            expect(p.length).toEqual(Math.sqrt(2 * 5 * 5));
            expect(p.angle).toEqual(45);
        });
    });

    describe('set, setX, setY', function() {
        it('should recalculate polar coordinates', function() {
            var p = new Ext.draw.Point(3, 4);

            p.setX(0);
            expect(p.length).toEqual(4);
            expect(p.angle).toEqual(90);
            p.setY(0);
            expect(p.length).toEqual(0);
            expect(p.angle).toEqual(0);
            p.set(5, 5);
            expect(p.length).toEqual(Math.sqrt(2 * 5 * 5));
            expect(p.angle).toEqual(45);
        });
    });

    describe('setPolar, setLength, setAngle', function() {
        it('should recalculate cartesian coordinates', function() {
            var p = new Ext.draw.Point();

            p.setLength(10);
            expect(p.x).toEqual(10);
            expect(p.y).toEqual(0);
            p.setAngle(90);
            expect(p.x).toBeCloseTo(0, precision);
            expect(p.y).toBeCloseTo(10, precision);
            p.setPolar(45, Math.sqrt(2 * 5 * 5));
            expect(p.x).toBeCloseTo(5, precision);
            expect(p.y).toBeCloseTo(5, precision);
        });
    });

    describe('clone', function() {
        it('should match original point coordinates but not the point itself', function() {
            var p = new Ext.draw.Point(2, 3),
                clone = p.clone();

            expect(clone.x).toEqual(p.x);
            expect(clone.y).toEqual(p.y);
            expect(clone).not.toBe(p);
        });
    });

    describe('add', function() {
        it('should return a new point which x/y values are sums of respective ' +
        'coordinates of this point and the given point', function() {
            var p1 = new Ext.draw.Point(2, 3),
                p2 = new Ext.draw.Point(-4, 5),
                p = p1.add(p2);

            expect(p.x).toEqual(-2);
            expect(p.y).toEqual(8);
            expect(p).not.toBe(p1);
        });
    });

    describe('sub', function() {
        it('should return a new point which x/y values are the difference between ' +
        'the respective coordinates of this point (minuend) ' +
        'and the given point (subtrahend)', function() {
            var p1 = new Ext.draw.Point(2, 3),
                p2 = new Ext.draw.Point(-4, 5),
                p = p1.sub(p2);

            expect(p.x).toEqual(6);
            expect(p.y).toEqual(-2);
            expect(p).not.toBe(p1);
        });
    });

    describe('mul', function() {
        it('should return a new point which x/y values are the product of multiplication of ' +
        'coordinates of this point by a specified value', function() {
            var p = new Ext.draw.Point(2, 3),
                mp = p.mul(3);

            expect(mp.x).toEqual(6);
            expect(mp.y).toEqual(9);
            expect(mp).not.toBe(p);
        });
    });

    describe('div', function() {
        it('should return a new point which x/y values are the product of division of ' +
        'coordinates of this point by a specified value', function() {
            var p = new Ext.draw.Point(2, 3),
                dp = p.div(2);

            expect(dp.x).toEqual(1);
            expect(dp.y).toEqual(1.5);
            expect(dp).not.toBe(p);
        });
    });

    describe('dot', function() {
        it('should return a dot product (scalar) of two vectors', function() {
            var p = new Ext.draw.Point(2, 0),
                op = new Ext.draw.Point(0, 3), // vector orthogonal to p
                p1 = new Ext.draw.Point(3, 4),
                dot_p_op = p.dot(op),
                dot_p_p1 = p.dot(p1);

            expect(dot_p_op).toEqual(0);
            expect(dot_p_p1).toEqual(6);
            expect(dot_p_p1).not.toBe(p);
        });
    });

    describe('equals', function() {
        it('should check if the respective coordinates of this point ' +
        'and provided point are equal', function() {
            var p1 = new Ext.draw.Point(2, 0),
                p2 = new Ext.draw.Point({ x: 2, y: 0 }),
                isEqual = p1.equals(p2);

            expect(isEqual).toBe(true);
        });
    });

    describe('rotate', function() {
        it('should rotate the point (around origin and an arbitrary point) ' +
        'by a specified angle', function() {
            var p = new Ext.draw.Point(1, 0),
                center = new Ext.draw.Point(0, 1),
                degrees = 45,
                rads = 45 / 180 * Math.PI,
                rp = p.rotate(degrees),
                rcp = p.rotate(degrees, center);

            expect(rp.x).toEqual(Math.cos(rads));
            expect(rp.y).toEqual(Math.sin(rads));
            expect(rcp.x).toBeCloseTo(Math.sqrt(2), precision);
            expect(rcp.y).toBeCloseTo(1, precision);
        });
    });

    describe('transform', function() {
        it('should transform a point from one coordinate system to another ' +
        'given a transformation matrix or its elements', function() {
            var p = new Ext.draw.Point(2, 0),
                matrix = new Ext.draw.Matrix(),
                tp;

            matrix.translate(1, 1);
            matrix.rotate(Math.PI / 2);
            matrix.scale(2);

            tp = p.transform(matrix);

            expect(tp.x).toBeCloseTo(1, precision);
            expect(tp.y).toBeCloseTo(5, precision);

            tp = p.transform.apply(p, matrix.elements);

            expect(tp.x).toBeCloseTo(1, precision);
            expect(tp.y).toBeCloseTo(5, precision);
        });
    });

    describe('normalize', function() {
        it('should return a new vector with the length of 1 and the same angle', function() {
            var p = new Ext.draw.Point(5, 5),
                sin = Math.sin(Math.PI / 4),
                cos = Math.cos(Math.PI / 4),
                np = p.normalize(),
                np5 = p.normalize(5);

            expect(np.x).toBeCloseTo(cos, precision);
            expect(np.y).toBeCloseTo(sin, precision);
            expect(np.length).toBeCloseTo(1, precision);
            expect(np.angle).toBeCloseTo(45, precision);

            expect(np5.x).toBeCloseTo(5 * cos, precision);
            expect(np5.y).toBeCloseTo(5 * sin, precision);
            expect(np5.length).toBeCloseTo(5, precision);
            expect(np5.angle).toBeCloseTo(45, precision);

            p = new Ext.draw.Point(-5, -5);
            np = p.normalize();
            np5 = p.normalize(5);
            sin = Math.sin(-3 * Math.PI / 4);
            cos = Math.cos(-3 * Math.PI / 4);

            expect(np.x).toBeCloseTo(cos, precision);
            expect(np.y).toBeCloseTo(sin, precision);
            expect(np.length).toBeCloseTo(1, precision);
            expect(np.angle).toBeCloseTo(-135, precision);

            expect(np5.x).toBeCloseTo(5 * cos, precision);
            expect(np5.y).toBeCloseTo(5 * sin, precision);
            expect(np5.length).toBeCloseTo(5, precision);
            expect(np5.angle).toBeCloseTo(-135, precision);

            p = new Ext.draw.Point(5, -5);
            np = p.normalize();
            np5 = p.normalize(5);
            sin = Math.sin(-Math.PI / 4);
            cos = Math.cos(-Math.PI / 4);

            expect(np.x).toBeCloseTo(cos, precision);
            expect(np.y).toBeCloseTo(sin, precision);
            expect(np.length).toBeCloseTo(1, precision);
            expect(np.angle).toBeCloseTo(-45, precision);

            expect(np5.x).toBeCloseTo(5 * cos, precision);
            expect(np5.y).toBeCloseTo(5 * sin, precision);
            expect(np5.length).toBeCloseTo(5, precision);
            expect(np5.angle).toBeCloseTo(-45, precision);

            p = new Ext.draw.Point(-5, 5);
            np = p.normalize();
            np5 = p.normalize(5);
            sin = Math.sin(3 * Math.PI / 4);
            cos = Math.cos(3 * Math.PI / 4);

            expect(np.x).toBeCloseTo(cos, precision);
            expect(np.y).toBeCloseTo(sin, precision);
            expect(np.length).toBeCloseTo(1, precision);
            expect(np.angle).toBeCloseTo(135, precision);

            expect(np5.x).toBeCloseTo(5 * cos, precision);
            expect(np5.y).toBeCloseTo(5 * sin, precision);
            expect(np5.length).toBeCloseTo(5, precision);
            expect(np5.angle).toBeCloseTo(135, precision);
        });
    });

    describe('getDistanceToLine', function() {
        it('should return a distance from the point to the line (as a vector)', function() {
            var p = new Ext.draw.Point(1, 1),
                p1 = new Ext.draw.Point(1, 2),
                p2 = new Ext.draw.Point(2, 1),
                d1 = p.getDistanceToLine(p1, p2),
                d2 = p.getDistanceToLine(1, 2, 2, 1),
                d = Math.sqrt(2) / 2;

            expect(d1.length).toBeCloseTo(d, precision);
            expect(d2.length).toBeCloseTo(d, precision);

        });
    });
});

topSuite("Ext.util.Region", ['Ext.dom.Element'], function() {
    var region,
        region1,
        region2,
        region3,
        region4,
        region5;

    beforeEach(function() {
        region1 = Ext.create('Ext.util.Region', 2, 5, 6, 1);
        region2 = Ext.create('Ext.util.Region', 1, 6, 3, 4);
        region3 = Ext.create('Ext.util.Region', 0, 2, 2, 0);
        region4 = Ext.create('Ext.util.Region', 3, 4, 5, 2);
        region5 = Ext.create('Ext.util.Region', 7, 3, 9, 1);
    });

    function expectTLBR(toTest, t, l, b, r) {
        expect(toTest.top).toBe(t);
        expect(toTest.left).toBe(l);
        expect(toTest.bottom).toBe(b);
        expect(toTest.right).toBe(r);
    }

    function expectXYWH(toTest, x, y, w, h) {
        expect(toTest.top).toBe(y);
        expect(toTest.left).toBe(x);
        expect(toTest.getWidth()).toBe(w);
        expect(toTest.getHeight()).toBe(h);
    }

    describe("contains", function() {
        describe("form region 1 point of view", function() {
            it("should not contain region 2", function() {
                expect(region1.contains(region2)).toBe(false);
            });

            it("should not contain region 3", function() {
                expect(region1.contains(region3)).toBe(false);
            });

            it("should contain region 4", function() {
                expect(region1.contains(region4)).toBe(true);
            });

            it("should not contain region 5", function() {
                expect(region1.contains(region5)).toBe(false);
            });
        });
    });

    describe("intersect", function() {
        describe("form region 1 point of view", function() {
            describe("between region 1 and 2", function() {
                beforeEach(function() {
                    region = region1.intersect(region2);
                });

                it("should not return false", function() {
                    expect(region).not.toBe(false);
                });

                it("should return a region with top property equal to 2", function() {
                    expect(region.top).toEqual(2);
                });

                it("should return a region with left property equal to 4", function() {
                    expect(region.left).toEqual(4);
                });

                it("should return a region with bottom property equal to 3", function() {
                    expect(region.bottom).toEqual(3);
                });

                it("should return a region with right property equal to 5", function() {
                    expect(region.right).toEqual(5);
                });
            });

            describe("between region 2 and 1", function() {
                beforeEach(function() {
                    region = region2.intersect(region1);
                });

                it("should not return false", function() {
                    expect(region).not.toBe(false);
                });

                it("should return a region with top property equal to 2", function() {
                    expect(region.top).toEqual(2);
                });

                it("should return a region with left property equal to 4", function() {
                    expect(region.left).toEqual(4);
                });

                it("should return a region with bottom property equal to 3", function() {
                    expect(region.bottom).toEqual(3);
                });

                it("should return a region with right property equal to 5", function() {
                    expect(region.right).toEqual(5);
                });
            });

            describe("between region 1 and 3", function() {
                it("should have no intersection", function() {
                    expect(region1.intersect(region3)).toBe(false);
                });
            });

            describe("between region 3 and 1", function() {
                it("should have no intersection1", function() {
                    expect(region3.intersect(region1)).toBe(false);
                });
            });

            describe("between region 1 and 4", function() {
                beforeEach(function() {
                    region = region1.intersect(region4);
                });

                it("should not return false", function() {
                    expect(region).not.toBe(false);
                });

                it("should return a region with top property equal to 3", function() {
                    expect(region.top).toEqual(3);
                });

                it("should return a region with left property equal to 2", function() {
                    expect(region.left).toEqual(2);
                });

                it("should return a region with bottom property equal to 5", function() {
                    expect(region.bottom).toEqual(5);
                });

                it("should return a region with right property equal to 4", function() {
                    expect(region.right).toEqual(4);
                });
            });

            describe("between region 4 and 1", function() {
                beforeEach(function() {
                    region = region4.intersect(region1);
                });

                it("should not return false", function() {
                    expect(region).not.toBe(false);
                });

                it("should return a region with top property equal to 3", function() {
                    expect(region.top).toEqual(3);
                });

                it("should return a region with left property equal to 2", function() {
                    expect(region.left).toEqual(2);
                });

                it("should return a region with bottom property equal to 5", function() {
                    expect(region.bottom).toEqual(5);
                });

                it("should return a region with right property equal to 4", function() {
                    expect(region.right).toEqual(4);
                });
            });

            describe("between region 1 and 5", function() {
                it("should have no intersection", function() {
                    expect(region1.intersect(region5)).toBe(false);
                });
            });

            describe("between region 5 and 1", function() {
                it("should have no intersection", function() {
                    expect(region5.intersect(region1)).toBe(false);
                });
            });
        });
    });

    describe("union", function() {
        describe("form region 1 point of view", function() {
            describe("between region 1 and 2", function() {
                beforeEach(function() {
                    region = region1.union(region2);
                });

                it("should return a region with top property equal to 1", function() {
                    expect(region.top).toEqual(1);
                });

                it("should return a region with left property equal to 1", function() {
                    expect(region.left).toEqual(1);
                });

                it("should return a region with bottom property equal to 6", function() {
                    expect(region.bottom).toEqual(6);
                });

                it("should return a region with right property equal to 6", function() {
                    expect(region.right).toEqual(6);
                });
            });

            describe("between region 2 and 1", function() {
                beforeEach(function() {
                    region = region2.union(region1);
                });

                it("should return a region with top property equal to 1", function() {
                    expect(region.top).toEqual(1);
                });

                it("should return a region with left property equal to 1", function() {
                    expect(region.left).toEqual(1);
                });

                it("should return a region with bottom property equal to 6", function() {
                    expect(region.bottom).toEqual(6);
                });

                it("should return a region with right property equal to 6", function() {
                    expect(region.right).toEqual(6);
                });
            });

            describe("between region 1 and 3", function() {
                beforeEach(function() {
                    region = region1.union(region3);
                });

                it("should return a region with top property equal to 0", function() {
                    expect(region.top).toEqual(0);
                });

                it("should return a region with left property equal to 0", function() {
                    expect(region.left).toEqual(0);
                });

                it("should return a region with bottom property equal to 6", function() {
                    expect(region.bottom).toEqual(6);
                });

                it("should return a region with right property equal to 5", function() {
                    expect(region.right).toEqual(5);
                });
            });

            describe("between region 3 and 1", function() {
                beforeEach(function() {
                    region = region3.union(region1);
                });

                it("should return a region with top property equal to 0", function() {
                    expect(region.top).toEqual(0);
                });

                it("should return a region with left property equal to 0", function() {
                    expect(region.left).toEqual(0);
                });

                it("should return a region with bottom property equal to 6", function() {
                    expect(region.bottom).toEqual(6);
                });

                it("should return a region with right property equal to 5", function() {
                    expect(region.right).toEqual(5);
                });
            });

            describe("between region 1 and 4", function() {
                beforeEach(function() {
                    region = region1.union(region4);
                });

                it("should return a region with top property equal to 2", function() {
                    expect(region.top).toEqual(2);
                });

                it("should return a region with left property equal to 1", function() {
                    expect(region.left).toEqual(1);
                });

                it("should return a region with bottom property equal to 6", function() {
                    expect(region.bottom).toEqual(6);
                });

                it("should return a region with right property equal to 5", function() {
                    expect(region.right).toEqual(5);
                });
            });

            describe("between region 4 and 1", function() {
                beforeEach(function() {
                    region = region4.union(region1);
                });

                it("should return a region with top property equal to 2", function() {
                    expect(region.top).toEqual(2);
                });

                it("should return a region with left property equal to 1", function() {
                    expect(region.left).toEqual(1);
                });

                it("should return a region with bottom property equal to 6", function() {
                    expect(region.bottom).toEqual(6);
                });

                it("should return a region with right property equal to 5", function() {
                    expect(region.right).toEqual(5);
                });
            });

            describe("between region 1 and 5", function() {
                beforeEach(function() {
                    region = region1.union(region5);
                });

                it("should return a region with top property equal to 2", function() {
                    expect(region.top).toEqual(2);
                });

                it("should return a region with left property equal to 1", function() {
                    expect(region.left).toEqual(1);
                });

                it("should return a region with bottom property equal to 9", function() {
                    expect(region.bottom).toEqual(9);
                });

                it("should return a region with right property equal to 5", function() {
                    expect(region.right).toEqual(5);
                });
            });

            describe("between region 5 and 1", function() {
                beforeEach(function() {
                    region = region5.union(region1);
                });

                it("should return a region with top property equal to 2", function() {
                    expect(region.top).toEqual(2);
                });

                it("should return a region with left property equal to 1", function() {
                    expect(region.left).toEqual(1);
                });

                it("should return a region with bottom property equal to 9", function() {
                    expect(region.bottom).toEqual(9);
                });

                it("should return a region with right property equal to 5", function() {
                    expect(region.right).toEqual(5);
                });
            });
        });
    });

    describe("constrainTo", function() {
        describe("form region 1 point of view", function() {
            describe("between region 1 and 2", function() {
                beforeEach(function() {
                    region1.constrainTo(region2);
                });

                it("should set region 1 top property equal to 2", function() {
                    expect(region1.top).toEqual(2);
                });

                it("should set region 1 left property equal to 4", function() {
                    expect(region1.left).toEqual(4);
                });

                it("should set region 1 bottom property equal to 3", function() {
                    expect(region1.bottom).toEqual(3);
                });

                it("should set region 1 right property equal to 5", function() {
                    expect(region1.right).toEqual(5);
                });
            });

            describe("between region 1 and 3", function() {
                beforeEach(function() {
                    region1.constrainTo(region3);
                });

                it("should set region 1 top property equal to 2", function() {
                    expect(region1.top).toEqual(2);
                });

                it("should set region 1 left property equal to 1", function() {
                    expect(region1.left).toEqual(1);
                });

                it("should set region 1 bottom property equal to 2", function() {
                    expect(region1.bottom).toEqual(2);
                });

                it("should set region 1 right property equal to 2", function() {
                    expect(region1.right).toEqual(2);
                });
            });

            describe("between region 1 and 4", function() {
                beforeEach(function() {
                    region1.constrainTo(region4);
                });

                it("should set region 1 top property equal to 3", function() {
                    expect(region1.top).toEqual(3);
                });

                it("should set region 1 left property equal to 2", function() {
                    expect(region1.left).toEqual(2);
                });

                it("should set region 1 bottom property equal to 5", function() {
                    expect(region1.bottom).toEqual(5);
                });

                it("should set region 1 right property equal to 4", function() {
                    expect(region1.right).toEqual(4);
                });
            });

            describe("between region 1 and 5", function() {
                beforeEach(function() {
                    region1.constrainTo(region5);
                });

                it("should set region 1 top property equal to 7", function() {
                    expect(region1.top).toEqual(7);
                });

                it("should set region 1 left property equal to 1", function() {
                    expect(region1.left).toEqual(1);
                });

                it("should set region 1 bottom property equal to 7", function() {
                    expect(region1.bottom).toEqual(7);
                });

                it("should set region 1 right property equal to 3", function() {
                    expect(region1.right).toEqual(3);
                });
            });
        });
    });

    describe("adjust", function() {
        describe("modify the current region to be adjusted by offset", function() {
            beforeEach(function() {
                region1.adjust(1, 2, 3, 4);
            });

            it("should set region 1 top property equal to 3", function() {
                expect(region1.top).toEqual(3);
            });

            it("should set region 1 left property equal to 5", function() {
                expect(region1.left).toEqual(5);
            });

            it("should set region 1 bottom property equal to 9", function() {
                expect(region1.bottom).toEqual(9);
            });

            it("should set region 1 right property equal to 7", function() {
                expect(region1.right).toEqual(7);
            });
        });
    });

    describe('alignTo', function() {
        var inside, target, testRegion, result, insideEl, targetEl, resultEl, visualize;

        // For debugging purposes.
        // visualize the situation.
        visualize = function() {
            insideEl = insideEl || Ext.getBody().createChild({ style: 'background-color:yellow;position:absolute' });
            insideEl.setBox(inside);
            targetEl = targetEl || Ext.getBody().createChild({ style: 'background-color:red;position:absolute' });
            targetEl.setBox(target);
            resultEl = resultEl || Ext.getBody().createChild({ style: 'background-color:blue;position:absolute' });
            resultEl.setBox(result);
        };

        describe('No anchor', function() {
            beforeEach(function() {
                inside = new Ext.util.Region(0, 500, 500, 0);
                target = new Ext.util.Region(0, 250, 250, 0);
                testRegion = new Ext.util.Region(0, 100, 100, 0);
            });

            describe('tl-br', function() {
                describe('Unconstrained', function() {
                    it('should position correctly', function() {
                        result = testRegion.alignTo({
                            target: target,
                            align: 'tl-br'
                        });

                        expectXYWH(result, 250, 250, 100, 100);
                    });
                });

                describe('Constrained', function() {
                    it('should position correctly when constrained right', function() {
                        inside.setWidth(300);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'tl-br',
                            inside: inside
                        });

                        expectXYWH(result, 200, 250, 100, 100);
                    });
                    it('should position correctly when constrained bottom', function() {
                        inside.setHeight(300);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'tl-br',
                            inside: inside
                        });

                        expectXYWH(result, 250, 200, 100, 100);
                    });
                    it('should flip to above when constrained right and bottom', function() {
                        target.setPosition(200, 200);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'tl-br',
                            inside: inside
                        });

                        expectXYWH(result, 400, 100, 100, 100);
                    });
                    it('should flip to left when constrained right and bottom and target is narrower than high', function() {
                        target.setWidth(50);
                        target.setPosition(400, 200);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'tl-br',
                            inside: inside
                        });

                        expectXYWH(result, 300, 400, 100, 100);
                    });
                });
            });

            describe('bl-tr', function() {
                beforeEach(function() {
                    target.setPosition(0, 250);
                });

                describe('Unconstrained', function() {
                    it('should position correctly', function() {
                        result = testRegion.alignTo({
                            target: target,
                            align: 'bl-tr'
                        });

                        expectXYWH(result, 250, 150, 100, 100);
                    });
                });

                describe('Constrained', function() {
                    it('should position correctly when constrained right', function() {
                        inside.setWidth(300);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'bl-tr',
                            inside: inside
                        });

                        expectXYWH(result, 200, 150, 100, 100);
                    });
                    it('should position correctly when constrained top', function() {
                        target.setPosition(0, 50);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'bl-tr',
                            inside: inside
                        });

                        expectXYWH(result, 250, 0, 100, 100);
                    });
                    it('should flip to below when constrained right and top', function() {
                        target.setPosition(200, 50);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'bl-tr',
                            inside: inside
                        });

                        expectXYWH(result, 400, 300, 100, 100);
                    });
                    it('should flip to left when constrained right and top and target is narrower than high', function() {
                        target.setWidth(50);
                        target.setPosition(400, 50);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'bl-tr',
                            inside: inside
                        });

                        expectXYWH(result, 300, 0, 100, 100);
                    });
                });
            });

            describe('br-tl', function() {
                beforeEach(function() {
                    target.setPosition(250, 250);
                });

                describe('Unconstrained', function() {
                    it('should position correctly', function() {
                        result = testRegion.alignTo({
                            target: target,
                            align: 'br-tl'
                        });

                        expectXYWH(result, 150, 150, 100, 100);
                    });
                });

                describe('Constrained', function() {
                    it('should position correctly when constrained left', function() {
                        target.setPosition(50, 250);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'br-tl',
                            inside: inside
                        });

                        expectXYWH(result, 0, 150, 100, 100);
                    });
                    it('should position correctly when constrained top', function() {
                        target.setPosition(250, 50);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'br-tl',
                            inside: inside
                        });

                        expectXYWH(result, 150, 0, 100, 100);
                    });
                    it('should flip to below when constrained left and top', function() {
                        target.setPosition(50, 50);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'br-tl',
                            inside: inside
                        });

                        expectXYWH(result, 0, 300, 100, 100);
                    });
                    it('should flip to right when constrained left and top and target is narrower than high', function() {
                        target.setPosition(50, 50);
                        target.setWidth(50);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'br-tl',
                            inside: inside
                        });

                        expectXYWH(result, 100, 0, 100, 100);
                    });
                });
            });

            describe('tr-bl', function() {
                beforeEach(function() {
                    target.setPosition(250, 0);
                });

                describe('Unconstrained', function() {
                    it('should position correctly', function() {
                        result = testRegion.alignTo({
                            target: target,
                            align: 'tr-bl'
                        });

                        expectXYWH(result, 150, 250, 100, 100);
                    });
                });

                describe('Constrained', function() {
                    it('should position correctly when constrained left', function() {
                        target.setPosition(50, 0);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'tr-bl',
                            inside: inside
                        });

                        expectXYWH(result, 0, 250, 100, 100);
                    });
                    it('should position correctly when constrained bottom', function() {
                        target.setPosition(250, 200);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'tr-bl',
                            inside: inside
                        });

                        expectXYWH(result, 150, 400, 100, 100);
                    });
                    it('should flip to above when constrained left and bottom', function() {
                        target.setPosition(50, 200);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'tr-bl',
                            inside: inside
                        });

                        expectXYWH(result, 0, 100, 100, 100);
                    });
                    it('should flip to right when constrained left and bottom and target is narrower than high', function() {
                        target.setPosition(50, 200);
                        target.setWidth(50);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'tr-bl',
                            inside: inside
                        });

                        expectXYWH(result, 100, 400, 100, 100);
                    });
                });
            });

            describe('t-b', function() {
                describe('Unconstrained', function() {
                    it('should position correctly', function() {
                        target.setPosition(0, 50);
                        result = testRegion.alignTo({
                            target: target,
                            align: 't-b'
                        });

                        expectXYWH(result, 75, 300, 100, 100);
                    });
                });
                describe('constrained', function() {
                    it('should flip rightwards if constrained bottom and left', function() {
                        target.setPosition(0, 200);
                        result = testRegion.alignTo({
                            target: target,
                            align: 't-b',
                            inside: inside
                        });

                        expectXYWH(result, 250, 400, 100, 100);
                    });
                    it('should flip leftwards if constrained bottom and right', function() {
                        target.setPosition(250, 200);
                        result = testRegion.alignTo({
                            target: target,
                            align: 't-b',
                            inside: inside
                        });

                        expectXYWH(result, 150, 400, 100, 100);
                    });
                    it('should flip upwards if constrained bottom and sides', function() {
                        target.setPosition(0, 200);
                        target.setWidth(500);
                        result = testRegion.alignTo({
                            target: target,
                            align: 't-b',
                            inside: inside
                        });

                        expectXYWH(result, 200, 100, 100, 100);
                    });
                    it('should flip upwards if that is the shortest translation', function() {
                        inside.setWidth(1000);
                        target.setPosition(0, 450);
                        target.setHeight(25);
                        target.setWidth(500);
                        result = testRegion.alignTo({
                            target: target,
                            align: 't-b',
                            inside: inside
                        });

                        expectXYWH(result, 200, 350, 100, 100);
                    });
                    it('should shrink if there\'s a minHeight', function() {
                        target.setPosition(0, 10);
                        target.setHeight(440);
                        target.setWidth(500);
                        result = testRegion.alignTo({
                            target: target,
                            align: 't-b',
                            inside: inside,
                            minHeight: 50
                        });

                        expectXYWH(result, 200, 450, 100, 50);
                    });
                    it('should shrink and flip if there\'s a minHeight and available space < minHeight', function() {
                        target.setPosition(0, 50);
                        target.setHeight(440);
                        target.setWidth(500);
                        result = testRegion.alignTo({
                            target: target,
                            align: 't-b',
                            inside: inside,
                            minHeight: 50
                        });

                        expectXYWH(result, 200, 0, 100, 50);
                    });
                });
            });

            describe('b-t', function() {
                describe('Unconstrained', function() {
                    it('should position correctly', function() {
                        target.setPosition(0, 200);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'b-t'
                        });

                        expectXYWH(result, 75, 100, 100, 100);
                    });
                });
                describe('constrained', function() {
                    it('should flip rightwards if constrained top and left', function() {
                        target.setPosition(50, 50);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'b-t',
                            inside: inside
                        });

                        expectXYWH(result, 300, 0, 100, 100);
                    });
                    it('should flip leftwards if constrained top and right', function() {
                        target.setPosition(200, 50);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'b-t',
                            inside: inside
                        });

                        expectXYWH(result, 100, 0, 100, 100);
                    });
                    it('should flip down if constrained top and sides', function() {
                        target.setPosition(0, 50);
                        target.setWidth(500);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'b-t',
                            inside: inside
                        });

                        expectXYWH(result, 200, 300, 100, 100);
                    });
                    it('should flip down if that is the shortest translation', function() {
                        target.setPosition(0, 25);
                        target.setHeight(25);
                        target.setWidth(500);
                        inside.setWidth(1000);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'b-t',
                            inside: inside
                        });

                        expectXYWH(result, 200, 50, 100, 100);
                    });
                    it('should shrink if there\'s a minHeight', function() {
                        target.setPosition(0, 10);
                        target.setHeight(440);
                        target.setWidth(500);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'b-t',
                            inside: inside,
                            minHeight: 50
                        });

                        expectXYWH(result, 200, 450, 100, 50);
                    });
                    it('should shrink and flip if there\'s a minHeight and available space < minHeight', function() {
                        target.setPosition(0, 50);
                        target.setHeight(440);
                        target.setWidth(500);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'b-t',
                            inside: inside,
                            minHeight: 50
                        });

                        expectXYWH(result, 200, 0, 100, 50);
                    });
                });
            });

            describe('l-r', function() {
                describe('Unconstrained', function() {
                    it('should position correctly', function() {
                        target.setPosition(0, 0);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'l-r'
                        });

                        expectXYWH(result, 250, 75, 100, 100);
                    });
                });
                describe('constrained', function() {
                    it('should flip downwards if constrained top and right', function() {
                        target.setPosition(200, 50);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'l-r',
                            inside: inside
                        });

                        expectXYWH(result, 400, 300, 100, 100);
                    });
                    it('should flip upwards if constrained right and bottom', function() {
                        target.setPosition(200, 200);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'l-r',
                            inside: inside
                        });

                        expectXYWH(result, 400, 100, 100, 100);
                    });
                    it('should flip left if constrained right, top and bottom', function() {
                        target.setPosition(200, 0);
                        target.setHeight(500);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'l-r',
                            inside: inside
                        });

                        expectXYWH(result, 100, 200, 100, 100);
                    });
                    it('should flip left if that is the shortest translation', function() {
                        target.setPosition(450, 0);
                        target.setWidth(25);
                        target.setHeight(500);
                        inside.setHeight(1000);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'l-r',
                            inside: inside
                        });

                        expectXYWH(result, 350, 200, 100, 100);
                    });
                    it('should shrink if there\'s a minHeight', function() {
                        target.setPosition(0, 125);
                        target.setWidth(440);
                        testRegion.setHeight(400);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'l-r',
                            inside: inside,
                            minHeight: 50
                        });

                        expectXYWH(result, 400, 0, 100, 125);
                    });
                    it('should shrink if there\'s a minWidth', function() {
                        target.setPosition(0, 125);
                        target.setWidth(440);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'l-r',
                            inside: inside,
                            minWidth: 50,
                            axisLock: true
                        });

                        expectXYWH(result, 440, 200, 60, 100);
                    });
                });
            });

            describe('r-l', function() {
                describe('Unconstrained', function() {
                    it('should position correctly', function() {
                        target.setPosition(250, 0);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'r-l'
                        });

                        expectXYWH(result, 150, 75, 100, 100);
                    });
                });
                describe('constrained', function() {
                    it('should flip downwards if constrained top and left', function() {
                        target.setPosition(50, 50);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'r-l',
                            inside: inside
                        });

                        expectXYWH(result, 0, 300, 100, 100);
                    });
                    it('should flip upwards if constrained left and bottom', function() {
                        target.setPosition(50, 200);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'r-l',
                            inside: inside
                        });

                        expectXYWH(result, 0, 100, 100, 100);
                    });
                    it('should flip right if constrained left, top and bottom', function() {
                        target.setPosition(50, 0);
                        target.setHeight(500);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'r-l',
                            inside: inside
                        });

                        expectXYWH(result, 300, 200, 100, 100);
                    });
                    it('should flip right if that is the shortest translation', function() {
                        target.setPosition(50, 0);
                        target.setWidth(25);
                        target.setHeight(500);
                        inside.setHeight(1000);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'r-l',
                            inside: inside
                        });

                        expectXYWH(result, 75, 200, 100, 100);
                    });
                    it('should shrink if there\'s a minHeight', function() {
                        target.setPosition(60, 125);
                        target.setWidth(440);
                        testRegion.setHeight(400);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'r-l',
                            inside: inside,
                            minHeight: 50
                        });

                        expectXYWH(result, 0, 0, 100, 125);
                    });
                    it('should shrink if there\'s a minWidth', function() {
                        target.setPosition(60, 125);
                        target.setWidth(440);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'r-l',
                            inside: inside,
                            minWidth: 50,
                            axisLock: true
                        });

                        expectXYWH(result, 0, 200, 60, 100);
                    });
                });
            });

            /**
             * Note the aligning the x position either above or below
             * will *NOT* flip to the left or the right to conform
             * to constraints.
             *
             * tl-bl and bl-tl only flip top to bottom and bottom to top.
             *
             * This is because these alignments are used for field dropdowns
             * and they should strictly align above or below.
             */
            describe('tl-bl', function() {
                describe('Unconstrained', function() {
                    it('should position correctly', function() {
                        target.setPosition(250, 0);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'tl-bl'
                        });

                        expectXYWH(result, 250, 250, 100, 100);
                    });
                });
                describe('constrained', function() {
                    it('should flip upwards if constrained bottom', function() {
                        target.setPosition(250, 200);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'tl-bl',
                            allowXTranslate: false,
                            inside: inside
                        });

                        expectXYWH(result, 250, 100, 100, 100);
                    });
                    it('should shrink if there\'s a minHeight', function() {
                        target.setPosition(250, 10);
                        target.setHeight(440);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'tl-bl',
                            allowXTranslate: false,
                            inside: inside,
                            minHeight: 50
                        });

                        expectXYWH(result, 250, 450, 100, 50);
                    });
                    it('should shrink and flip if there\'s a minHeight and available space < minHeight', function() {
                        target.setPosition(0, 50);
                        target.setHeight(440);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'tl-bl',
                            allowXTranslate: false,
                            inside: inside,
                            minHeight: 50
                        });

                        expectXYWH(result, 0, 0, 100, 50);
                    });
                });
            });

            describe('bl-tl', function() {
                describe('Unconstrained', function() {
                    it('should position correctly', function() {
                        target.setPosition(250, 250);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'bl-tl'
                        });

                        expectXYWH(result, 250, 150, 100, 100);
                    });
                });
                describe('constrained', function() {
                    it('should flip downwards if constrained top', function() {
                        target.setPosition(250, 50);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'bl-tl',
                            allowXTranslate: false,
                            inside: inside
                        });

                        expectXYWH(result, 250, 300, 100, 100);
                    });
                    it('should shrink if there\'s a minHeight', function() {
                        target.setPosition(250, 50);
                        target.setHeight(440);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'bl-tl',
                            allowXTranslate: false,
                            inside: inside,
                            minHeight: 50
                        });

                        expectXYWH(result, 250, 0, 100, 50);
                    });
                    it('should shrink and flip if there\'s a minHeight and available space < minHeight', function() {
                        target.setPosition(0, 10);
                        target.setHeight(440);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'bl-tl',
                            allowXTranslate: false,
                            inside: inside,
                            minHeight: 50
                        });

                        expectXYWH(result, 0, 450, 100, 50);
                    });
                });
            });
        });

        describe('Anchor', function() {
            beforeEach(function() {
                inside = new Ext.util.Region(0, 500, 500, 0);
                target = new Ext.util.Region(0, 250, 250, 0);
                testRegion = new Ext.util.Region(0, 90, 90, 0);
            });

            describe('t-b', function() {
                describe('Unconstrained', function() {
                    it('should position correctly', function() {
                        target.setPosition(0, 50);
                        result = testRegion.alignTo({
                            target: target,
                            align: 't-b',
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 80, 310, 90, 90);
                        expectXYWH(result.anchor, 120, 300, 10, 10);
                    });
                });
                describe('constrained', function() {
                    it('should flip rightwards if constrained bottom and left', function() {
                        target.setPosition(0, 200);
                        result = testRegion.alignTo({
                            target: target,
                            align: 't-b',
                            inside: inside,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 260, 280, 90, 90);
                        expectXYWH(result.anchor, 250, 320, 10, 10);
                    });
                    it('should flip leftwards if constrained bottom and right', function() {
                        target.setPosition(250, 200);
                        result = testRegion.alignTo({
                            target: target,
                            align: 't-b',
                            inside: inside,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 150, 280, 90, 90);
                        expectXYWH(result.anchor, 240, 320, 10, 10);
                    });
                    it('should flip upwards if constrained bottom and sides', function() {
                        target.setPosition(0, 200);
                        target.setWidth(500);
                        result = testRegion.alignTo({
                            target: target,
                            align: 't-b',
                            inside: inside,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 205, 100, 90, 90);
                        expectXYWH(result.anchor, 245, 190, 10, 10);
                    });
                    it('should flip upwards if that is the shortest translation', function() {
                        inside.setWidth(1000);
                        target.setPosition(0, 450);
                        target.setHeight(25);
                        target.setWidth(500);
                        result = testRegion.alignTo({
                            target: target,
                            align: 't-b',
                            inside: inside,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 205, 350, 90, 90);
                        expectXYWH(result.anchor, 245, 440, 10, 10);
                    });
                    it('should shrink if there\'s a minHeight', function() {
                        target.setPosition(0, 10);
                        target.setHeight(440);
                        target.setWidth(500);
                        result = testRegion.alignTo({
                            target: target,
                            align: 't-b',
                            inside: inside,
                            minHeight: 40,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 205, 460, 90, 40);
                        expectXYWH(result.anchor, 245, 450, 10, 10);
                    });
                    it('should shrink and flip if there\'s a minHeight and available space < minHeight', function() {
                        target.setPosition(0, 50);
                        target.setHeight(440);
                        target.setWidth(500);
                        result = testRegion.alignTo({
                            target: target,
                            align: 't-b',
                            inside: inside,
                            minHeight: 40,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 205, 0, 90, 40);
                        expectXYWH(result.anchor, 245, 40, 10, 10);
                    });
                });
            });

            describe('b-t', function() {
                describe('Unconstrained', function() {
                    it('should position correctly', function() {
                        target.setPosition(0, 200);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'b-t',
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 80, 100, 90, 90);
                        expectXYWH(result.anchor, 120, 190, 10, 10);
                    });
                });
                describe('constrained', function() {
                    it('should flip rightwards if constrained top and left', function() {
                        target.setPosition(50, 50);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'b-t',
                            inside: inside,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 310, 130, 90, 90);
                        expectXYWH(result.anchor, 300, 170, 10, 10);
                    });
                    it('should flip leftwards if constrained top and right', function() {
                        target.setPosition(200, 50);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'b-t',
                            inside: inside,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 100, 130, 90, 90);
                        expectXYWH(result.anchor, 190, 170, 10, 10);
                    });
                    it('should flip down if constrained top and sides', function() {
                        target.setPosition(0, 50);
                        target.setWidth(500);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'b-t',
                            inside: inside,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 205, 310, 90, 90);
                        expectXYWH(result.anchor, 245, 300, 10, 10);
                    });
                    it('should flip down if that is the shortest translation', function() {
                        target.setPosition(0, 25);
                        target.setHeight(25);
                        target.setWidth(500);
                        inside.setWidth(1000);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'b-t',
                            inside: inside,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 205, 60, 90, 90);
                        expectXYWH(result.anchor, 245, 50, 10, 10);
                    });
                    it('should shrink if there\'s a minHeight', function() {
                        target.setPosition(0, 10);
                        target.setHeight(440);
                        target.setWidth(500);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'b-t',
                            inside: inside,
                            minHeight: 40,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 205, 460, 90, 40);
                        expectXYWH(result.anchor, 245, 450, 10, 10);
                    });
                    it('should shrink and flip if there\'s a minHeight and available space < minHeight', function() {
                        target.setPosition(0, 50);
                        target.setHeight(440);
                        target.setWidth(500);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'b-t',
                            inside: inside,
                            minHeight: 40,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 205, 0, 90, 40);
                        expectXYWH(result.anchor, 245, 40, 10, 10);
                    });
                });
            });

            describe('l-r', function() {
                describe('Unconstrained', function() {
                    it('should position correctly', function() {
                        target.setPosition(0, 0);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'l-r',
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 260, 80, 90, 90);
                        expectXYWH(result.anchor, 250, 120, 10, 10);
                    });
                });
                describe('constrained', function() {
                    it('should flip downwards if constrained top and right', function() {
                        target.setPosition(200, 50);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'l-r',
                            inside: inside,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 280, 310, 90, 90);
                        expectXYWH(result.anchor, 320, 300, 10, 10);
                    });
                    it('should flip upwards if constrained right and bottom', function() {
                        target.setPosition(200, 200);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'l-r',
                            inside: inside,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 280, 100, 90, 90);
                        expectXYWH(result.anchor, 320, 190, 10, 10);
                    });
                    it('should flip left if constrained right, top and bottom', function() {
                        target.setPosition(200, 0);
                        target.setHeight(500);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'l-r',
                            inside: inside,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 100, 205, 90, 90);
                        expectXYWH(result.anchor, 190, 245, 10, 10);
                    });
                    it('should flip left if that is the shortest translation', function() {
                        target.setPosition(450, 0);
                        target.setWidth(25);
                        target.setHeight(500);
                        inside.setHeight(1000);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'l-r',
                            inside: inside,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 350, 205, 90, 90);
                        expectXYWH(result.anchor, 440, 245, 10, 10);
                    });
                    it('should shrink if there\'s a minHeight', function() {
                        target.setPosition(0, 125);
                        target.setWidth(440);
                        testRegion.setHeight(400);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'l-r',
                            inside: inside,
                            minHeight: 40,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 175, 0, 90, 115);
                        expectXYWH(result.anchor, 215, 115, 10, 10);
                    });
                });
            });

            describe('r-l', function() {
                describe('Unconstrained', function() {
                    it('should position correctly', function() {
                        target.setPosition(250, 0);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'r-l',
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 150, 80, 90, 90);
                        expectXYWH(result.anchor, 240, 120, 10, 10);
                    });
                });
                describe('constrained', function() {
                    it('should flip downwards if constrained top and left', function() {
                        target.setPosition(50, 50);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'r-l',
                            inside: inside,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 130, 310, 90, 90);
                        expectXYWH(result.anchor, 170, 300, 10, 10);
                    });
                    it('should flip upwards if constrained left and bottom', function() {
                        target.setPosition(50, 200);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'r-l',
                            inside: inside,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 130, 100, 90, 90);
                        expectXYWH(result.anchor, 170, 190, 10, 10);
                    });
                    it('should flip right if constrained left, top and bottom', function() {
                        target.setPosition(50, 0);
                        target.setHeight(500);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'r-l',
                            inside: inside,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 310, 205, 90, 90);
                        expectXYWH(result.anchor, 300, 245, 10, 10);
                    });
                    it('should flip right if that is the shortest translation', function() {
                        target.setPosition(50, 0);
                        target.setWidth(25);
                        target.setHeight(500);
                        inside.setHeight(1000);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'r-l',
                            inside: inside,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 85, 205, 90, 90);
                        expectXYWH(result.anchor, 75, 245, 10, 10);
                    });
                    it('should shrink if there\'s a minHeight', function() {
                        target.setPosition(60, 125);
                        target.setWidth(440);
                        testRegion.setHeight(400);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'r-l',
                            inside: inside,
                            minHeight: 40,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 235, 0, 90, 115);
                        expectXYWH(result.anchor, 275, 115, 10, 10);
                    });
                });
            });

            /**
             * Note the aligning the x position either above or below
             * will *NOT* flip to the left or the right to conform
             * to constraints.
             *
             * tl-bl and bl-tl only flip top to bottom and bottom to top.
             *
             * This is because these alignments are used for field dropdowns
             * and they should strictly align above or below.
             */
            describe('tl-bl', function() {
                describe('Unconstrained', function() {
                    it('should position correctly', function() {
                        target.setPosition(250, 0);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'tl-bl',
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 250, 260, 90, 90);
                        expectXYWH(result.anchor, 290, 250, 10, 10);
                    });
                });
                describe('constrained', function() {
                    it('should flip upwards if constrained bottom', function() {
                        target.setPosition(250, 200);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'tl-bl',
                            allowXTranslate: false,
                            inside: inside,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 250, 100, 90, 90);
                        expectXYWH(result.anchor, 290, 190, 10, 10);
                    });
                    it('should shrink if there\'s a minHeight', function() {
                        target.setPosition(250, 10);
                        target.setHeight(440);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'tl-bl',
                            allowXTranslate: false,
                            inside: inside,
                            minHeight: 40,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 250, 460, 90, 40);
                        expectXYWH(result.anchor, 290, 450, 10, 10);
                    });
                    it('should shrink and flip if there\'s a minHeight and available space < minHeight', function() {
                        target.setPosition(0, 50);
                        target.setHeight(440);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'tl-bl',
                            allowXTranslate: false,
                            inside: inside,
                            minHeight: 40,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 0, 0, 90, 40);
                        expectXYWH(result.anchor, 40, 40, 10, 10);
                    });
                });
            });

            describe('bl-tl', function() {
                describe('Unconstrained', function() {
                    it('should position correctly', function() {
                        target.setPosition(250, 250);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'bl-tl',
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 250, 150, 90, 90);
                        expectXYWH(result.anchor, 290, 240, 10, 10);
                    });
                });
                describe('constrained', function() {
                    it('should flip downwards if constrained top', function() {
                        target.setPosition(250, 50);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'bl-tl',
                            allowXTranslate: false,
                            inside: inside,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 250, 310, 90, 90);
                        expectXYWH(result.anchor, 290, 300, 10, 10);
                    });
                    it('should shrink if there\'s a minHeight', function() {
                        target.setPosition(250, 50);
                        target.setHeight(440);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'bl-tl',
                            allowXTranslate: false,
                            inside: inside,
                            minHeight: 40,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 250, 0, 90, 40);
                        expectXYWH(result.anchor, 290, 40, 10, 10);
                    });
                    it('should shrink and flip if there\'s a minHeight and available space < minHeight', function() {
                        target.setPosition(0, 10);
                        target.setHeight(440);
                        result = testRegion.alignTo({
                            target: target,
                            align: 'bl-tl',
                            allowXTranslate: false,
                            inside: inside,
                            minHeight: 40,
                            anchorSize: 10,
                            offset: 0
                        });

                        expectXYWH(result, 0, 460, 90, 40);
                        expectXYWH(result.anchor, 40, 450, 10, 10);
                    });
                });
            });

            describe('corner to corner alignment', function() {
                it('should handle b100-t0', function() {
                    target.setPosition(250, 250);
                    result = testRegion.alignTo({
                        target: target,
                        align: 'b100-t0',
                        anchorSize: 10,
                        offset: 0
                    });

                    expectXYWH(result, 168, 150, 90, 90);
                    expectXYWH(result.anchor, 246, 240, 10, 10);
                });
                it('should handle b0-t100', function() {
                    target.setPosition(250, 250);
                    result = testRegion.alignTo({
                        target: target,
                        align: 'b0-t100',
                        anchorSize: 10,
                        offset: 0
                    });

                    expectXYWH(result, 491, 150, 90, 90);
                    expectXYWH(result.anchor, 493, 240, 10, 10);
                });
                it('should handle t100-b0', function() {
                    target.setPosition(250, 250);
                    result = testRegion.alignTo({
                        target: target,
                        align: 't100-b0',
                        anchorSize: 10,
                        offset: 0
                    });

                    expectXYWH(result, 168, 510, 90, 90);
                    expectXYWH(result.anchor, 246, 500, 10, 10);
                });
                it('should handle t0-b100', function() {
                    target.setPosition(250, 250);
                    result = testRegion.alignTo({
                        target: target,
                        align: 't0-b100',
                        anchorSize: 10,
                        offset: 0
                    });

                    expectXYWH(result, 491, 510, 90, 90);
                    expectXYWH(result.anchor, 493, 500, 10, 10);
                });
                it('should handle r100-l0', function() {
                    target.setPosition(250, 250);
                    result = testRegion.alignTo({
                        target: target,
                        align: 'r100-l0',
                        anchorSize: 10,
                        offset: 0
                    });

                    expectXYWH(result, 150, 168, 90, 90);
                    expectXYWH(result.anchor, 240, 246, 10, 10);
                });
                it('should handle r0-l100', function() {
                    target.setPosition(250, 250);
                    result = testRegion.alignTo({
                        target: target,
                        align: 'r0-l100',
                        anchorSize: 10,
                        offset: 0
                    });

                    expectXYWH(result, 150, 491, 90, 90);
                    expectXYWH(result.anchor, 240, 493, 10, 10);
                });
                it('should handle l100-r0', function() {
                    target.setPosition(250, 250);
                    result = testRegion.alignTo({
                        target: target,
                        align: 'l100-r0',
                        anchorSize: 10,
                        offset: 0
                    });

                    expectXYWH(result, 510, 168, 90, 90);
                    expectXYWH(result.anchor, 500, 246, 10, 10);
                });
                it('should handle l0-r100', function() {
                    target.setPosition(250, 250);
                    result = testRegion.alignTo({
                        target: target,
                        align: 'l0-r100',
                        anchorSize: 10,
                        offset: 0
                    });

                    expectXYWH(result, 510, 491, 90, 90);
                    expectXYWH(result.anchor, 500, 493, 10, 10);
                });
            });
        });
    });

    describe('getAnchorPoint', function() {
        it('should return the requested anchor point', function() {
            region1 = new Ext.util.Region(0, 100, 100, 0);

            // The 9 primary anchor points
            expect(region1.getAnchorPoint('tl')).toEqual([0, 0]);
            expect(region1.getAnchorPoint('t')).toEqual([50, 0]);
            expect(region1.getAnchorPoint('tr')).toEqual([100, 0]);
            expect(region1.getAnchorPoint('l')).toEqual([0, 50]);
            expect(region1.getAnchorPoint('c')).toEqual([50, 50]);
            expect(region1.getAnchorPoint('r')).toEqual([100, 50]);
            expect(region1.getAnchorPoint('bl')).toEqual([0, 100]);
            expect(region1.getAnchorPoint('b')).toEqual([50, 100]);
            expect(region1.getAnchorPoint('br')).toEqual([100, 100]);
        });
    });
});

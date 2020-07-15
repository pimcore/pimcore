topSuite("Ext.drag.Info", ['Ext.drag.*', 'Ext.dom.Element'], function() {
    var helper = Ext.testHelper,
        touchId = 0,
        cursorTrack, source, target,
        dragEl, dropEl, defaultElCfg;

    function makeEl(cfg) {
        return Ext.getBody().createChild(cfg);
    }

    function makeFloatEl(borderColor, top, left, width, height) {
        return makeEl({
            style: {
                position: 'absolute',
                border: '1px solid ' + borderColor,
                top: top + 'px',
                left: left + 'px',
                width: (width || 50) + 'px',
                height: (height || 50) + 'px'
            }
        });
    }

    function makeDragEl(x, y) {
        if (typeof x !== 'number') {
            x = 50;
        }

        if (typeof y !== 'number') {
            y = 50;
        }

        dragEl = makeEl({
            style: {
                position: 'absolute',
                width: '20px',
                height: '20px',
                left: x + 'px',
                top: y + 'px',
                border: '1px solid red'
            }
        });
    }

    function makeDropEl(x, y) {
        if (typeof x !== 'number') {
            x = 100;
        }

        if (typeof y !== 'number') {
            y = 100;
        }

        dropEl = makeEl({
            style: {
                position: 'absolute',
                width: '100px',
                height: '100px',
                left: x + 'px',
                top: y + 'px',
                border: '1px solid blue'
            }
        });
    }

    function makeSource(cfg) {
        cfg = cfg || {};

        if (!cfg.element) {
            if (!dragEl) {
                makeDragEl();
            }

            cfg.element = dragEl;
        }

        source = new Ext.drag.Source(cfg);
    }

    function makeTarget(cfg) {
        cfg = cfg || {};

        if (!cfg.element) {
            if (!dropEl) {
                makeDropEl();
            }

            cfg.element = dropEl;
        }

        target = new Ext.drag.Target(cfg);
    }

    function start(cfg, target) {
        cursorTrack = [cfg.x || 0, cfg.y || 0];
        helper.touchStart(target || dragEl, cfg);
    }

    function move(cfg, target) {
        cursorTrack = [cfg.x || 0, cfg.y || 0];
        helper.touchMove(target || dragEl, cfg);
    }

    function end(cfg, target) {
        cursorTrack = [cfg.x || 0, cfg.y || 0];
        helper.touchEnd(target || dragEl, cfg);
    }

    function cancel(cfg, target) {
        cursorTrack = [cfg.x || 0, cfg.y || 0];
        helper.touchCancel(target || dragEl, cfg);
    }

    function startDrag(x, y, target) {
        runs(function() {
            var xy = source.getElement().getXY();

            x = x || xy[0];
            y = y || xy[1];

            start({
                id: touchId,
                x: x,
                y: y
            }, target);
        });
        waitsForAnimation();
    }

     function startOffsetDrag(offsetX, offsetY, target) {
        runs(function() {
            var xy = source.getElement().getXY();

            start({
                id: touchId,
                x: xy[0] + (offsetX || 0),
                y: xy[1] + (offsetY || 0)
            }, target);
        });
        waitsForAnimation();
    }

    function moveBy(x, y, target) {
        runs(function() {
            move({
                id: touchId,
                x: cursorTrack[0] + (x || 0),
                y: cursorTrack[1] + (y || 0)
            }, target);
        });
        waitsForAnimation();
    }

    function endDrag(x, y, target) {
        runs(function() {
            x = x || cursorTrack[0];
            y = y || cursorTrack[1];

            end({
                id: touchId,
                x: x,
                y: y
            }, target);
        });
        waitsForAnimation();
        runs(function() {
            ++touchId;
        });
    }

    function getCenter(el) {
        var xy = el.getXY();

        return [xy[0] + (el.getWidth() / 2), xy[1] + (el.getHeight() / 2)];
    }

    function runsExpectCallCount(spies, n) {
        runs(function() {
            if (!Ext.isArray(spies)) {
                spies = [spies];
            }

            Ext.Array.forEach(spies, function(spy) {
                expect(spy.callCount).toBe(n);
            });
        });
    }

    beforeEach(function() {
        cursorTrack = null;
        ++touchId;
        defaultElCfg = {
            style: {
                position: 'absolute',
                width: '20px',
                height: '20px',
                left: '50px',
                top: '50px',
                border: '1px solid red'
            }
        };
    });

    afterEach(function() {
        cursorTrack = source = target = dragEl = dropEl = Ext.destroy(dragEl, dropEl, source, target);
    });

    function makeData(xy) {
        return {
            x: xy[0],
            y: xy[1]
        };
    }

    describe("clone", function() {
        it("should clone correctly", function() {
            var spy = jasmine.createSpy(),
                clone;

            makeSource();
            makeTarget();

            source.on('dragmove', spy);

            startDrag();
            moveBy(50, 50);
            runs(function() {
                var info = spy.mostRecentCall.args[1];

                clone = info.clone();

                expect(clone.cursor).toEqual(info.cursor);
                expect(clone.data).toEqual(info.data);
                expect(clone.element).toEqual(info.element);
                expect(clone.eventTarget).toBe(info.eventTarget);
                expect(clone.proxy).toEqual(info.proxy);
                expect(clone.source).toBe(info.source);
                expect(clone.target).toBe(info.target);
                expect(clone.valid).toBe(info.valid);
            });
            moveBy(100, 100);
            runs(function() {
                var other = spy.mostRecentCall.args[1].clone();

                expect(other.cursor).not.toEqual(clone.cursor);
                expect(other.data).toEqual(clone.data);
                expect(other.element).not.toEqual(clone.element);
                expect(other.eventTarget).toBe(clone.eventTarget);
                expect(other.proxy).not.toEqual(clone.proxy);
                expect(other.source).toBe(clone.source);
                expect(other.target).not.toBe(clone.source);
                expect(other.valid).not.toBe(clone.valid);
            });
            endDrag();
        });
    });

    describe("target/valid", function() {
        var spy;

        beforeEach(function() {
            makeSource();
            makeTarget();

            spy = jasmine.createSpy();

            source.on('dragmove', spy.andCallFake(function(source, info) {
                spy.mostRecentCall.dragInfo = info.clone();
            }));
        });

        afterEach(function() {
            spy = null;
        });

        function runsExpectTarget(target, valid) {
            runs(function() {
                var info = spy.mostRecentCall.dragInfo;

                expect(info.target).toBe(target);
                expect(info.valid).toBe(valid);
            });
        }

        it("should be null, not valid by default", function() {
            startDrag();
            moveBy(10, 10);
            runsExpectTarget(null, false);
            endDrag();
        });

        it("should be the target and valid when it is active", function() {
            startDrag();
            moveBy(50, 50);
            runsExpectTarget(target, true);
            endDrag();
        });

        it("should null out when moved out", function() {
            startDrag();
            moveBy(50, 50);
            runsExpectTarget(target, true);
            moveBy(100, 100);
            runsExpectTarget(null, false);
            endDrag();
        });

        it("should update to a new target", function() {
            var other = new Ext.drag.Target({
                element: makeFloatEl('green', 100, 0)
            });

            startDrag();
            moveBy(50, 50);
            runsExpectTarget(target, true);
            moveBy(0, 120);
            runsExpectTarget(null, false);
            moveBy(-60, -100);
            runsExpectTarget(other, true);
            endDrag();
            runs(function() {
                other.destroy();
            });
        });

        it("should not not be valid on an invalid target", function() {
            target.disable();
            startDrag();
            moveBy(50, 50);
            runsExpectTarget(target, false);
            endDrag();
        });
    });

    describe("eventTarget", function() {
        it("should have the element as the eventTarget if the drag was on the element", function() {
            var spy = jasmine.createSpy();

            makeSource();
            source.on('dragmove', spy);
            startDrag();
            moveBy(10, 0);
            runs(function() {
                expect(spy.mostRecentCall.args[1].eventTarget).toBe(dragEl.dom);
            });
            endDrag();
        });

        it("should have the real eventTarget if it was a child of the element", function() {
            var spy = jasmine.createSpy();

            makeDragEl();
            var child = Ext.fly(dragEl.createChild({
                width: '20px',
                height: '20px',
                left: '40px',
                top: '40px',
                position: 'absolute'
            }, null, true));

            makeSource();
            source.on('dragmove', spy);
            var center = getCenter(child);

            startDrag(center[0], center[1], child);
            moveBy(10, 0);
            runs(function() {
                expect(spy.mostRecentCall.args[1].eventTarget).toBe(child.dom);
            });
            endDrag();
        });
    });

    describe("data", function() {
        var dropSpy;

        beforeEach(function() {
            dropSpy = jasmine.createSpy();
            makeTarget();
            target.on('drop', dropSpy);
        });

        afterEach(function() {
            dropSpy = null;
        });

        function expectPromiseValue(key, v) {
            var promiseSpy = jasmine.createSpy();

            runs(function() {
                var info = dropSpy.mostRecentCall.args[1];

                info.getData(key).then(promiseSpy);
            });
            waitsFor(function() {
                return promiseSpy.callCount > 0;
            });
            runs(function() {
                expect(promiseSpy.mostRecentCall.args[0]).toBe(v);
            });
        }

        it("should call the describe method once at the start of the drag", function() {
            var describeSpy = jasmine.createSpy(),
                dragSpy = jasmine.createSpy();

            makeSource({
                describe: describeSpy
            });

            source.on('dragmove', dragSpy);

            startDrag();
            moveBy(10, 10);
            endDrag();

            runs(function() {
                expect(describeSpy.callCount).toBe(1);
                expect(describeSpy.mostRecentCall.args[0]).toBe(dragSpy.mostRecentCall.args[1]);
            });
        });

        describe("setData", function() {
            it("should populate the types collection", function() {
                makeSource({
                    describe: function(info) {
                        info.setData('type1', 'foo');
                        info.setData('type2', 'bar');

                        expect(info.types).toEqual(['type1', 'type2']);
                    }
                });

                startDrag();
                moveBy(10, 10);
                endDrag();
            });
        });

        describe("getData", function() {
            it("should throw an exception if accessed before the drop is complete", function() {
                makeSource({
                    describe: function(info) {
                        info.setData('foo', 1);
                    }
                });
                source.on('dragmove', dropSpy);
                startDrag(50, 50);
                moveBy(50, 50);
                runs(function() {
                    var info = dropSpy.mostRecentCall.args[1];

                    expect(function() {
                        info.getData('foo');
                    }).toThrow();
                });
                endDrag();
            });

            it("should return a promise with an empty string if the type doesn't exist", function() {
                makeSource({
                    describe: function(info) {
                        info.setData('foo', 1);
                    }
                });

                startDrag();
                moveBy(50, 50);
                endDrag();

                waitsForSpy(dropSpy);

                runs(function() {
                    expectPromiseValue('bar', '');
                });
            });

            describe("with static data", function() {
                it("should wrap the data in a promise", function() {
                    makeSource({
                        describe: function(info) {
                            info.setData('foo', 1);
                        }
                    });

                    startDrag();
                    moveBy(50, 50);
                    endDrag();

                    waitsForSpy(dropSpy);

                    runs(function() {
                        expectPromiseValue('foo', 1);
                    });
                });

                it("should be able to retrieve data from multiple types", function() {
                    makeSource({
                        describe: function(info) {
                            info.setData('foo', 1);
                            info.setData('bar', 2);
                        }
                    });

                    startDrag();
                    moveBy(50, 50);
                    endDrag();

                    waitsForSpy(dropSpy);

                    runs(function() {
                        expectPromiseValue('foo', 1);
                        expectPromiseValue('bar', 2);
                    });
                });

                it("should return complex data", function() {
                    var o = [{}, {}, {}];

                    makeSource({
                        describe: function(info) {
                            info.setData('foo', o);
                        }
                    });

                    startDrag();
                    moveBy(50, 50);
                    endDrag();

                    waitsForSpy(dropSpy);

                    runs(function() {
                        expectPromiseValue('foo', o);
                    });
                });
            });

            describe("with a function", function() {
                it("should call the function in the scope source and pass the info object", function() {
                    var dataSpy = jasmine.createSpy();

                    makeSource({
                        describe: function(info) {
                            info.setData('foo', dataSpy);
                        }
                    });

                    startDrag();
                    moveBy(50, 50);
                    endDrag();

                    waitsForSpy(dropSpy);

                    runs(function() {
                        var info = dropSpy.mostRecentCall.args[1];

                        info.getData('foo');
                        expect(dataSpy.callCount).toBe(1);
                        expect(dataSpy.mostRecentCall.object).toBe(source);
                        // The info from the drop spy
                        expect(dataSpy.mostRecentCall.args[0]).toBe(dropSpy.mostRecentCall.args[1]);
                    });
                });

                it("should call the function only once on repeat access", function() {
                    var dataSpy = jasmine.createSpy();

                    makeSource({
                        describe: function(info) {
                            info.setData('foo', dataSpy);
                        }
                    });

                    startDrag();
                    moveBy(50, 50);
                    endDrag();

                    waitsForSpy(dropSpy);

                    runs(function() {
                        var info = dropSpy.mostRecentCall.args[1];

                        info.getData('foo');
                        info.getData('foo');
                        info.getData('foo');
                        info.getData('foo');
                        expect(dataSpy.callCount).toBe(1);
                    });
                });

                it("should execute a function and wrap the result in a promise", function() {
                    makeSource({
                        describe: function(info) {
                            info.setData('foo', function() {
                                return 2;
                            });
                        }
                    });

                    startDrag();
                    moveBy(50, 50);
                    endDrag();

                    waitsForSpy(dropSpy);

                    runs(function() {
                        expectPromiseValue('foo', 2);
                    });
                });

                it("should be able to retrieve data from multiple types", function() {
                    makeSource({
                        describe: function(info) {
                            info.setData('foo', function() {
                                return 1;
                            });
                            info.setData('bar', function() {
                                return 2;
                            });
                        }
                    });

                    startDrag();
                    moveBy(50, 50);
                    endDrag();

                    waitsForSpy(dropSpy);

                    runs(function() {
                        expectPromiseValue('foo', 1);
                        expectPromiseValue('bar', 2);
                    });
                });

                it("should return complex data", function() {
                    var o = [{}, {}, {}];

                    makeSource({
                        describe: function(info) {
                            info.setData('foo', function() {
                                return o;
                            });
                        }
                    });

                    startDrag();
                    moveBy(50, 50);
                    endDrag();

                    waitsForSpy(dropSpy);

                    runs(function() {
                        expectPromiseValue('foo', o);
                    });
                });
            });
        });

        describe("clearData", function() {
            it("should remove from the types", function() {
                makeSource({
                    describe: function(info) {
                        info.setData('type1', 'foo');
                        info.setData('type2', 'bar');

                        expect(info.types).toEqual(['type1', 'type2']);

                        info.clearData('type1');
                        expect(info.types).toEqual(['type2']);
                    }
                });

                startDrag();
                moveBy(10, 10);
                endDrag();
            });

            it("should clear the data value", function() {
                makeSource({
                    describe: function(info) {
                        info.setData('type1', 'foo');
                        info.clearData('type1');
                    }
                });

                startDrag();
                moveBy(50, 50);
                endDrag();

                waitsForSpy(dropSpy);

                runs(function() {
                    expectPromiseValue('type1', '');
                });
            });
        });
    });

    describe("positioning", function() {
        var spy;

        beforeEach(function() {
            spy = jasmine.createSpy();
        });

        afterEach(function() {
            spy = null;
        });

        function makeDataSource(cfg) {
            makeSource(cfg);
            source.on('dragmove', spy.andCallFake(function(source, info) {
                spy.mostRecentCall.dragInfo = info.clone();
            }));
        }

        function runsExpectData(key, initial, current, delta, offset) {
            runs(function() {
                var data = spy.mostRecentCall.dragInfo[key];

                expect(data.initial).toEqual(makeData(initial));
                expect(data.current).toEqual(makeData(current));
                expect(data.delta).toEqual(makeData(delta));

                if (offset) {
                    expect(data.offset).toEqual(makeData(offset));
                }
            });
        }

        describe("cursor", function() {
            function runsExpectCursor(initial, current, delta, offset) {
                runsExpectData('cursor', initial, current, delta, offset);
            }

            it("should begin capturing from the initial touchstart", function() {
                makeDataSource();

                startDrag();
                moveBy(10, 20);
                runsExpectCursor([50, 50], [60, 70], [10, 20], [0, 0]);
                endDrag();
            });

            it("should ignore constraints", function() {
                makeDataSource({
                    constrain: {
                        x: [20, 80],
                        y: [30, 90]
                    }
                });

                startDrag();
                // top left
                moveBy(-40, -40);
                runsExpectCursor([50, 50], [10, 10], [-40, -40], [0, 0]);
                // top right
                moveBy(100, 0);
                runsExpectCursor([50, 50], [110, 10], [60, -40], [0, 0]);
                // bottom left
                moveBy(-100, 100);
                runsExpectCursor([50, 50], [10, 110], [-40, 60], [0, 0]);
                // bottom right
                moveBy(100, 0);
                runsExpectCursor([50, 50], [110, 110], [60, 60], [0, 0]);
                endDrag();
            });

            it("should set the offset correctly and calculate the delta based off that", function() {
                makeDataSource();
                startOffsetDrag(25, 25);
                moveBy(10, 10);
                runsExpectCursor([75, 75], [85, 85], [10, 10], [25, 25]);
                moveBy(-50, -50);
                runsExpectCursor([75, 75], [35, 35], [-40, -40], [25, 25]);
                endDrag();
            });
        });

        describe("proxy", function() {
            function runsExpectProxy(initial, current, delta) {
                runsExpectData('proxy', initial, current, delta);
            }

            describe("with element as proxy", function() {
                it("should return the element", function() {
                    makeDataSource();
                    startDrag();
                    moveBy(10, 10);
                    runs(function() {
                        expect(spy.mostRecentCall.args[1].proxy.element).toBe(dragEl);
                    });
                    endDrag();
                });

                it("should capture the position from touchstart", function() {
                    makeDataSource();

                    startDrag();
                    moveBy(10, 10);
                    runsExpectProxy([50, 50], [60, 60], [10, 10]);
                    endDrag();
                });

                it("should respect constraints", function() {
                    makeDataSource({
                        constrain: {
                            x: [20, 80],
                            y: [30, 90]
                        }
                    });

                    startDrag();
                    // top left
                    moveBy(-40, -40);
                    runsExpectProxy([50, 50], [20, 30], [-30, -20]);
                    // top right
                    moveBy(100, 0);
                    runsExpectProxy([50, 50], [80, 30], [30, -20]);
                    // bottom left
                    moveBy(-100, 100);
                    runsExpectProxy([50, 50], [20, 90], [-30, 40]);
                    // bottom right
                    moveBy(100, 0);
                    runsExpectProxy([50, 50], [80, 90], [30, 40]);
                    endDrag();
                });

                it("should calculate correctly when the offset is not at the top left", function() {
                    makeDataSource();
                    startOffsetDrag(25, 25);
                    moveBy(10, 10);
                    runsExpectProxy([50, 50], [60, 60], [10, 10]);
                    moveBy(-20, -20);
                    runsExpectProxy([50, 50], [40, 40], [-10, -10]);
                    endDrag();
                });
            });

            describe("with custom proxy", function() {
                it("should return the element", function() {
                    makeDataSource({
                        proxy: {
                            type: 'placeholder',
                            cursorOffset: [0, 0]
                        }
                    });
                    startDrag();
                    moveBy(10, 10);
                    runs(function() {
                        expect(spy.mostRecentCall.args[1].proxy.element).not.toBe(source.getElement());
                    });
                    endDrag();
                });

                it("should capture the position from touchstart", function() {
                    makeDataSource({
                        proxy: {
                            type: 'placeholder',
                            cursorOffset: [0, 0]
                        }
                    });

                    startDrag();
                    moveBy(10, 10);
                    runsExpectProxy([50, 50], [60, 60], [10, 10]);
                    endDrag();
                });

                it("should respect constraints", function() {
                    makeDataSource({
                        proxy: {
                            type: 'placeholder',
                            cursorOffset: [0, 0]
                        },
                        constrain: {
                            x: [20, 80],
                            y: [30, 90]
                        }
                    });

                    startDrag();
                    // top left
                    moveBy(-40, -40);
                    runsExpectProxy([50, 50], [20, 30], [-30, -20]);
                    // top right
                    moveBy(100, 0);
                    runsExpectProxy([50, 50], [80, 30], [30, -20]);
                    // bottom left
                    moveBy(-100, 100);
                    runsExpectProxy([50, 50], [20, 90], [-30, 40]);
                    // bottom right
                    moveBy(100, 0);
                    runsExpectProxy([50, 50], [80, 90], [30, 40]);
                    endDrag();
                });

                it("should calculate correctly when the offset is not at the top left", function() {
                    makeDataSource({
                        proxy: {
                            type: 'placeholder',
                            cursorOffset: [0, 0]
                        }
                    });
                    startOffsetDrag(25, 25);
                    moveBy(10, 10);
                    runsExpectProxy([50, 50], [85, 85], [35, 35]);
                    moveBy(-20, -20);
                    runsExpectProxy([50, 50], [65, 65], [15, 15]);
                    endDrag();
                });
            });
        });

        describe("element", function() {
            function runsExpectElement(initial, current, delta) {
                runsExpectData('element', initial, current, delta);
            }

            describe("with element as proxy", function() {
                it("should capture the position from touchstart", function() {
                    makeDataSource();

                    startDrag();
                    moveBy(10, 10);
                    runsExpectElement([50, 50], [60, 60], [10, 10]);
                    endDrag();
                });

                it("should respect constraints", function() {
                    makeDataSource({
                        constrain: {
                            x: [20, 80],
                            y: [30, 90]
                        }
                    });

                    startDrag();
                    // top left
                    moveBy(-40, -40);
                    runsExpectElement([50, 50], [20, 30], [-30, -20]);
                    // top right
                    moveBy(100, 0);
                    runsExpectElement([50, 50], [80, 30], [30, -20]);
                    // bottom left
                    moveBy(-100, 100);
                    runsExpectElement([50, 50], [20, 90], [-30, 40]);
                    // bottom right
                    moveBy(100, 0);
                    runsExpectElement([50, 50], [80, 90], [30, 40]);
                    endDrag();
                });

                it("should calculate correctly when the offset is not at the top left", function() {
                    makeDataSource();
                    startOffsetDrag(25, 25);
                    moveBy(10, 10);
                    runsExpectElement([50, 50], [60, 60], [10, 10]);
                    moveBy(-20, -20);
                    runsExpectElement([50, 50], [40, 40], [-10, -10]);
                    endDrag();
                });
            });

            describe("with custom proxy", function() {
                it("should not modify the element", function() {
                    makeDataSource({
                        proxy: {
                            type: 'placeholder',
                            html: 'Foo'
                        }
                    });
                    startDrag();
                    moveBy(150, 150);
                    runsExpectElement([50, 50], [50, 50], [0, 0]);
                    moveBy(-50, -75);
                    runsExpectElement([50, 50], [50, 50], [0, 0]);
                    endDrag();
                });
            });
        });
    });

    describe("source", function() {
        it("should set the source", function() {
            var spy = jasmine.createSpy();

            makeSource();
            source.on('dragmove', spy);
            startDrag();
            moveBy(10, 10);
            runs(function() {
                expect(spy.mostRecentCall.args[1].source).toBe(source);
            });
            endDrag();
        });
    });
});

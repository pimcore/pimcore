(Ext.isAndroid || Ext.isiOS ? xtopSuite : topSuite)("Ext.drag.Source", ['Ext.drag.*', 'Ext.dom.Element'], function() {
    var helper = Ext.testHelper,
        touchId = 0,
        cursorTrack, source, target,
        dragEl, dropEl, defaultElCfg, dragSpy;

    function makeEl(cfg) {
        return Ext.getBody().createChild(cfg);
    }

    function makeDragEl(x, y) {
        if (typeof x !== 'number') {
            x = 5;
        }

        if (typeof y !== 'number') {
            y = 5;
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
            x = 50;
        }

        if (typeof y !== 'number') {
            y = 50;
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

    function makeSource(cfg, Type) {
        cfg = cfg || {};

        if (!cfg.element) {
            if (!dragEl) {
                makeDragEl();
            }

            cfg.element = dragEl;
        }

        Type = Type || Ext.drag.Source;
        source = new Type(cfg);
        source.on('dragmove', dragSpy.andCallFake(function(source, info) {
            dragSpy.mostRecentCall.dragInfo = info;
        }));
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

    function doCancel(x, y, target) {
        runs(function() {
            ++touchId;
            x = x || cursorTrack[0];
            y = y || cursorTrack[1];

            cancel({
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

    function dragBy(x, y, target) {
        startDrag(null, null, target);
        moveBy(x, y, target);
        endDrag(null, null, target);
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
        dragSpy = jasmine.createSpy();
    });

    afterEach(function() {
        dragSpy = cursorTrack = source = target = dragEl = dropEl = Ext.destroy(dragEl, dropEl, source, target);
    });

    function expectXY(x, y) {
        var info = dragSpy.mostRecentCall.dragInfo,
            el = (info && info.proxy.element) || source.getElement();

        expect(el.getXY()).toEqual([x, y]);
    }

    function expectElXY(x, y) {
        expect(source.getElement().getXY()).toEqual([x, y]);
    }

    function expectProxyXY(x, y) {
        var info = dragSpy.mostRecentCall.dragSpy;

        expect(info.proxy.element.getXY()).toEqual([x, y]);
    }

    function runsExpectXY(x, y) {
        runs(function() {
            expectXY(x, y);
        });
    }

    function runsExpectElXY(x, y) {
        runs(function() {
            expectElXY(x, y);
        });
    }

    function runsExpectProxyXY(x, y) {
        runs(function() {
            expectProxyXY(x, y);
        });
    }

    describe("autoDestroy", function() {
        it("should destroy the element by default", function() {
            makeSource();

            source.destroy();
            expect(Ext.get(dragEl.id)).toBeNull();
        });

        it("should destroy the element with autoDestroy: true", function() {
            makeSource({
                autoDestroy: true
            });

            source.destroy();
            expect(Ext.get(dragEl.id)).toBeNull();
        });

        it("should not destroy the element with autoDestroy: false", function() {
            makeSource({
                autoDestroy: false
            });

            source.destroy();
            expect(Ext.get(dragEl.id)).toBe(dragEl);
        });
    });

    describe("enable/disable", function() {
        beforeEach(function() {
            makeSource();
        });

        it("should not be disabled by default", function() {
            expect(source.isDisabled()).toBe(false);
        });

        it("should be disabled after a call to disable", function() {
            source.disable();
            expect(source.isDisabled()).toBe(true);
        });

        it("should not be disabled after calling enable", function() {
            source.disable();
            source.enable();
            expect(source.isDisabled()).toBe(false);
        });

        it("should not drag while disabled", function() {
            source.disable();
            dragBy(100, 100);
            runsExpectXY(5, 5);
        });
    });

    describe("isDragging", function() {
        beforeEach(function() {
            makeSource();
        });

        it("should be false by default", function() {
            expect(source.isDragging()).toBe(false);
        });

        it("should be set to true when dragging starts", function() {
            startDrag();
            moveBy(10, 10);
            runs(function() {
                expect(source.isDragging()).toBe(true);
            });
            moveBy(100, 100);
            runs(function() {
                expect(source.isDragging()).toBe(true);
            });
            endDrag();
        });

        it("should be false when dragging completes", function() {
            startDrag();
            moveBy(10, 10);
            moveBy(100, 100);
            endDrag();
            runs(function() {
                expect(source.isDragging()).toBe(false);
            });
        });

        it("should be false if dragging is vetoed", function() {
            source.on('beforedragstart', function() {
                return false;
            });
            startDrag();
            runs(function() {
                expect(source.isDragging()).toBe(false);
            });
            moveBy(10, 10);
            runs(function() {
                expect(source.isDragging()).toBe(false);
            });
            endDrag();
            runs(function() {
                expect(source.isDragging()).toBe(false);
            });
        });
    });

    describe("data methods", function() {
        describe("describe", function() {
            describe("as a config", function() {
                it("should be able to be passed as a config", function() {
                    var dragSpy = jasmine.createSpy(),
                        promiseSpy = jasmine.createSpy(),
                        spy = jasmine.createSpy().andCallFake(function(info) {
                            info.setData('foo', 100);
                        });

                    makeSource({
                        describe: spy
                    });

                    source.on('dragmove', dragSpy);

                    startDrag();
                    moveBy(10, 10);
                    endDrag();
                    runs(function() {
                        var info = dragSpy.mostRecentCall.args[1];

                        info.getData('foo').then(promiseSpy);
                        expect(spy.callCount).toBe(1);
                    });
                    waitsFor(function() {
                        return promiseSpy.callCount > 0;
                    });
                    runs(function() {
                        expect(promiseSpy.mostRecentCall.args[0]).toBe(100);
                    });
                });

                it("should pass the info object", function() {
                    var spy = jasmine.createSpy();

                    makeSource({
                        describe: spy
                    });

                    startDrag();
                    moveBy(10, 10);
                    runs(function() {
                        expect(spy.mostRecentCall.args[0] instanceof Ext.drag.Info).toBe(true);
                    });
                    endDrag();
                });
            });

            describe("as a subclassed method", function() {
                it("should be able to be passed as a config", function() {
                    var dragSpy = jasmine.createSpy(),
                        promiseSpy = jasmine.createSpy(),
                        spy = jasmine.createSpy().andCallFake(function(info) {
                            info.setData('foo', 101);
                        });

                    var T = Ext.define(null, {
                        extend: 'Ext.drag.Source',
                        describe: spy
                    });

                    makeSource({}, T);

                    source.on('dragmove', dragSpy);

                    startDrag();
                    moveBy(10, 10);
                    endDrag();
                    runs(function() {
                        var info = dragSpy.mostRecentCall.args[1];

                        info.getData('foo').then(promiseSpy);
                        expect(spy.callCount).toBe(1);
                    });
                    waitsFor(function() {
                        return promiseSpy.callCount > 0;
                    });
                    runs(function() {
                        expect(promiseSpy.mostRecentCall.args[0]).toBe(101);
                    });
                });

                it("should pass the info object", function() {
                    var spy = jasmine.createSpy();

                    var T = Ext.define(null, {
                        extend: 'Ext.drag.Source',
                        describe: spy
                    });

                    makeSource({}, T);

                    startDrag();
                    moveBy(10, 10);
                    runs(function() {
                        expect(spy.mostRecentCall.args[0] instanceof Ext.drag.Info).toBe(true);
                    });
                    endDrag();
                });
            });
        });
    });

    describe("template methods", function() {
        var spy;

        beforeEach(function() {
            makeSource();
        });

        afterEach(function() {
            spy = null;
        });

        describe("beforeDragStart", function() {
            it("should fired before a drag is initiated", function() {
                spy = spyOn(source, 'beforeDragStart');
                startDrag();
                // Not moved past threshhold yet
                runsExpectCallCount(spy, 0);
                moveBy(10, 0);
                runsExpectCallCount(spy, 1);
                moveBy(100, 0);
                runsExpectCallCount(spy, 1);
                moveBy(100, 0);
                runsExpectCallCount(spy, 1);
                endDrag();
                runsExpectCallCount(spy, 1);
            });

            it("should pass the info object", function() {
                spy = spyOn(source, 'beforeDragStart');
                startDrag();
                moveBy(10, 0);
                runs(function() {
                    var args = spy.mostRecentCall.args;

                    expect(args[0] instanceof Ext.drag.Info).toBe(true);
                });
                endDrag();
            });

            it("should not drag if the handler returns false", function() {
                source.beforeDragStart = function() {
                    return false;
                };
                var spy = jasmine.createSpy();

                source.on('dragstart', spy);
                source.on('dragmove', spy);
                source.on('dragend', spy);
                startDrag();
                moveBy(10, 0);
                runsExpectXY(5, 5);
                moveBy(100, 100);
                runsExpectXY(5, 5);
                endDrag();
                runsExpectCallCount(spy, 0);
            });
        });

        describe("onDragStart", function() {
            beforeEach(function() {
                spy = spyOn(source, 'beforeDragStart');
            });

            it("should fire once a drag is initiated", function() {
                startDrag();
                // Not moved past threshhold yet
                runsExpectCallCount(spy, 0);
                moveBy(10, 0);
                runsExpectCallCount(spy, 1);
                moveBy(100, 0);
                runsExpectCallCount(spy, 1);
                moveBy(100, 0);
                runsExpectCallCount(spy, 1);
                endDrag();
                runsExpectCallCount(spy, 1);
            });

            it("should pass the source, info and the event object", function() {
                startDrag();
                moveBy(10, 0);
                runs(function() {
                    var args = spy.mostRecentCall.args;

                    expect(args[0] instanceof Ext.drag.Info).toBe(true);
                });
                endDrag();
            });
        });

        describe("onDragMove", function() {
            beforeEach(function() {
                spy = spyOn(source, 'onDragMove');
            });

            it("should fire for each drag movement after initialization", function() {
                startDrag();
                runsExpectCallCount(spy, 0);
                moveBy(10, 0);
                runsExpectCallCount(spy, 1);
                moveBy(0, 10);
                runsExpectCallCount(spy, 2);
                moveBy(10, 10);
                runsExpectCallCount(spy, 3);
                endDrag();
                runsExpectCallCount(spy, 3);
            });

            it("should pass the source, info and the event object", function() {
                startDrag();
                moveBy(10, 0);
                runs(function() {
                    var args = spy.mostRecentCall.args;

                    expect(args[0] instanceof Ext.drag.Info).toBe(true);
                });
                endDrag();
            });
        });

        describe("onDragEnd", function() {
            beforeEach(function() {
                spy = spyOn(source, 'onDragEnd');
            });

            it("should fire once a drag is completed", function() {
                startDrag();
                // Not moved past threshhold yet
                runsExpectCallCount(spy, 0);
                moveBy(10, 0);
                runsExpectCallCount(spy, 0);
                moveBy(100, 0);
                runsExpectCallCount(spy, 0);
                moveBy(100, 0);
                runsExpectCallCount(spy, 0);
                endDrag();
                runsExpectCallCount(spy, 1);
            });

            it("should pass the source, info and the event object", function() {
                startDrag();
                moveBy(10, 0);
                endDrag();
                runs(function() {
                    var args = spy.mostRecentCall.args;

                    expect(args[0] instanceof Ext.drag.Info).toBe(true);
                });
            });

            it("should not call onDragCancel", function() {
                var otherSpy = spyOn(source, 'onDragCancel');

                startDrag();
                moveBy(10, 0);
                endDrag();
                runsExpectCallCount(otherSpy, 0);
            });
        });

        if (Ext.supports.Touch) {
            describe("onDragCancel", function() {
                beforeEach(function() {
                    spy = spyOn(source, 'onDragCancel');
                });

                it("should fire once a drag is cancelled", function() {
                    startDrag();
                    // Not moved past threshhold yet
                    runsExpectCallCount(spy, 0);
                    moveBy(10, 0);
                    runsExpectCallCount(spy, 0);
                    moveBy(100, 0);
                    runsExpectCallCount(spy, 0);
                    moveBy(100, 0);
                    runsExpectCallCount(spy, 0);
                    doCancel(0, 0);
                    runsExpectCallCount(spy, 1);
                });

                it("should pass the source, info and the event object", function() {
                    startDrag();
                    moveBy(10, 0);
                    doCancel(0, 0);
                    runs(function() {
                        var args = spy.mostRecentCall.args;

                        expect(args[0] instanceof Ext.drag.Info).toBe(true);
                    });
                });

                it("should not call onDragEnd", function() {
                    var otherSpy = spyOn(source, 'onDragEnd');

                    startDrag();
                    moveBy(10, 0);
                    doCancel(0, 0);
                    runsExpectCallCount(otherSpy, 0);
                });
            });
        }
    });

    describe("events", function() {
        var spy;

        beforeEach(function() {
            spy = jasmine.createSpy();
            makeSource();
        });

        afterEach(function() {
            spy = null;
        });

        describe("beforedragstart", function() {
            it("should fired before a drag is initiated", function() {
                source.on('beforedragstart', spy);
                startDrag();
                // Not moved past threshhold yet
                runsExpectCallCount(spy, 0);
                moveBy(10, 0);
                runsExpectCallCount(spy, 1);
                moveBy(100, 0);
                runsExpectCallCount(spy, 1);
                moveBy(100, 0);
                runsExpectCallCount(spy, 1);
                endDrag();
                runsExpectCallCount(spy, 1);
            });

            it("should pass the source, info and the event object", function() {
                source.on('beforedragstart', spy);
                startDrag();
                moveBy(10, 0);
                runs(function() {
                    var args = spy.mostRecentCall.args;

                    expect(args[0]).toBe(source);
                    expect(args[1] instanceof Ext.drag.Info).toBe(true);
                    expect(args[2] instanceof Ext.event.Event).toBe(true);
                });
                endDrag();
            });

            it("should not drag if the handler returns false", function() {
                source.on('beforedragstart', function() {
                    return false;
                });
                source.on('dragstart', spy);
                source.on('dragmove', spy);
                source.on('dragend', spy);
                startDrag();
                moveBy(10, 0);
                runsExpectXY(5, 5);
                moveBy(100, 100);
                runsExpectXY(5, 5);
                endDrag();
                runsExpectCallCount(spy, 0);
            });
        });

        describe("dragstart", function() {
            it("should fire once a drag is initiated", function() {
                source.on('dragstart', spy);
                startDrag();
                // Not moved past threshhold yet
                runsExpectCallCount(spy, 0);
                moveBy(10, 0);
                runsExpectCallCount(spy, 1);
                moveBy(100, 0);
                runsExpectCallCount(spy, 1);
                moveBy(100, 0);
                runsExpectCallCount(spy, 1);
                endDrag();
                runsExpectCallCount(spy, 1);
            });

            it("should pass the source, info and the event object", function() {
                source.on('dragstart', spy);
                startDrag();
                moveBy(10, 0);
                runs(function() {
                    var args = spy.mostRecentCall.args;

                    expect(args[0]).toBe(source);
                    expect(args[1] instanceof Ext.drag.Info).toBe(true);
                    expect(args[2] instanceof Ext.event.Event).toBe(true);
                });
                endDrag();
            });
        });

        describe("dragmove", function() {
            it("should fire for each drag movement after initialization", function() {
                source.on('dragmove', spy);
                startDrag();
                runsExpectCallCount(spy, 0);
                moveBy(10, 0);
                runsExpectCallCount(spy, 1);
                moveBy(0, 10);
                runsExpectCallCount(spy, 2);
                moveBy(10, 10);
                runsExpectCallCount(spy, 3);
                endDrag();
                runsExpectCallCount(spy, 3);
            });

            it("should pass the source, info and the event object", function() {
                source.on('dragmove', spy);
                startDrag();
                moveBy(10, 0);
                runs(function() {
                    var args = spy.mostRecentCall.args;

                    expect(args[0]).toBe(source);
                    expect(args[1] instanceof Ext.drag.Info).toBe(true);
                    expect(args[2] instanceof Ext.event.Event).toBe(true);
                });
                endDrag();
            });
        });

        describe("dragend", function() {
            it("should fire once a drag is completed", function() {
                source.on('dragend', spy);
                startDrag();
                // Not moved past threshhold yet
                runsExpectCallCount(spy, 0);
                moveBy(10, 0);
                runsExpectCallCount(spy, 0);
                moveBy(100, 0);
                runsExpectCallCount(spy, 0);
                moveBy(100, 0);
                runsExpectCallCount(spy, 0);
                endDrag();
                runsExpectCallCount(spy, 1);
            });

            it("should pass the source, info and the event object", function() {
                source.on('dragend', spy);
                startDrag();
                moveBy(10, 0);
                endDrag();
                runs(function() {
                    var args = spy.mostRecentCall.args;

                    expect(args[0]).toBe(source);
                    expect(args[1] instanceof Ext.drag.Info).toBe(true);
                    expect(args[2] instanceof Ext.event.Event).toBe(true);
                });
            });

            it("should not fire dragcancel", function() {
                source.on('dragcancel', spy);
                startDrag();
                // Not moved past threshhold yet
                runsExpectCallCount(spy, 0);
                moveBy(10, 0);
                runsExpectCallCount(spy, 0);
                moveBy(100, 0);
                runsExpectCallCount(spy, 0);
                moveBy(100, 0);
                runsExpectCallCount(spy, 0);
                endDrag();
                runsExpectCallCount(spy, 0);
            });
        });

        if (Ext.supports.Touch) {
            describe("dragcancel", function() {
                it("should fire once a drag is cancelled", function() {
                    source.on('dragcancel', spy);
                    startDrag();
                    // Not moved past threshhold yet
                    runsExpectCallCount(spy, 0);
                    moveBy(10, 0);
                    runsExpectCallCount(spy, 0);
                    moveBy(100, 0);
                    runsExpectCallCount(spy, 0);
                    moveBy(100, 0);
                    runsExpectCallCount(spy, 0);
                    doCancel(0, 0);
                    runsExpectCallCount(spy, 1);
                });

                it("should pass the source, info and the event object", function() {
                    source.on('dragcancel', spy);
                    startDrag();
                    moveBy(10, 0);
                    doCancel(0, 0);
                    runs(function() {
                        var args = spy.mostRecentCall.args;

                        expect(args[0]).toBe(source);
                        expect(args[1] instanceof Ext.drag.Info).toBe(true);
                        expect(args[2] instanceof Ext.event.Event).toBe(true);
                    });
                });

                it("should not fire dragend", function() {
                    source.on('dragend', spy);
                    startDrag();
                    moveBy(10, 0);
                    doCancel(0, 0);
                    runsExpectCallCount(spy, 0);
                });
            });
        }
    });

    describe("proxy", function() {
        it("should default to using proxy.Original", function() {
            var spy = jasmine.createSpy();

            makeSource();
            source.on('dragmove', spy.andCallFake(function(source, info) {
                spy.mostRecentCall.dragInfo = info.clone();
            }));
            expect(source.getProxy().$className).toBe('Ext.drag.proxy.Original');
            startDrag();
            moveBy(10, 10);
            runs(function() {
                expect(spy.mostRecentCall.dragInfo.proxy.element).toBe(source.getElement());
            });
            endDrag();
        });

        it("should call getElement once at the start of the drag", function() {
            makeSource();
            var spy = spyOn(source.getProxy(), 'getElement').andCallThrough();

            startDrag();
            // Need to move over threshhold to start the drag
            moveBy(10, 10);
            runsExpectCallCount(spy, 1);
            moveBy(50, 50);
            moveBy(30, 30);
            moveBy(-60, -60);
            endDrag();
            runsExpectCallCount(spy, 1);

            startDrag();
            // Need to move over threshhold to start the drag
            moveBy(10, 10);
            runsExpectCallCount(spy, 2);
            moveBy(50, 50);
            moveBy(30, 30);
            moveBy(-60, -60);
            endDrag();
            runsExpectCallCount(spy, 2);
        });

        it("should call cleanup when the drag completes", function() {
            makeSource();
            var spy = spyOn(source.getProxy(), 'cleanup').andCallThrough();

            startDrag();
            // Need to move over threshhold to start the drag
            moveBy(10, 10);
            runs(function() {
                expect(spy).not.toHaveBeenCalled();
            });
            moveBy(50, 50);
            moveBy(30, 30);
            moveBy(-60, -60);
            runsExpectCallCount(spy, 0);
            endDrag();
            runsExpectCallCount(spy, 1);

            startDrag();
            // Need to move over threshhold to start the drag
            moveBy(10, 10);
            runsExpectCallCount(spy, 1);
            moveBy(50, 50);
            moveBy(30, 30);
            moveBy(-60, -60);
            runsExpectCallCount(spy, 1);
            endDrag();
            runsExpectCallCount(spy, 2);
        });

        describe("proxy.None", function() {
            it("should not create an element", function() {
                var spy = jasmine.createSpy();

                makeSource({
                    proxy: {
                        type: 'none'
                    }
                });

                source.on('dragmove', spy.andCallFake(function(source, info) {
                    spy.mostRecentCall.dragInfo = info.clone();
                }));

                startDrag();
                moveBy(100, 100);
                runs(function() {
                    expect(spy.mostRecentCall.dragInfo.proxy.element).toBeNull();
                });
                endDrag();
            });
        });

        describe("proxy.Original", function() {
            beforeEach(function() {
                makeSource();
            });

            it("should use the drag element", function() {
                var spy = jasmine.createSpy();

                source.on('dragmove', spy.andCallFake(function(source, info) {
                    spy.mostRecentCall.dragInfo = info.clone();
                }));
                startDrag();
                moveBy(10, 10);
                runs(function() {
                    expect(spy.mostRecentCall.dragInfo.proxy.element).toBe(source.getElement());
                });
                endDrag();
            });

            it("should leave the element in place after drag", function() {
                startDrag();
                moveBy(100, 100);
                endDrag();
                runsExpectElXY(105, 105);
                runs(function() {
                    expect(source.getElement().isVisible()).toBe(true);
                });
            });
        });

        describe("proxy.Placeholder", function() {
            beforeEach(function() {
                makeSource({
                    proxy: {
                        type: 'placeholder',
                        html: 'TheText',
                        cls: 'foo'
                    }
                });
            });

            it("should create a new element with the configured options", function() {
                var spy = jasmine.createSpy();

                source.on('dragmove', spy.andCallFake(function(source, info) {
                    spy.mostRecentCall.dragInfo = info.clone();
                }));

                startDrag();
                // Go past threshhold
                moveBy(10, 10);
                runs(function() {
                    var el = spy.mostRecentCall.dragInfo.proxy.element;

                    expect(el).toHaveCls('foo');
                    expect(el.dom).hasHTML('TheText');
                });
                endDrag();
            });

            it("should destroy the proxy after drag", function() {
                var spy = jasmine.createSpy(),
                    el;

                source.on('dragmove', spy.andCallFake(function(source, info) {
                    spy.mostRecentCall.dragInfo = info.clone();
                }));
                startDrag();
                // Go past threshhold
                moveBy(10, 10);
                endDrag();
                runs(function() {
                    var el = spy.mostRecentCall.dragInfo.proxy.element;

                    expect(el.destroyed).toBe(true);
                });
            });

            it("should destroy the proxy element when the source is destroyed", function() {
                var spy = jasmine.createSpy(),
                    id;

                source.on('dragmove', spy.andCallFake(function(source, info) {
                    spy.mostRecentCall.dragInfo = info.clone();
                }));
                startDrag();
                // Go past threshhold
                moveBy(10, 10);
                endDrag();
                runs(function() {
                    var id = spy.mostRecentCall.dragInfo.proxy.element.id;

                    source.destroy();
                    expect(Ext.get(id)).toBeNull();
                });
            });
        });
    });

    describe("activeCls", function() {
        function runsExpectCls(cls) {
            runs(function() {
                expect(dragEl).toHaveCls(cls);
            });
        }

        function runsExpectNotCls(cls) {
            runs(function() {
                expect(dragEl).not.toHaveCls(cls);
            });
        }

        it("should add the cls when dragging starts and remove when it ends", function() {
            makeSource({
                activeCls: 'foo'
            });
            startDrag();
            moveBy(10, 10);
            runsExpectCls('foo');
            moveBy(100, 100);
            runsExpectCls('foo');
            endDrag();
            runsExpectNotCls('foo');
        });

        it("should not modify other classes", function() {
            makeDragEl();
            dragEl.dom.className = 'bar';
            makeSource({
                activeCls: 'foo'
            });

            startDrag();
            moveBy(100, 100);
            runsExpectCls('foo');
            runsExpectCls('bar');
            endDrag();
            runsExpectCls('bar');
            runsExpectNotCls('foo');
        });

        describe("dynamically setting", function() {
            describe("not during drag", function() {
                describe("before first drag", function() {
                    it("should be able to set a cls", function() {
                        makeSource();
                        source.setActiveCls('foo');
                        startDrag();
                        moveBy(100, 100);
                        runsExpectCls('foo');
                        endDrag();
                        runsExpectNotCls('foo');
                    });

                    it("should be able to clear a cls", function() {
                        makeSource({
                            activeCls: 'foo'
                        });
                        source.setActiveCls('');
                        startDrag();
                        moveBy(100, 100);
                        runsExpectNotCls('foo');
                        endDrag();
                        runsExpectNotCls('foo');
                    });

                    it("should be able to change a cls", function() {
                        makeSource({
                            activeCls: 'foo'
                        });
                        source.setActiveCls('bar');
                        startDrag();
                        moveBy(100, 100);
                        runsExpectNotCls('foo');
                        runsExpectCls('bar');
                        endDrag();
                        runsExpectNotCls('foo');
                        runsExpectNotCls('bar');
                    });
                });

                describe("after first drag", function() {
                    it("should be able to set a cls", function() {
                        makeSource();
                        dragBy(100, 100);
                        runs(function() {
                            source.setActiveCls('foo');
                        });
                        startDrag();
                        moveBy(100, 100);
                        runsExpectCls('foo');
                        endDrag();
                        runsExpectNotCls('foo');
                    });

                    it("should be able to clear a cls", function() {
                        makeSource({
                            activeCls: 'foo'
                        });
                        dragBy(100, 100);
                        runs(function() {
                            source.setActiveCls('');
                        });
                        startDrag();
                        moveBy(100, 100);
                        runsExpectNotCls('foo');
                        endDrag();
                        runsExpectNotCls('foo');
                    });

                    it("should be able to change a cls", function() {
                        makeSource({
                            activeCls: 'foo'
                        });
                        dragBy(100, 100);
                        runs(function() {
                            source.setActiveCls('bar');
                        });
                        startDrag();
                        moveBy(100, 100);
                        runsExpectNotCls('foo');
                        runsExpectCls('bar');
                        endDrag();
                        runsExpectNotCls('foo');
                        runsExpectNotCls('bar');
                    });
                });
            });

            describe("during drag", function() {
                it("should be able to set a cls", function() {
                    makeSource();
                    startDrag();
                    moveBy(100, 100);
                    runsExpectNotCls('foo');
                    runs(function() {
                        source.setActiveCls('foo');
                    });
                    runsExpectCls('foo');
                    endDrag();
                    runsExpectNotCls('foo');
                });

                it("should be able to clear a cls", function() {
                    makeSource({
                        activeCls: 'foo'
                    });
                    startDrag();
                    moveBy(100, 100);
                    runsExpectCls('foo');
                    runs(function() {
                        source.setActiveCls('');
                    });
                    runsExpectNotCls('foo');
                    endDrag();
                    runsExpectNotCls('foo');
                });

                it("should be able to change a cls", function() {
                    makeSource({
                        activeCls: 'foo'
                    });
                    startDrag();
                    moveBy(100, 100);
                    runsExpectCls('foo');
                    runs(function() {
                        source.setActiveCls('bar');
                    });
                    runsExpectNotCls('foo');
                    runsExpectCls('bar');
                    endDrag();
                    runsExpectNotCls('foo');
                    runsExpectNotCls('bar');
                });
            });
        });
    });

    describe("moving", function() {
        describe("cursor offset", function() {
            beforeEach(function() {
                makeDragEl(50, 50);
            });

            // No matter where we start the drag from in the in the element, 
            // moving the cursor by [x, y] should move the element the same amount
            describe("with a proxy that is the element", function() {
                beforeEach(function() {
                    makeSource();
                });

                it("should not offset the drag when the cursor is at the top left", function() {
                    startOffsetDrag(0, 0);
                    moveBy(100, 100);
                    runsExpectXY(150, 150);
                    endDrag();
                });

                it("should offset the drag when the cursor is at the top right", function() {
                    startOffsetDrag(50, 0);
                    moveBy(100, 100);
                    runsExpectXY(150, 150);
                    endDrag();
                });

                it("should offset the drag when the cursor is at the bottom left", function() {
                    startOffsetDrag(0, 50);
                    moveBy(100, 100);
                    runsExpectXY(150, 150);
                    endDrag();
                });

                it("should offset the drag when the cursor is at the bottom right", function() {
                    startOffsetDrag(50, 50);
                    moveBy(100, 100);
                    runsExpectXY(150, 150);
                    endDrag();
                });

                it("should offset the drag when the cursor is in the middle", function() {
                    startOffsetDrag(25, 25);
                    moveBy(100, 100);
                    runsExpectXY(150, 150);
                    endDrag();
                });
            });

            // The element is not moving here, so the proxy should follow the mouse.
            describe("with a proxy that is not the element", function() {
                beforeEach(function() {
                    makeSource({
                        proxy: {
                            type: 'placeholder',
                            html: 'Foo',
                            cursorOffset: [0, 0]
                        }
                    });
                });

                it("should not offset the drag when the cursor is at the top left", function() {
                    startOffsetDrag(0, 0);
                    moveBy(300, 300);
                    runsExpectXY(350, 350);
                    endDrag();
                });

                it("should not offset the drag when the cursor is at the top right", function() {
                    startOffsetDrag(50, 0);
                    moveBy(300, 300);
                    runsExpectXY(400, 350);
                    endDrag();
                });

                it("should not offset the drag when the cursor is at the bottom left", function() {
                    startOffsetDrag(0, 50);
                    moveBy(300, 300);
                    runsExpectXY(350, 400);
                    endDrag();
                });

                it("should not offset the drag when the cursor is at the bottom right", function() {
                    startOffsetDrag(50, 50);
                    moveBy(300, 300);
                    runsExpectXY(400, 400);
                    endDrag();
                });

                it("should not offset the drag when the cursor is in the middle", function() {
                    startOffsetDrag(25, 25);
                    moveBy(300, 300);
                    runsExpectXY(375, 375);
                    endDrag();
                });
            });
        });

        describe("handle", function() {
            function makeDragElWithHandles(preventChildren) {
                function getChild(cls, left, top) {
                    return {
                        cls: cls,
                        style: {
                            left: left + 'px',
                            top: top + 'px',
                            width: '20px',
                            height: '20px',
                            position: 'absolute'
                        }
                    };
                }

                dragEl = new Ext.getBody().createChild({
                    style: {
                        width: '200px',
                        height: '200px',
                        position: 'absolute',
                        left: '50px',
                        top: '50px'
                    },
                    children: preventChildren
                        ? []
                        : [
                            getChild('foo', 10, 10),
                            getChild('bar', 100, 100),
                            getChild('foo', 150, 150)
                        ]
                });
            }

            function startHandleDrag(handle) {
                runs(function() {
                    // Start a drag in the middle of a handle
                    var center = getCenter(handle);

                    start({
                        id: touchId,
                        x: center[0],
                        y: center[1]
                    }, handle);
                });
                waitsForAnimation();

            }

            function endHandleDrag(handle) {
                endDrag(null, null, handle);
            }

            it("should drag when the mouse is over a child element and there is no handle configured", function() {
                makeDragElWithHandles();
                makeSource();
                var handle = Ext.fly(dragEl.child('.foo', true));

                startHandleDrag(handle);
                moveBy(100, 100, handle);
                runsExpectXY(150, 150);
                endHandleDrag(handle);
            });

            it("should not drag when the drag is on the element (non handle) with a handle", function() {
                makeDragElWithHandles();
                makeSource({
                    handle: '.foo'
                });
                dragBy(100, 100);
                runsExpectXY(50, 50);
            });

            it("should not drag when the drag is on a child element that does not match the handle", function() {
                makeDragElWithHandles();
                makeSource({
                    handle: '.foo'
                });
                var handle = Ext.fly(dragEl.child('.bar', true));

                startHandleDrag(handle);
                moveBy(200, 200, handle);
                runsExpectXY(50, 50);
                endHandleDrag(handle);
            });

            it("should drag when the drag is on a matching handle", function() {
                makeDragElWithHandles();
                makeSource({
                    handle: '.foo'
                });
                var handle = Ext.fly(dragEl.child('.foo', true));

                startHandleDrag(handle);
                moveBy(100, 100, handle);
                runsExpectXY(150, 150);
                endHandleDrag(handle);
            });

            it("should be able to use multiple handles", function() {
                makeDragElWithHandles();
                makeSource({
                    handle: '.foo'
                });
                var handle = Ext.fly(dragEl.child('.foo', true));

                startHandleDrag(handle);
                moveBy(100, 100, handle);
                runsExpectXY(150, 150);
                endHandleDrag(handle);
                handle = dragEl.select('.foo').item(1);
                startHandleDrag(handle);
                moveBy(100, 100, handle);
                runsExpectXY(250, 250);
                endHandleDrag(handle);
            });

            it("should allow a deep child to be a handle", function() {
                makeDragElWithHandles(true);
                dragEl.createChild({
                    style: {
                        left: '10px',
                        top: '10px',
                        width: '40px',
                        height: '40px',
                        position: 'absolute'
                    },
                    children: [{
                        children: [{
                            cls: 'foo'
                        }]
                    }]
                }, null, true);
                makeSource({
                    handle: '.foo'
                });
                var handle = Ext.fly(dragEl.down('.foo', true));

                startHandleDrag(handle);
                moveBy(100, 100, handle);
                runsExpectXY(150, 150);
                endHandleDrag(handle);
            });

            describe("dynamic", function() {
                beforeEach(function() {
                    makeDragElWithHandles();
                });

                describe("before first drag", function() {
                    it("should be able to set a handle", function() {
                        makeSource();
                        source.setHandle('.foo');
                        dragBy(100, 100);
                        // Shouldn't move
                        runsExpectXY(50, 50);
                        var handle = Ext.fly(dragEl.child('.foo', true));

                        startHandleDrag(handle);
                        moveBy(100, 100, handle);
                        runsExpectXY(150, 150);
                        endHandleDrag(handle);
                    });

                    it("should be able to clear a handle", function() {
                        makeSource({
                            handle: '.foo'
                        });
                        source.setHandle(null);
                        dragBy(100, 100);
                        runsExpectXY(150, 150);
                    });

                    it("should be able to change a handle", function() {
                        makeSource({
                            handle: '.foo'
                        });
                        source.setHandle('.bar');
                        var handle = dragEl.child('.foo');

                        startHandleDrag(handle);
                        moveBy(100, 100, handle);
                        endHandleDrag(handle);
                        // Shouldn't move
                        runsExpectXY(50, 50);
                        var handle2 = dragEl.child('.bar');

                        startHandleDrag(handle2);
                        moveBy(100, 100, handle2);
                        endHandleDrag(handle2);
                        runsExpectXY(150, 150);

                        runs(function() {
                            handle.destroy();
                            handle2.destroy();
                        });
                    });
                });

                describe("after first drag", function() {
                    it("should be able to set a handle", function() {
                        makeSource();
                        dragBy(100, 100);
                        runsExpectXY(150, 150);
                        runs(function() {
                            source.setHandle('.foo');
                        });
                        dragBy(100, 100);
                        // Shouldn't move
                        runsExpectXY(150, 150);
                        var handle = Ext.fly(dragEl.child('.foo', true));

                        startHandleDrag(handle);
                        moveBy(100, 100, handle);
                        runsExpectXY(250, 250);
                        endHandleDrag(handle);
                    });

                    it("should be able to clear a handle", function() {
                        makeSource({
                            handle: '.foo'
                        });
                        var handle = Ext.fly(dragEl.child('.foo', true));

                        startHandleDrag(handle);
                        moveBy(100, 100, handle);
                        runsExpectXY(150, 150);
                        endHandleDrag(handle);
                        runs(function() {
                            source.setHandle(null);
                        });
                        dragBy(100, 100);
                        runsExpectXY(250, 250);
                    });

                    it("should be able to change a handle", function() {
                        makeSource({
                            handle: '.foo'
                        });
                        var handle = dragEl.child('.foo');

                        startHandleDrag(handle);
                        moveBy(100, 100, handle);
                        endHandleDrag(handle);
                        runsExpectXY(150, 150);
                        runs(function() {
                            source.setHandle('.bar');
                        });
                        startHandleDrag(handle);
                        moveBy(100, 100, handle);
                        // Shouldn't move
                        runsExpectXY(150, 150);
                        endHandleDrag(handle);
                        var handle2 = dragEl.child('.bar');

                        startHandleDrag(handle2);
                        moveBy(100, 100, handle2);
                        endHandleDrag(handle2);
                        runsExpectXY(250, 250);

                        runs(function() {
                            handle.destroy();
                            handle2.destroy();
                        });
                    });
                });
            });
        });

        describe("constraints", function() {
            describe("shortcuts", function() {
                var parent, child;

                function setupElements() {
                    parent = Ext.getBody().createChild({
                        children: [{}],
                        style: {
                            position: 'absolute',
                            width: '600px',
                            height: '600px',
                            top: '50px',
                            left: '50px'
                        }
                    });
                    child = parent.first();
                    dragEl = child.createChild(defaultElCfg);
                }

                afterEach(function() {
                    child = parent = Ext.destroy(child, parent);
                });

                it("should accept an id", function() {
                    setupElements();
                    makeSource({
                        constrain: parent.id
                    });
                    dragBy(-400, -400);
                    runsExpectXY(50, 50);
                });

                it("should accept a DOM element", function() {
                    setupElements();
                    makeSource({
                        constrain: parent.dom
                    });
                    dragBy(-400, -400);
                    runsExpectXY(50, 50);
                });

                it("should accept an element reference", function() {
                    setupElements();
                    makeSource({
                        constrain: parent
                    });
                    dragBy(-400, -400);
                    runsExpectXY(50, 50);
                });

                it("should use a specified region", function() {
                    makeSource({
                        constrain: new Ext.util.Region(30, 200, 200, 30)
                    });
                    dragBy(-400, -400);
                    runsExpectXY(30, 30);
                });
            });

            describe("horizontal", function() {
                beforeEach(function() {
                    makeDragEl(50, 50);
                });

                it("should not move the element on the vertical axis", function() {
                    makeSource({
                        constrain: {
                            horizontal: true
                        }
                    });
                    startDrag();
                    moveBy(100, 100);
                    runsExpectXY(150, 50);
                    moveBy(100, -200);
                    runs(function() {
                        expectXY(250, 50);
                    });
                    endDrag();
                });

                it("should be able to set constraint after initial drag", function() {
                    makeSource();
                    dragBy(100, 100);
                    runsExpectXY(150, 150);
                    runs(function() {
                        source.setConstrain({
                            horizontal: true
                        });
                    });
                    dragBy(100, 100);
                    runsExpectXY(250, 150);
                });

                it("should be able to clear constraint after initial drag", function() {
                    makeSource({
                        constrain: {
                            horizontal: true
                        }
                    });
                    dragBy(100, 100);
                    runsExpectXY(150, 50);
                    runs(function() {
                        source.setConstrain(null);
                    });
                    dragBy(100, 100);
                    runsExpectXY(250, 150);
                });
            });

            describe("vertical", function() {
                beforeEach(function() {
                    makeDragEl(50, 50);
                });

                it("should not move the element on the horizontal axis", function() {
                    makeSource({
                        constrain: {
                            vertical: true
                        }
                    });
                    startDrag();
                    moveBy(100, 100);
                    runsExpectXY(50, 150);
                    moveBy(-200, 100);
                    runsExpectXY(50, 250);
                    endDrag();
                });

                it("should be able to set constraint after initial drag", function() {
                    makeSource();
                    dragBy(100, 100);
                    runsExpectXY(150, 150);
                    runs(function() {
                        source.setConstrain({
                            vertical: true
                        });
                    });
                    dragBy(100, 100);
                    runsExpectXY(150, 250);
                });

                it("should be able to clear constraint after initial drag", function() {
                    makeSource({
                        constrain: {
                            vertical: true
                        }
                    });
                    dragBy(100, 100);
                    runsExpectXY(50, 150);
                    runs(function() {
                        source.setConstrain(null);
                    });
                    dragBy(100, 100);
                    runsExpectXY(150, 250);
                });
            });

            describe("x", function() {
                beforeEach(function() {
                    makeDragEl(50, 50);
                });

                describe("min only", function() {
                    it("should constrain to the min", function() {
                        makeSource({
                            constrain: {
                                x: [20, null]
                            }
                        });
                        dragBy(-50, 0);
                        runsExpectXY(20, 50);
                    });

                    it("should be able to set constraint after initial drag", function() {
                        makeSource();
                        dragBy(50, 0);
                        runsExpectXY(100, 50);
                        runs(function() {
                            source.setConstrain({
                                x: [20, null]
                            });
                        });
                        dragBy(-100, 0);
                        runsExpectXY(20, 50);
                    });

                    it("should be able to clear constraint after initial drag", function() {
                        makeSource({
                            constrain: {
                                x: [20, null]
                            }
                        });
                        dragBy(-50, 0);
                        runsExpectXY(20, 50);
                        runs(function() {
                            source.setConstrain(null);
                        });
                        dragBy(-10, 0);
                        runsExpectXY(10, 50);
                    });
                });

                describe("max only", function() {
                    it("should constrain to the max", function() {
                        makeSource({
                            constrain: {
                                x: [null, 200]
                            }
                        });
                        dragBy(300, 0);
                        runsExpectXY(200, 50);
                    });

                    it("should be able to set constraint after initial drag", function() {
                        makeSource();
                        dragBy(50, 0);
                        runsExpectXY(100, 50);
                        runs(function() {
                            source.setConstrain({
                                x: [null, 200]
                            });
                        });
                        dragBy(200, 0);
                        runsExpectXY(200, 50);
                    });

                    it("should be able to clear constraint after initial drag", function() {
                        makeSource({
                            constrain: {
                                x: [null, 100]
                            }
                        });
                        dragBy(200, 0);
                        runsExpectXY(100, 50);
                        runs(function() {
                            source.setConstrain(null);
                        });
                        dragBy(100, 0);
                        runsExpectXY(200, 50);
                    });
                });

                describe("min & max", function() {
                    it("should constrain to the min/max", function() {
                        makeSource({
                            constrain: {
                                x: [30, 60]
                            }
                        });
                        startDrag();
                        moveBy(-40, 0);
                        runsExpectXY(30, 50);
                        moveBy(100, 0);
                        runsExpectXY(60, 50);
                        endDrag();
                    });

                    it("should be able to set the constraint after an initial drag", function() {
                        makeSource();
                        dragBy(50, 0);
                        runsExpectXY(100, 50);
                        runs(function() {
                            source.setConstrain({
                                x: [50, 150]
                            });
                        });
                        startDrag();
                        moveBy(-100, 0);
                        runsExpectXY(50, 50);
                        moveBy(200, 0);
                        runsExpectXY(150, 50);
                        endDrag();
                    });

                    it("should be able to clear the constraint after an initial drag", function() {
                        makeSource({
                            constrain: {
                                x: [30, 70]
                            }
                        });
                        dragBy(100, 0);
                        runsExpectXY(70, 50);
                        dragBy(-100, 0);
                        runsExpectXY(30, 50);
                        runs(function() {
                            source.setConstrain(null);
                        });
                        dragBy(200, 0);
                        runsExpectXY(230, 50);
                        dragBy(-220, 0);
                        runsExpectXY(10, 50);
                    });
                });

                describe("not limiting y", function() {
                    it("should not limit y axis", function() {
                        makeSource({
                            constrain: {
                                x: [20, 80]
                            }
                        });
                        startDrag();
                        moveBy(100, 100);
                        runsExpectXY(80, 150);
                        moveBy(100, 50);
                        runsExpectXY(80, 200);
                        moveBy(-230, -100);
                        runsExpectXY(20, 100);
                        endDrag();
                    });
                });
            });

            describe("y", function() {
                beforeEach(function() {
                    makeDragEl(50, 50);
                });

                describe("min only", function() {
                    it("should constrain to the min", function() {
                        makeSource({
                            constrain: {
                                y: [20, null]
                            }
                        });
                        dragBy(0, -50);
                        runsExpectXY(50, 20);
                    });

                    it("should be able to set constraint after initial drag", function() {
                        makeSource();
                        dragBy(0, 50);
                        runsExpectXY(50, 100);
                        runs(function() {
                            source.setConstrain({
                                y: [20, null]
                            });
                        });
                        dragBy(0, -100);
                        runsExpectXY(50, 20);
                    });

                    it("should be able to clear constraint after initial drag", function() {
                        makeSource({
                            constrain: {
                                y: [20, null]
                            }
                        });
                        dragBy(0, -50);
                        runsExpectXY(50, 20);
                        runs(function() {
                            source.setConstrain(null);
                        });
                        dragBy(0, -10);
                        runsExpectXY(50, 10);
                    });
                });

                describe("max only", function() {
                    it("should constrain to the max", function() {
                        makeSource({
                            constrain: {
                                y: [null, 200]
                            }
                        });
                        dragBy(0, 300);
                        runsExpectXY(50, 200);
                    });

                    it("should be able to set constraint after initial drag", function() {
                        makeSource();
                        dragBy(0, 50);
                        runsExpectXY(50, 100);
                        runs(function() {
                            source.setConstrain({
                                y: [null, 200]
                            });
                        });
                        dragBy(0, 200);
                        runsExpectXY(50, 200);
                    });

                    it("should be able to clear constraint after initial drag", function() {
                        makeSource({
                            constrain: {
                                y: [null, 100]
                            }
                        });
                        dragBy(0, 200);
                        runsExpectXY(50, 100);
                        runs(function() {
                            source.setConstrain(null);
                        });
                        dragBy(0, 100);
                        runsExpectXY(50, 200);
                    });
                });

                describe("min & max", function() {
                    it("should constrain to the min/max", function() {
                        makeSource({
                            constrain: {
                                y: [30, 60]
                            }
                        });
                        startDrag();
                        moveBy(0, -40);
                        runsExpectXY(50, 30);
                        moveBy(0, 100);
                        runsExpectXY(50, 60);
                        endDrag();
                    });

                    it("should be able to set the constraint after an initial drag", function() {
                        makeSource();
                        dragBy(0, 50);
                        runsExpectXY(50, 100);
                        runs(function() {
                            source.setConstrain({
                                y: [50, 150]
                            });
                        });
                        startDrag();
                        moveBy(0, -100);
                        runsExpectXY(50, 50);
                        moveBy(0, 200);
                        runsExpectXY(50, 150);
                        endDrag();
                    });

                    it("should be able to clear the constraint after an initial drag", function() {
                        makeSource({
                            constrain: {
                                y: [30, 70]
                            }
                        });
                        dragBy(0, 100);
                        runsExpectXY(50, 70);
                        dragBy(0, -100);
                        runsExpectXY(50, 30);
                        runs(function() {
                            source.setConstrain(null);
                        });
                        dragBy(0, 200);
                        runsExpectXY(50, 230);
                        dragBy(0, -220);
                        runsExpectXY(50, 10);
                    });
                });

                describe("not limiting x", function() {
                    it("should not limit x axis", function() {
                        makeSource({
                            constrain: {
                                y: [20, 80]
                            }
                        });
                        startDrag();
                        moveBy(100, 100);
                        runsExpectXY(150, 80);
                        moveBy(50, 100);
                        runsExpectXY(200, 80);
                        moveBy(-100, -230);
                        runsExpectXY(100, 20);
                        endDrag();
                    });
                });
            });

            describe("element", function() {
                describe("constrain with true", function() {
                    var parent;

                    beforeEach(function() {
                        parent = Ext.getBody().createChild({
                            style: {
                                position: 'absolute',
                                width: '400px',
                                height: '400px',
                                top: '50px',
                                left: '50px'
                            }
                        });
                        dragEl = parent.createChild(defaultElCfg);
                    });

                    afterEach(function() {
                        parent = Ext.destroy(parent);
                    });

                    it("should constrain to the parent element", function() {
                        makeSource({
                            constrain: {
                                element: true
                            }
                        });

                        startDrag();
                        // top left
                        moveBy(-100, -100);
                        runsExpectXY(50, 50);
                        // top right
                        moveBy(600, -100);
                        runsExpectXY(430, 50);
                        // bottom left
                        moveBy(-620, 600);
                        runsExpectXY(50, 430);
                        // bottom right
                        moveBy(500, 500);
                        runsExpectXY(430, 430);
                        endDrag();
                    });

                    it("should adjust if the element moves", function() {
                        makeSource({
                            constrain: {
                                element: true
                            }
                        });

                        dragBy(800, 800);
                        runsExpectXY(430, 430);
                        runs(function() {
                            parent.setSize(600, 600);
                        });
                        dragBy(800, 800);
                        runsExpectXY(630, 630);
                    });

                    it("should be able to set the constraint after an initial drag", function() {
                        makeSource();
                        dragBy(100, 100);
                        runsExpectXY(200, 200);
                        runs(function() {
                            source.setConstrain({
                                element: true
                            });
                        });
                        dragBy(-400, -400);
                        runsExpectXY(50, 50);
                    });

                    it("should be able to clear the constraint after an initial drag", function() {
                        makeSource({
                            constrain: {
                                element: true
                            }
                        });
                        dragBy(800, 800);
                        runsExpectXY(430, 430);
                        runs(function() {
                            source.setConstrain(null);
                        });
                        dragBy(400, 400);
                        runsExpectXY(830, 830);
                    });

                    it("should exclude borders", function() {
                        parent.setStyle('border', '20px solid yellow');
                        makeSource({
                            constrain: {
                                element: true
                            }
                        });

                        startDrag();
                        // top left
                        moveBy(-100, -100);
                        runsExpectXY(70, 70);
                        // top right
                        moveBy(600, -100);
                        runsExpectXY(410, 70);
                        // bottom left
                        moveBy(-620, 600);
                        runsExpectXY(70, 410);
                        // bottom right
                        moveBy(500, 500);
                        runsExpectXY(410, 410);
                        endDrag();
                    });
                });

                describe("constrain with element", function() {
                    var parent, child;

                    beforeEach(function() {
                        parent = Ext.getBody().createChild({
                            children: [{}],
                            style: {
                                position: 'absolute',
                                width: '600px',
                                height: '600px',
                                top: '50px',
                                left: '50px'
                            }
                        });
                        child = parent.first();
                        dragEl = child.createChild(defaultElCfg);
                    });

                    afterEach(function() {
                        child = parent = Ext.destroy(child, parent);
                    });

                    describe("arg types", function() {
                        it("should accept an id", function() {
                            makeSource({
                                constrain: {
                                    element: parent.id
                                }
                            });
                            dragBy(-400, -400);
                            runsExpectXY(50, 50);
                        });

                        it("should accept a DOM element", function() {
                            makeSource({
                                constrain: {
                                    element: parent.dom
                                }
                            });
                            dragBy(-400, -400);
                            runsExpectXY(50, 50);
                        });

                        it("should accept an element reference", function() {
                            makeSource({
                                constrain: {
                                    element: parent
                                }
                            });
                            dragBy(-400, -400);
                            runsExpectXY(50, 50);
                        });
                    });

                    it("should constrain to the parent element", function() {
                        makeSource({
                            constrain: {
                                element: parent
                            }
                        });

                        startDrag();
                        // top left
                        moveBy(-100, -100);
                        runsExpectXY(50, 50);
                        // top right
                        moveBy(600, 0);
                        runsExpectXY(600, 50);
                        // bottom left
                        moveBy(-700, 700);
                        runsExpectXY(50, 630);
                        // bottom right
                        moveBy(800, 800);
                        runsExpectXY(630, 630);
                        endDrag();
                    });

                    it("should adjust if the element moves", function() {
                        makeSource({
                            constrain: {
                                element: parent
                            }
                        });

                        dragBy(800, 800);
                        runsExpectXY(630, 630);
                        runs(function() {
                            parent.setSize(700, 700);
                        });
                        dragBy(800, 800);
                        runsExpectXY(730, 730);
                    });

                    it("should be able to set the constraint after an initial drag", function() {
                        makeSource();
                        dragBy(100, 100);
                        runsExpectXY(200, 200);
                        runs(function() {
                            source.setConstrain({
                                element: parent
                            });
                        });
                        dragBy(-400, -400);
                        runsExpectXY(50, 50);
                    });

                    it("should be able to clear the constraint after an initial drag", function() {
                        makeSource({
                            constrain: {
                                element: parent
                            }
                        });
                        dragBy(800, 800);
                        runsExpectXY(630, 630);
                        runs(function() {
                            source.setConstrain(null);
                        });
                        dragBy(300, 300);
                        runsExpectXY(930, 930);
                    });

                    it("should exclude borders", function() {
                        parent.setStyle('border', '20px solid yellow');
                        makeSource({
                            constrain: {
                                element: parent
                            }
                        });

                        startDrag();
                        // top left
                        moveBy(-100, -100);
                        runsExpectXY(70, 70);
                        // top right
                        moveBy(600, 0);
                        runsExpectXY(610, 70);
                        // bottom left
                        moveBy(-700, 700);
                        runsExpectXY(70, 610);
                        // bottom right
                        moveBy(800, 800);
                        runsExpectXY(610, 610);
                        endDrag();
                    });
                });
            });

            describe("constrain with region", function() {
                var region;

                beforeEach(function() {
                    makeDragEl(50, 50);
                    region = new Ext.util.Region(20, 400, 350, 30);
                });

                afterEach(function() {
                    region = null;
                });

                it("should constrain to the region", function() {
                    makeSource({
                        constrain: {
                            region: region
                        }
                    });
                    startDrag();
                    // top left
                    moveBy(-100, -100);
                    runsExpectXY(30, 20);
                    // top right
                    moveBy(600, -100);
                    runsExpectXY(380, 20);
                    // bottom left
                    moveBy(-620, 600);
                    runsExpectXY(30, 330);
                    // bottom right
                    moveBy(500, 500);
                    runsExpectXY(380, 330);
                    endDrag();
                });

                it("should be able to set the constraint after an initial drag", function() {
                    makeSource();
                    dragBy(100, 100);
                    runsExpectXY(150, 150);
                    runs(function() {
                        source.setConstrain({
                            region: region
                        });
                    });
                    dragBy(-400, -400);
                    runsExpectXY(30, 20);
                });

                it("should be able to clear the constraint after an initial drag", function() {
                    makeSource({
                        constrain: {
                            region: region
                        }
                    });
                    dragBy(800, 800);
                    runsExpectXY(380, 330);
                    runs(function() {
                        source.setConstrain(null);
                    });
                    dragBy(400, 400);
                    runsExpectXY(780, 730);
                });
            });

            describe("constrain with region + x/y", function() {
                // We don't want to test the various flavours of configuring, rather
                // that they work together and pick the right thing.
                 beforeEach(function() {
                    makeDragEl(50, 50);
                });

                describe("x", function() {
                    describe("min", function() {
                        it("should use the region min if it is larger", function() {
                            makeSource({
                                constrain: {
                                    x: [20, null],
                                    region: new Ext.util.Region(0, 1500, 1500, 40)
                                }
                            });

                            dragBy(-50, 0);
                            runsExpectXY(40, 50);
                        });

                        it("should use the x min if it is larger", function() {
                            makeSource({
                                constrain: {
                                    x: [40, null],
                                    region: new Ext.util.Region(0, 1500, 1500, 20)
                                }
                            });

                            dragBy(-50, 0);
                            runsExpectXY(40, 50);
                        });
                    });

                    describe("max", function() {
                        it("should use the region max if it is smaller", function() {
                            makeSource({
                                constrain: {
                                    x: [null, 400],
                                    region: new Ext.util.Region(0, 300, 1500, 0)
                                }
                            });

                            dragBy(300, 0);
                            // Max needs to take into account proxy size
                            runsExpectXY(280, 50);
                        });

                        it("should use the x max if it is smaller", function() {
                            makeSource({
                                constrain: {
                                    x: [null, 100],
                                    region: new Ext.util.Region(0, 200, 1500, 0)
                                }
                            });

                            dragBy(300, 0);
                            runsExpectXY(100, 50);
                        });
                    });
                });

                describe("y", function() {
                    describe("min", function() {
                        it("should use the region min if it is larger", function() {
                            makeSource({
                                constrain: {
                                    y: [20, null],
                                    region: new Ext.util.Region(40, 1500, 1500, 0)
                                }
                            });

                            dragBy(0, -50);
                            runsExpectXY(50, 40);
                        });

                        it("should use the y min if it is larger", function() {
                            makeSource({
                                constrain: {
                                    y: [40, null],
                                    region: new Ext.util.Region(20, 1500, 1500, 0)
                                }
                            });

                            dragBy(0, -50);
                            runsExpectXY(50, 40);
                        });
                    });

                    describe("max", function() {
                        it("should use the region max if it is smaller", function() {
                            makeSource({
                                constrain: {
                                    y: [null, 400],
                                    region: new Ext.util.Region(0, 1500, 300, 0)
                                }
                            });

                            dragBy(0, 300);
                            // Max needs to take into account proxy size
                            runsExpectXY(50, 280);
                        });

                        it("should use the y max if it is smaller", function() {
                            makeSource({
                                constrain: {
                                    y: [null, 100],
                                    region: new Ext.util.Region(0, 1500, 200, 0)
                                }
                            });

                            dragBy(0, 300);
                            runsExpectXY(50, 100);
                        });
                    });
                });
            });

            describe("snap", function() {
                beforeEach(function() {
                    makeDragEl(50, 50);
                });

                describe("configuring", function() {
                    it("should expand a single number", function() {
                        makeSource({
                            constrain: {
                                snap: 50
                            }
                        });
                        dragBy(26, 26);
                        runsExpectXY(100, 100);
                    });
                });

                describe("x only", function() {
                    describe("with a numeric value", function() {
                        it("should snap to the nearest value", function() {
                            makeSource({
                                constrain: {
                                    snap: { x: 30 }
                                }
                            });
                            startDrag();
                            moveBy(15, 0);
                            runsExpectXY(50, 50);
                            moveBy(1, 0);
                            runsExpectXY(80, 50);
                            moveBy(10, 0);
                            runsExpectXY(80, 50);
                            moveBy(20, 0);
                            runsExpectXY(110, 50);
                            moveBy(49, 0);
                            runsExpectXY(140, 50);
                            moveBy(200, 0);
                            runsExpectXY(350, 50);
                            moveBy(-10, 0);
                            runsExpectXY(320, 50);
                            moveBy(-100, 0);
                            runsExpectXY(230, 50);
                            moveBy(-19, 0);
                            runsExpectXY(230, 50);
                            moveBy(-180, 0);
                            runsExpectXY(50, 50);
                            endDrag();
                        });

                        it("should respect constraints", function() {
                            makeSource({
                                constrain: {
                                    snap: { x: 10 },
                                    x: [10, 160]
                                }
                            });

                            startDrag();
                            moveBy(-10, 0);
                            runsExpectXY(40, 50);
                            moveBy(-40, 0);
                            runsExpectXY(10, 50);
                            moveBy(79, 0);
                            runsExpectXY(80, 50);
                            moveBy(65, 0);
                            runsExpectXY(140, 50);
                            moveBy(40, 0);
                            runsExpectXY(160, 50);
                            endDrag();
                        });

                        it("should be able to set snap after an initial drag", function() {
                            makeSource();
                            dragBy(10, 0);
                            runsExpectXY(60, 50);
                            runs(function() {
                                source.setConstrain({
                                    snap: { x: 50 }
                                });
                            });
                            startDrag();
                            moveBy(10, 0);
                            runsExpectXY(60, 50);
                            moveBy(30, 0);
                            runsExpectXY(110, 50);
                            endDrag();
                        });

                        it("should be able to clear snap after an initial drag", function() {
                            makeSource({
                                constrain: {
                                    snap: { x: 50 }
                                }
                            });
                            dragBy(30, 0);
                            runsExpectXY(100, 50);
                            runs(function() {
                                source.setConstrain(null);
                            });
                            startDrag();
                            moveBy(-10, 0);
                            runsExpectXY(90, 50);
                            moveBy(-20, 0);
                            runsExpectXY(70, 50);
                            endDrag();
                        });

                        it("should not affect the y value", function() {
                            makeSource({
                                constrain: {
                                    snap: { x: 40 }
                                }
                            });

                            startDrag();
                            moveBy(10, 10);
                            runsExpectXY(50, 60);
                            moveBy(15, 10);
                            runsExpectXY(90, 70);
                            moveBy(25, 25);
                            runsExpectXY(90, 95);
                            moveBy(-10, -30);
                            runsExpectXY(90, 65);
                            endDrag();
                        });
                    });

                    describe("as a function", function() {
                        it("should be called on each move", function() {
                            var spy = jasmine.createSpy().andReturn(1);

                            makeSource({
                                constrain: {
                                    snap: {
                                        x: spy
                                    }
                                }
                            });

                            startDrag();
                            moveBy(10, 10);
                            runs(function() {
                                // Gets called twice on startup
                                expect(spy.callCount).toBe(3);
                                expect(spy.mostRecentCall.args[0] instanceof Ext.drag.Info).toBe(true);
                                expect(spy.mostRecentCall.args[1]).toBe(60);
                            });
                            moveBy(100, 0);
                            runs(function() {
                                expect(spy.callCount).toBe(4);
                                expect(spy.mostRecentCall.args[0] instanceof Ext.drag.Info).toBe(true);
                                expect(spy.mostRecentCall.args[1]).toBe(160);
                            });
                            moveBy(30, 0);
                            runs(function() {
                                endDrag();
                                expect(spy.callCount).toBe(5);
                                expect(spy.mostRecentCall.args[0] instanceof Ext.drag.Info).toBe(true);
                                expect(spy.mostRecentCall.args[1]).toBe(190);
                            });
                        });

                        it("should use the returned value", function() {
                            makeSource({
                                constrain: {
                                    snap: {
                                        x: function(info, x) {
                                            if (x < 100) {
                                                return 10;
                                            }
                                            else if (x < 200) {
                                                return 150;
                                            }
                                            else {
                                                return 400;
                                            }
                                        }
                                    }
                                }
                            });

                            startDrag();
                            moveBy(10, 0);
                            runsExpectXY(10, 50);
                            moveBy(20, 0);
                            runsExpectXY(10, 50);
                            moveBy(50, 0);
                            runsExpectXY(150, 50);
                            moveBy(10, 0);
                            runsExpectXY(150, 50);
                            moveBy(200, 0);
                            runsExpectXY(400, 50);
                            endDrag();
                        });
                    });
                });

                describe("y only", function() {
                    describe("with a numeric value", function() {
                        it("should snap to the nearest value", function() {
                            makeSource({
                                constrain: {
                                    snap: { y: 30 }
                                }
                            });
                            startDrag();
                            moveBy(0, 15);
                            runsExpectXY(50, 50);
                            moveBy(0, 1);
                            runsExpectXY(50, 80);
                            moveBy(0, 10);
                            runsExpectXY(50, 80);
                            moveBy(0, 20);
                            runsExpectXY(50, 110);
                            moveBy(0, 49);
                            runsExpectXY(50, 140);
                            moveBy(0, 200);
                            runsExpectXY(50, 350);
                            moveBy(0, -10);
                            runsExpectXY(50, 320);
                            moveBy(0, -100);
                            runsExpectXY(50, 230);
                            moveBy(0, -19);
                            runsExpectXY(50, 230);
                            moveBy(0, -180);
                            runsExpectXY(50, 50);
                            endDrag();
                        });

                        it("should respect constraints", function() {
                            makeSource({
                                constrain: {
                                    snap: { y: 10 },
                                    y: [10, 160]
                                }
                            });

                            startDrag();
                            moveBy(0, -10);
                            runsExpectXY(50, 40);
                            moveBy(0, -40);
                            runsExpectXY(50, 10);
                            moveBy(0, 79);
                            runsExpectXY(50, 80);
                            moveBy(0, 65);
                            runsExpectXY(50, 140);
                            moveBy(0, 40);
                            runsExpectXY(50, 160);
                            endDrag();
                        });

                        it("should be able to set snap after an initial drag", function() {
                            makeSource();
                            dragBy(0, 10);
                            runsExpectXY(50, 60);
                            runs(function() {
                                source.setConstrain({
                                    snap: { y: 50 }
                                });
                            });
                            startDrag();
                            moveBy(0, 10);
                            runsExpectXY(50, 60);
                            moveBy(0, 30);
                            runsExpectXY(50, 110);
                            endDrag();
                        });

                        it("should be able to clear snap after an initial drag", function() {
                            makeSource({
                                constrain: {
                                    snap: { y: 50 }
                                }
                            });
                            dragBy(0, 30);
                            runsExpectXY(50, 100);
                            runs(function() {
                                source.setConstrain(null);
                            });
                            startDrag();
                            moveBy(0, -10);
                            runsExpectXY(50, 90);
                            moveBy(0, -20);
                            runsExpectXY(50, 70);
                            endDrag();
                        });

                        it("should not affect the x value", function() {
                            makeSource({
                                constrain: {
                                    snap: { y: 40 }
                                }
                            });

                            startDrag();
                            moveBy(10, 10);
                            runsExpectXY(60, 50);
                            moveBy(10, 15);
                            runsExpectXY(70, 90);
                            moveBy(25, 25);
                            runsExpectXY(95, 90);
                            moveBy(-30, -10);
                            runsExpectXY(65, 90);
                            endDrag();
                        });
                    });

                    describe("as a function", function() {
                        it("should be called on each move", function() {
                            var spy = jasmine.createSpy().andReturn(1);

                            makeSource({
                                constrain: {
                                    snap: {
                                        y: spy
                                    }
                                }
                            });

                            startDrag();
                            moveBy(10, 10);
                            runs(function() {
                                // Gets called twice on startup
                                expect(spy.callCount).toBe(3);
                                expect(spy.mostRecentCall.args[0] instanceof Ext.drag.Info).toBe(true);
                                expect(spy.mostRecentCall.args[1]).toBe(60);
                            });
                            moveBy(0, 100);
                            runs(function() {
                                expect(spy.callCount).toBe(4);
                                expect(spy.mostRecentCall.args[0] instanceof Ext.drag.Info).toBe(true);
                                expect(spy.mostRecentCall.args[1]).toBe(160);
                            });
                            moveBy(0, 30);
                            runs(function() {
                                endDrag();
                                expect(spy.callCount).toBe(5);
                                expect(spy.mostRecentCall.args[0] instanceof Ext.drag.Info).toBe(true);
                                expect(spy.mostRecentCall.args[1]).toBe(190);
                            });
                        });

                        it("should use the returned value", function() {
                            makeSource({
                                constrain: {
                                    snap: {
                                        y: function(info, y) {
                                            if (y < 100) {
                                                return 10;
                                            }
                                            else if (y < 200) {
                                                return 150;
                                            }
                                            else {
                                                return 400;
                                            }
                                        }
                                    }
                                }
                            });

                            startDrag();
                            moveBy(0, 10);
                            runsExpectXY(50, 10);
                            moveBy(0, 20);
                            runsExpectXY(50, 10);
                            moveBy(0, 50);
                            runsExpectXY(50, 150);
                            moveBy(0, 10);
                            runsExpectXY(50, 150);
                            moveBy(0, 200);
                            runsExpectXY(50, 400);
                            endDrag();
                        });
                    });
                });

                describe("x & y", function() {
                    describe("both numbers", function() {
                        it("should snap in both directions as needed", function() {
                            makeSource({
                                constrain: {
                                    snap: {
                                        x: 30,
                                        y: 40
                                    }
                                }
                            });

                            startDrag();
                            moveBy(20, 10);
                            runsExpectXY(80, 50);
                            moveBy(15, 50);
                            runsExpectXY(80, 90);
                            moveBy(0, 115);
                            runsExpectXY(80, 210);
                            moveBy(125, 0);
                            runsExpectXY(200, 210);
                            endDrag();
                        });
                    });

                    describe("x as a number, y as a function", function() {
                        it("should snap in both directions as needed", function() {
                            makeSource({
                                constrain: {
                                    snap: {
                                        x: 30,
                                        y: function(info, y) {
                                            return y < 100 ? 50 : 150;
                                        }
                                    }
                                }
                            });

                            startDrag();
                            moveBy(20, 10);
                            runsExpectXY(80, 50);
                            moveBy(15, 50);
                            runsExpectXY(80, 150);
                            moveBy(0, 115);
                            runsExpectXY(80, 150);
                            moveBy(125, 0);
                            runsExpectXY(200, 150);
                            endDrag();
                        });
                    });

                    describe("x as a function, y as a number", function() {
                        it("should snap in both directions as needed", function() {
                            makeSource({
                                constrain: {
                                    snap: {
                                        x: function(info, x) {
                                            return x < 80 ? 10 : 200;
                                        },
                                        y: 40
                                    }
                                }
                            });

                            startDrag();
                            moveBy(20, 10);
                            runsExpectXY(10, 50);
                            moveBy(15, 50);
                            runsExpectXY(200, 90);
                            moveBy(0, 115);
                            runsExpectXY(200, 210);
                            moveBy(125, 0);
                            runsExpectXY(200, 210);
                            endDrag();
                        });
                    });

                    describe("both functions", function() {
                        it("should snap in both directions as needed", function() {
                            makeSource({
                                constrain: {
                                    snap: {
                                        x: function(info, x) {
                                            return x < 80 ? 10 : 200;
                                        },
                                        y: function(info, y) {
                                            return y < 100 ? 50 : 150;
                                        }
                                    }
                                }
                            });

                            startDrag();
                            moveBy(20, 10);
                            runsExpectXY(10, 50);
                            moveBy(15, 50);
                            runsExpectXY(200, 150);
                            moveBy(0, 115);
                            runsExpectXY(200, 150);
                            moveBy(125, 0);
                            runsExpectXY(200, 150);
                            endDrag();
                        });
                    });
                });
            });
        });
    });
});

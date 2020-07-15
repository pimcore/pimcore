topSuite("Ext.util.ClickRepeater", 'Ext.dom.Element', function() {
    var el, repeater;

    function makeElement(cfg) {
        cfg = Ext.apply({
            tag: 'div',
            style: {
                width: '100px',
                height: '100px',
                'background-color': 'green'
            }
        }, cfg);

        el = Ext.getBody().appendChild(cfg);

        return el;
    }

    function makeRepeater(cfg, elCfg) {
        if (!el) {
            makeElement(elCfg);
        }

        cfg = Ext.apply({}, cfg);

        repeater = new Ext.util.ClickRepeater(el, cfg);

        return repeater;
    }

    function clickElement(type) {
        jasmine.fireMouseEvent(el, type || 'click', 10, 10);
    }

    afterEach(function() {
        if (repeater) {
            repeater.destroy();
        }

        if (el) {
            el.destroy();
        }

        el = repeater = null;
    });

    describe("mousedown prevention", function() {
        var event, handler;

        beforeEach(function() {
            handler = jasmine.createSpy('handler').andCallFake(function(r, e) {
                event = e;
            });
        });

        afterEach(function() {
            handler = event = null;
        });

        describe("when no flags", function() {
            beforeEach(function() {
                makeRepeater({
                    handler: handler
                });

                clickElement();
            });

            it("should not prevent default", function() {
                expect(event.defaultPrevented).toBeFalsy();
            });

            it("should not stop event", function() {
                expect(event.stopped).toBeFalsy();
            });
        });

        describe("with mousedownPreventDefault", function() {
            beforeEach(function() {
                makeRepeater({
                    handler: handler,
                    mousedownPreventDefault: true
                });

                clickElement();
            });

            it("should prevent default", function() {
                expect(event.defaultPrevented).toBe(true);
            });

            it("should not stop event", function() {
                expect(event.stopped).toBeFalsy();
            });
        });

        describe("with mousedownStopEvent", function() {
            beforeEach(function() {
                makeRepeater({
                    handler: handler,
                    mousedownStopEvent: true
                });

                clickElement();
            });

            it("should prevent default", function() {
                expect(event.defaultPrevented).toBe(true);
            });

            it("should stop event", function() {
                expect(event.stopped).toBe(true);
            });
        });
    });

    describe("disabling", function() {
        it("should respond to events after enabling", function() {
            var spy = jasmine.createSpy();

            makeRepeater({
                handler: spy
            });
            repeater.setDisabled(false);
            clickElement();
            expect(spy.callCount).toBe(1);
        });
    });
});

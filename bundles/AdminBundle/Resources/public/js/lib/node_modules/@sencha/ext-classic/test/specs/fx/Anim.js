topSuite("Ext.fx.Anim", function() {
    var anim, target, animEndSpy;

    beforeEach(function() {
        target = Ext.getBody().createChild({ cls: 'fxtarget' });
        spyOn(Ext.fx.Anim.prototype, "end").andCallThrough();
    });

    afterEach(function() {
        target.destroy();
    });

    describe("instantiation", function() {
        beforeEach(function() {
            spyOn(Ext.fx.Manager, "addAnim"); // avoid animation start
            anim = new Ext.fx.Anim({
                target: target
            });
        });

        it("should mix in Ext.util.Observable", function() {
            expect(anim.mixins.observable).toEqual(Ext.util.Observable.prototype);
        });

        it("should have a default duration configuration option equal to 250", function() {
            expect(anim.duration).toEqual(250);
        });

        it("should have a default delay configuration option equal to 0", function() {
            expect(anim.delay).toEqual(0);
        });

        it("should have a default easing configuration option equal to ease", function() {
            expect(anim.easing).toEqual('ease');
        });

        it("should have a default reverse configuration option equal to false", function() {
            expect(anim.reverse).toBe(false);
        });

        it("should have a default running configuration option equal to false", function() {
            expect(anim.running).toBe(false);
        });

        it("should have a default paused configuration option equal to false", function() {
            expect(anim.paused).toBe(false);
        });

        it("should have a default iterations configuration option equal to 1", function() {
            expect(anim.iterations).toEqual(1);
        });

        it("should have a default currentIteration configuration option equal to 0", function() {
            expect(anim.currentIteration).toEqual(0);
        });

        it("should have a default startTime configuration option equal to 0", function() {
            expect(anim.startTime).toEqual(0);
        });
    });

    describe("events", function() {
        beforeEach(function() {
            spyOn(Ext.fx.Anim.prototype, "fireEvent").andCallThrough();

            anim = new Ext.fx.Anim({
                target: target,
                duration: 1,
                from: {
                    opacity: 0
                },
                to: {
                    opacity: 1
                }
            });
        });

        it("should fire beforeanimate and afteranimate", function() {
            waitsFor(function() {
                return Ext.fx.Anim.prototype.end.calls.length === 1;
            }, "event firing was never completed");

            runs(function() {
                expect(Ext.fx.Anim.prototype.fireEvent).toHaveBeenCalledWith("beforeanimate", anim);
                expect(Ext.fx.Anim.prototype.fireEvent.calls[1].args[0]).toEqual("afteranimate");
                expect(Ext.fx.Anim.prototype.fireEvent.calls[1].args[1]).toEqual(anim);
            });
        });
    });

    describe("opacity", function() {
        beforeEach(function() {
            anim = new Ext.fx.Anim({
                target: target,
                duration: 1,
                from: {
                    opacity: 0
                },
                to: {
                    opacity: 1
                }
            });

            waitsFor(function() {
                return Ext.fx.Anim.prototype.end.calls.length === 1;
            }, "event firing was never completed");
        });

        it("should change opacity", function() {
            if (Ext.isIE || Ext.isOpera) {
                expect(target.dom.style.filter).toEqual("");
            }
            else {
                expect(target.dom.style.opacity).toEqual("1");
            }
        });
    });

    describe("color", function() {
        describe("hexadecimal colors", function() {
            beforeEach(function() {
                anim = new Ext.fx.Anim({
                    target: target,
                    duration: 1,
                    from: {
                        color: "#000000"
                    },
                    to: {
                        color: "#f1c101"
                    }
                });

                waitsFor(function() {
                    return Ext.fx.Anim.prototype.end.calls.length === 1;
                }, "event firing was never completed");
            });

            it("should change color", function() {
                // IE8 there aren't spaces after the commas, for other browsers there are
                var color = target.dom.style.color.replace(/ /g, '');

                if (color.charAt(0) === '#') {
                    // Old Opera (not sure which versions exactly but pre-11.51)
                    expect(color).toEqual("#f1c101");
                }
                else {
                    expect(color).toEqual("rgb(241,193,1)");
                }
            });
        });

       xdescribe("shorthand hexadecimal colors", function() {
            beforeEach(function() {
                anim = new Ext.fx.Anim({
                    target: target,
                    duration: 1,
                    from: {
                        color: "#000000"
                    },
                    to: {
                        color: "#fc0"
                    }
                });
            });

            it("should change color", function() {
                waitsFor(function() {
                    var style = target.dom.style;

                    return style.color === 'rgb(255, 204, 0)';
                }, "color wasn't changed");
            });
        });

    });
});

topSuite("Ext.picker.Time", function() {
    var component, makeComponent;

    beforeEach(function() {
        makeComponent = function(config) {
            config = Ext.applyIf(config || {}, {
                renderTo: Ext.getBody()
            });
            component = new Ext.picker.Time(config);
        };
    });

    function componentHasTimes() {
        return component.rendered && component.all.elements.length;
    }

    afterEach(function() {
        if (component) {
            component.destroy();
        }

        component = makeComponent = null;
    });

    it("should extend Ext.BoundList", function() {
        makeComponent();
        expect(component instanceof Ext.BoundList).toBe(true);
    });

    describe("defaults", function() {
        beforeEach(function() {
            makeComponent();
        });

        it("should have minValue = undefined", function() {
            expect(component.minValue).not.toBeDefined();
        });

        it("should have maxValue = undefined", function() {
            expect(component.maxValue).not.toBeDefined();
        });

        it("should have increment = 15", function() {
            expect(component.increment).toEqual(15);
        });

        it("should have format = 'g:i A'", function() {
            expect(component.format).toEqual('g:i A');
        });

        it("should have componentCls = 'x-timepicker'", function() {
            expect(component.componentCls).toEqual('x-timepicker');
        });
    });

    describe("rendering", function() {
        it("should render items", function() {
            makeComponent();
            waitsFor(componentHasTimes);
            runs(function() {
                expect(component.getNodes().length).toEqual(96);
            });

        });

        it("should render formatted times into the items", function() {
            makeComponent();
            waitsFor(componentHasTimes);
            runs(function() {
                var nodes = component.getNodes();

                expect(nodes[0]).hasHTML('12:00 AM');
                expect(nodes[nodes.length - 1]).hasHTML('11:45 PM');
            });
        });

        it("should honor the 'format' config when rendering the times", function() {
            makeComponent({
                format: 'G,i,s'
            });
            waitsFor(componentHasTimes);
            runs(function() {
                expect(component.getNode(0)).hasHTML('0,00,00');
            });
        });
    });

    describe("increment", function() {
        it("should set the number of minutes between times in the list", function() {
            makeComponent({
                increment: 30
            });
            waitsFor(componentHasTimes);
            runs(function() {
                var nodes = component.getNodes();

                expect(nodes.length).toEqual(48);
                expect(nodes[1]).hasHTML('12:30 AM');
                expect(nodes[nodes.length - 1]).hasHTML('11:30 PM');
            });
        });
    });

    describe("minValue", function() {
        it("should be used as the minimum time in the list", function() {
            var date = new Date('1/1/2011 06:30:00');

            // opera 10.5 awful bug fix !!!
            if (jasmine.browser.isOpera) {
                date.setSeconds(0);
                date.setMilliseconds(0);
            }

            makeComponent({
                minValue: date
            });
            waitsFor(componentHasTimes);
            runs(function() {
                var nodes = component.getNodes();

                expect(nodes[0]).hasHTML('6:30 AM');
            });
        });

        describe("setMinValue method", function() {
            it("should set the minValue config", function() {
                makeComponent({
                    minValue: new Date('1/1/2011 06:30:00')
                });
                var newMinValue = new Date('1/1/2011 08:45:00');

                component.setMinValue(newMinValue);
                expect(component.minValue).toEqual(newMinValue);
            });

            it("should update the list to match the new minValue", function() {
                makeComponent({
                    minValue: new Date('1/1/2011 06:30:00')
                });
                var newMinValue = new Date('1/1/2011 08:45:00');

                // opera 10.5 awful bug fix !!!
                if (jasmine.browser.isOpera) {
                    newMinValue.setSeconds(0);
                    newMinValue.setMilliseconds(0);
                }

                component.setMinValue(newMinValue);
                expect(component.getNodes().length).toEqual(61);
                expect(component.getNode(0)).hasHTML('8:45 AM');
            });
        });
    });

    describe("maxValue", function() {
        it("should be used as the maximum time in the list", function() {
            makeComponent({
                maxValue: new Date('1/1/2011 21:30:00')
            });
            waitsFor(componentHasTimes);
            runs(function() {
                var nodes = component.getNodes();

                expect(nodes[nodes.length - 1]).hasHTML('9:30 PM');
            });
        });

        describe("setMaxValue method", function() {
            it("should set the maxValue config", function() {
                makeComponent({
                    maxValue: new Date('1/1/2011 21:30:00')
                });
                var newMaxValue = new Date('1/1/2011 13:15:00');

                component.setMaxValue(newMaxValue);
                expect(component.maxValue).toEqual(newMaxValue);
            });

            it("should update the list to match the new maxValue", function() {
                makeComponent({
                    maxValue: new Date('1/1/2011 21:30:00')
                });
                var newMaxValue = new Date('1/1/2011 13:15:00');

                component.setMaxValue(newMaxValue);
                var nodes = component.getNodes();

                expect(nodes.length).toEqual(54);
                expect(nodes[nodes.length - 1]).hasHTML('1:15 PM');
            });
        });
    });
});

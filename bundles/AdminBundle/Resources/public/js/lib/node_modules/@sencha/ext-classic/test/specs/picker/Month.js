topSuite("Ext.picker.Month", function() {
    var component, makeComponent;

    function getByElementsByClassName(dom, className) {
        var elements, length, result, i, el, testRe;

        if (document.getElementsByClassName) {
            return dom.getElementsByClassName(className);
        }

        testRe = new RegExp("(^|\\s)" + className + "(\\s|$)");
        elements = dom.getElementsByTagName("*");
        length = elements.length;
        result = [];

        for (i = 0; i < length; i++) {
            el = elements[i];

            if (testRe.test(el.className)) {
                result.push(el);
            }
        }

        return result;
    }

    beforeEach(function() {
        makeComponent = function(config) {
            config = config || {};
            config = Ext.applyIf({
                renderTo: Ext.getBody()
            }, config);
            component = new Ext.picker.Month(config);
        };
    });

    afterEach(function() {
        if (component) {
            component.destroy();
        }

        component = makeComponent = null;
    });

    describe("alternate class name", function() {
        it("should have Ext.MonthPicker as the alternate class name", function() {
            expect(Ext.picker.Month.prototype.alternateClassName).toEqual("Ext.MonthPicker");
        });

        it("should allow the use of Ext.MonthPicker", function() {
            expect(Ext.MonthPicker).toBeDefined();
        });
    });

    describe("initial value", function() {

        it("should not default any value", function() {
            makeComponent();
            expect(component.getValue()).toEqual([null, null]);
        });

        it("should accept a date as the value config", function() {
            makeComponent({
                value: new Date(2009, 3, 3)
            });

            expect(component.getValue()).toEqual([3, 2009]);
        });

        it("should accept an array as the value", function() {
            makeComponent({
                value: [4, 1984]
            });

            expect(component.getValue()).toEqual([4, 1984]);
        });

    });

    describe("setting value", function() {

        it("should accept a date when setting a value", function() {
            makeComponent();
            component.setValue(new Date(2004, 1, 12));
            expect(component.getValue()).toEqual([1, 2004]);
        });

        it("should accept an array when setting a value", function() {
            makeComponent();
            component.setValue([9, 2001]);
            expect(component.getValue()).toEqual([9, 2001]);
        });

        it("should be able to null out certain values", function() {
            makeComponent({
                value: [3, 2010]
            });
            component.setValue([null, 2010]);
            expect(component.getValue()).toEqual([null, 2010]);
        });
    });

    describe("rendering", function() {
        it("should respect the padding config", function() {
            makeComponent({
                padding: 10
            });
            expect(component.getWidth()).toBe(197);
        });

        it("should not show buttons if showButtons is false", function() {
            makeComponent({
                showButtons: false
            });

            expect(getByElementsByClassName(component.el.dom, 'x-monthpicker-buttons').length).toEqual(0);
        });

        describe("year range", function() {
            var getYears, getYearText;

            beforeEach(function() {
                getYears = function() {
                    return getByElementsByClassName(component.el.dom, 'x-monthpicker-year');
                };

                getYearText = function(years, index) {
                    return years[index].firstChild.innerHTML;
                };
            });

            afterEach(function() {
                getYears = null;
            });

            it("should use the current year if none is provided", function() {
                makeComponent();

                var years = getYears(),
                    year = (new Date()).getFullYear();

                expect(getYearText(years, 0)).toEqual((year - 4).toString());
                expect(getYearText(years, years.length - 1)).toEqual((year + 5).toString());
            });

            it("should use the value year as the active year if passed", function() {
                makeComponent({
                    value: [0, 1970]
                });

                var years = getYears();

                expect(getYearText(years, 0)).toEqual('1966');
                expect(getYearText(years, years.length - 1, 0)).toEqual('1975');
            });

            it("should change the year range if a new value is set", function() {
                makeComponent();
                component.setValue([0, 1980]);

                var years = getYears();

                expect(getYearText(years, 0)).toEqual('1976');
                expect(getYearText(years, years.length - 1, 0)).toEqual('1985');
            });

            it("it should not change the range if the value is within the current range", function() {
                makeComponent();
                var year = new Date().getFullYear();

                component.setValue([0, year + 1]);
                var years = getYears();

                expect(getYearText(years, 0)).toEqual((year - 4).toString());
                expect(getYearText(years, years.length - 1, 0)).toEqual((year + 5).toString());
            });
        });
    });

    describe("selection", function() {

        var getSelection;

        beforeEach(function() {
            getSelection = function(isMonth) {
                var items = getByElementsByClassName(component.el.dom, 'x-monthpicker-' + (isMonth ? 'month' : 'year')),
                    len = items.length,
                    i = 0,
                    item;

                for (; i < len; ++i) {
                    item = items[i];

                    if (item.firstChild.className.indexOf('x-monthpicker-selected') > -1) {
                        return item.firstChild;
                    }
                }

                return null;
            };
        });

        afterEach(function() {
            getSelection = null;
        });

        it("should have no selections if no value is specified", function() {
            makeComponent();
            expect(getSelection()).toBeNull();
            expect(getSelection(true)).toBeNull();
        });

        it("should only have a month selection for a month-only value", function() {
            makeComponent({
                value: [3, null]
            });
            expect(getSelection()).toBeNull();
            expect(getSelection(true)).hasHTML('Apr');
        });

        it("should only have a year selection for a year-only value", function() {
            makeComponent({
                value: [null, 2000]
            });
            expect(getSelection()).hasHTML('2000');
            expect(getSelection(true)).toBeNull();
        });

        it("should select have selections when both items are selected", function() {
            makeComponent({
                value: [0, 2004]
            });
            expect(getSelection()).hasHTML('2004');
            expect(getSelection(true)).hasHTML('Jan');
        });

        it("should remove any selection if it's not valid for the range", function() {
            var d = new Date();

            makeComponent({
                value: d
            });
            expect(getSelection()).hasHTML(d.getFullYear().toString());
            component.adjustYear(-10);
            expect(getSelection()).toBeNull();
        });
    });
});

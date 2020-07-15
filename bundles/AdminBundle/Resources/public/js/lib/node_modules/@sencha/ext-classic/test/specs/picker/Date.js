// date picker has 42 cells

topSuite("Ext.picker.Date", ['Ext.form.field.Date'], function() {
    var component, makeComponent, makeRange;

    beforeEach(function() {
        makeComponent = function(config) {
            component = new Ext.picker.Date(Ext.apply({
                renderTo: Ext.getBody()
            }, config));
        };

        makeRange = function(min, max) {
            var out = [],
                i = min;

            for (; i <= max; ++i) {
                out.push(i);
            }

            return out;
        };
    });

    afterEach(function() {
        if (component) {
            component.destroy();
        }

        component = makeComponent = makeRange = null;
    });

    describe("alternate class name", function() {
        it("should have Ext.DatePicker as the alternate class name", function() {
            expect(Ext.picker.Date.prototype.alternateClassName).toEqual("Ext.DatePicker");
        });

        it("should allow the use of Ext.DatePicker", function() {
            expect(Ext.DatePicker).toBeDefined();
        });
    });

    describe("rendering", function() {
        it("should respect the showToday config", function() {
            makeComponent({
                showToday: false
            });

            expect(component.footerEl).toBeFalsy();
        });

        it("should respect the padding config", function() {
            makeComponent({
                padding: 10
            });
            expect(component.getWidth()).toBe(197);
        });

        it("should be able to be configured as disabled", function() {
            expect(function() {
                makeComponent({
                    disabled: true
                });
            }).not.toThrow();
        });

        describe("defaultValue", function() {
            it("should default to the current date", function() {
                makeComponent();

                expect(component.getValue()).toEqual(Ext.Date.clearTime(new Date()));
            });

            it("should allow for the user to set a defaultValue", function() {
                makeComponent({
                    defaultValue: new Date(2017, 5, 31)
                });

                expect(component.getValue()).toEqual(new Date(2017, 5, 31));
            });

            it("should not be used if a value is set", function() {
                makeComponent({
                    defaultValue: new Date(2017, 5, 31),
                    value: new Date(2017, 6, 1)
                });

                expect(component.getValue()).toEqual(new Date(2017, 6, 1));
            });
        });

        describe("startDay", function() {
            var weekStart;

            beforeEach(function() {
                weekStart = Ext.Date.firstDayOfWeek;
                Ext.Date.firstDayOfWeek = 1;
            });

            afterEach(function() {
                Ext.Date.firstDayOfWeek = weekStart;
            });

            it("should default to Ext.Date.firstDayOfWeek", function() {
                makeComponent();

                var th = component.eventEl.down('th', true);

                expect(th.firstChild.innerHTML).toBe('M');
            });

            it("should take config option", function() {
                makeComponent({
                    startDay: 2
                });

                var th = component.eventEl.down('th', true);

                expect(th.firstChild.innerHTML).toBe('T');
            });
        });

        // https://sencha.jira.com/browse/EXTJS-15718
        describe("when rendered within a td element", function() {
            function setupTable() {
                Ext.DomHelper.append(Ext.getBody(), {
                    tag: 'table',
                    id: 'ownerTable',
                    children: [{
                        tag: 'tr',
                        children: [{
                            tag: 'td',
                            children: [{
                                tag: 'div',
                                id: 'nestedDiv'
                            }]
                        }]
                    }]
                });
            }

            afterEach(function() {
                component = Ext.destroy(component);
                Ext.get('nestedDiv').destroy();
                Ext.get('ownerTable').destroy();
            });

            it("should display the day header", function() {
                var node;

                setupTable();
                makeComponent({
                    renderTo: Ext.get('nestedDiv')
                });

                node = component.el.down('.x-datepicker-column-header', true);
                // should have 42 text nodes (6 weeks x 7 days)
                expect(component.textNodes.length).toBe(42);
                // check first and last node in first row
                expect(node.firstChild.innerHTML).toBe('S');
            });

            it("should select the correct item", function() {
                var node, value, pickerValue;

                setupTable();
                makeComponent({
                    renderTo: Ext.get('nestedDiv')
                });

                node = Ext.fly(component.textNodes[17]);
                // this is the raw value in the cell
                value = node.getHtml();
                // fire click to select
                jasmine.fireMouseEvent(node.dom, 'click');
                // get the value from the picker now that selection has occurred
                pickerValue = component.getValue();

                // pickerValue date should be the same as the raw value date
                expect(pickerValue.getDate()).toBe(parseInt(value));
            });
        });
    });

    describe("restrictions", function() {
        var isDisabled;

        beforeEach(function() {
            isDisabled = function(range, title) {
                var i = 0,
                    cells = component.cells.elements,
                    len = range.length,
                    cell, cellTitle,
                    checkTitle = title !== null;

                for (; i < len; ++i) {
                    cell = cells[range[i]];
                    cellTitle = cell.getAttribute('data-qtip');

                    if (cell.className.indexOf(component.disabledCellCls) === -1 || (checkTitle && cellTitle !== title)) {
                        return false;
                    }
                }

                return true;
            };
        });

        afterEach(function() {
            isDisabled = null;
        });

        describe("max date", function() {
            it("should not have any max date set if not specified", function() {
                makeComponent({
                    value: new Date(2010, 10, 4) // 4th Nov 2010
                });

                // go way into the future
                for (var i = 0; i < 10; ++i) {
                    component.showNextYear();
                }

                expect(component.el.select('td[title="' + component.maxText + '"]').getCount()).toEqual(0);

            });

            it("should set the class and title on elements over the max date 1", function() {
                makeComponent({
                    value: new Date(2010, 10, 4), // 4th Nov 2010
                    maxDate: new Date(2010, 10, 18) // 18th Nov, 2010
                });

                expect(isDisabled(makeRange(19, 41), component.maxText)).toBeTruthy();
            });

            it("should set the class and title on elements over the max date 2", function() {
                makeComponent({
                    value: new Date(2007, 4, 3), // 3rd May 2017
                    maxDate: new Date(2007, 4, 7) // 7th May 2007
                });

                expect(isDisabled(makeRange(9, 41), component.maxText)).toBeTruthy();
            });

            it("should not set the class/title if the max date isn't on the current page", function() {
                makeComponent({
                    value: new Date(2007, 4, 3), // 3rd May 2007
                    maxDate: new Date(2010, 4, 7) // 7th May 2010
                });

                var cells = component.cells,
                    len = cells.getCount(),
                    i = 0;

                for (; i < len; ++i) {
                    expect(cells.item(i).dom.title).not.toEqual(component.maxText);
                    expect(cells.item(i).dom.className).not.toEqual(component.disabledCellCls);
                }
            });

            it("should update the class/title if required when changing the active 'page'", function() {
                makeComponent({
                    value: new Date(2007, 4, 3), // 3rd May 2007
                    maxDate: new Date(2007, 5, 15) // 15th Jun 2007
                });

                component.showNextMonth();
                expect(isDisabled(makeRange(20, 41), component.maxText)).toBeTruthy();
            });

            it("should set the value to the max date if its greater than max date", function() {
                makeComponent({
                    value: new Date(2007, 4, 3),
                    maxDate: new Date(2007, 3, 3)
                });

                expect(component.getValue()).toEqual(component.maxDate);
            });
        });

        describe("min date", function() {
            it("should not have any min date set if not specified", function() {
                makeComponent({
                    value: new Date(2010, 10, 4) // 4th Nov 2010
                });

                // go way into the future
                for (var i = 0; i < 10; ++i) {
                    component.showPrevYear();
                }

                expect(component.el.select('td[title="' + component.minText + '"]').getCount()).toEqual(0);

            });

            it("should set the class and title on elements under the min date 1", function() {
                makeComponent({
                    value: new Date(2010, 8, 18), // 18th Sep 2010
                    minDate: new Date(2010, 8, 4) // 4th Sep, 2010
                });

                expect(isDisabled(makeRange(0, 5), component.minText)).toBeTruthy();
            });

            it("should set the class and title on elements over the min date 2", function() {
                makeComponent({
                    value: new Date(2006, 2, 3), // 3rd Mar 2006
                    minDate: new Date(2006, 2, 7) // 7th Mar 2006
                });

                expect(isDisabled(makeRange(0, 8), component.minText)).toBeTruthy();
            });

            it("should not set the class/title if the min date isn't on the current page", function() {
                makeComponent({
                    minDate: new Date(2007, 2, 3), // 3rd Mar 2007
                    value: new Date(2010, 2, 7) // 7th Mar 2010
                });

                var cells = component.cells,
                    len = cells.getCount(),
                    i = 0;

                for (; i < len; ++i) {
                    expect(cells.item(i).dom.title).not.toEqual(component.minText);
                    expect(cells.item(i).dom.className).not.toEqual(component.disabledCellCls);
                }
            });

            it("should update the class/title if required when changing the active 'page'", function() {
                makeComponent({
                    minDate: new Date(2007, 4, 3), // 3rd May 2017
                    value: new Date(2007, 5, 15) // 15th Jun 2007
                });

                component.showPrevMonth();
                expect(isDisabled(makeRange(0, 3), component.minText)).toBeTruthy();
            });

            it("should set the value to the min date if its less than min date", function() {
                makeComponent({
                    value: new Date(2007, 2, 3),
                    minDate: new Date(2007, 3, 3)
                });

                expect(component.getValue()).toEqual(component.minDate);
            });
        });

        describe("disabledDays", function() {
            it("should not disabled anything if there any no disabledDays", function() {
                makeComponent();
                expect(component.el.select('.' + component.disabledCellCls).getCount()).toEqual(0);
            });

            it("should disable the appropriate days 1", function() {
                makeComponent({
                    value: new Date(2010, 10, 4),
                    disabledDays: [0, 6] // sat, sun
                });

                expect(isDisabled([0, 6, 7, 13, 14, 20, 21, 27, 28, 34, 35], component.disabledDaysText)).toBeTruthy();
            });

            it("should disable the appropriate days 2", function() {
                makeComponent({
                    value: new Date(2010, 10, 4),
                    disabledDays: [1, 5] // mon, fri
                });

                expect(isDisabled([1, 5, 8, 12, 15, 19, 22, 26, 29, 33, 36, 40], component.disabledDaysText)).toBeTruthy();
            });
        });

        describe("disabledDates", function() {
            it("should disabled specific dates", function() {
                makeComponent({
                    value: new Date(2010, 10, 4),
                    format: 'Y/m/d',
                    disabledDates: ['2010/11/07', '2010/11/14']
                });

                expect(isDisabled([7, 14], null)).toBeTruthy();
            });

            it("should disabled specific dates according to regex - year", function() {

                var date = new Date(2010, 10, 4),
                    range = makeRange(0, 41);

                makeComponent({
                    value: date,
                    format: 'Y/m/d',
                    disabledDates: ['2010/*']
                });

                while (date.getFullYear() === 2010) {
                    if (date.getMonth() > 0) {
                        expect(isDisabled(range, null)).toBeTruthy();
                    }
                    else {
                        expect(isDisabled(makeRange(5, 41), null)).toBeTruthy();
                    }

                    date = Ext.Date.add(date, Ext.Date.MONTH, -1);
                    component.showPrevMonth();
                }
            });

            it("should disabled specific dates according to regex - month", function() {

                makeComponent({
                    value: new Date(2010, 10, 4),
                    format: 'Y/m/d',
                    disabledDates: ['2010/11/*']
                });

                expect(isDisabled(makeRange(1, 30), null)).toBeTruthy();
                component.showPrevMonth();
                expect(isDisabled(makeRange(0, 35), null)).toBeFalsy();
            });

            it("should disabled specific dates according to regex - day", function() {

                makeComponent({
                    value: new Date(2010, 10, 4),
                    format: 'Y/m/d',
                    disabledDates: ['2010/11/1*']
                });

                expect(isDisabled(makeRange(14, 23), null)).toBeTruthy();
            });
        });

        describe("today button", function() {
            var D = Ext.Date,
                now = new Date(),
                earlier = D.subtract(now, D.DAY, 7),
                later = D.add(now, D.DAY, 7);

            function expectDisabled() {
                expect(component.todayBtn.disabled).toBe(true);
            }

            function expectEnabled() {
                expect(component.todayBtn.disabled).toBe(false);
            }

            describe("initial", function() {
                it("should disable the button if today is greater than the max value", function() {
                    makeComponent({
                        maxDate: earlier
                    });
                    expectDisabled();
                });

                it("should disable the button if today is less than the min value", function() {
                    makeComponent({
                        minDate: later
                    });
                    expectDisabled();
                });

                it("should not disable the button if it's within the min/max bounds", function() {
                    makeComponent({
                        minDate: earlier,
                        maxDate: later
                    });
                    expectEnabled();
                });

                it("should be disabled if the picker is disabled", function() {
                    makeComponent({
                        disabled: true
                    });
                    expectDisabled();
                });
            });

            describe("setting min/max after configuring", function() {
                describe("setting min", function() {
                    it("should enable after clearing the min", function() {
                        makeComponent({
                            minDate: later
                        });
                        expectDisabled();
                        component.setMinDate(null);
                        expectEnabled();
                    });

                    it("should enable after setting a minimum before today", function() {
                        makeComponent({
                            minDate: later
                        });
                        expectDisabled();
                        component.setMinDate(earlier);
                        expectEnabled();
                    });

                    it("should disable after setting a minimum after today", function() {
                        makeComponent();
                        expectEnabled();
                        component.setMinDate(later);
                        expectDisabled();
                    });
                });

                describe("setting max", function() {
                    it("should enable after clearing the max", function() {
                        makeComponent({
                            maxDate: earlier
                        });
                        expectDisabled();
                        component.setMaxDate(null);
                        expectEnabled();
                    });

                    it("should enable after setting a maximum after today", function() {
                        makeComponent({
                            maxDate: earlier
                        });
                        expectDisabled();
                        component.setMaxDate(later);
                        expectEnabled();
                    });

                    it("should disable after setting a maximum before today", function() {
                        makeComponent();
                        expectEnabled();
                        component.setMaxDate(earlier);
                        expectDisabled();
                    });
                });
            });

            describe("setting disabled after configuring", function() {
                describe("enabling", function() {
                    it("should enable the button", function() {
                        makeComponent({
                            disabled: true
                        });
                        expectDisabled();
                        component.enable();
                        expectEnabled();
                    });

                    it("should not enable the button if today does not fall in a valid date range", function() {
                        makeComponent({
                            disabled: true,
                            minDate: later
                        });
                        expectDisabled();
                        component.enable();
                        expectDisabled();
                    });
                });

                describe("disabling", function() {
                    it("should disable the button", function() {
                        makeComponent();
                        expectEnabled();
                        component.disable();
                        expectDisabled();
                    });
                });
            });
        });

    });

    describe('showing month picker', function() {
        var df, picker;

        beforeEach(function() {
            df = new Ext.form.field.Date({
                renderTo: Ext.getBody(),
                disableAnim: true
            });

            df.focus();

            jasmine.waitForFocus(df);
        });

        afterEach(function() {
            df.destroy();
            df = picker = null;
        });

        it('should show the month picker on click of the button', function() {
            runs(function() {
                df.expand();
                picker = df.getPicker();

                jasmine.fireMouseEvent(picker.monthBtn.el, 'click');
            });

            waitsFor(function() {
                return !!picker.monthPicker.isVisible();
            }, 'for month picker to show', 1000);

            // https://sencha.jira.com/browse/EXTJS-15968
            // MonthPicker AND DatePicker hid slightly after completing show animation
            waits(100);

            runs(function() {
                expect(picker.isVisible()).toBe(true);
                expect(picker.monthPicker.isVisible()).toBe(true);
            });
        });
    });

    // Space, Enter, Escape, and Tab keys are tested in Date field suite
    describe("keyboard interaction", function() {
        var eDate = Ext.Date,
            focusAndWait = jasmine.focusAndWait,
            today = eDate.clearTime(new Date()),
            spy, event;

        function expectDate(date) {
            var activeDate = Ext.Date.clearTime(new Date(component.activeCell.firstChild.dateValue));

            expect(activeDate.toString()).toBe(date.toString());
        }

        function pressKey(key, options) {
            component.eventEl.on('keydown', spy);
            jasmine.syncPressKey(component, key, options);
            component.eventEl.un('keydown', spy);

            event = spy.mostRecentCall.args[0];
        }

        beforeEach(function() {
            spy = jasmine.createSpy('keydown');

            makeComponent();

            focusAndWait(component);
        });

        afterEach(function() {
            spy = event = null;
        });

        describe("left arrow", function() {
            beforeEach(function() {
                pressKey('left');
            });

            it("should select the day before", function() {
                expectDate(eDate.add(today, eDate.DAY, -1));
            });

            it("should prevent default on the event", function() {
                expect(event.defaultPrevented).toBe(true);
            });
        });

        describe("ctrl-left arrow", function() {
            beforeEach(function() {
                pressKey('left', { ctrlKey: true });
            });

            it("should select same day of the previous month", function() {
                expectDate(eDate.add(today, eDate.MONTH, -1));
            });

            it("should prevent default on the event", function() {
                expect(event.defaultPrevented).toBe(true);
            });
        });

        describe("right arrow", function() {
            beforeEach(function() {
                pressKey('right');
            });

            it("should select the next day", function() {
                expectDate(eDate.add(today, eDate.DAY, 1));
            });

            it("should prevent default on the event", function() {
                expect(event.defaultPrevented).toBe(true);
            });
        });

        describe("ctrl-right arrow", function() {
            beforeEach(function() {
                pressKey('right', { ctrlKey: true });
            });

            it("should select same day of the next month", function() {
                expectDate(eDate.add(today, eDate.MONTH, 1));
            });

            it("should prevent default on the event", function() {
                expect(event.defaultPrevented).toBe(true);
            });
        });

        describe("up arrow", function() {
            beforeEach(function() {
                pressKey('up');
            });

            it("should select the day a week ago", function() {
                expectDate(eDate.add(today, eDate.DAY, -7, true));
            });

            it("should prevent default on the event", function() {
                expect(event.defaultPrevented).toBe(true);
            });
        });

        // This is non-standard historical behavior
        describe("ctrl-up arrow", function() {
            beforeEach(function() {
                pressKey('up', { ctrlKey: true });
            });

            it("should select the same day of the next year", function() {
                expectDate(eDate.add(today, eDate.YEAR, 1));
            });

            it("should prevent default on the event", function() {
                expect(event.defaultPrevented).toBe(true);
            });
        });

        describe("down arrow", function() {
            beforeEach(function() {
                pressKey('down');
            });

            it("should select the day a week ahead", function() {
                expectDate(eDate.add(today, eDate.DAY, 7));
            });

            it("should prevent default on the event", function() {
                expect(event.defaultPrevented).toBe(true);
            });
        });

        // This is non-standard historical behavior
        describe("ctrl-down arrow", function() {
            beforeEach(function() {
                pressKey('down', { ctrlKey: true });
            });

            it("should select the same day a year before", function() {
                expectDate(eDate.add(today, eDate.YEAR, -1));
            });

            it("should prevent default on the event", function() {
                expect(event.defaultPrevented).toBe(true);
            });
        });

        describe("pageUp", function() {
            beforeEach(function() {
                pressKey('page_up');
            });

            it("should select the same day of the previous month", function() {
                expectDate(eDate.add(today, eDate.MONTH, -1));
            });

            it("should prevent default on the event", function() {
                expect(event.defaultPrevented).toBe(true);
            });
        });

        describe("ctrl-pageUp", function() {
            beforeEach(function() {
                pressKey('page_up', { ctrlKey: true });
            });

            it("should select the same day of the previous year", function() {
                expectDate(eDate.add(today, eDate.YEAR, -1));
            });

            it("should prevent default on the event", function() {
                expect(event.defaultPrevented).toBe(true);
            });
        });

        describe("pageDown", function() {
            beforeEach(function() {
                pressKey('page_down');
            });

            it("should select the same day of the next month", function() {
                expectDate(eDate.add(today, eDate.MONTH, 1));
            });

            it("should prevent default on the event", function() {
                expect(event.defaultPrevented).toBe(true);
            });
        });

        describe("ctrl-pageDown", function() {
            beforeEach(function() {
                pressKey('page_down', { ctrlKey: true });
            });

            it("should select the same day of the next year", function() {
                expectDate(eDate.add(today, eDate.YEAR, 1));
            });

            it("should prevent default on the event", function() {
                expect(event.defaultPrevented).toBe(true);
            });
        });

        describe("home key", function() {
            beforeEach(function() {
                pressKey('home');
            });

            it("should select the first day of the current month", function() {
                expectDate(eDate.getFirstDateOfMonth(today));
            });

            it("should prevent default on the event", function() {
                expect(event.defaultPrevented).toBe(true);
            });
        });

        describe("end key", function() {
            beforeEach(function() {
                pressKey('end');
            });

            it("should select the last day of the current month", function() {
                expectDate(eDate.getLastDateOfMonth(today));
            });

            it("should prevent default on the event", function() {
                expect(event.defaultPrevented).toBe(true);
            });
        });
    });
});

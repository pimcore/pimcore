topSuite("Ext.menu.DatePicker", function() {
    var menu;

    function makeMenu(cfg) {
        cfg = Ext.apply({
            floating: true
        }, cfg);

        menu = new Ext.menu.DatePicker(cfg);

        return menu;
    }

    afterEach(function() {
        if (menu) {
            menu.destroy();
        }

        menu = null;
    });

    describe("pickerCfg", function() {
        beforeEach(function() {
            makeMenu({
                pickerCfg: {
                    foo: 'bar'
                },

                blerg: 'throbbe'
            });
        });

        it("should apply pickerCfg", function() {
            expect(menu.picker.foo).toBe('bar');
        });

        it("should not apply other configs", function() {
            expect(menu.picker.blerg).not.toBeDefined();
        });
    });

    describe("no pickerCfg", function() {
        it("should apply config", function() {
            makeMenu({
                frobbe: 'gurgle'
            });

            expect(menu.picker.frobbe).toBe('gurgle');
        });
    });

    describe("interaction", function() {
        var button, dateItem;

        beforeEach(function() {
            button = new Ext.button.Button({
                renderTo: Ext.getBody(),
                text: 'foo',
                menu: [{
                    text: 'no submenu'
                }, {
                    text: 'date',
                    menu: {
                        xtype: 'datemenu'
                    }
                }]
            });

            button.showMenu();

            dateItem = button.menu.down('[text=date]');

            dateItem.focus();
            dateItem.expandMenu(null, 0);

            menu = dateItem.menu;
        });

        afterEach(function() {
            if (button) {
                button.destroy();
            }

            button = null;
        });

        describe("keyboard interaction", function() {
            it("should focus the picker eventEl on open", function() {
                expectFocused(menu.picker.eventEl, false);
            });

            it("should close the date menu on Esc key", function() {
                pressKey(menu.picker.eventEl, 'esc');

                waitForFocus(dateItem);

                runs(function() {
                    expect(menu.isVisible()).toBeFalsy();
                });
            });

            it("should focus the owner menu item on Esc key", function() {
                pressKey(menu.picker.eventEl, 'esc');

                expectFocused(dateItem);
            });
        });

        describe('clicking', function() {
            it('should not hide on click of monthButton', function() {
                Ext.testHelper.tap(menu.items.items[0].monthBtn.el);
                expect(menu.isVisible()).toBe(true);
            });
        });
    });
});

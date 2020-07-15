topSuite("Ext.menu.ColorPicker", function() {
    var menu;

    function makeMenu(cfg) {
        cfg = Ext.apply({
            floating: true
        }, cfg);

        menu = new Ext.menu.ColorPicker(cfg);

        return menu;
    }

    afterEach(function() {
        if (menu) {
            menu.destroy();
        }

        menu = null;
    });

    describe("contructor", function() {
        it("should have the same owner as the picker", function() {
            makeMenu();

            expect(menu.picker.ownerCmp).toBe(menu.ownerCmp);
        });
    });

    describe("interaction", function() {
        var button, colorItem;

        beforeEach(function() {
            button = new Ext.button.Button({
                renderTo: Ext.getBody(),
                text: 'foo',
                menu: [{
                    text: 'no submenu'
                }, {
                    text: 'color',
                    menu: {
                        xtype: 'colormenu'
                    }
                }]
            });

            button.showMenu();

            colorItem = button.menu.down('[text=color]');

            colorItem.focus();
            colorItem.expandMenu(null, 0);

            menu = colorItem.menu;
        });

        afterEach(function() {
            if (button) {
                button.destroy();
            }

            button = null;
        });

        describe('clicking', function() {
            it('should select a color', function() {
                var el = menu.picker.el.down('a.color-000000');

                Ext.testHelper.tap(el);
                expect(menu.picker.value).toBe('000000');
                el.destroy();
            });
        });
    });
});

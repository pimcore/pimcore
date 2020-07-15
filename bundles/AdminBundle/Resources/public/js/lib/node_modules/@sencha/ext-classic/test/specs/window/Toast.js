/* global jasmine, Ext, expect */

topSuite("Ext.window.Toast", ['Ext.form.field.Text'], function() {
    var fireMouseEvent = jasmine.fireMouseEvent,
        win, win2, toast, field1, field2, field3,
        showSpy, destroySpy;

    function makeToast(config) {
        toast = Ext.toast(Ext.apply({
            html: 'A toast to focus',
            slideInDuration: 100,
            hideDuration: 100,
            autoClose: false,
            listeners: {
                show: showSpy,
                destroy: destroySpy
            }
        }, config));

        return toast;
    }

    beforeEach(function() {
        showSpy = jasmine.createSpy('show');
        destroySpy = jasmine.createSpy('destroy');
    });

    afterEach(function() {
        toast = win = win2 = Ext.destroy(toast, win, win2);

        field1 = field2 = null;
    });

    describe("creation", function() {
        describe("autoClose is true", function() {
            describe("closable is not defined", function() {
                beforeEach(function() {
                    makeToast({
                        autoClose: true
                    });
                });
                afterEach(function() {
                    Ext.destroy(toast);
                });

                it("should force closable to false", function() {
                    expect(toast.closable).toBe(false);
                });

                it("should not render close tool", function() {
                    var tool = toast.down('[type=close]');

                    expect(tool).toBeFalsy();
                });
            });

            describe("closable is true", function() {
                beforeEach(function() {
                    makeToast({
                        closable: true,
                        autoClose: true
                    });
                });

                it("should not force closable to false", function() {
                    expect(toast.closable).toBe(true);
                });

                it("should render close tool", function() {
                    var tool = toast.down('[type=close]');

                    expect(tool).toBeTruthy();
                });
            });
        });
    });

    describe("closeOnMouseDown", function() {
        beforeEach(function() {
            makeToast({
                align: 'tl',
                html: 'foo',
                animate: false,
                closeOnMouseDown: true
            });
        });

        it("should dismiss toast", function() {
            fireMouseEvent(Ext.getBody(), 'click', 0, 0);

            waitForSpy(destroySpy, 'destroy spy', 100);

            runs(function() {
                expect(destroySpy).toHaveBeenCalled();
            });
        });
    });

    describe("canceling animation", function() {
        beforeEach(function() {
            makeToast({
                align: 'tl',
                animate: false
            });
        });

        describe("showing", function() {
            it("should show", function() {
                expect(toast.el.isVisible()).toBe(true);
            });

            it("should be visibly positioned", function() {
                var xy = toast.getPosition();

                expect(xy).toEqual([toast.xPos, toast.yPos]);
            });

            it("should appear synchronously", function() {
                expect(showSpy).toHaveBeenCalled();
            });
        });

        describe("hiding", function() {
            beforeEach(function() {
                toast.close();
            });

            it("should hide", function() {
                expect(toast.el).toBe(null);
            });

            it("should disappear synchronously", function() {
                expect(destroySpy).toHaveBeenCalled();
            });

            it("should be destroyed", function() {
                expect(toast.destroyed).toBe(true);
            });
        });
    });

    describe("focus handling", function() {
        beforeEach(function() {
            win = new Ext.window.Window({
                width: 300,
                x: 10,
                y: 10,
                items: [{
                    xtype: 'textfield',
                    id: 'field1'
                }, {
                    xtype: 'textfield',
                    id: 'field2'
                }]
            });

            field1 = win.down('#field1');
            field2 = win.down('#field2');

            win.show();

            focusAndWait(field1);
        });

        // https://sencha.jira.com/browse/EXTJS-15357
        it("should not steal focus from a floater", function() {
            runs(function() {
                makeToast();
            });

            waitsForSpy(showSpy, 'show toast', 1000);

            // the toast should not take focus when it is shown
            expectFocused(field1, true);

            runs(function() {
                // change the focus to ensure we don't try to place the focus back on inputEl1
                // when the toast is destroyed
                field2.focus();
            });

            waitForFocus(field2);

            runs(function() {
                toast.close();
            });

            waitsForSpy(destroySpy, 'close toast', 1000);

            // the toast should also not attempt to return focus when it is destroyed
            expectFocused(field2, true);
        });

        it("should not steal focus from 2nd level floater", function() {
            win2 = new Ext.window.Window({
                width: 300,
                x: 50,
                y: 50,
                defaultFocus: 'field3',
                items: [{
                    xtype: 'textfield',
                    id: 'field3'
                }]
            });

            field3 = win2.down('#field3');
            win2.show();

            waitForFocus(field3, 'field3 to focus for the first time');

            runs(function() {
                makeToast();
            });

            waitForSpy(showSpy, 'show toast', 1000, 'toast to show');

            expectFocused(field3, true);

            runs(function() {
                toast.close();
            });

            waitForSpy(destroySpy, 'close toast', 1000, 'toast to close');

            expectFocused(field3, true);
        });
    });
});

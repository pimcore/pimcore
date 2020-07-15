topSuite("Ext.Editor", ['Ext.form.field.*'], function() {
    var editor, field, target;

    function makeEditor(cfg) {
        editor = new Ext.Editor(cfg);
        field = editor.field;
    }

    function makeTarget(cfg) {
        target = Ext.getBody().createChild(Ext.apply({
            tag: 'span',
            html: 'Sample Text'
        }, cfg));
    }

    afterEach(function() {
        Ext.destroy(editor, target);
        target = field = editor = null;
    });

    function startEditWithTarget(value) {
        makeTarget();

        if (arguments.length) {
            editor.startEdit(target, value);
        }
        else {
            editor.startEdit(target);
        }
    }

    function fireKeyOnField(key) {
        jasmine.fireKeyEvent(field.inputEl, 'keydown', key);
    }

    describe("field creation", function() {
        it("should create a text field by default", function() {
            makeEditor();
            expect(field.$className).toBe('Ext.form.field.Text');
        });

        it("should accept a string xtype", function() {
            makeEditor({
                field: 'datefield'
            });
            expect(field.$className).toBe('Ext.form.field.Date');
        });

        it("should accept a config without xtype and default to a text field", function() {
            makeEditor({
                field: {
                    maxLength: 10
                }
            });
            expect(field.$className).toBe('Ext.form.field.Text');
            expect(field.maxLength).toBe(10);
        });

        it("should accept an object config including xtype", function() {
            makeEditor({
                field: {
                    xtype: 'numberfield',
                    maxValue: 20
                }
            });
            expect(field.$className).toBe('Ext.form.field.Number');
            expect(field.maxValue).toBe(20);
        });
    });

    describe("getValue/setValue", function() {
        it("should get the value from the underlying field", function() {
            makeEditor();
            startEditWithTarget();
            editor.field.setValue('asdf');
            expect(editor.getValue()).toBe('asdf');
        });

        it("should set the value on the underlying field", function() {
            makeEditor();
            startEditWithTarget();
            editor.setValue('foo');
            expect(editor.getValue()).toBe('foo');
        });
    });

    describe("startEdit", function() {
        describe("basic functionality", function() {
            it("should show the editor", function() {
                makeEditor();
                startEditWithTarget();
                expect(editor.isVisible()).toBe(true);
            });

            it("should set the editing property to true", function() {
                makeEditor();
                startEditWithTarget();
                expect(editor.editing).toBe(true);
            });

            it("should focus the field", function() {
                makeEditor();
                startEditWithTarget();

                runs(function() {
                    expectFocused(field);
                });
            });

            it("should complete an existing edit when starting", function() {
                makeEditor({
                    updateEl: true
                });
                startEditWithTarget();
                editor.setValue('Foo');
                editor.startEdit(target);
                expect(target.getHtml()).toBe('Foo');
            });
        });

        describe("positioning", function() {
            it("should align to c-c as the default", function() {
                makeEditor();
                startEditWithTarget();
                expect(editor.getXY()).toEqual([0, 0]);
            });

            it("should use another alignment", function() {
                // Top left of the field aligns to the bottom right of the target
                makeEditor({
                    alignment: 'tl-br'
                });
                startEditWithTarget();
                var size = target.getSize();

                expect(editor.getXY()).toEqual([size.width, size.height]);
            });

            it("should use offsets", function() {
                makeEditor({
                    alignment: 'tl-tl',
                    offsets: [20, 30]
                });
                startEditWithTarget();
                expect(editor.getXY()).toEqual([20, 30]);
            });

            it("should use a combination of alignment & offsets", function() {
                // Top left of the field aligns to the bottom right of the target
                makeEditor({
                    alignment: 'tl-br',
                     offsets: [20, 30]
                });
                startEditWithTarget();
                var size = target.getSize();

                expect(editor.getXY()).toEqual([size.width + 20, size.height + 30]);
            });
        });

        describe("boundEl", function() {
            it("should accept an Ext.dom.Element", function() {
                makeEditor();
                makeTarget();
                editor.startEdit(target);
                expect(field.getValue()).toBe('Sample Text');
            });

            it("should accept an HtmlElement", function() {
                makeEditor();
                makeTarget();
                editor.startEdit(target.dom);
                expect(field.getValue()).toBe('Sample Text');
            });

            it("should accept an id", function() {
                makeEditor();
                makeTarget();
                editor.startEdit(target.id);
                expect(field.getValue()).toBe('Sample Text');
            });
        });

        describe("value", function() {
            it("should take the value from the element by default", function() {
                makeEditor();
                startEditWithTarget();
                expect(field.getValue()).toBe('Sample Text');
            });

            it("should use the passed value", function() {
                makeEditor();
                startEditWithTarget('Foo');
                expect(field.getValue()).toBe('Foo');
            });

            it("should retain the type of the passed value", function() {
                var d = new Date();

                makeEditor({
                    field: 'datefield'
                });
                spyOn(field, 'setValue');
                startEditWithTarget(d);
                expect(field.setValue).toHaveBeenCalledWith(d);
            });
        });

        describe("the field", function() {
            it("should not fire the change event", function() {
                makeEditor();
                var spy = jasmine.createSpy();

                field.on('change', spy);
                startEditWithTarget();
                expect(spy).not.toHaveBeenCalled();
            });

            it("should not be dirty", function() {
                makeEditor();
                startEditWithTarget();
                expect(field.isDirty()).toBe(false);
            });
        });

        describe("hideEl", function() {
            it("should hide the el with hideEl: true", function() {
                makeEditor({
                    hideEl: true
                });
                startEditWithTarget();
                expect(target.isVisible()).toBe(false);
            });

            it("should not hide the el with hideEl: false", function() {
                makeEditor({
                    hideEl: false
                });
                startEditWithTarget();
                expect(target.isVisible()).toBe(true);
            });
        });

        describe("events", function() {
            var spy;

            beforeEach(function() {
                spy = jasmine.createSpy();
            });

            afterEach(function() {
                spy = null;
            });

            it("should fire beforestartedit and pass the editor, boundEl & value", function() {
                makeEditor();
                editor.on('beforestartedit', spy);
                startEditWithTarget();
                expect(spy).toHaveBeenCalled();
                var args = spy.mostRecentCall.args;

                expect(args[0]).toBe(editor);
                expect(args[1]).toBe(target);
                expect(args[2]).toBe('Sample Text');
            });

            it("should fire startedit and pass the editor, boundEl & value", function() {
                makeEditor();
                editor.on('startedit', spy);
                startEditWithTarget();
                expect(spy).toHaveBeenCalled();
                var args = spy.mostRecentCall.args;

                expect(args[0]).toBe(editor);
                expect(args[1]).toBe(target);
                expect(args[2]).toBe('Sample Text');
            });

            it("should not show or set to editing if it returns false", function() {
                var editSpy = jasmine.createSpy();

                makeEditor();
                makeTarget();
                editor.on('beforestartedit', spy.andReturn(false));
                editor.on('startedit', editSpy);
                editor.startEdit(target);
                expect(editor.isVisible()).toBe(false);
                expect(editor.editing).toBe(false);
                expect(editSpy).not.toHaveBeenCalled();
            });

            it("should allow the value to be changed in beforestartedit", function() {
                spy.andCallFake(function(editor) {
                    editor.context = editor.context || {};
                    editor.context.value = 'blergo';
                });

                makeEditor();
                makeTarget();

                editor.on('beforestartedit', spy);

                editor.startEdit(target);

                expect(editor.field.getValue()).toBe('blergo');
            });
        });
    });

    describe("completeEdit", function() {
        it("should not cause an exception if not editing", function() {
            makeEditor();
            expect(function() {
                editor.completeEdit();
            }).not.toThrow();
        });

        it("should hide the editor and set editing to false", function() {
            makeEditor();
            startEditWithTarget();
            editor.completeEdit();
            expect(editor.isVisible()).toBe(false);
            expect(editor.editing).toBe(false);
        });

        describe("validity", function() {
            describe("with revertInvalid: false", function() {
                it("should not complete the edit if the field is not valid", function() {
                    makeEditor({
                        revertInvalid: false,
                        field: {
                            allowBlank: false
                        }
                    });
                    startEditWithTarget('');
                    editor.completeEdit();
                    expect(editor.editing).toBe(true);
                    expect(editor.isVisible()).toBe(true);
                    expect(editor.getValue()).toBe('');
                });
            });

            describe("with revertInvalid: true", function() {
                it("should cancel the edit if the field is not valid", function() {
                    makeEditor({
                        revertInvalid: true,
                        field: {
                            allowBlank: false
                        }
                    });
                    startEditWithTarget();
                    field.setValue('');
                    editor.completeEdit();
                    expect(editor.getValue()).toBe('Sample Text');
                    expect(editor.editing).toBe(false);
                    expect(editor.isVisible()).toBe(false);
                });
            });
        });

        describe("hideEl", function() {
            it("should not show the boundEl if complete is vetoed with revertInvalid: false", function() {
                makeEditor({
                    revertInvalid: false,
                    field: {
                        allowBlank: false
                    }
                });
                startEditWithTarget('');
                editor.completeEdit();
                expect(target.isVisible()).toBe(false);
            });

            it("should show the boundEl if complete is vetoed with revertInvalid: true", function() {
                makeEditor({
                    revertInvalid: true,
                    field: {
                        allowBlank: false
                    }
                });
                startEditWithTarget('');
                editor.completeEdit();
                expect(target.isVisible()).toBe(true);
            });

            it("should show the boundEl if complete is successful", function() {
                makeEditor({
                    revertInvalid: true,
                    field: {
                        allowBlank: false
                    }
                });
                startEditWithTarget('Foo');
                editor.completeEdit();
                expect(target.isVisible()).toBe(true);
            });
        });

        describe("remainVisible", function() {
            it("should leave the editor visible with remainVisible", function() {
                makeEditor();
                startEditWithTarget();
                editor.completeEdit(true);
                expect(editor.isVisible()).toBe(true);
                expect(editor.editing).toBe(false);
            });

            it("should leave the editor visible with remainVisible when the edit is cancelled for being invalid", function() {
                makeEditor({
                    revertInvalid: true,
                    field: {
                        allowBlank: true
                    }
                });
                startEditWithTarget('');
                editor.completeEdit(true);
                expect(editor.isVisible()).toBe(true);
                expect(editor.editing).toBe(false);
            });
        });

        describe("updateEl", function() {
            it("should set the html if the boundEl with updateEl: true", function() {
                makeEditor({
                    updateEl: true
                });
                startEditWithTarget('Foo');
                editor.completeEdit();
                expect(target.getHtml()).toBe('Foo');
            });

            it("should not set the html if the boundEl with updateEl: false", function() {
                makeEditor({
                    updateEl: false
                });
                startEditWithTarget('Foo');
                editor.completeEdit();
                expect(target.getHtml()).toBe('Sample Text');
            });
        });

        // FF randomly errors out on focus test in the test runner
        (Ext.isGecko ? xdescribe : describe)("allowBlur", function() {
            it("should not complete on blur with allowBlur: false", function() {
                makeEditor({
                    allowBlur: false
                });
                startEditWithTarget();
                spyOn(editor, 'completeEdit').andCallThrough();
                waitsFor(function() {
                    return field.hasFocus;
                }, "Field never focused");
                runs(function() {
                    // Programmatic blur fails on IEs. Focus then remove an input field
                    Ext.getBody().createChild({ tag: 'input', type: 'text' }).focus().remove();
                });
                waitsFor(function() {
                    return !field.hasFocus;
                }, "Field never blurred");
                runs(function() {
                    expect(editor.completeEdit).not.toHaveBeenCalled();
                });
            });

            it("should complete on blur with allowBlur: true", function() {
                makeEditor({
                    allowBlur: true
                });
                startEditWithTarget();
                spyOn(editor, 'completeEdit').andCallThrough();
                waitsFor(function() {
                    return field.hasFocus;
                }, "Field never focused");
                runs(function() {
                    // Programmatic blur fails on IEs. Focus then remove an input field
                    Ext.getBody().createChild({ tag: 'input', type: 'text' }).focus().remove();
                });
                waitsFor(function() {
                    return !field.hasFocus;
                }, "Field never blurred");
                runs(function() {
                    expect(editor.completeEdit).toHaveBeenCalled();
                });
            });
        });

        // FF randomly errors out on focus test in the test runner
        (Ext.isGecko ? xdescribe : describe)("completeOnEnter", function() {
            it("should not complete on enter with completeOnEnter: false", function() {
                makeEditor({
                    completeOnEnter: false
                });
                editor.specialKeyDelay = 0;
                startEditWithTarget();
                spyOn(editor, 'completeEdit').andCallThrough();
                waitsFor(function() {
                    return field.hasFocus;
                }, "Field never focused");
                runs(function() {
                    fireKeyOnField(Ext.event.Event.ENTER);
                    expect(editor.completeEdit).not.toHaveBeenCalled();
                });
            });

            it("should complete on enter with completeOnEnter: true", function() {
                makeEditor({
                    completeOnEnter: true
                });
                editor.specialKeyDelay = 0;
                startEditWithTarget();
                spyOn(editor, 'completeEdit').andCallThrough();
                waitsFor(function() {
                    return field.hasFocus;
                }, "Field never focused");
                runs(function() {
                    fireKeyOnField(Ext.event.Event.ENTER);
                    expect(editor.completeEdit).toHaveBeenCalled();
                });
            });
        });

        describe("events", function() {
            var spy;

            beforeEach(function() {
                spy = jasmine.createSpy();
            });

            afterEach(function() {
                spy = null;
            });

            it("should fire beforecomplete & pass the editor, value & start value", function() {
                makeEditor();
                editor.on('beforecomplete', spy);
                startEditWithTarget();
                field.setValue('ASDF');
                editor.completeEdit();
                expect(spy).toHaveBeenCalled();
                var args = spy.mostRecentCall.args;

                expect(args[0]).toBe(editor);
                expect(args[1]).toBe('ASDF');
                expect(args[2]).toBe('Sample Text');
            });

            it("should fire the complete event & pass the editor, value & start value", function() {
                makeEditor();
                editor.on('complete', spy);
                startEditWithTarget();
                field.setValue('ASDF');
                editor.completeEdit();
                expect(spy).toHaveBeenCalled();
                var args = spy.mostRecentCall.args;

                expect(args[0]).toBe(editor);
                expect(args[1]).toBe('ASDF');
                expect(args[2]).toBe('Sample Text');
            });

            it("should not fire beforecomplete/complete if not editing", function() {
                makeEditor();
                editor.on('beforecomplete', spy);
                editor.on('complete', spy);
                editor.completeEdit();
                expect(spy).not.toHaveBeenCalled();
            });

            describe("vetoing beforecomplete", function() {
                it("should not fire complete", function() {
                    var completeSpy = jasmine.createSpy();

                    makeEditor();
                    editor.on('beforecomplete', spy.andReturn(false));
                    editor.on('complete', completeSpy);
                    startEditWithTarget('Value');
                    editor.completeEdit();
                    expect(completeSpy).not.toHaveBeenCalled();
                });

                it("should not update the boundEl", function() {
                    makeEditor();
                    editor.on('beforecomplete', spy.andReturn(false));
                    startEditWithTarget('Value');
                    editor.completeEdit();
                    expect(target.getHtml()).toBe('Sample Text');
                });
            });

            describe("invalid values", function() {
                it("should not fire beforecomplete/complete if the value is invalid with revertInvalid: false", function() {
                    makeEditor({
                        revertInvalid: false,
                        field: {
                            allowBlank: false
                        }
                    });
                    editor.on('beforecomplete', spy);
                    editor.on('complete', spy);
                    startEditWithTarget('');
                    editor.completeEdit();
                    expect(spy).not.toHaveBeenCalled();
                });

                it("should not fire beforecomplete/complete if the value is invalid with revertInvalid: true", function() {
                    makeEditor({
                        revertInvalid: true,
                        field: {
                            allowBlank: false
                        }
                    });
                    editor.on('beforecomplete', spy);
                    editor.on('complete', spy);
                    startEditWithTarget('');
                    editor.completeEdit();
                    expect(spy).not.toHaveBeenCalled();
                });
            });

            describe("ignoreNoChange", function() {
                it("should not fire beforecomplete/complete if the value did not change with ignoreNoChange: true", function() {
                    makeEditor({
                        ignoreNoChange: true
                    });
                    editor.on('beforecomplete', spy);
                    editor.on('complete', spy);
                    startEditWithTarget();
                    editor.completeEdit();
                    expect(spy).not.toHaveBeenCalled();
                });

                it("should fire beforecomplete/complete if the value did not change with ignoreNoChange: true", function() {
                    var completeSpy = jasmine.createSpy();

                    makeEditor({
                        ignoreNoChange: false
                    });
                    editor.on('beforecomplete', spy);
                    editor.on('complete', completeSpy);
                    startEditWithTarget();
                    editor.completeEdit();
                    expect(spy).toHaveBeenCalled();
                    expect(completeSpy).toHaveBeenCalled();
                });
            });
        });
    });

    describe("cancelEdit", function() {
        it("should not cause an error when not editing", function() {
            makeEditor();
            expect(function() {
                editor.cancelEdit();
            }).not.toThrow();
        });

        it("should hide the editor & set editing to false", function() {
            makeEditor();
            startEditWithTarget();
            editor.cancelEdit();
            expect(editor.isVisible()).toBe(false);
            expect(editor.editing).toBe(false);
        });

        it("should set the original value on the field and not fire the change event", function() {
            makeEditor();
            startEditWithTarget();
            editor.setValue('Foo');
            var spy = jasmine.createSpy();

            field.on('change', spy);
            editor.cancelEdit();
            expect(editor.getValue()).toBe('Sample Text');
            expect(spy).not.toHaveBeenCalled();
        });

        describe("with updateEl", function() {
            it("should not update the boundEl", function() {
                makeEditor();
                startEditWithTarget();
                editor.setValue('Foo');
                editor.cancelEdit();
                expect(target.getHtml()).toBe('Sample Text');
            });
        });

        // FF randomly errors out on focus test in the test runner
        (Ext.isGecko ? xdescribe : describe)("cancelOnEsc", function() {
            it("should not cancel on esc key with cancelOnEsc: false", function() {
                makeEditor({
                    cancelOnEsc: false
                });
                editor.specialKeyDelay = 0;
                startEditWithTarget();
                spyOn(editor, 'cancelEdit').andCallThrough();
                waitsFor(function() {
                    return field.hasFocus;
                }, "Field never focused");
                runs(function() {
                    fireKeyOnField(Ext.event.Event.ESC);
                    expect(editor.cancelEdit).not.toHaveBeenCalled();
                });
            });

            it("should cancel on esc key with cancelOnEsc: true", function() {
                makeEditor({
                    cancelOnEsc: true
                });
                editor.specialKeyDelay = 0;
                startEditWithTarget();
                spyOn(editor, 'cancelEdit').andCallThrough();
                waitsFor(function() {
                    return field.hasFocus;
                }, "Field never focused");
                runs(function() {
                    fireKeyOnField(Ext.event.Event.ESC);
                    expect(editor.cancelEdit).toHaveBeenCalled();
                });
            });
        });

        describe("hideEl", function() {
            it("should show the boundEl", function() {
                makeEditor();
                startEditWithTarget();
                editor.cancelEdit();
                expect(target.isVisible()).toBe(true);
            });
        });

        describe("remainVisible", function() {
            it("should leave the editor visible with remainVisible", function() {
                makeEditor();
                startEditWithTarget();
                editor.cancelEdit(true);
                expect(editor.isVisible()).toBe(true);
            });
        });

        describe("events", function() {
            it("should fire canceledit and pass the editor, current value & start value", function() {
                var spy = jasmine.createSpy();

                makeEditor();
                startEditWithTarget();
                editor.setValue('foo');
                editor.on('canceledit', spy);
                editor.cancelEdit();
                expect(spy).toHaveBeenCalled();
                var args = spy.mostRecentCall.args;

                expect(args[0]).toBe(editor);
                expect(args[1]).toBe('foo');
                expect(args[2]).toBe('Sample Text');
            });

            it("should not fire canceledit if not editing", function() {
                var spy = jasmine.createSpy();

                makeEditor();
                editor.on('canceledit', spy);
                editor.cancelEdit();
                expect(spy).not.toHaveBeenCalled();
            });
        });
    });
});

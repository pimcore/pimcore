/* global expect, Ext, jasmine, spyOn */

topSuite("Ext.panel.Tool", function() {
    var describeNotTouch = jasmine.supportsTouch ? xdescribe : describe,
        tool, el;

    function makeTool(cfg) {
        cfg = Ext.apply({
            renderTo: Ext.getBody()
        }, cfg);

        tool = new Ext.panel.Tool(cfg);
        el = tool.el;

        return tool;
    }

    afterEach(function() {
        Ext.destroy(tool);
        tool = null;
    });

    describe("ARIA attributes", function() {
        describe("rendered with no tooltip", function() {
            beforeEach(function() {
                makeTool({
                    type: 'collapse'
                });
            });

            it("should have el as ariaEl", function() {
                expect(tool.ariaEl).toBe(tool.el);
            });

            it("should have button role", function() {
                expect(tool).toHaveAttr('role', 'button');
            });

            it("should not have title", function() {
                expect(tool).not.toHaveAttr('title');
            });

            it("should not have aria-label", function() {
                expect(tool).not.toHaveAttr('aria-label');
            });

            describe("setTooltip", function() {
                describe("default type", function() {
                    beforeEach(function() {
                        tool.setTooltip('foo');
                    });

                    it("should set aria-label", function() {
                        expect(tool).toHaveAttr('aria-label', 'foo');
                    });

                    it("should not set title", function() {
                        expect(tool).not.toHaveAttr('title');
                    });
                });

                describe("forced type", function() {
                    beforeEach(function() {
                        tool.setTooltip('bar', 'title');
                    });

                    it("should set title", function() {
                        expect(tool).toHaveAttr('title', 'bar');
                    });

                    it("should not set aria-label", function() {
                        expect(tool).not.toHaveAttr('aria-label');
                    });
                });
            });
        });

        describe("rendered with tooltip", function() {
            beforeEach(function() {
                makeTool({
                    type: 'expand',
                    tooltip: 'frob'
                });
            });

            it("should set aria-label", function() {
                expect(tool).toHaveAttr('aria-label', 'frob');
            });

            it("should not set title", function() {
                expect(tool).not.toHaveAttr('title');
            });
        });
    });

    describe("interaction", function() {
        var callbackSpy, handlerSpy, clickSpy, scope,
            toolOwner, ownerCt;

        beforeEach(function() {
            callbackSpy = jasmine.createSpy('callback');
            handlerSpy = jasmine.createSpy('handler');
            clickSpy = jasmine.createSpy('click');
            scope = {};
            toolOwner = {};
            ownerCt = {
                getInherited: function() {
                    return {};
                }
            };

            makeTool({
                type: 'close',
                callback: callbackSpy,
                handler: handlerSpy,
                scope: scope,
                listeners: {
                    click: clickSpy
                },
                renderTo: undefined
            });

            spyOn(tool, 'onClick').andCallThrough();
            tool.render(Ext.getBody());
            el = tool.el;

            tool.toolOwner = toolOwner;
        });

        afterEach(function() {
            callbackSpy = handlerSpy = clickSpy = scope = null;
            toolOwner = ownerCt = null;
        });

        describe("pointer", function() {
            describeNotTouch("mouseover", function() {
                beforeEach(function() {
                    jasmine.fireMouseEvent(el, 'mouseover', 1, 1);
                });

                it("should add toolOverCls on over", function() {
                    expect(el.hasCls(tool.toolOverCls)).toBe(true);
                });

                it("should remove toolOverCls on out", function() {
                    jasmine.fireMouseEvent(el, 'mouseout', 1, 1);

                    expect(el.hasCls(tool.toolOveCls)).toBe(false);
                });
            });

            describe("mousedown", function() {
                beforeEach(function() {
                    jasmine.fireMouseEvent(el, 'mousedown', 1, 1);
                });

                afterEach(function() {
                    jasmine.fireMouseEvent(el, 'mouseup', 1, 1);
                });

                it("should add toolPressedCls", function() {
                    expect(el.hasCls(tool.toolPressedCls)).toBe(true);
                });

                it("should prevent focusing the tool", function() {
                    expect(tool.hasFocus).toBe(false);
                });
            });

            describe("click", function() {
                var cArgs, cScope, hArgs, hScope, eArgs;

                function clickTool(t) {
                    t = t || tool;

                    jasmine.fireMouseEvent(t.el, 'click', 1, 1);

                    cArgs = callbackSpy.mostRecentCall.args;
                    cScope = callbackSpy.mostRecentCall.scope;

                    hArgs = handlerSpy.mostRecentCall.args;
                    hScope = handlerSpy.mostRecentCall.scope;

                    eArgs = clickSpy.mostRecentCall.args;
                }

                describe("enabled", function() {
                    beforeEach(function() {
                        tool.ownerCt = ownerCt;
                        clickTool();
                    });

                    afterEach(function() {
                        cArgs = cScope = hArgs = hScope = eArgs = null;
                    });

                    it("should remove toolPressedCls", function() {
                        expect(el.hasCls(tool.toolPressedCls)).toBe(false);
                    });

                    describe("stopEvent", function() {
                        it("should stop the event by default", function() {
                            var e = tool.onClick.mostRecentCall.args[0];

                            expect(e.stopped).toBe(true);
                        });

                        it("should not stop event when stopEvent is false", function() {
                            tool.stopEvent = false;

                            clickTool(tool);

                            var e = tool.onClick.mostRecentCall.args[0];

                            expect(!!e.stopped).toBe(false);
                        });
                    });

                    describe("callback", function() {
                        beforeEach(function() {
                            tool.handler = null;
                            clickTool();
                        });

                        it("should fire", function() {
                            expect(callbackSpy).toHaveBeenCalled();
                        });

                        it("should fire in the specified scope", function() {
                            expect(cScope).toBe(scope);
                        });

                        it("should pass event as the last argument", function() {
                            var e = cArgs.pop();

                            expect(e.isEvent).toBe(true);
                        });

                        it("should pass expected arguments with toolOwner", function() {
                            // Remove event
                            cArgs.pop();

                            expect(cArgs).toEqual([toolOwner, tool]);
                        });

                        it("should pass expected arguments w/o toolOwner", function() {
                            tool.toolOwner = null;
                            clickTool(tool);

                            cArgs.pop();

                            expect(cArgs).toEqual([ownerCt, tool]);
                        });
                    });

                    describe("handler", function() {
                        it("should fire", function() {
                            expect(handlerSpy).toHaveBeenCalled();
                        });

                        it("should fire in the specified scope", function() {
                            expect(hScope).toBe(scope);
                        });

                        it("should pass event as first argument", function() {
                            var e = hArgs[0];

                            expect(e.isEvent).toBe(true);
                        });

                        it("should pass expected arguments", function() {
                            // Remove the event
                            hArgs.shift();

                            expect(hArgs).toEqual([el.dom, ownerCt, tool]);
                        });
                    });

                    describe("click event", function() {
                        it("should fire", function() {
                            expect(clickSpy).toHaveBeenCalled();
                        });

                        it("should pass the tool as first argument", function() {
                            expect(eArgs[0]).toBe(tool);
                        });

                        it("should pass event as the second argument", function() {
                            expect(eArgs[1].isEvent).toBe(true);
                        });

                        it("should pass toolOwner as the third argument", function() {
                            expect(eArgs[2]).toBe(toolOwner);
                        });

                        it("should pass ownerCt as the third argument w/o toolOwner", function() {
                            tool.toolOwner = null;
                            clickTool(tool);

                            expect(eArgs[2]).toBe(ownerCt);
                        });
                    });
                });

                describe("disabled", function() {
                    beforeEach(function() {
                        tool.disable();
                        clickTool();
                    });

                    it("should not fire callback", function() {
                        expect(callbackSpy).not.toHaveBeenCalled();
                    });

                    it("should not fire handler", function() {
                        expect(handlerSpy).not.toHaveBeenCalled();
                    });

                    it("should not fire click event", function() {
                        expect(clickSpy).not.toHaveBeenCalled();
                    });

                    it("should not stop event by default", function() {
                        var e = tool.onClick.mostRecentCall.args[0];

                        expect(!!e.stopped).toBe(false);
                    });
                });
            });
        });

        describe("keyboard", function() {
            var pressKey = jasmine.asyncPressKey;

            it("should be tabbable by default", function() {
                expect(el.isTabbable()).toBe(true);
            });

            describe("Space key", function() {
                beforeEach(function() {
                    pressKey(tool, 'space');
                });

                it("should call onClick when Space key is pressed", function() {
                    expect(tool.onClick).toHaveBeenCalled();
                });

                it("should stop event by default", function() {
                    var e = tool.onClick.mostRecentCall.args[0];

                    expect(e.stopped).toBe(true);
                });
            });

            describe("Enter key", function() {
                beforeEach(function() {
                    pressKey(tool, 'enter');
                });

                it("should call onClick when Enter key is pressed", function() {
                    expect(tool.onClick).toHaveBeenCalled();
                });

                it("should stop the event by default", function() {
                    var e = tool.onClick.mostRecentCall.args[0];

                    expect(e.stopped).toBe(true);
                });
            });
        });
    });

    describe('type', function() {
        describe("before render", function() {
            beforeEach(function() {
                makeTool({
                    type: 'expand',
                    renderTo: null
                });
            });

            it('should switch from using type to glyph', function() {
                // Hex 48 is "H". Must switch to using that with no background image
                tool.setGlyph('x48@FontAwesome');

                tool.render(Ext.getBody());

                expect(tool.toolEl).not.toHaveCls('x-tool-expand');
                expect(tool.toolEl.getStyle('font-family')).toBe('FontAwesome');
                expect(tool.toolEl.dom).hasHTML('H');
            });

            it('should switch from using type to iconCls', function() {
                tool.setIconCls('foo-icon-cls');

                tool.render(Ext.getBody());

                // toolEl must use the iconCls
                expect(tool.toolEl).toHaveCls('foo-icon-cls');
                expect(tool.toolEl).not.toHaveCls('x-tool-expand');
            });

            it("should be able to switch to another type", function() {
                tool.setType('print');

                tool.render(Ext.getBody());

                expect(tool.toolEl).toHaveCls('x-tool-print');
                expect(tool.toolEl).not.toHaveCls('x-tool-expand');
            });
        });

        describe("after render", function() {
            beforeEach(function() {
                makeTool({
                    type: 'expand'
                });
                // Must start with type's class
                expect(tool.toolEl).toHaveCls('x-tool-expand');
            });

            it('should switch from using type to glyph', function() {
                // Hex 48 is "H". Must switch to using that with no background image
                tool.setGlyph('x48@FontAwesome');
                expect(tool.toolEl).not.toHaveCls('x-tool-expand');
                expect(tool.toolEl.getStyle('font-family')).toBe('FontAwesome');
                expect(tool.toolEl.dom).hasHTML('H');
            });

            it('should switch from using type to iconCls', function() {
                tool.setIconCls('foo-icon-cls');

                // toolEl must use the iconCls
                expect(tool.toolEl).toHaveCls('foo-icon-cls');
                expect(tool.toolEl).not.toHaveCls('x-tool-expand');
            });

            it("should be able to switch to another type", function() {
                tool.setType('print');
                expect(tool.toolEl).toHaveCls('x-tool-print');
                expect(tool.toolEl).not.toHaveCls('x-tool-expand');
            });
        });
    });

    describe('iconCls', function() {
        describe("before render", function() {
            beforeEach(function() {
                makeTool({
                    iconCls: 'foo-icon-cls',
                    renderTo: null
                });
            });

            it('should switch from using iconCls to glyph', function() {
                // Hex 48 is "H". Must switch to using that with no background image
                tool.setGlyph('x48@FontAwesome');

                tool.render(Ext.getBody());

                expect(tool.toolEl).not.toHaveCls('foo-icon-cls');
                expect(tool.toolEl.getStyle('font-family')).toBe('FontAwesome');
                expect(tool.toolEl.dom).hasHTML('H');
            });

            it('should switch from using iconCls to type', function() {
                tool.setType('expand');

                tool.render(Ext.getBody());

                expect(tool.toolEl).not.toHaveCls('foo-icon-cls');

                // toolEl must use the type's class
                expect(tool.toolEl).toHaveCls('x-tool-expand');
            });

            it("should switch classes", function() {
                tool.setIconCls('bar-icon-cls');

                tool.render(Ext.getBody());

                expect(tool.toolEl).not.toHaveCls('x-tool-img');
                expect(tool.toolEl).toHaveCls('bar-icon-cls');
                expect(tool.toolEl).not.toHaveCls('foo-icon-cls');
            });
        });

        describe("after render", function() {
            beforeEach(function() {
                makeTool({
                    iconCls: 'foo-icon-cls'
                });

                // Must start with iconCls
                expect(tool.toolEl).toHaveCls('foo-icon-cls');
            });

            it('should switch from using iconCls to glyph', function() {
                // Hex 48 is "H". Must switch to using that with no background image
                tool.setGlyph('x48@FontAwesome');

                expect(tool.toolEl).not.toHaveCls('foo-icon-cls');
                expect(tool.toolEl.getStyle('font-family')).toBe('FontAwesome');
                expect(tool.toolEl.dom).hasHTML('H');
            });

            it('should switch from using iconCls to type', function() {
                tool.setType('expand');

                expect(tool.toolEl).not.toHaveCls('foo-icon-cls');

                // toolEl must use the type's class
                expect(tool.toolEl).toHaveCls('x-tool-expand');
            });

            it("should switch classes", function() {
                tool.setIconCls('bar-icon-cls');

                expect(tool.toolEl).toHaveCls('bar-icon-cls');
                expect(tool.toolEl).not.toHaveCls('foo-icon-cls');
            });
        });
    });

    describe('glyph', function() {
        describe("before render", function() {
            beforeEach(function() {
                makeTool({
                    glyph: 'x48@FontAwesome',
                    renderTo: null
                });
            });

            it('should switch from using glyph to type', function() {
                tool.setType('expand');

                tool.render(Ext.getBody());

                // No glyph character
                expect(tool.toolEl.dom).hasHTML('');

                // toolEl must use the type's class
                expect(tool.toolEl).toHaveCls('x-tool-expand');
            });

            it('should switch from using glyph to iconCls', function() {
                tool.setIconCls('foo-icon-cls');

                tool.render(Ext.getBody());

                // No glyph character
                expect(tool.toolEl.dom.innerHTML).toBe('');

                // toolEl must use the iconCls
                expect(tool.toolEl).toHaveCls('foo-icon-cls');
            });

            it('should switch glyphs', function() {
                tool.setGlyph('x49@FontAwesome');

                tool.render(Ext.getBody());

                expect(tool.toolEl.getStyle('font-family')).toBe('FontAwesome');
                expect(tool.toolEl.dom).hasHTML('I');
            });
        });

        describe("after render", function() {
            beforeEach(function() {
                makeTool({
                    glyph: 'x48@FontAwesome'
                });
                // Hex 48 is "H". Must switch to using that with no background image
                expect(tool.toolEl.getStyle('font-family')).toBe('FontAwesome');
                expect(tool.toolEl.dom).hasHTML('H');
            });

            it('should switch from using glyph to type', function() {
                tool.setType('expand');

                // No glyph character
                expect(tool.toolEl.dom).hasHTML('');

                // toolEl must use the type's class
                expect(tool.toolEl).toHaveCls('x-tool-expand');
            });

            it('should switch from using glyph to iconCls', function() {
                tool.setIconCls('foo-icon-cls');

                // No glyph character
                expect(tool.toolEl.dom.innerHTML).toBe('');

                // toolEl must use the iconCls
                expect(tool.toolEl).toHaveCls('foo-icon-cls');
            });

            it('should switch glyphs', function() {
                tool.setGlyph('x49@FontAwesome');

                expect(tool.toolEl.getStyle('font-family')).toBe('FontAwesome');
                expect(tool.toolEl.dom).hasHTML('I');
            });
        });
    });
});

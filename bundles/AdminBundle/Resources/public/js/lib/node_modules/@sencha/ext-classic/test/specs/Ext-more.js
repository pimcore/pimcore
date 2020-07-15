topSuite("Ext-more", ['Ext.dom.Element', 'Ext.app.Application'], function() {
    describe("Ext.id", function() {
        var el;

        describe("if element passed as first argument is different of document or window", function() {
            beforeEach(function() {
                el = document.createElement("div");
                document.body.appendChild(el);
            });

            afterEach(function() {
                Ext.getBody().dom.removeChild(el);
            });

            it("should generate an unique id for the element with default prefix ext-element-", function() {
                expect(Ext.id(el)).toEqual("ext-element-" + Ext.idSeed);
            });

            it("should generate an unique id for the element with custom prefix", function() {
                var prefix = "nico-yhwh";

                expect(Ext.id(el, prefix)).toEqual(prefix + Ext.idSeed);
            });

            it("should not override existing id", function() {
                var id = "unchanged";

                el.id = id;
                expect(Ext.id(el)).toEqual(id);
            });
        });
    });

    describe("Ext.getBody", function() {
        it("should return current document body as an Ext.Element", function() {
            expect(Ext.getBody()).toEqual(Ext.get(document.body));
        });
    });

    describe("Ext.getHead", function() {
        it("should return current document head as an Ext.Element", function() {
            expect(Ext.getHead()).toEqual(Ext.get(document.getElementsByTagName("head")[0]));
        });
    });

    describe("Ext.getDoc", function() {
        it("should return the current HTML document object as an Ext.element", function() {
            expect(Ext.getDoc()).toEqual(Ext.get(document));
        });
    });

    if (Ext.Component) {
        describe("Ext.getCmp", function() {
            it("should return a component", function() {
                var cmp = new Ext.Component({ id: 'foobar' });

                expect(Ext.getCmp('foobar')).toBe(cmp);
                cmp.destroy();
            });
        });
    }

    if (!Ext.isWindows && !Ext.isMac && !Ext.isLinux) {
        describe("Ext.dom.Element.getOrientation", function() {
            it("should return the current orientation of the mobile device", function() {
                if (window.innerHeight <= window.innerWidth) {
                    expect(Ext.dom.Element.getOrientation()).toEqual("landscape");
                }
                else {
                    expect(Ext.dom.Element.getOrientation()).toEqual("portrait");
                }
            });
        });
    }

    describe("Ext.destroy", function() {
        var o1, o2, o3;

        beforeEach(function() {
            o1 = jasmine.createSpyObj("o1", ["destroy"]);

            o2 = jasmine.createSpyObj("o2", ["destroy"]);

            o3 = jasmine.createSpyObj("o3", ["dest"]);

        });

        it("should destroy an object", function() {
            Ext.destroy(o1);

            expect(o1.destroy).toHaveBeenCalled();
        });

        it("should not destroy an object without a destroy method", function() {
            Ext.destroy(o3);

            expect(o3.dest).not.toHaveBeenCalled();
        });

        it("should destroy an array of objects", function() {
            Ext.destroy([o1, o2, o3]);

            expect(o1.destroy).toHaveBeenCalled();
            expect(o2.destroy).toHaveBeenCalled();
            expect(o3.dest).not.toHaveBeenCalled();
        });

        it("should destroy multiple objects", function() {
            Ext.destroy(o1, o2, o3);

            expect(o1.destroy).toHaveBeenCalled();
            expect(o2.destroy).toHaveBeenCalled();
            expect(o3.dest).not.toHaveBeenCalled();
        });

        it("should remove dom if object is an Ext.element", function() {
            var el = Ext.getBody().createChild({ id: "to_destroy" });

            Ext.destroy(el);

            expect(Ext.fly("to_destroy")).toBeNull();
        });
    });

    describe("Ext.urlAppend", function() {
        var url = "http://example.com/";

        it("should manage question mark", function() {
            expect(Ext.urlAppend(url, "test=1")).toEqual("http://example.com/?test=1");
        });

        it("should manage ampersand", function() {
            expect(Ext.urlAppend(url + "?test=1", "foo=2")).toEqual("http://example.com/?test=1&foo=2");
        });

        it("should return directly url if content is empty", function() {
            expect(Ext.urlAppend(url)).toEqual(url);
        });
    });

    describe("Ext.getDom", function() {
        var el1;

        beforeEach(function() {
            el1 = Ext.getBody().createChild({ id: "elone" });
        });

        afterEach(function() {
            el1.destroy();
        });

        it("should return a dom element if an Ext.element is passed as first argument", function() {
            expect(Ext.getDom(el1)).toEqual(el1.dom);
        });

        it("should return a dom element if the string (id) passed as first argument", function() {
            expect(Ext.getDom("elone")).toEqual(el1.dom);
        });
    });

    describe("Ext.removeNode", function() {
        var el, id, dom;

        beforeEach(function() {
            el = Ext.getBody().createChild({
                tag: 'span',
                html: 'foobar'
            });

            id = el.id;
            dom = el.dom;
        });

        afterEach(function() {
            el = id = dom = null;
        });

        if (Ext.isIE8) {
            it("should schedule element for garbage collection", function() {
                var queue = Ext.Element.destroyQueue,
                    len = queue.length;

                Ext.removeNode(dom);

                expect(queue.length).toBe(len + 1);
                expect(queue[len]).toBe(dom);
            });

            it("should finally destroy the element after a timeout", function() {
                runs(function() {
                    Ext.removeNode(dom);
                });

                // The timeout is hardcoded in Element override
                waits(32);

                runs(function() {
                    expect(dom.parentNode).toBeFalsy();
                });
            });
        }
        else {
            it("should remove a dom element from document", function() {
                Ext.removeNode(dom);
                expect(dom.parentNode).toBeFalsy();
            });
        }

        it("should delete the cache reference", function() {
            expect(Ext.cache[id]).toBeDefined();
            Ext.removeNode(el.dom);
            expect(Ext.cache[id]).toBeUndefined();
        });

        it("should remove all listeners from the dom element", function() {
            var listener = jasmine.createSpy();

            el.on('mouseup', listener);
            Ext.removeNode(dom);
            jasmine.fireMouseEvent(document, 'mousedown');
            jasmine.fireMouseEvent(dom, 'mouseup');
            expect(listener).not.toHaveBeenCalled();
            jasmine.fireMouseEvent(document, 'mouseup');
        });
    });

    describe("Ext.addBehaviors", function() {
        var listener, span1, span2, div1;

        beforeEach(function() {
            span1 = Ext.getBody().createChild({
                tag: 'span'
            });

            span2 = Ext.getBody().createChild({
                tag: 'span'
            });

            div1 = Ext.getBody().createChild({
                cls: 'foo'
            });

            listener = jasmine.createSpy();
        });

        afterEach(function() {
            span1.destroy();
            span2.destroy();
            div1.destroy();
        });

        it("should apply event listeners to elements by selectors", function() {
            Ext.addBehaviors({
                'span @mouseup': listener
            });

            // Touch platforms won't fire a touch end without a touch start.
            jasmine.fireMouseEvent(span1.dom, 'mousedown');
            jasmine.fireMouseEvent(span1.dom, 'mouseup');
            jasmine.fireMouseEvent(span2.dom, 'mousedown');
            jasmine.fireMouseEvent(span2.dom, 'mouseup');
            jasmine.fireMouseEvent(div1.dom, 'mousedown');
            jasmine.fireMouseEvent(div1.dom, 'mouseup');

            expect(listener.calls.length).toEqual(2);
        });

        it("should manage multiple selectors", function() {
            Ext.addBehaviors({
                'span, div.foo @mouseup': listener
            });
            // Touch platforms won't fire a touch end without a touch start.
            jasmine.fireMouseEvent(span1.dom, 'mousedown');
            jasmine.fireMouseEvent(span1.dom, 'mouseup');
            jasmine.fireMouseEvent(span2.dom, 'mousedown');
            jasmine.fireMouseEvent(span2.dom, 'mouseup');
            jasmine.fireMouseEvent(div1.dom, 'mousedown');
            jasmine.fireMouseEvent(div1.dom, 'mouseup');

            expect(listener.calls.length).toEqual(3);
        });
    });

    xdescribe("Ext.getScrollBarWidth", function() {
        it("should return a number between 10 and 40 (we assume that document is loaded)", function() {
            expect(Ext.getScrollBarWidth() > 10).toBe(true);
            expect(Ext.getScrollBarWidth() < 40).toBe(true);
        });
    });

    describe('Ext.copyToIf', function() {
        it('should not overwrite defined properties', function() {
            var dest = {
                a: 1,
                b: undefined
            };

            Ext.copyToIf(dest, {
                a: 2,
                b: 3,
                c: 4
            }, 'a,b,c');
            expect(dest.a).toBe(1);

            // Test the bug in the deprecated copyToIf.
            // If the property existed but was undefined, it was overwritten
            expect(dest.b).toBe(3);

            // Test valid copying
            expect(dest.c).toBe(4);
        });
    });

    describe('Ext.copyIf', function() {
        it('should not overwrite existing properties', function() {
            var dest = {
                a: 1,
                b: undefined
            };

            Ext.copyIf(dest, {
                a: 2,
                b: 3,
                c: 4
            }, 'a,b,c');
            expect(dest.a).toBe(1);

            // Property b was present in dest, so is left alone.
            expect(dest.b).toBeUndefined();

            // Test valid copying
            expect(dest.c).toBe(4);
        });
    });

    describe("Ext.copyTo", function() {
        var src, dest;

        beforeEach(function() {
            src = {
                a: 1,
                b: 2,
                c: 3,
                d: 4
            };

            dest = {};
        });

        afterEach(function() {
            src = null;
            dest = null;
        });

        describe("with an array of named properties", function() {
            it("should copy a set of named properties fom the source object to the destination object.", function() {
                Ext.copyTo(dest, src, ['a', 'b', 'e']);

                expect(dest).toEqual({
                    a: 1,
                    b: 2
                });
            });
        });

        describe("with a string list of named properties", function() {
            it("should copy a set of named properties fom the source object to the destination object.", function() {
                Ext.copyTo(dest, src, 'c,b,e');
                expect(dest).toEqual({
                    b: 2,
                    c: 3
                });
            });
        });

        describe('including prototype properties', function() {
            var CopyToSource = function(obj) {
                Ext.apply(this, obj);
            };

            CopyToSource.prototype = {
                prototypeProperty: "I'm from the prototype"
            };

            beforeEach(function() {
                src = new CopyToSource({
                    a: 1,
                    b: 2,
                    c: 3,
                    d: 4
                });
            });
            it('should not copy prototype properties unless asked', function() {
                Ext.copyTo(dest, src, 'a,nonExistent,prototypeProperty');

                // There was only ONE property that could be copied over.
                // nonExistent should NOT end up as a property reference to undefined.
                // prototypeProperty was not copied because it's on the prototype and
                // we did not pass the usePrototypeKeys parameter.
                expect(dest).toEqual({
                    a: 1
                });
            });
            it('should copy prototype properties when asked', function() {
                Ext.copyTo(dest, src, 'a,nonExistent,prototypeProperty', true);

                // There were TWO that could be copied over.
                // nonExistent should NOT end up as a property reference to undefined.
                // prototypeProperty is copied over because we passed the usePrototypeKeys parameter.
                expect(dest).toEqual({
                    a: 1,
                    prototypeProperty: "I'm from the prototype"
                });

                // Test the bug in the deprecated method.
                // copyTo copies nonexistent properties if usePrototypeKeys is true.
                expect('nonExistent' in dest).toBe(true);
                expect(dest.nonExistent).toBeUndefined();
            });
        });
    });

    describe("Ext.copy", function() {
        var src, dest;

        beforeEach(function() {
            src = {
                a: 1,
                b: 2,
                c: 3,
                d: 4
            };

            dest = {};
        });

        afterEach(function() {
            src = null;
            dest = null;
        });

        describe("with an array of named properties", function() {
            it("should copy a set of named properties fom the source object to the destination object.", function() {
                Ext.copy(dest, src, ['a', 'b', 'e']);

                expect(dest).toEqual({
                    a: 1,
                    b: 2
                });
            });
        });

        describe("with a string list of named properties", function() {
            it("should copy a set of named properties fom the source object to the destination object.", function() {
                Ext.copy(dest, src, 'c,b,e');
                expect(dest).toEqual({
                    b: 2,
                    c: 3
                });
            });
        });

        describe('including prototype properties', function() {
            var CopyToSource = function(obj) {
                Ext.apply(this, obj);
            };

            CopyToSource.prototype = {
                prototypeProperty: "I'm from the prototype"
            };

            beforeEach(function() {
                src = new CopyToSource({
                    a: 1,
                    b: 2,
                    c: 3,
                    d: 4
                });
            });
            it('should not copy prototype properties unless asked', function() {
                Ext.copy(dest, src, 'a,nonExistent,prototypeProperty');

                // There was only ONE property that could be copied over.
                // nonExistent should NOT end up as a property reference to undefined.
                // prototypeProperty was not copied because it's on the prototype and
                // we did not pass the usePrototypeKeys parameter.
                expect(dest).toEqual({
                    a: 1
                });
            });
            it('should copy prototype properties when asked', function() {
                Ext.copy(dest, src, 'a,nonExistent,prototypeProperty', true);

                // There were TWO that could be copied over.
                // nonExistent should NOT end up as a property reference to undefined.
                // prototypeProperty is copied over because we passed the usePrototypeKeys parameter.
                expect(dest).toEqual({
                    a: 1,
                    prototypeProperty: "I'm from the prototype"
                });

                // Test that the new version does NOT copy nonexistent properties when usePrototypeKeys is true.
                expect('nonExistent' in dest).toBe(false);
            });
        });
    });

    describe("Ext.destroyMembers", function() {
        var obj, destroyable;

        beforeEach(function() {
            destroyable = {
                destroy: jasmine.createSpy()
            };
            obj = {
                a: 1,
                b: 2,
                c: 3,
                d: 4,
                me: destroyable
            };
        });

        it("should remove named properties from a passed object", function() {
            Ext.destroyMembers(obj, 'a', 'c', 'i');
            expect(obj).toEqual({
                a: null,
                b: 2,
                c: null,
                d: 4,
                me: destroyable
            });
        });

        it("should attempt to destroy passed properties", function() {
            Ext.destroyMembers(obj, 'a', 'c', 'me');

            expect(destroyable.destroy).toHaveBeenCalled();
        });
    });

    describe('Ext.escapeId', function() {
        it("should escape element id sequences with special characters", function() {
            expect(Ext.escapeId('abcdef')).toBe('abcdef');
            expect(Ext.escapeId('.abcdef')).toBe('\\.abcdef');
            expect(Ext.escapeId('0a...')).toBe('\\0030 a\\.\\.\\.');
            expect(Ext.escapeId('12345')).toBe('\\0031 2345');
            expect(Ext.escapeId('.abc-def')).toBe('\\.abc\\-def');
            expect(Ext.escapeId('<12345/>')).toBe('\\<12345\\/\\>');
            expect(Ext.escapeId('1<>234.567')).toBe('\\0031 \\<\\>234\\.567');
        });
    });

    describe("Ext.application", function() {
        beforeEach(function() {
            spyOn(Ext.Loader, 'setPath').andReturn();
        });

        afterEach(function() {
            Ext.app.Application.instance.destroy();

            Ext.undefine('Test.$application');
            Ext.undefine('Test');

            try {
                delete window.Test;
            }
            catch (e) {
                window.Test = undefined;
            }
        });

        it("should set application path", function() {
            Ext.application({
                name: 'Test',
                appFolder: 'fooFolder'
            });

            expect(Ext.Loader.setPath).toHaveBeenCalledWith('Test', 'fooFolder');
        });

        it("should process appFolder and paths array", function() {
            Ext.application({
                name: 'Test',
                appFolder: 'barFolder',
                paths: {
                    baz: 'bazFolder',
                    qux: 'quxFolder'
                }
            });

            var args = Ext.Loader.setPath.argsForCall;

            expect(args).toEqual([
                ['Test', 'barFolder'],
                ['baz', 'bazFolder'],
                ['qux', 'quxFolder']
            ]);
        });
    });

    describe("Ext.splitAndUnescape", function() {
        var fn = Ext.splitAndUnescape,
            result;

        it("should return an empty array when origin string is empty", function() {
            result = fn('', ',');

            expect(result).toEqual([]);
        });

        it("should return the origin when delimiter is empty", function() {
            result = fn('foo', '');

            expect(result).toEqual(['foo']);
        });

        it("should split on delimiter", function() {
            result = fn('foo,bar', ',');

            expect(result).toEqual(['foo', 'bar']);
        });

        it("should not split on escaped delimiter", function() {
            result = fn('foo\\,bar', ',');

            expect(result).toEqual(['foo,bar']);
        });

        it("should not choke on a mix of escaped and unescaped delimiters", function() {
            result = fn('foo\\,bar,baz\\,qux', ',');

            expect(result).toEqual(['foo,bar', 'baz,qux']);
        });

        it("should allow front unescaped delimiter", function() {
            result = fn(',foo', ',');

            expect(result).toEqual(['', 'foo']);
        });

        it("should allow dangling unescaped delimiter", function() {
            result = fn('foo,', ',');

            expect(result).toEqual(['foo', '']);
        });

        it("should allow front escaped delimiter", function() {
            result = fn('\\,foo', ',');

            expect(result).toEqual([',foo']);
        });

        it("should allow dangling escaped delimiter", function() {
            result = fn('foo\\,', ',');

            expect(result).toEqual(['foo,']);
        });
    });
});

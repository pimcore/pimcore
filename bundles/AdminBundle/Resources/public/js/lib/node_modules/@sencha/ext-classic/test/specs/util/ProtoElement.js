topSuite("Ext.util.ProtoElement", function() {
    var makeEl, el;

    beforeEach(function() {
        makeEl = function(cfg) {
            el = new Ext.util.ProtoElement(cfg || {});
        };
    });

    afterEach(function() {
        makeEl = el = null;
    });

    describe("defaults", function() {
        beforeEach(function() {
            makeEl();
        });

        it("should set the clsProp to 'cls'", function() {
            expect(el.clsProp).toBe('cls');
        });

        it("should set the styleProp to 'style'", function() {
            expect(el.styleProp).toBe('style');
        });

        it("should set the removedProp to 'removed'", function() {
            expect(el.removedProp).toBe('removed');
        });
    });

    describe("initialization", function() {
        describe("cls", function() {
            it("should accept an array of classes", function() {
                makeEl({
                    cls: ['foo', 'bar']
                });
                expect(el.writeTo({}).cls).toBe('foo bar');
            });

            it("should accept a single class name", function() {
                makeEl({
                    cls: 'foo'
                });
                expect(el.writeTo({}).cls).toBe('foo');
            });

            it("should accept a string of classes", function() {
                makeEl({
                    cls: 'foo bar baz'
                });
                expect(el.writeTo({}).cls).toBe('foo bar baz');
            });
        });

        describe("style", function() {
            it("should accept a style string", function() {
                makeEl({
                    style: 'border: 1px solid red; color: blue;'
                });
                expect(el.writeTo({}).style).toEqual({
                    border: '1px solid red',
                    color: 'blue'
                });
            });

            it("should accept a style object", function() {
                makeEl({
                    style: {
                        color: 'red',
                        margin: '5px'
                    }
                });
                expect(el.writeTo({}).style).toEqual({
                    color: 'red',
                    margin: '5px'
                });
            });

            it("should accept a function for styles", function() {
                makeEl({
                    style: function() {
                        return {
                            color: 'yellow',
                            padding: '2px'
                        };
                    }
                });

                expect(el.writeTo({}).style).toEqual({
                    color: 'yellow',
                    padding: '2px'
                });
            });
        });
    });

    describe("classes", function() {

        describe("setting classes dynamically", function() {
            describe("addCls", function() {
                beforeEach(function() {
                    makeEl();
                });

                it("should accept an array of classes", function() {
                    el.addCls(['foo', 'bar']);
                    expect(el.writeTo({}).cls).toBe('foo bar');
                });

                it("should accept a single class name", function() {
                    el.addCls('foo');
                    expect(el.writeTo({}).cls).toBe('foo');
                });

                it("should accept a string of classes", function() {
                    el.addCls('foo bar baz');
                    expect(el.writeTo({}).cls).toBe('foo bar baz');
                });

                it("should ignore an already added class", function() {
                    el.addCls('foo');
                    el.addCls('foo');
                    expect(el.writeTo({}).cls).toBe('foo');
                });

                it("should return itself", function() {
                    expect(el.addCls('foo')).toBe(el);
                });
            });

            describe("removeCls", function() {
                beforeEach(function() {
                    makeEl({
                        cls: 'foo bar baz'
                    });
                });

                it("should accept an array of classes", function() {
                    el.removeCls(['foo', 'bar']);
                    expect(el.writeTo({}).cls).toBe('baz');
                });

                it("should accept a single class name", function() {
                    el.removeCls('foo');
                    expect(el.writeTo({}).cls).toBe('bar baz');
                });

                it("should accept a string of classes", function() {
                    el.removeCls('bar baz');
                    expect(el.writeTo({}).cls).toBe('foo');
                });

                it("should ignore a class that doesn't exist", function() {
                    el.removeCls('fake');
                    expect(el.writeTo({}).cls).toBe('foo bar baz');
                });

                it("should return itself", function() {
                    expect(el.removeCls('foo')).toBe(el);
                });
            });
        });

        describe("hasCls", function() {
            beforeEach(function() {
                makeEl();
            });

            it("should return false when just created", function() {
                expect(el.hasCls('foo')).toBe(false);
            });

            it("should return false when the class doesn't exist", function() {
                expect(el.addCls('foo').hasCls('bar')).toBe(false);
            });

            it("should return true when the class exists", function() {
                expect(el.addCls('foo').hasCls('foo')).toBe(true);
            });
        });
    });

    describe("styles", function() {
        beforeEach(function() {
            makeEl();
        });

        it("should accept a style string", function() {
            el.setStyle('color: red; margin: 3px;');
            expect(el.writeTo({}).style).toEqual({
                color: 'red',
                margin: '3px'
            });
        });

        it("should accept a prop/value", function() {
            el.setStyle('color', 'green');
            expect(el.writeTo({}).style).toEqual({
                color: 'green'
            });
        });

        it("should accept a style object", function() {
            el.setStyle({
                color: 'blue',
                padding: '1px'
            });
            expect(el.writeTo({}).style).toEqual({
                color: 'blue',
                padding: '1px'
            });
        });

    });

    describe("writeTo", function() {
        beforeEach(function() {
            makeEl();
        });

        it("should modify the passed object", function() {
            var o = {};

            el.addCls('foo');
            el.writeTo(o);
            expect(o.cls).toBe('foo');
        });

        it("should return the passed object", function() {
            var o = {},
                ret;

            el.addCls('foo');
            ret = el.writeTo(o);
            expect(ret).toBe(o);
        });

        it("should write out the class list", function() {
            el.addCls('foo bar');
            expect(el.writeTo({}).cls).toBe('foo bar');
        });

        it("should write out the styles as an object if styleIsText is false", function() {
            el.setStyle('color', 'red');
            expect(el.writeTo({}).style).toEqual({
                color: 'red'
            });
        });

        it("should write out the styles as a string if styleIsText is true", function() {
            el.setStyle('color', 'green');
            el.styleIsText = true;
            expect(el.writeTo({}).style).toBe('color:green;');
        });
    });

    describe("flushing", function() {
        beforeEach(function() {
            makeEl();
        });

        describe("addCls", function() {
            it("should return only added classes after flushing", function() {
                el.addCls('foo');
                el.flush();
                el.addCls('bar');

                expect(el.writeTo({}).cls).toBe('bar');
            });

            it("should ignore already added classes", function() {
                el.addCls('foo');
                el.flush();
                el.addCls('foo');

                expect(el.writeTo({}).cls).toBe('');
            });

            it("should be able to flush multiple times", function() {
                el.addCls('foo');
                el.flush();
                el.addCls('bar');
                el.flush();
                el.addCls('baz');

                expect(el.writeTo({}).cls).toBe('baz');
            });
        });

        describe("hasCls", function() {
            it("should still keep a class list after flushing", function() {
                el.addCls('foo');
                el.flush();
                el.addCls('bar');
                expect(el.hasCls('foo')).toBe(true);
            });

            it("should keep the class when removed and re-added", function() {
                el.addCls('foo');
                el.flush();
                el.removeCls('foo');
                el.addCls('foo');
                expect(el.hasCls('foo')).toBe(true);
            });

            it("should respect removed classes removed after a flush", function() {
                el.addCls('foo');
                el.flush();
                el.removeCls('foo');
                expect(el.hasCls('foo')).toBe(false);
            });
        });

        describe("removeCls", function() {
            it("should ignore classes that don't exist", function() {
                el.addCls('foo');
                el.flush();
                el.removeCls('bar');
                expect(el.writeTo({}).removed).toBeUndefined();
            });

            it("should remove an existing class", function() {
                el.addCls('foo');
                el.flush();
                el.removeCls('foo');
                expect(el.writeTo({}).removed).toEqual('foo');
            });
        });

        describe("styles", function() {
            it("should overwrite any style", function() {
                el.setStyle('color', 'red');
                el.flush();
                el.setStyle('color', 'blue');
                expect(el.writeTo({}).style).toEqual({
                    color: 'blue'
                });
            });

            it("should only contain new styles", function() {
                el.setStyle('color', 'red');
                el.flush();
                el.setStyle('margin', '2px');
                expect(el.writeTo({}).style).toEqual({
                    margin: '2px'
                });
            });
        });
    });

});

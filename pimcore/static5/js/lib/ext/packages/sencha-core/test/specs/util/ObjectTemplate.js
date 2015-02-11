describe('Ext.util.ObjectTemplate', function () {
    var tpl, output,
        context = Ext.Object.chain({
            direct: false,
            object: {
                property: 456
            }
        });

    context.text = 'Don';

    var fn1 = function() {},
        fn2 = function() {},
        fn3 = function() {},
        fn4 = function() {},
        fn5 = function() {};

    beforeEach(function () {
        tpl = new Ext.util.ObjectTemplate({
            foo: 42,
            rootFn: fn1,
            bar: 'Hello {text}',
            baz: '{direct}',
            array: [
                427,
                'Hey {text} {object.property}',
                '{object.property}',
                '{direct}',
                fn2
            ],
            object: {
                prop: 3,
                objFn: fn3,
                tpl: 'Yo {text}',
                value: '{direct}',
                items: [
                    77,
                    '{direct}',
                    {
                        v: '{direct}',
                        x: 1,
                        s: '-- {text}',
                        innerFn: fn4
                    },
                    'Oy {text}',
                    fn5
                ]
            }
        });

        output = tpl.apply(context);
    });

    describe('the root', function () {
        it('should pass through numbers on the root', function () {
            expect(output.foo).toBe(42);
        });

        it('should apply Ext.Template on the root', function () {
            expect(output.bar).toBe('Hello Don');
        });

        it('should map values on the root', function () {
            expect(output.baz).toBe(false);
        });

        it('should pass through functions on the root', function() {
            expect(output.rootFn).toBe(fn1);
        });
    });

    describe('array on the root', function () {
        it('should have the correct length', function () {
            expect(output.array.length).toBe(5);
        });
        it('should pass through numbers', function () {
            expect(output.array[0]).toBe(427);
        });
        it('should apply templates', function () {
            expect(output.array[1]).toBe('Hey Don 456');
        });
        it('should pull single values through dotted template expansions', function () {
            expect(output.array[2]).toBe(456);
        });
        it('should pull primitives through simple name expansions', function () {
            expect(output.array[3]).toBe(false);
        });
        it('should pass through functions', function() {
            expect(output.array[4]).toBe(fn2);
        });
    });

    describe('object off the root', function () {
        describe('properties', function () {
            it('should pass through numbers', function () {
                expect(output.object.prop).toBe(3);
            });

            it('should apply XTemplate', function () {
                expect(output.object.tpl).toBe('Yo Don');
            });

            it('should map values', function () {
                expect(output.object.value).toBe(false);
            });

            it('should pass through functions', function () {
                expect(output.object.objFn).toBe(fn3);
            });
        });

        describe('an array property', function () {
            it('should have the correct length', function () {
                expect(output.object.items.length).toBe(5);
            });
            it('should pass through numbers', function () {
                expect(output.object.items[0]).toBe(77);
            });
            it('should pull primitives through simple name expansions', function () {
                expect(output.object.items[1]).toBe(false);
            });
            it('should apply XTemplate', function () {
                expect(output.object.items[3]).toBe('Oy Don');
            });
            it('should pass through functions', function () {
                expect(output.object.items[4]).toBe(fn5);
            });
        });

        describe('an object element of an array property', function () {
            it('should pull primitives through simple name expansions', function () {
                expect(output.object.items[2].v).toBe(false);
            });
            it('should pass through numbers', function () {
                expect(output.object.items[2].x).toBe(1);
            });
            it('should apply XTemplate', function () {
                expect(output.object.items[2].s).toBe('-- Don');
            });
            it('should pass through numbers', function () {
                expect(output.object.items[2].innerFn).toBe(fn4);
            });
        });
    });
});

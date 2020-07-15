topSuite("Ext.app.bind.Parser", function() {
    var parser;

    beforeEach(function() {
        parser = Ext.app.bind.Parser.fly();
    });

    afterEach(function() {
        parser.release();
        parser = null;
    });

    function dump(data) {
        var s = JSON.stringify(data, null, '    ');

        console.log(s);
    }

    function parseExpression(text) {
        parser.reset(text);

        return parser.parseExpression();
    }

    describe('expressions with formatters', function() {
        it('should parse simple formatter', function() {
            //                          012345678
            var expr = parseExpression('foo:bar'),
                data = expr.dump();

            expect(data).toEqual({
                at: 3,
                arity: "formatter",
                value: ":",
                operand: {
                    at: 0,
                    arity: "ident",
                    value: "foo"
                },
                fmt: [{
                    at: 4,
                    arity: "ident",
                    value: "bar"
                }]
            });
        });

        it('should parse chained formatters', function() {
            //                          0123456789
            var expr = parseExpression('foo:b.r:zip'),
                data = expr.dump();

            expect(data).toEqual({
                at: 3,
                arity: "formatter",
                value: ":",
                operand: {
                    at: 0,
                    arity: "ident",
                    value: "foo"
                },
                fmt: [{
                    at: 4,
                    arity: "ident",
                    value: "b.r"
                }, {
                    at: 8,
                    arity: "ident",
                    value: "zip"
                }]
            });
        });

        it('should parse chained formatters with other operators', function() {
            //                                    111111
            //                          0123456789012345
            var expr = parseExpression('2/foo:bar:zip*3'),
                data = expr.dump();

            expect(data).toEqual({
                at: 13,
                arity: 'binary',
                value: '*',
                lhs: {
                    at: 1,
                    arity: 'binary',
                    value: '/',
                    lhs: {
                        at: 0,
                        arity: 'literal',
                        value: 2
                    },
                    rhs: {
                        at: 5,
                        arity: "formatter",
                        value: ":",
                        operand: {
                            at: 2,
                            arity: "ident",
                            value: "foo"
                        },
                        fmt: [{
                            at: 6,
                            arity: "ident",
                            value: "bar"
                        }, {
                            at: 10,
                            arity: "ident",
                            value: "zip"
                        }]
                    }
                },
                rhs: {
                    at: 14,
                    arity: 'literal',
                    value: 3
                }
            });
        });

        it('should parse formatter with args', function() {
            //                                    11111111112
            //                          012345678901234567890
            var expr = parseExpression('foo:bar(.314,"abc()")'),
                data = expr.dump();

            expect(data).toEqual({
                at: 3,
                arity: "formatter",
                value: ":",
                operand: {
                    at: 0,
                    arity: "ident",
                    value: "foo"
                },
                fmt: [{
                    at: 7,
                    arity: "invoke",
                    value: "(",
                    operand: {
                        at: 4,
                        arity: 'ident',
                        value: 'bar'
                    },
                    args: [{
                        at: 8,
                        arity: 'literal',
                        value: 0.314
                    }, {
                        at: 13,
                        arity: 'literal',
                        value: 'abc()'
                    }]
                }]
            });
        });

        it('should parse chained formatter with args', function() {
            //                                    111111111122222 2222 23333333333444444444
            //                          0123456789012345678901234 5678 90123456789012345678
            var expr = parseExpression('foo:bar(10,"abc()"): zip(\'xyz\',null,true, false )'),
                data = expr.dump();

            expect(data).toEqual({
                at: 3,
                arity: "formatter",
                value: ":",
                operand: {
                    at: 0,
                    arity: "ident",
                    value: "foo"
                },
                fmt: [{
                    at: 7,
                    arity: "invoke",
                    value: "(",
                    operand: {
                        at: 4,
                        arity: 'ident',
                        value: 'bar'
                    },
                    args: [{
                        at: 8,
                        arity: 'literal',
                        value: 10
                    }, {
                        at: 11,
                        arity: 'literal',
                        value: 'abc()'
                    }]
                }, {
                    at: 24,
                    arity: "invoke",
                    value: "(",
                    operand: {
                        at: 21,
                        arity: 'ident',
                        value: 'zip'
                    },
                    args: [{
                        at: 25,
                        arity: 'literal',
                        value: 'xyz'
                    }, {
                        at: 31,
                        arity: 'literal',
                        value: null
                    }, {
                        at: 36,
                        arity: 'literal',
                        value: true
                    }, {
                        at: 42,
                        arity: 'literal',
                        value: false
                    }]
                }]
            });
        });

        it('should parse nested formatter', function() {
            //                                    111111111122
            //                          0123456789012345678901
            var expr = parseExpression('foo:bar(@d.rp:zip(42))'),
                data = expr.dump();

            expect(data).toEqual({
                at: 3,
                arity: "formatter",
                value: ":",
                operand: {
                    at: 0,
                    arity: "ident",
                    value: "foo"
                },
                fmt: [{
                    at: 7,
                    arity: "invoke",
                    value: "(",
                    operand: {
                        at: 4,
                        arity: 'ident',
                        value: 'bar'
                    },
                    args: [{
                        at: 13,
                        arity: "formatter",
                        value: ":",
                        operand: {
                            at: 8,
                            arity: "unary",
                            value: "@",
                            operand: {
                                at: 9,
                                arity: "ident",
                                value: "d.rp"
                            }
                        },
                        fmt: [{
                            at: 17,
                            arity: "invoke",
                            value: "(",
                            operand: {
                                at: 14,
                                arity: 'ident',
                                value: 'zip'
                            },
                            args: [{
                                at: 18,
                                arity: 'literal',
                                value: 42
                            }]
                        }]
                    }]
                }]
            });
        });

        it('should parse nested formatter with args', function() {
            //                                    1111111111222222222 2333333333 34444444444
            //                          01234567890123456789012345678 9012345678 90123456789
            var expr = parseExpression('foo:bar(10,"abc()",@derp:zip(\'{"a(:)a"}\',2.1e-07))'),
                data = expr.dump();

            expect(data).toEqual({
                at: 3,
                arity: "formatter",
                value: ":",
                operand: {
                    at: 0,
                    arity: "ident",
                    value: "foo"
                },
                fmt: [{
                    at: 7,
                    arity: "invoke",
                    value: "(",
                    operand: {
                        at: 4,
                        arity: "ident",
                        value: "bar"
                    },
                    args: [{
                        at: 8,
                        arity: "literal",
                        value: 10
                    }, {
                        at: 11,
                        arity: "literal",
                        value: "abc()"
                    }, {
                        at: 24,
                        arity: "formatter",
                        value: ":",
                        operand: {
                            at: 19,
                            arity: "unary",
                            value: "@",
                            operand: {
                                at: 20,
                                arity: "ident",
                                value: "derp"
                            }
                        },
                        fmt: [{
                            at: 28,
                            arity: "invoke",
                            value: "(",
                            operand: {
                                at: 25,
                                arity: "ident",
                                value: "zip"
                            },
                            args: [{
                                at: 29,
                                arity: "literal",
                                value: "{\"a(:)a\"}"
                            }, {
                                at: 41,
                                arity: "literal",
                                value: 2.1e-7
                            }]
                        }]
                    }]
                }]
            });
        });

        it('should parse chained and nested formatters', function() {
            //                                    111111111122222222223333333333444444
            //                          0123456789012345678901234567890123456789012345
            var expr = parseExpression(' f :bar(@derp:zip(42):boo:zoo(2)):woot(21):waz'),
                data = expr.dump();

            expect(data).toEqual({
                at: 3,
                arity: "formatter",
                value: ":",
                operand: {
                    at: 1,
                    arity: "ident",
                    value: "f"
                },
                fmt: [{
                    at: 7,
                    arity: "invoke",
                    value: "(",
                    operand: {
                        at: 4,
                        arity: 'ident',
                        value: 'bar'
                    },
                    args: [{
                        at: 13,
                        arity: "formatter",
                        value: ":",
                        operand: {
                            at: 8,
                            arity: "unary",
                            value: "@",
                            operand: {
                                at: 9,
                                arity: "ident",
                                value: "derp"
                            }
                        },
                        fmt: [{
                            at: 17,
                            arity: "invoke",
                            value: "(",
                            operand: {
                                at: 14,
                                arity: 'ident',
                                value: 'zip'
                            },
                            args: [{
                                at: 18,
                                arity: 'literal',
                                value: 42
                            }]
                        }, {
                            at: 22,
                            arity: 'ident',
                            value: 'boo'
                        }, {
                            at: 29,
                            arity: 'invoke',
                            value: '(',
                            operand: {
                                at: 26,
                                arity: 'ident',
                                value: 'zoo'
                            },
                            args: [{
                                at: 30,
                                arity: 'literal',
                                value: 2
                            }]
                        }]
                    }]
                }, {
                    at: 38,
                    arity: 'invoke',
                    value: '(',
                    operand: {
                        at: 34,
                        arity: 'ident',
                        value: 'woot'
                    },
                    args: [{
                        at: 39,
                        arity: 'literal',
                        value: 21
                    }]
                }, {
                    at: 43,
                    arity: 'ident',
                    value: 'waz'
                }]
            });
        });

    }); // operators

    describe('compileFormat', function() {
        var parser;

        beforeEach(function() {
            parser = Ext.app.bind.Parser.fly();
        });

        afterEach(function() {
            parser.release();
            parser = null;
        });

        it('should parse basic formats', function() {
            parser.reset('round');
            var fmt = parser.compileFormat();

            var s = fmt(3.14);

            expect(s).toBe(3);
        });

        it('should parse formats with basic arguments', function() {
            parser.reset('round(2)');
            var fmt = parser.compileFormat();

            var s = fmt(3.139);

            expect(s).toBe(3.14);
        });

        it('should parse formats with string arguments', function() {
            parser.reset('date("Y-m-d")');
            var fmt = parser.compileFormat();

            var s = fmt(new Date(2013, 2, 2));

            expect(s).toBe('2013-03-02');
        });

        it('should parse chained formatters', function() {
            parser.reset('lowercase:capitalize');
            var fmt = parser.compileFormat();

            var s = fmt('SENCHA');

            expect(s).toBe('Sencha');
        });

        it('should parse chained formatters with arguments', function() {
            parser.reset('round:this.multiply(4):this.divide(2)');
            var fmt = parser.compileFormat();

            var s = fmt(3.14, {
                multiply: function(v, factor) {
                    return v * factor;
                },
                divide: function(v, factor) {
                    return v / factor;
                }
            });

            expect(s).toBe(6);
        });
    });

    describe('syntax errors', function() {
        it('should fail for numbers in formatter', function() {
            try {
                var expr = parseExpression('foo:2');
            }
            catch (e) {
                expect(parser.error).not.toBeNull();

                return;
            }

            expect('Invalid formatter').toBe('an exception');
        });

        it('should fail for operators in formatter', function() {
            try {
                var expr = parseExpression('foo:2+2');
            }
            catch (e) {
                expect(parser.error).not.toBeNull();

                return;
            }

            expect('Invalid formatter').toBe('an exception');
        });
    });
});

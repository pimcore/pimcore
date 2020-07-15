topSuite('Ext.parse.Parser', function() {
    var parser;

    beforeEach(function() {
        parser = Ext.parse.Parser.fly();
    });

    afterEach(function() {
        parser.release();
        parser = null;
    });

    function parseExpression(text) {
        parser.reset(text);

        return parser.parseExpression();
    }

    describe('constants', function() {
        it('should parse null', function() {
            var expr = parseExpression('null');

            var data = expr.dump();

            expect(data).toEqual({
                at: 0,
                arity: "literal",
                value: null
            });
        });

        it('should parse false', function() {
            var expr = parseExpression('false');

            var data = expr.dump();

            expect(data).toEqual({
                at: 0,
                arity: "literal",
                value: false
            });
        });

        it('should parse true', function() {
            var expr = parseExpression('true');

            var data = expr.dump();

            expect(data).toEqual({
                at: 0,
                arity: "literal",
                value: true
            });
        });
    });

    describe('operators', function() {
        it('should bind plus left associatively', function() {
            //                          012345678
            var expr = parseExpression('a+b+c');

            var data = expr.dump();

            expect(data).toEqual({
                at: 3,
                arity: "binary",
                value: "+",
                lhs: {
                    at: 1,
                    arity: "binary",
                    value: "+",
                    lhs: {
                        at: 0,
                        arity: "ident",
                        value: "a"
                    },
                    rhs: {
                        at: 2,
                        arity: "ident",
                        value: "b"
                    }
                },
                rhs: {
                    at: 4,
                    arity: "ident",
                    value: "c"
                }
            });
        });

        it('should bind plus weaker than multiply', function() {
            //                          012345678
            var expr = parseExpression('a+b*c+d');

            var data = expr.dump();

            expect(data).toEqual({
                at: 5,
                arity: "binary",
                value: '+',
                lhs: {
                    at: 1,
                    arity: "binary",
                    value: "+",
                    lhs: {
                        at: 0,
                        arity: "ident",
                        value: "a"
                    },
                    rhs: {
                        at: 3,
                        arity: "binary",
                        value: "*",
                        lhs: {
                            at: 2,
                            arity: "ident",
                            value: "b"
                        },
                        rhs: {
                            at: 4,
                            arity: "ident",
                            value: "c"
                        }
                    }
                },
                rhs: {
                    at: 6,
                    arity: 'ident',
                    value: 'd'
                }
            });
        });

        it('should respect parenthesis', function() {
            //                          012345678
            var expr = parseExpression('(a +b)* c');

            var data = expr.dump();

            expect(data).toEqual({
                at: 6,
                arity: "binary",
                value: "*",
                lhs: {
                    at: 3,
                    arity: "binary",
                    value: "+",
                    lhs: {
                        at: 1,
                        arity: "ident",
                        value: "a"
                    },
                    rhs: {
                        at: 4,
                        arity: "ident",
                        value: "b"
                    }
                },
                rhs: {
                    at: 8,
                    arity: "ident",
                    value: "c"
                }
            });
        });
    }); // operators

    describe('function call', function() {
        it('should parse function calls with simple name', function() {
            //                          012345678
            var expr = parseExpression('foo(10)');

            var data = expr.dump();

            expect(data).toEqual({
                at: 3,
                arity: 'invoke',
                value: '(',
                operand: {
                    at: 0,
                    arity: 'ident',
                    value: 'foo'
                },
                args: [{
                    at: 4,
                    arity: 'literal',
                    value: 10
                }]
            });
        });
    });
});

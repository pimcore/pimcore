topSuite('Ext.data.Query', [
    'Ext.data.Model'
], function() {
    describe('Empty', function() {
        it('should construct with empty ast', function() {
            var query = new Ext.data.Query();

            expect(query.ast).toBe(null);
            expect(query.generation).toBe(1);
        });

        it('should be empty string if empty ast', function() {
            var query = new Ext.data.Query();

            expect(query.toString()).toBe('');
        });

        it('should evaluate true if empty ast', function() {
            var query = new Ext.data.Query();

            expect(query.filter()).toBe(true);
        });
    });

    describe('smoke test', function() {
        var query = new Ext.data.Query(
            'x && ' +                       // 0
            '!x && ' +                      // 1
            '-x && ' +                      // 2
            '!-x && ' +                     // 3
            'x = 4 && ' +                   // 4
            'x == 5 && ' +                  // 5
            'x === 6 && ' +                 // 6
            'x != 7 && ' +                  // 7
            'x !== 8 && ' +                 // 8
            'x < 9 and ' +                  // 9
            'x <= 10 && ' +                 // 10
            'x > 11 && ' +                  // 11
            'x >= 12 && ' +                 // 12
            'x in (x, 2) && ' +             // 13
            'x like "Bob" && ' +            // 14
            'x like "[Bb]ob" && ' +         // 15
            'x like /R(o)+b/ && ' +         // 16
            'x between y and 10 && ' +      // 17
            '(x || y or 42) && ' +          // 18
            'x + 13 && ' +                  // 19
            '14 * x && ' +                  // 20
            'x - 15 && ' +                  // 21
            'x / 16 && ' +                  // 22
            '42 && ' +                      // 23
            '6.02e23 && ' +                 // 24
            'true && ' +                    // 25
            'false' +                       // 26
            '' +
            '');

        var ast = query.ast;

        function EQ(i) {
            var exp = expect(ast.on[i]);

            return exp.toEqual.bind(exp);
        }

        it('should handle an expression with everything', function() {
            expect(ast.type).toBe('and');
            expect(ast.on.length).toBe(27);
        });
        it('should handle identifier', function() {
            EQ(0)({ type: 'id', value: 'x' });
        });
        it('should handle loglcal not', function() {
            EQ(1)({ type: 'not', on: { type: 'id', value: 'x' } });
        });
        it('should handle unary minus', function() {
            EQ(2)({ type: 'neg', on: { type: 'id', value: 'x' } });
        });
        it('should handle combined unary not and minus', function() {
            EQ(3)({ type: 'not', on: { type: 'neg', on: { type: 'id', value: 'x' } } });
        });
        it('should handle equality', function() {
            EQ(4)({ type: 'eq', on: [{ type: 'id', value: 'x' }, 4] });
        });
        it('should handle strict equality', function() {
            EQ(5)({ type: 'seq', on: [{ type: 'id', value: 'x' }, 5] });
        });
        it('should handle full strict equality', function() {
            EQ(6)({ type: 'seq', on: [{ type: 'id', value: 'x' }, 6] });
        });
        it('should handle inequality', function() {
            EQ(7)({ type: 'ne', on: [{ type: 'id', value: 'x' }, 7] });
        });
        it('should handle string inequality', function() {
            EQ(8)({ type: 'sne', on: [{ type: 'id', value: 'x' }, 8] });
        });
        it('should handle less-than', function() {
            EQ(9)({ type: 'lt', on: [{ type: 'id', value: 'x' }, 9] });
        });

        it('should handle less-than-or-equal', function() {
            EQ(10)({ type: 'le', on: [{ type: 'id', value: 'x' }, 10] });
        });
        it('should handle greater-than', function() {
            EQ(11)({ type: 'gt', on: [{ type: 'id', value: 'x' }, 11] });
        });
        it('should handle greater-than-or-equal', function() {
            EQ(12)({ type: 'ge', on: [{ type: 'id', value: 'x' }, 12] });
        });
        it('should handle "in" operator', function() {
            EQ(13)({ type: 'in', on: [{ type: 'id', value: 'x' }, {
                type: 'list', value: [{ type: 'id', value: 'x' }, 2]
            }] });
        });
        it('should handle "like" operator with basic string', function() {
            EQ(14)({ type: 'like', on: [{ type: 'id', value: 'x' }, {
                type: 'string', value: 'Bob', re: 'Bob', flags: 'i'
            }] });
        });
        it('should handle "like" operator with wildcards', function() {
            EQ(15)({ type: 'like', on: [{ type: 'id', value: 'x' }, {
                type: 'string', value: '[Bb]ob', re: '^[Bb]ob$'
            }] });
        });
        it('should handle "like" operator with regexp', function() {
            EQ(16)({ type: 'like', on: [{ type: 'id', value: 'x' }, {
                type: 'regexp', value: 'R(o)+b'
            }] });
        });
        it('should handle "between" operator', function() {
            EQ(17)({ type: 'between', on: [{ type: 'id', value: 'x' }, {
                type: 'id', value: 'y'
            }, 10] });
        });
        it('should handle "or" operator', function() {
            EQ(18)({ type: 'or', on: [{ type: 'id', value: 'x' }, {
                type: 'id', value: 'y'
            }, 42] });
        });
        it('should handle addition', function() {
            EQ(19)({ type: 'add', on: [{ type: 'id', value: 'x' }, 13] });
        });
        it('should handle multiplication', function() {
            EQ(20)({ type: 'mul', on: [14, { type: 'id', value: 'x' }] });
        });
        it('should handle subtraction', function() {
            EQ(21)({ type: 'sub', on: [{ type: 'id', value: 'x' }, 15] });
        });
        it('should handle division', function() {
            EQ(22)({ type: 'div', on: [{ type: 'id', value: 'x' }, 16] });
        });

        it('should handle integer literal', function() {
            EQ(23)(42);
        });
        it('should handle floating literal', function() {
            EQ(24)(6.02e23);
        });
        it('should handle "true" literal', function() {
            EQ(25)(true);
        });
        it('should handle "false" literal', function() {
            EQ(26)(false);
        });

        it('should stringify correctly', function() {
            var actual = query.toString();

            // eslint-disable-next-line vars-on-top
            var expected =
                'x and ' +                       // 0
                '!x and ' +                      // 1
                '-x and ' +                      // 2
                '!(-x) and ' +                   // 3
                'x = 4 and ' +                   // 4
                'x == 5 and ' +                  // 5
                'x == 6 and ' +                  // 6
                'x != 7 and ' +                  // 7
                'x !== 8 and ' +                 // 8
                'x < 9 and ' +                   // 9
                'x <= 10 and ' +                 // 10
                'x > 11 and ' +                  // 11
                'x >= 12 and ' +                 // 12
                'x in (x, 2) and ' +             // 13
                'x like "Bob" and ' +            // 14
                'x like "[Bb]ob" and ' +         // 15
                'x like /R(o)+b/ and ' +         // 16
                'x between y and 10 and ' +      // 17
                'x or y or 42 and ' +            // 18
                'x + 13 and ' +                  // 19
                '14 * x and ' +                  // 20
                'x - 15 and ' +                  // 21
                'x / 16 and ' +                  // 22
                '42 and ' +                      // 23
                '6.02e+23 and ' +                // 24
                'true and ' +                    // 25
                'false' +                        // 26
                '' +
                '';

            // console.log(actual);
            // console.log(expected);
            expect(actual).toEqual(expected);
        });

        // it('should be able to step into generated fn', function() {
        //     debugger
        //     var rec = new Ext.data.Model({ name: 'Mr Boberto' });
        //     var v = query.filter(rec);
        // });
    });

    describe('Parse', function() {
        it('should parse AND', function() {
            var query = new Ext.data.Query('2 and not 4');

            expect(query.ast).toEqual({
                type: 'and',
                on: [2, {
                    type: 'not',
                    on: 4
                }]
            });
        });

        it('should parse OR', function() {
            var query = new Ext.data.Query('2 or !x');

            expect(query.ast).toEqual({
                type: 'or',
                on: [2, {
                    type: 'not',
                    on: {
                        type: 'id',
                        value: 'x'
                    }
                }]
            });
        });

        it('should parse simple regexp', function() {
            var query = new Ext.data.Query('/a/');

            expect(query.ast).toEqual({
                type: 'regexp',
                value: 'a'
            });
        });

        it('should parse regexp with flags', function() {
            var query = new Ext.data.Query('/a/gim');

            expect(query.ast).toEqual({
                type: 'regexp',
                value: 'a',
                flags: 'gim'
            });
        });

        it('should parse in operator', function() {
            var query = new Ext.data.Query('foo in ("a","b")');

            expect(query.ast).toEqual({
                type: 'in',
                on: [{
                    type: 'id',
                    value: 'foo'
                }, {
                    type: 'list',
                    value: [ 'a', 'b' ]
                }]
            });
        });

        it('should parse complex regexp', function() {
            var re = /a\/["']\\\/(?!\/)(\[.+?]|\\.|[^/\\\r\n])+\/[gimyu]{0,5}/,
                query = new Ext.data.Query('/' + re.source + '/');

            // expect(query.ast).toEqual({
            //     type: 'regexp',
            //     value: 'a\\/[\"\']\\\\\\/(?!\\/)(\\[.+?]|\\\\.|[^\\/\\\\\\r\\n])+\\/[gimyu]{0,5}'
            // });

            var re2 = new RegExp(query.ast.value);  // should parse

            // expect(re.source).toBe(re2.source);

            // Chrome can handle these expects but not Safari/Edge (they don't repro
            // the exact source for some reason). But we can make sure the round-trip
            // regex matches correctly.
            expect(re2.test('a/"\\/g(sdf)g/i')).toBe(true);
            expect(re2.test('a/\\/g(sdf)g/i')).toBe(false);
        });

        it('should parse like empty string', function() {
            var query = new Ext.data.Query('foo like ""');

            expect(query.ast).toEqual({
                type: 'like',
                on: [{
                    type: 'id',
                    value: 'foo'
                }, {
                    type: 'string',
                    value: '',
                    re: '.*',
                    flags: 'i'
                }]
            });

            var s = query.toString();

            expect(s).toBe('foo like ""');

            var v = query.fn({ foo: 'abc' });

            expect(v).toBe(true);
        });

        it('should parse like wildcards', function() {
            var query = new Ext.data.Query('foo like "Bob%"');

            expect(query.ast).toEqual({
                type: 'like',
                on: [{
                    type: 'id',
                    value: 'foo'
                }, {
                    type: 'string',
                    value: 'Bob%',
                    re: '^Bob.*$'
                }]
            });
        });

        it('should parse regexp on rhs', function() {
            var query = new Ext.data.Query('foo like /a[\'"](foo|bar)/');

            expect(query.ast).toEqual({
                type: 'like',
                on: [{
                    type: 'id',
                    value: 'foo'
                }, {
                    type: 'regexp',
                    value: 'a[\'"](foo|bar)'
                }]
            });
        });

        it('should parse AND sequence', function() {
            var query = new Ext.data.Query('a < 4 and b in (4,5,"a") and c != d');

            expect(query.ast).toEqual({
                type: 'and',
                on: [{
                    type: 'lt',
                    on: [{
                        type: 'id',
                        value: 'a'
                    }, 4]
                }, {
                    type: 'in',
                    on: [{
                        type: 'id',
                        value: 'b'
                    }, {
                        type: 'list',
                        value: [4, 5, 'a']
                    }]
                }, {
                    type: 'ne',
                    on: [{
                        type: 'id',
                        value: 'c'
                    }, {
                        type: 'id',
                        value: 'd'
                    }]
                }]
            });

            var s = query.toString();

            expect(s).toBe('a < 4 and b in (4, 5, "a") and c != d');
        });

        it('should parse OR sequence', function() {
            var query = new Ext.data.Query('a < 4 or b in (4,5,"a") or c != d');

            expect(query.ast).toEqual({
                type: 'or',
                on: [{
                    type: 'lt',
                    on: [{
                        type: 'id',
                        value: 'a'
                    }, 4]
                }, {
                    type: 'in',
                    on: [{
                        type: 'id',
                        value: 'b'
                    }, {
                        type: 'list',
                        value: [4, 5, 'a']
                    }]
                }, {
                    type: 'ne',
                    on: [{
                        type: 'id',
                        value: 'c'
                    }, {
                        type: 'id',
                        value: 'd'
                    }]
                }]
            });

            var s = query.toString();

            expect(s).toBe('a < 4 or b in (4, 5, "a") or c != d');
        });
    });

    describe('toString', function() {
        it('should stringify arithmetic without parens', function() {
            var query = new Ext.data.Query('a + b * c');

            expect(query.ast).toEqual({
                type: 'add',
                on: [{
                    type: 'id',
                    value: 'a'
                }, {
                    type: 'mul',
                    on: [{
                        type: 'id',
                        value: 'b'
                    }, {
                        type: 'id',
                        value: 'c'
                    }]
                }]
            });

            var str = query.toString();

            expect(str).toBe('a + b * c');

            var data = { a: 2, b: 10, c: 123 },
                v = query.fn(data);

            expect(v).toBe(2 + 10 * 123);
        });

        it('should stringify arithmetic with parens', function() {
            var query = new Ext.data.Query('(a + b) * c');

            expect(query.ast).toEqual({
                type: 'mul',
                on: [{
                    type: 'add',
                    on: [{
                        type: 'id',
                        value: 'a'
                    }, {
                        type: 'id',
                        value: 'b'
                    }]
                }, {
                    type: 'id',
                    value: 'c'
                }]
            });

            var str = query.toString();

            expect(str).toBe('(a + b) * c');

            var data = { a: 2, b: 10, c: 123 };

            var v = query.fn(data);

            expect(v).toBe((2 + 10) * 123);
        });

        it('should stringify relational operators with parens', function() {
            var query = new Ext.data.Query('(a && b) <= c');

            expect(query.ast).toEqual({
                type: 'le',
                on: [{
                    type: 'and',
                    on: [{
                        type: 'id',
                        value: 'a'
                    }, {
                        type: 'id',
                        value: 'b'
                    }]
                }, {
                    type: 'id',
                    value: 'c'
                }]
            });

            var str = query.toString();

            expect(str).toBe('(a and b) <= c');

            var data = { a: 2, b: 10, c: 123 };

            var v = query.fn(data);

            expect(v).toBe((2 && 10) <= 123);
        });

        it('should stringify in/like operators', function() {
            var query = new Ext.data.Query('a in (1,2,"a") || c like /x[\'"\\/]/i');

            expect(query.ast).toEqual({
                type: 'or',
                on: [{
                    type: 'in',
                    on: [{
                        type: 'id',
                        value: 'a'
                    }, {
                        type: 'list',
                        value: [1, 2, 'a']
                    }]
                }, {
                    type: 'like',
                    on: [{
                        type: 'id',
                        value: 'c'
                    }, {
                        type: 'regexp',
                        value: 'x[\'"\\/]',
                        flags: 'i'
                    }]
                }]
            });

            var str = query.toString();

            expect(str).toBe('a in (1, 2, "a") or c like /x[\'"\\/]/i');

            var v = query.fn({ a: 2, c: 'X"' });

            expect(v).toBe(true);

            v = query.fn({ a: 21, c: 'X"' });
            expect(v).toBe(true);

            v = query.fn({ a: 21, c: 'X' });
            expect(v).toBe(false);
        });

        it('should handle unary operators', function() {
            var query = new Ext.data.Query('!a = !-c');

            expect(query.ast).toEqual({
                type: 'eq',
                on: [{
                    type: 'not',
                    on: {
                        type: 'id',
                        value: 'a'
                    }
                }, {
                    type: 'not',
                    on: {
                        type: 'neg',
                        on: {
                            type: 'id',
                            value: 'c'
                        }
                    }
                }]
            });

            var str, v;

            str = query.toString();
            expect(str).toBe('!a = !(-c)');

            v = query.fn({ a: 0, c: 1 });

            expect(v).toBe(!0 == !-(1));  // eslint-disable-line eqeqeq
        });

        it('should handle complex unary operators', function() {
            var query = new Ext.data.Query('!(a || b) <> !-(c + d)');

            expect(query.ast).toEqual({
                type: 'ne',
                on: [{
                    type: 'not',
                    on: {
                        type: 'or',
                        on: [{
                            type: 'id',
                            value: 'a'
                        }, {
                            type: 'id',
                            value: 'b'
                        }]
                    }
                }, {
                    type: 'not',
                    on: {
                        type: 'neg',
                        on: {
                            type: 'add',
                            on: [{
                                type: 'id',
                                value: 'c'
                            }, {
                                type: 'id',
                                value: 'd'
                            }]
                        }
                    }
                }]
            });

            var str, v;

            str = query.toString();
            expect(str).toBe('!(a or b) != !(-(c + d))');

            v = query.fn({ a: 0, b: 2, c: 1, d: 3 });

            expect(v).toBe(!(0 || 2) != !-(1 + 3));  // eslint-disable-line eqeqeq
        });

        it('should handle like wildcards', function() {
            var parser = Ext.data.query.Parser.fly(),
                s, pattern;

            function likeToRe(s) {
                var node = {
                    type: 'string',
                    value: s
                };

                parser.likeToRe(node);
                pattern = !node.flags;

                return node.re;
            }

            s = likeToRe('\\[Bob');
            expect(s).toBe('\\[Bob');
            expect(pattern).toBe(false);

            s = likeToRe('[BR]ob');
            expect(s).toBe('^[BR]ob$');
            expect(pattern).toBe(true);

            s = likeToRe('[BR]ob[^a-e\\]]');
            expect(s).toBe('^[BR]ob[^a-e\\]]$');
            expect(pattern).toBe(true);

            s = likeToRe('[BR]ob[^a-e\\]]_foo%');
            expect(s).toBe('^[BR]ob[^a-e\\]].foo.*$');
            expect(pattern).toBe(true);

            s = likeToRe('[BR]ob[^a-e\\]]\\_foo\\%');
            expect(s).toBe('^[BR]ob[^a-e\\]]_foo%$');
            expect(pattern).toBe(true);

            s = likeToRe('[BR]ob[^a-e\\]]?foo*');
            expect(s).toBe('^[BR]ob[^a-e\\]].foo.*$');
            expect(pattern).toBe(true);

            s = likeToRe('[BR]ob[^a-e\\]]\\?foo\\*');
            expect(s).toBe('^[BR]ob[^a-e\\]]\\?foo\\*$');
            expect(pattern).toBe(true);
        });

        it('should handle strings', function() {
            var query = new Ext.data.Query('name like "Bob" or age < 20');

            expect(query.ast).toEqual({
                type: 'or',
                on: [{
                    type: 'like',
                    on: [{
                        type: 'id',
                        value: 'name'
                    }, {
                        type: 'string',
                        value: 'Bob',
                        re: 'Bob',
                        flags: 'i'
                    }]
                }, {
                    type: 'lt',
                    on: [{
                        type: 'id',
                        value: 'age'
                    }, 20]
                }]
            });

            var str, v;

            str = query.toString();
            expect(str).toBe('name like "Bob" or age < 20');

            v = query.fn({ name: 'Bobby', age: 100 });

            expect(v).toBe(true);
        });

        it('should handle dot paths', function() {
            var query = new Ext.data.Query('order.total > 100.5');

            expect(query.ast).toEqual({
                type: 'gt',
                on: [{
                    type: 'id',
                    value: 'order.total'
                }, 100.5]
            });

            var str, v;

            str = query.toString();
            expect(str).toBe('order.total > 100.5');

            v = query.fn({ order: { total: 150 } });

            expect(v).toBe(true);
        });

        it('should handle abs function', function() {
            var query = new Ext.data.Query('abs(-order.total) - 100.5');

            expect(query.ast).toEqual({
                type: 'sub',
                on: [{
                    type: 'fn',
                    fn: 'abs',
                    args: [{
                        type: 'neg',
                        on: {
                            type: 'id',
                            value: 'order.total'
                        }
                    }]
                }, 100.5]
            });

            var str, v;

            str = query.toString();
            expect(str).toBe('abs(-order.total) - 100.5');

            v = query.fn({ order: { total: 150 } });

            expect(v).toBe(49.5);
        });

        it('should handle avg function', function() {
            var query = new Ext.data.Query('avg(50, order.total)');

            expect(query.ast).toEqual({
                type: 'fn',
                fn: 'avg',
                args: [50, {
                    type: 'id',
                    value: 'order.total'
                }]
            });

            var str, v;

            str = query.toString();
            expect(str).toBe('avg(50, order.total)');

            v = query.fn({ order: { total: 150 } });

            expect(v).toBe((50 + 150) / 2);
        });

        it('should handle max function', function() {
            var query = new Ext.data.Query('max(50, order.total)');

            expect(query.ast).toEqual({
                type: 'fn',
                fn: 'max',
                args: [50, {
                    type: 'id',
                    value: 'order.total'
                }]
            });

            var str, v;

            str = query.toString();
            expect(str).toBe('max(50, order.total)');

            v = query.fn({ order: { total: 150 } });

            expect(v).toBe(Math.max(50, 150));
        });

        it('should handle min function', function() {
            var query = new Ext.data.Query('min(50, order.total)');

            expect(query.ast).toEqual({
                type: 'fn',
                fn: 'min',
                args: [50, {
                    type: 'id',
                    value: 'order.total'
                }]
            });

            var str, v;

            str = query.toString();
            expect(str).toBe('min(50, order.total)');

            v = query.fn({ order: { total: 150 } });

            expect(v).toBe(Math.min(50, 150));
        });

        it('should handle sum function', function() {
            var query = new Ext.data.Query('sum(50, order.total)');

            expect(query.ast).toEqual({
                type: 'fn',
                fn: 'sum',
                args: [50, {
                    type: 'id',
                    value: 'order.total'
                }]
            });

            var str, v;

            str = query.toString();
            expect(str).toBe('sum(50, order.total)');

            v = query.fn({ order: { total: 150 } });

            expect(v).toBe(50 + 150);
        });

        it('should handle between operator', function() {
            var query = new Ext.data.Query('order.total between 20*2 and 200');

            expect(query.ast).toEqual({
                type: 'between',
                on: [{
                    type: 'id',
                    value: 'order.total'
                }, {
                    type: 'mul',
                    on: [ 20, 2 ]
                }, 200]
            });

            var str, v;

            str = query.toString();
            expect(str).toBe('order.total between 20 * 2 and 200');

            v = query.fn({ order: { total: 150 } });

            expect(v).toBe(true);

            v = query.fn({ order: { total: 500 } });

            expect(v).toBe(false);
        });

        it('should handle complex between operator', function() {
            var query = new Ext.data.Query('x + (order.total * 1) between 20*2 and (200+20) + 2');

            expect(query.ast).toEqual({
                type: 'add',
                on: [{
                    type: 'add',
                    on: [{
                        type: 'id',
                        value: 'x'
                    }, {
                        type: 'between',
                        on: [{
                            type: 'mul',
                            on: [{
                                type: 'id',
                                value: 'order.total'
                            }, 1]
                        }, {
                            type: 'mul',
                            on: [ 20, 2 ]
                        }, {
                            type: 'add',
                            on: [ 200, 20 ]
                        }]
                    }]
                }, 2]
            });

            var str, v;

            str = query.toString();
            expect(str).toBe('x + (order.total * 1) between 20 * 2 and (200 + 20) + 2');

            v = query.fn({ x: 42, order: { total: 150 } });

            expect(v).toBe(42 + true + 2);

            v = query.fn({ x: 42, order: { total: 500 } });

            expect(v).toBe(42 + false + 2);
        });

        it('should handle records', function() {
            var query = new Ext.data.Query('name like "Bob" and phantom');

            expect(query.ast).toEqual({
                type: 'and',
                on: [{
                    type: 'like',
                    on: [{
                        type: 'id',
                        value: 'name'
                    }, {
                        type: 'string',
                        value: 'Bob',
                        re: 'Bob',
                        flags: 'i'
                    }]
                }, {
                    type: 'id',
                    value: 'phantom'
                }]
            });

            var str, v;

            str = query.toString();
            expect(str).toBe('name like "Bob" and phantom');

            var rec = new Ext.data.Model({ name: 'Mr Boberto' });

            v = query.filter(rec);

            expect(v).toBe(true);
        });
    });

    describe('syntax errors', function() {
        function query(s) {
            try {
                new Ext.data.Query(s);
            }
            catch (e) {
                return e;
            }

            throw new Error('new Query(' + Ext.JSON.encode(s) + ') should fail');
        }

        it('incomplete like charset', function() {
            //             0123456789012345
            var e = query('name like "[Bob"');

            expect(e.message.indexOf('Incomplete character set')).toBeGE(0);
            expect(e.at).toBe(10);
        });
    });

    describe('refresh', function() {
        it('should construct empty and refresh properly', function() {
            var query = new Ext.data.Query();

            expect(query.generation).toBe(1);
            expect(query.ast).toBe(null);
            expect(query.fn).toBe(Ext.returnTrue);
            expect(query.toString()).toBe('');

            query.ast = {
                type: 'and',
                on: [{
                    type: 'like',
                    on: [{
                        type: 'id',
                        value: 'name'
                    }, {
                        type: 'string',
                        value: 'Bob',
                        re: 'Bob',
                        flags: 'i'
                    }]
                }, {
                    type: 'id',
                    value: 'phantom'
                }]
            };

            query.refresh();

            expect(query.fn).not.toBe(Ext.returnTrue);
            expect(query.generation).toBe(2);

            var str, v;

            str = query.toString();
            expect(str).toBe('name like "Bob" and phantom');

            var rec = new Ext.data.Model({ name: 'Mr Boberto' });

            v = query.filter(rec);

            expect(v).toBe(true);

            rec.set('name', 'Flip');

            v = query.filter(rec);

            expect(v).toBe(false);

            query.setSource(null);

            expect(query.generation).toBe(3);
            expect(query.ast).toBe(null);
            expect(query.fn).toBe(Ext.returnTrue);
            expect(query.toString()).toBe('');
        });
    });

    describe('filters', function() {
        it('should convert empty query to null filters', function() {
            var query = new Ext.data.Query(),
                filters = query.getFilters();

            expect(filters).toBe(null);
        });

        it('should convert complex query to undefined filters', function() {
            var query = new Ext.data.Query('!phantom'),
                filters = query.getFilters();

            expect(filters).toBe(undefined);
        });

        it('should convert to filters', function() {
            var query = new Ext.data.Query('a < 4 and name like "Bob" and ' +
                    'state in ("KS","MO") and city like /Spring[Ff]ield/');

            expect(query.ast).toEqual({
                type: 'and',
                on: [{
                    type: 'lt',
                    on: [{
                        type: 'id',
                        value: 'a'
                    }, 4]
                }, {
                    type: 'like',
                    on: [{
                        type: 'id',
                        value: 'name'
                    }, {
                        type: 'string',
                        value: 'Bob',
                        re: 'Bob',
                        flags: 'i'
                    }]
                }, {
                    type: 'in',
                    on: [{
                        type: 'id',
                        value: 'state'
                    }, {
                        type: 'list',
                        value: [
                            'KS', 'MO'
                        ]
                    }]
                }, {
                    type: 'like',
                    on: [{
                        type: 'id',
                        value: 'city'
                    }, {
                        type: 'regexp',
                        value: 'Spring[Ff]ield'
                    }]
                }]
            });

            var filters = query.getFilters();

            expect(filters).toEqual([{
                property: 'a',
                operator: '<',
                value: 4
            }, {
                property: 'name',
                operator: 'like',
                value: 'Bob'
            }, {
                property: 'state',
                operator: 'in',
                value: ['KS', 'MO']
            }, {
                property: 'city',
                operator: '/=',
                value: 'Spring[Ff]ield'
            }]);
        });

        it('should convert filters into query', function() {
            var query = new Ext.data.Query();

            query.setFilters([{
                property: 'a',
                operator: '<',
                value: 4
            }, {
                property: 'name',
                operator: 'like',
                value: 'Bob'
            }, {
                property: 'state',
                operator: 'in',
                value: ['KS', 'MO']
            }, {
                property: 'city',
                operator: '/=',
                value: 'Spring[Ff]ield'
            }]);

            var s = query.toString();

            expect(s).toBe('a < 4 and name like "Bob" and ' +
                'state in ("KS", "MO") and city like /Spring[Ff]ield/');
        });
    }); // filters
});

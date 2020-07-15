topSuite('Ext.parse.Tokenizer', function() {
    var tokenizer,
        OPERATORS = {
            '+': { type: 'operator', name: 'plus', value: '+', is: { operator: true, plus: true } },
            '-': { type: 'operator', name: 'minus', value: '-', is: { operator: true, minus: true } },
            '*': { type: 'operator', name: 'multiple', value: '*', is: { operator: true, multiply: true } },
            '/': { type: 'operator', name: 'divide', value: '/', is: { operator: true, divide: true } },

            '!': { type: 'operator', name: 'not', value: '!', is: { operator: true, not: true } },
            ',': { type: 'operator', name: 'comma', value: ',', is: { operator: true, comma: true } },
            ':': { type: 'operator', name: 'colon', value: ':', is: { operator: true, colon: true } },
            '[': { type: 'operator', name: 'arrayOpen', value: '[', is: { operator: true, arrayOpen: true } },
            ']': { type: 'operator', name: 'arrayClose', value: ']', is: { operator: true, arrayClose: true } },
            '{': { type: 'operator', name: 'curlyOpen', value: '{', is: { operator: true, curlyOpen: true } },
            '}': { type: 'operator', name: 'curlyClose', value: '}', is: { operator: true, curlyClose: true } },
            '(': { type: 'operator', name: 'parenOpen', value: '(', is: { operator: true, parenOpen: true } },
            ')': { type: 'operator', name: 'parenClose', value: ')', is: { operator: true, parenClose: true } }
        };

    beforeEach(function() {
        tokenizer = Ext.parse.Tokenizer.fly();
    });
    afterEach(function() {
        tokenizer.release();
        tokenizer = null;
    });

    function tokenize(tokens, text) {
        var ret = [];

        var tok;

        if (!text) {
            text = tokens;
            tokens = tokenizer;
        }

        tokens.reset(text);

        do {
            ret.push(tok = tokens.next());
        } while (tok && !tok.is.error);

        return ret;
    }

    describe('fly', function() {
        it('should return the same instance', function() {
            var f0 = Ext.parse.Tokenizer.fly();

            f0.release();

            var f1 = Ext.parse.Tokenizer.fly();

            expect(f1).toBe(f0);
            f1.release();

            var f2 = Ext.parse.Tokenizer.fly();

            expect(f2).toBe(f0);
        });
    });

    describe('peek', function() {
        var t0, t1, t2, t3, t4, t5, t6, t7;

        beforeEach(function() {
            if (!t0) {
                tokenizer.reset('abc 123');

                t0 = tokenizer.peek();
                t1 = tokenizer.peek();
                t2 = tokenizer.next();

                t3 = tokenizer.peek();
                t4 = tokenizer.peek();
                t5 = tokenizer.next();

                t6 = tokenizer.peek();
                t7 = tokenizer.next();
            }
        });

        it('should return the first token', function() {
            expect(t0).toEqual({
                type: 'ident',
                is: {
                    ident: true
                },
                value: 'abc'
            });
        });

        it('should return the same first token', function() {
            expect(t1).toBe(t0);
        });

        it('should return and consume the same first token', function() {
            expect(t2).toBe(t0);
        });

        it('should return the second token', function() {
            expect(t3).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    number: true,
                    type: 'number'
                },
                value: 123
            });
        });

        it('should return the same second token', function() {
            expect(t4).toBe(t3);
        });

        it('should return and consume the same second token', function() {
            expect(t5).toBe(t3);
        });

        it('should detect end of tokens via peek', function() {
            expect(t6).toBe(null);
        });

        it('should detect end of tokens via next', function() {
            expect(t7).toBe(null);
        });
    });

    describe('strings', function() {
        it('should handle simple strings', function() {
            tokenizer.reset('"String 1" "String 2"');

            var t0 = tokenizer.next();

            var t1 = tokenizer.next();

            var t3 = tokenizer.next();

            var t4 = tokenizer.next();

            expect(t0).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    string: true,
                    type: 'string'
                },
                value: 'String 1'
            });

            expect(t1).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    string: true,
                    type: 'string'
                },
                value: 'String 2'
            });

            expect(t3).toBe(null);
            expect(t4).toBe(null);
        });

        it('should handle both types of quotes and escapes', function() {
            tokenizer.reset('\'String 1"\' "\'String\\\\\\\" 2\'"');

            var t0 = tokenizer.next();

            var t1 = tokenizer.next();

            var t3 = tokenizer.next();

            expect(t0).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    string: true,
                    type: 'string'
                },
                value: 'String 1"'
            });

            expect(t1).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    string: true,
                    type: 'string'
                },
                value: '\'String\\" 2\''
            });

            expect(t3).toBe(null);
        });
    });

    describe('booleans', function() {
        it('should handle true', function() {
            tokenizer.reset('true true');

            var t0 = tokenizer.next();

            var t1 = tokenizer.next();

            var t2 = tokenizer.next();

            expect(t0).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    boolean: true,
                    type: 'boolean'
                },
                value: true
            });

            expect(t0).toBe(t1);  // should reuse the same token instance

            expect(t2).toBe(null);
        });

        it('should handle false', function() {
            tokenizer.reset('  false  \t false  ');

            var t0 = tokenizer.next();

            var t1 = tokenizer.next();

            var t2 = tokenizer.next();

            expect(t0).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    boolean: true,
                    type: 'boolean'
                },
                value: false
            });

            expect(t0).toBe(t1);  // should reuse the same token instance

            expect(t2).toBe(null);
        });

        it('should handle mixed values', function() {
            tokenizer.reset('\tfalse\ttrue\t');

            var t0 = tokenizer.next();

            var t1 = tokenizer.next();

            var t2 = tokenizer.next();

            expect(t0).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    boolean: true,
                    type: 'boolean'
                },
                value: false
            });

            expect(t1).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    boolean: true,
                    type: 'boolean'
                },
                value: true
            });

            expect(t2).toBe(null);
        });
    });

    describe('identifiers', function() {
        it('should handle simple identifiers', function() {
            tokenizer.reset('  foo  \tbar');

            var t0 = tokenizer.next();

            var t1 = tokenizer.next();

            var t2 = tokenizer.next();

            expect(t0).toEqual({
                type: 'ident',
                is: {
                    ident: true
                },
                value: 'foo'
            });

            expect(t1).toEqual({
                type: 'ident',
                is: {
                    ident: true
                },
                value: 'bar'
            });

            expect(t2).toBe(null);
        });

        it('should handle dotpath identifiers', function() {
            tokenizer.reset('foo.bar\t bar.baz.zip ');

            var t0 = tokenizer.next();

            var t1 = tokenizer.next();

            var t2 = tokenizer.next();

            expect(t0).toEqual({
                type: 'ident',
                is: {
                    ident: true
                },
                value: 'foo.bar'
            });

            expect(t1).toEqual({
                type: 'ident',
                is: {
                    ident: true
                },
                value: 'bar.baz.zip'
            });

            expect(t2).toBe(null);
        });
    });

    describe('null', function() {
        it('should handle null', function() {
            tokenizer.reset('  null  \tnull');

            var t0 = tokenizer.next();

            var t1 = tokenizer.next();

            var t2 = tokenizer.next();

            expect(t0).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    nil: true
                },
                value: null
            });

            expect(t0).toBe(t1);  // should reuse the same token instance

            expect(t2).toBe(null);
        });
    });

    describe('numbers', function() {
        it('should be able to parse integers', function() {
            tokenizer.reset('  427  \t23');

            var t0 = tokenizer.next();

            var t1 = tokenizer.next();

            var t2 = tokenizer.next();

            expect(t0).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    number: true,
                    type: 'number'
                },
                value: 427
            });

            expect(t1).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    number: true,
                    type: 'number'
                },
                value: 23
            });

            expect(t2).toBe(null);
        });

        it('should be able to parse signed integers', function() {
            tokenizer.reset('  +427  \t-23 21');

            var t0 = tokenizer.next();

            var t1 = tokenizer.next();

            var t2 = tokenizer.next();

            var t3 = tokenizer.next();

            var t4 = tokenizer.next();

            var t5 = tokenizer.next();

            expect(t0).toEqual(OPERATORS['+']);
            expect(t1).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    number: true,
                    type: 'number'
                },
                value: 427
            });

            expect(t2).toEqual(OPERATORS['-']);
            expect(t3).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    number: true,
                    type: 'number'
                },
                value: 23
            });

            expect(t4).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    number: true,
                    type: 'number'
                },
                value: 21
            });

            expect(t5).toBe(null);
        });

        it('should be able to parse decimals', function() {
            tokenizer.reset('  +.427  \t-23.234 2.1');

            var t0 = tokenizer.next();

            var t1 = tokenizer.next();

            var t2 = tokenizer.next();

            var t3 = tokenizer.next();

            var t4 = tokenizer.next();

            var t5 = tokenizer.next();

            expect(t0).toEqual(OPERATORS['+']);
            expect(t1).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    number: true,
                    type: 'number'
                },
                value: 0.427
            });

            expect(t2).toEqual(OPERATORS['-']);
            expect(t3).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    number: true,
                    type: 'number'
                },
                value: 23.234
            });

            expect(t4).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    number: true,
                    type: 'number'
                },
                value: 2.1
            });

            expect(t5).toBe(null);
        });

        it('should be able to parse exponentials', function() {
            tokenizer.reset('  +.42e7  \t-23.234e+2 2.1e-21');

            var t0 = tokenizer.next();

            var t1 = tokenizer.next();

            var t2 = tokenizer.next();

            var t3 = tokenizer.next();

            var t4 = tokenizer.next();

            var t5 = tokenizer.next();

            expect(t0).toEqual(OPERATORS['+']);
            expect(t1).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    number: true,
                    type: 'number'
                },
                value: 0.42e7
            });

            expect(t2).toEqual(OPERATORS['-']);
            expect(t3).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    number: true,
                    type: 'number'
                },
                value: 23.234e2
            });

            expect(t4).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    number: true,
                    type: 'number'
                },
                value: 2.1e-21
            });

            expect(t5).toBe(null);
        });
    });

    describe('operators', function() {
        it('should be able to parse numbers and operators w/o spaces', function() {
            tokenizer.reset('1+2');

            var t0 = tokenizer.next();

            var t1 = tokenizer.next();

            var t2 = tokenizer.next();

            expect(t0).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    number: true,
                    type: 'number'
                },
                value: 1
            });

            expect(t1).toEqual(OPERATORS['+']);

            expect(t2).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    number: true,
                    type: 'number'
                },
                value: 2
            });
        });

        it('should be able to parse exclamation', function() {
            tokenizer.reset(' ! ! ');

            var t0 = tokenizer.next(),
                t1 = tokenizer.next(),
                t2 = tokenizer.next();

            expect(t0).toEqual(OPERATORS['!']);

            expect(t0).toBe(t1);  // should reuse the same token instance

            expect(t2).toBe(null);
        });

        it('should be able to parse comma', function() {
            tokenizer.reset(',,');

            var t0 = tokenizer.next();

            var t1 = tokenizer.next();

            var t2 = tokenizer.next();

            expect(t0).toEqual(OPERATORS[',']);

            expect(t0).toBe(t1);  // should reuse the same token instance

            expect(t2).toBe(null);
        });

        it('should be able to parse colon', function() {
            tokenizer.reset('\t:\t: ');

            var t0 = tokenizer.next();

            var t1 = tokenizer.next();

            var t2 = tokenizer.next();

            expect(t0).toEqual(OPERATORS[':']);

            expect(t0).toBe(t1);  // should reuse the same token instance

            expect(t2).toBe(null);
        });

        it('should be able to parse array open', function() {
            tokenizer.reset('[ [');

            var t0 = tokenizer.next();

            var t1 = tokenizer.next();

            var t2 = tokenizer.next();

            expect(t0).toEqual(OPERATORS['[']);

            expect(t0).toBe(t1);  // should reuse the same token instance

            expect(t2).toBe(null);
        });

        it('should be able to parse array close', function() {
            tokenizer.reset(' ]] ');

            var t0 = tokenizer.next();

            var t1 = tokenizer.next();

            var t2 = tokenizer.next();

            expect(t0).toEqual(OPERATORS[']']);

            expect(t0).toBe(t1);  // should reuse the same token instance

            expect(t2).toBe(null);
        });

        it('should be able to parse brace open', function() {
            tokenizer.reset('\t  {{\t');

            var t0 = tokenizer.next();

            var t1 = tokenizer.next();

            var t2 = tokenizer.next();

            expect(t0).toEqual(OPERATORS['{']);

            expect(t0).toBe(t1);  // should reuse the same token instance

            expect(t2).toBe(null);
        });

        it('should be able to parse brace close', function() {
            tokenizer.reset(' }}');

            var t0 = tokenizer.next();

            var t1 = tokenizer.next();

            var t2 = tokenizer.next();

            expect(t0).toEqual(OPERATORS['}']);

            expect(t0).toBe(t1);  // should reuse the same token instance

            expect(t2).toBe(null);
        });

        it('should be able to parse paren open', function() {
            tokenizer.reset(' ( (\t');

            var t0 = tokenizer.next();

            var t1 = tokenizer.next();

            var t2 = tokenizer.next();

            expect(t0).toEqual(OPERATORS['(']);

            expect(t0).toBe(t1);  // should reuse the same token instance

            expect(t2).toBe(null);
        });

        it('should be able to parse paren close', function() {
            tokenizer.reset('))');

            var t0 = tokenizer.next();

            var t1 = tokenizer.next();

            var t2 = tokenizer.next();

            expect(t0).toEqual(OPERATORS[')']);

            expect(t0).toBe(t1);  // should reuse the same token instance

            expect(t2).toBe(null);
        });
    });

    describe('custom operators', function() {
        var customTokenizer,
            tokens;

        beforeEach(function() {
            customTokenizer = new Ext.parse.Tokenizer({
                operators: {
                    '!': null,  // disable ! operator
                    '$': 'dollar'
                }
            });

            tokens = tokenize(customTokenizer, '$!');
        });

        it('should tokenize custom operator', function() {
            expect(tokens[0]).toEqual({
                type: 'operator',
                value: '$',
                name: 'dollar',
                is: {
                    operator: true,
                    dollar: true
                }
            });
        });

        it('should disable standard operator', function() {
            var tok = tokens[1];

            expect(tok instanceof Error).toBe(true);
            expect(tok.type).toBe('error');
            expect(tok.is.error).toBe(true);
            expect(tok.at).toBe(1);
        });
    });

    describe('multi-character operators', function() {
        /* eslint-disable key-spacing */
        var MULTIOP = {
            '!=': { type: 'operator', name: 'ne', value: '!=', is: { operator: true, ne: true } },
            '==': { type: 'operator', name: 'eq', value: '==', is: { operator: true, eq: true } },

            '<=': { type: 'operator', name: 'le', value: '<=', is: { operator: true, le: true } },
            '<':  { type: 'operator', name: 'lt', value: '<', is: { operator: true, lt: true } },
            '>':  { type: 'operator', name: 'gt', value: '>', is: { operator: true, gt: true } },
            '>=': { type: 'operator', name: 'ge', value: '>=', is: { operator: true, ge: true } },

            '=':   { type: 'operator', name: 'assign', value: '=', is: { operator: true, assign: true } },
            '===': { type: 'operator', name: 'seq', value: '===', is: { operator: true, seq: true } },
            '!==': { type: 'operator', name: 'sne', value: '!==', is: { operator: true, sne: true } }
        };

        var multiOpTokenizer,
            tokens;

        beforeEach(function() {
            multiOpTokenizer = new Ext.parse.Tokenizer({
                operators: {
                    '!=': 'ne',
                    '==': 'eq',
                    '<=': 'le',
                    '<': 'lt',
                    '>': 'gt',
                    '>=': 'ge',
                    '=': 'assign',
                    '===': 'seq',
                    '!==': 'sne'
                }
            });

            tokens = tokenize(multiOpTokenizer, '< <= > >= \t=\t!= == === !==');
        });

        it('should tokenize less-than operator', function() {
            expect(tokens[0]).toEqual(MULTIOP['<']);
        });

        it('should tokenize less-than-or-equal operator', function() {
            expect(tokens[1]).toEqual(MULTIOP['<=']);
        });

        it('should tokenize greater-than operator', function() {
            expect(tokens[2]).toEqual(MULTIOP['>']);
        });

        it('should tokenize greater-than-or-equal operator', function() {
            expect(tokens[3]).toEqual(MULTIOP['>=']);
        });

        it('should tokenize assignment operator', function() {
            expect(tokens[4]).toEqual(MULTIOP['=']);
        });

        it('should tokenize not-equals operator', function() {
            expect(tokens[5]).toEqual(MULTIOP['!=']);
        });

        it('should tokenize equals operator', function() {
            expect(tokens[6]).toEqual(MULTIOP['==']);
        });

        it('should tokenize strict equality operator', function() {
            expect(tokens[7]).toEqual(MULTIOP['===']);
        });

        it('should tokenize strict inequality operator', function() {
            expect(tokens[8]).toEqual(MULTIOP['!==']);
        });
    });

    describe('all the things', function() {
        var tokens;

        beforeEach(function() {
            if (tokens) {
                return;
            }

            tokens = tokenize('foo: bar ( ' +
                    '"\\"a\\\\b\'):\\"" , ' +
                    '32,' +
                    '! zip.fiz:woot(true,null)' +
                '):ack(\'x\\\'"y\', 32e-21 , -3.14e0 )');
        });

        it('should parse token 0', function() {
            expect(tokens[0]).toEqual(
                { type: 'ident', is: { ident: true }, value: 'foo' }
            );
        });

        it('should parse token 1', function() {
            expect(tokens[1]).toEqual(OPERATORS[':']);
        });

        it('should parse token 2', function() {
            expect(tokens[2]).toEqual(
                { type: 'ident', is: { ident: true }, value: 'bar' }
            );
        });

        it('should parse token 3', function() {
            expect(tokens[3]).toEqual(OPERATORS['(']);
        });

        it('should parse token 4', function() {
            expect(tokens[4]).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    string: true,
                    type: 'string'
                },
                value: '"a\\b\'):"'
            });
        });

        it('should parse token 5', function() {
            expect(tokens[5]).toEqual(OPERATORS[',']);
        });

        it('should parse token 6', function() {
            expect(tokens[6]).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    number: true,
                    type: 'number'
                },
                value: 32
            });
        });

        it('should parse token 7', function() {
            expect(tokens[7]).toEqual(OPERATORS[',']);
        });

        it('should parse token 8', function() {
            expect(tokens[8]).toEqual(OPERATORS['!']);
        });

        it('should parse token 9', function() {
            expect(tokens[9]).toEqual(
                { type: 'ident', is: { ident: true }, value: 'zip.fiz' }
            );
        });

        it('should parse token 10', function() {
            expect(tokens[10]).toEqual(OPERATORS[':']);
        });

        it('should parse token 11', function() {
            expect(tokens[11]).toEqual(
                { type: 'ident', is: { ident: true }, value: 'woot' }
            );
        });

        it('should parse token 12', function() {
            expect(tokens[12]).toEqual(OPERATORS['(']);
        });

        it('should parse token 13', function() {
            expect(tokens[13]).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    boolean: true,
                    type: 'boolean'
                },
                value: true
            });
        });

        it('should parse token 14', function() {
            expect(tokens[14]).toEqual(OPERATORS[',']);
        });

        it('should parse token 15', function() {
            expect(tokens[15]).toEqual(
                { type: 'literal', is: { literal: true, nil: true }, value: null }
            );
        });

        it('should parse token 16', function() {
            expect(tokens[16]).toEqual(OPERATORS[')']);
        });

        it('should parse token 17', function() {
            expect(tokens[17]).toEqual(OPERATORS[')']);
        });

        it('should parse token 18', function() {
            expect(tokens[18]).toEqual(OPERATORS[':']);
        });

        it('should parse token 19', function() {
            expect(tokens[19]).toEqual(
                { type: 'ident', is: { ident: true }, value: 'ack' }
            );
        });

        it('should parse token 20', function() {
            expect(tokens[20]).toEqual(OPERATORS['(']);
        });

        it('should parse token 21', function() {
            expect(tokens[21]).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    string: true,
                    type: 'string'
                },
                value: 'x\'"y'
            });
        });

        it('should parse token 22', function() {
            expect(tokens[22]).toEqual(OPERATORS[',']);
        });

        it('should parse token 23', function() {
            expect(tokens[23]).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    number: true,
                    type: 'number'
                },
                value: 32e-21
            });
        });

        it('should parse token 24', function() {
            expect(tokens[24]).toEqual(OPERATORS[',']);
        });

        it('should parse token 25', function() {
            expect(tokens[24]).toEqual(OPERATORS[',']);
        });

        it('should parse token 26', function() {
            expect(tokens[26]).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    number: true,
                    type: 'number'
                },
                value: 3.14
            });
        });

        it('should parse token 27', function() {
            expect(tokens[27]).toEqual(OPERATORS[')']);
        });

        it('should parse token 28', function() {
            expect(tokens[28]).toBe(null);
        });

        it('should parse all the tokens', function() {
            expect(tokens.length).toBe(29);
        });
    });

    describe('syntax errors', function() {
        it('should catch invalid characters', function() {
            tokenizer.reset('&');

            var t0 = tokenizer.next();

            var t1 = tokenizer.next();

            var t2 = tokenizer.next();

            expect(t0 instanceof Error).toBe(true);
            expect(t0.type).toBe('error');
            expect(t0.is.error).toBe(true);
            expect(t0.at).toBe(0);

            expect(t1).toBe(t0);  // should reuse the same token instance
            expect(t2).toBe(t0);  // never recovers from error
        });

        it('should catch invalid characters after valid ones', function() {
            tokenizer.reset('123&');

            var t0 = tokenizer.next();

            var t1 = tokenizer.next();

            var t2 = tokenizer.next();

            expect(t0).toEqual({
                type: 'literal',
                is: {
                    literal: true,
                    number: true,
                    type: 'number'
                },
                value: 123
            });

            expect(t1 instanceof Error).toBe(true);
            expect(t1.type).toBe('error');
            expect(t1.is.error).toBe(true);
            expect(t1.at).toBe(3);

            expect(t2).toBe(t1);  // never recovers from error
        });

        it('should handle errors after dotpath identifiers', function() {
            tokenizer.reset('foo.bar\t bar.baz.zip &');

            var t0 = tokenizer.next();

            var t1 = tokenizer.next();

            var t2 = tokenizer.next();

            expect(t0).toEqual({
                type: 'ident',
                is: {
                    ident: true
                },
                value: 'foo.bar'
            });

            expect(t1).toEqual({
                type: 'ident',
                is: {
                    ident: true
                },
                value: 'bar.baz.zip'
            });

            expect(t2 instanceof Error).toBe(true);
            expect(t2.type).toBe('error');
            expect(t2.is.error).toBe(true);
            expect(t2.at).toBe(21);
        });

        it('should report dotpaths with adjacent dots', function() {
            tokenizer.reset('foo..bar');

            var t0 = tokenizer.next();

            var t1 = tokenizer.next();

            expect(t0 instanceof Error).toBe(true);
            expect(t0.type).toBe('error');
            expect(t0.is.error).toBe(true);
            expect(t0.at).toBe(4);

            expect(t1).toBe(t0);
        });

        it('should report dotpaths that start with a dot', function() {
            tokenizer.reset(' .foo');

            var t0 = tokenizer.next();

            var t1 = tokenizer.next();

            expect(t0 instanceof Error).toBe(true);
            expect(t0.type).toBe('error');
            expect(t0.is.error).toBe(true);
            expect(t0.at).toBe(1);

            expect(t1).toBe(t0);
        });

        it('should report dotpaths that end with a dot', function() {
            tokenizer.reset('foo.');

            var t0 = tokenizer.next();

            var t1 = tokenizer.next();

            expect(t0 instanceof Error).toBe(true);
            expect(t0.type).toBe('error');
            expect(t0.is.error).toBe(true);
            expect(t0.at).toBe(3);

            expect(t1).toBe(t0);
        });
    });
});

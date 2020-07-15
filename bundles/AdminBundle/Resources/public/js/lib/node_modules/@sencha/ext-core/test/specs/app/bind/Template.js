topSuite("Ext.app.bind.Template", function() {
    var BindTemplate;

    function getNumFragments(tpl) {
        var count = 0;

        for (var i = tpl.buffer.length; i-- > 0;) {
            if (tpl.buffer[i]) {
                ++count;
            }
        }

        return count;
    }

    function getNumSlots(tpl) {
        var count = 0;

        for (var i = tpl.slots.length; i-- > 0;) {
            if (tpl.slots[i]) {
                ++count;
            }
        }

        return count;
    }

    beforeEach(function() {
        BindTemplate = Ext.app.bind.Template;
    });

    describe('tokens', function() {
        it('should parse on first use', function() {
            var tpl = new BindTemplate('Hello {foo}');

            expect(tpl.tokens).toBe(null);

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo']);

            expect(getNumFragments(tpl)).toBe(1);
            expect(getNumSlots(tpl)).toBe(1);
        });

        it('should parse simple names', function() {
            var tpl = new BindTemplate('Hello {foo} {bar}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo', 'bar']);

            expect(getNumFragments(tpl)).toBe(2);
            expect(getNumSlots(tpl)).toBe(2);
        });

        it('should parse dotted names', function() {
            var tpl = new BindTemplate('Hello {foo.bar} {bar.foo}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar', 'bar.foo']);

            expect(getNumFragments(tpl)).toBe(2);
            expect(getNumSlots(tpl)).toBe(2);
        });

        it('should consolidate tokens', function() {
            var tpl = new BindTemplate('Hello {foo.bar} {bar} {foo.bar} {bar}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar', 'bar']);

            expect(getNumFragments(tpl)).toBe(4);
            expect(getNumSlots(tpl)).toBe(4);
        });

        it('should match slots to consolidated tokens', function() {
            //                          1      2       3    4  5        6
            var tpl = new BindTemplate('Hello {foo.bar}{bar} - {foo.bar}{bar}');

            tpl.parse();

            expect(getNumFragments(tpl)).toBe(2);
            expect(getNumSlots(tpl)).toBe(4);

            expect(typeof tpl.slots[1]).toBe('function');
            expect(typeof tpl.slots[2]).toBe('function');
            // slots[3] is null due to " - " in buffer[3]
            expect(typeof tpl.slots[4]).toBe('function');
            expect(typeof tpl.slots[5]).toBe('function');
        });

        it("should not attempt to parse outside of curly braces", function() {
            var tpl = new BindTemplate('Hello `{foo}`!'),
                tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo']);

            var s = tpl.apply([5]);

            expect(s).toBe('Hello `5`!');

        });
    });

    describe('unary operators', function() {
        it('should parse -', function() {
            var tpl = new BindTemplate('Hello {foo.bar + -5}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar']);

            var s = tpl.apply([10]);

            expect(s).toBe('Hello 5!');
        });

        it('should parse - before an expression', function() {
            var tpl = new BindTemplate('Hello {foo.bar + -(bar + 3):number("0.00")}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar', 'bar']);

            var s = tpl.apply([10, 7]);

            expect(s).toBe('Hello 0!'); // 10 - '10.00'
        });

        it('should parse - before an expression and follow parans', function() {
            var tpl = new BindTemplate('Hello {(foo.bar + -(bar + 3)):number("0.00")}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar', 'bar']);

            var s = tpl.apply([10, 7]);

            expect(s).toBe('Hello 0.00!');
        });

        it('should parse - before parans and before literal', function() {
            var tpl = new BindTemplate('Hello {(foo.bar + -(bar +- 3)):number("0.00")}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar', 'bar']);

            var s = tpl.apply([10, 7]);

            expect(s).toBe('Hello 6.00!');
        });

        it('should parse - before parans and before token', function() {
            var tpl = new BindTemplate('Hello {(foo.bar + -(bar -- foo)):number("0.00")}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar', 'bar', 'foo']);

            var s = tpl.apply([10, 7, 4]);

            expect(s).toBe('Hello -1.00!');
        });

        it('should parse @ unary operator', function() {
            var tpl = new BindTemplate('Hello {@Ext.justTemp}!');

            Ext.justTemp = 'foo';
            var s = tpl.apply();

            expect(s).toBe('Hello foo!');
            Ext.justTemp = 'bar';
            s = tpl.apply();
            expect(s).toBe('Hello bar!');
            expect(tpl.isStatic()).toBe(false);
            Ext.justTemp = null;
        });

    });

    describe('binary operators', function() {
        it('should parse + operations', function() {
            var tpl = new BindTemplate('Hello {foo.bar + bar.foo}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar', 'bar.foo']);

            var s = tpl.apply([5, 7]);

            expect(s).toBe('Hello 12!');
        });

        it('should parse - operations', function() {
            var tpl = new BindTemplate('Hello {foo.bar - bar.foo}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar', 'bar.foo']);

            var s = tpl.apply([5, 7]);

            expect(s).toBe('Hello -2!');
        });

        it('should parse * operations', function() {
            var tpl = new BindTemplate('Hello {foo.bar * bar.foo}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar', 'bar.foo']);

            var s = tpl.apply([5, 7]);

            expect(s).toBe('Hello 35!');
        });

        it('should parse / operations', function() {
            var tpl = new BindTemplate('Hello {foo.bar / bar.foo}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar', 'bar.foo']);

            var s = tpl.apply([10, 2]);

            expect(s).toBe('Hello 5!');
        });

        it('should parse > operations', function() {
            var tpl = new BindTemplate('{foo.bar > bar.foo}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar', 'bar.foo']);

            var s = tpl.apply([10, 2]);

            expect(s).toBe('true!');
        });

        it('should parse < operations', function() {
            var tpl = new BindTemplate('{foo.bar < bar.foo}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar', 'bar.foo']);

            var s = tpl.apply([2, 10]);

            expect(s).toBe('true!');
        });

        it('should parse >= operations', function() {
            var tpl = new BindTemplate('{foo.bar >= bar.foo}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar', 'bar.foo']);

            var s = tpl.apply([10, 10]);

            expect(s).toBe('true!');
        });

        it('should parse <= operations', function() {
            var tpl = new BindTemplate('{foo.bar <= bar.foo}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar', 'bar.foo']);

            var s = tpl.apply([10, 10]);

            expect(s).toBe('true!');
        });

        it('should parse === operations', function() {
            var tpl = new BindTemplate('{foo.bar === bar.foo}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar', 'bar.foo']);

            var s = tpl.apply([10, '10']);

            expect(s).toBe('false!');
        });

        it('should parse == operations', function() {
            var tpl = new BindTemplate('{foo.bar == bar.foo}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar', 'bar.foo']);

            var s = tpl.apply([10, '10']);

            expect(s).toBe('true!');
        });

        it('should parse !== operations', function() {
            var tpl = new BindTemplate('{foo.bar !== bar.foo}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar', 'bar.foo']);

            var s = tpl.apply([10, '10']);

            expect(s).toBe('true!');
        });

        it('should parse != operations', function() {
            var tpl = new BindTemplate('{foo.bar != bar.foo}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar', 'bar.foo']);

            var s = tpl.apply([10, '10']);

            expect(s).toBe('false!');
        });

        it('should parse && operations', function() {
            var tpl = new BindTemplate('{foo.bar > bar.foo && bar > 5}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar', 'bar.foo', 'bar']);

            var s = tpl.apply([10, 5, 3]);

            expect(s).toBe('false!');
        });

        it('should parse || operations', function() {
            var tpl = new BindTemplate('{foo.bar > bar.foo || bar > 5}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar', 'bar.foo', 'bar']);

            var s = tpl.apply([10, 5, 3]);

            expect(s).toBe('true!');
        });

        it('should parse operations by priority', function() {
            var tpl = new BindTemplate('Hello {foo.bar * foo + bar / bar.foo}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar', 'foo', 'bar', 'bar.foo']);

            var s = tpl.apply([10, 2, 5, 2]);

            expect(s).toBe('Hello 22.5!');
        });
    });

    describe('ternary operator', function() {
        it('should parse token condition', function() {
            var tpl = new BindTemplate('Hello {foo ? 5 : 6}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo']);

            var s = tpl.apply([true]);

            expect(s).toBe('Hello 5');
        });

        it('should parse binary condition >', function() {
            var tpl = new BindTemplate('Hello {foo > 3 ? 5 : 6}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo']);

            var s = tpl.apply([2]);

            expect(s).toBe('Hello 6');
        });

        it('should parse binary condition >=', function() {
            var tpl = new BindTemplate('Hello {foo >= 3 ? 5 : 6}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo']);

            var s = tpl.apply([3]);

            expect(s).toBe('Hello 5');
        });

        it('should parse binary condition <', function() {
            var tpl = new BindTemplate('Hello {foo < 3 ? 5 : 6}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo']);

            var s = tpl.apply([2]);

            expect(s).toBe('Hello 5');
        });

        it('should parse binary condition <=', function() {
            var tpl = new BindTemplate('Hello {foo <= 3 ? 5 : 6}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo']);

            var s = tpl.apply([4]);

            expect(s).toBe('Hello 6');
        });

        it('should parse binary condition ==', function() {
            var tpl = new BindTemplate('Hello {foo == "3" ? 5 : 6}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo']);

            var s = tpl.apply([3]);

            expect(s).toBe('Hello 5');
        });

        it('should parse binary condition ===', function() {
            var tpl = new BindTemplate('Hello {foo === "3" ? 5 : 6}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo']);

            var s = tpl.apply([3]);

            expect(s).toBe('Hello 6');
        });

        it('should parse binary condition !=', function() {
            var tpl = new BindTemplate('Hello {foo != "3" ? 5 : 6}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo']);

            var s = tpl.apply([3]);

            expect(s).toBe('Hello 6');
        });

        it('should parse binary condition !==', function() {
            var tpl = new BindTemplate('Hello {foo !== "3" ? 5 : 6}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo']);

            var s = tpl.apply([3]);

            expect(s).toBe('Hello 5');
        });

        it('should parse condition with format fn', function() {
            var tpl = new BindTemplate('Hello {foo:this.fn ? 5 : 6}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo']);

            var s = tpl.apply([4], {
                fn: function() { return false; }
            });

            expect(s).toBe('Hello 6');
        });

        it('should parse condition with format fn and args', function() {
            var tpl = new BindTemplate('Hello {foo:this.fn("testing", 4) ? 5 : 6}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo']);

            var s = tpl.apply([4], {
                fn: function() { return false; }
            });

            expect(s).toBe('Hello 6');
        });

        it('should parse condition with chained format fn and args', function() {
            var tpl = new BindTemplate('Hello {foo:this.fn("testing", 4):this.fn2 ? 5 : 6}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo']);

            var s = tpl.apply([4], {
                fn: function() { return false; },
                fn2: function() { return true; }
            });

            expect(s).toBe('Hello 5');
        });

        it('should parse condition with chained and nested format fn and args', function() {
            var tpl = new BindTemplate('Hello {foo:this.fn("testing", bar:this.fn3(null, true)):this.fn2 ? 5 : 6}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo', 'bar']);

            var s = tpl.apply([4], {
                fn: function() { return false; },
                fn2: function() { return true; },
                fn3: function() { return 5; }
            });

            expect(s).toBe('Hello 5');
        });

        it('should parse true part with literal', function() {
            var tpl = new BindTemplate('Hello {foo ? "test" : 6}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo']);

            var s = tpl.apply([true]);

            expect(s).toBe('Hello test');
        });

        it('should parse true part with number', function() {
            var tpl = new BindTemplate('Hello {foo ? .04 : 6}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo']);

            var s = tpl.apply([true]);

            expect(s).toBe('Hello 0.04');
        });

        it('should parse true part with null', function() {
            var tpl = new BindTemplate('Hello {foo ? null : 6}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo']);

            var s = tpl.apply([true]);

            expect(s).toBe('Hello ');
        });

        it('should parse true part with boolean', function() {
            var tpl = new BindTemplate('Hello {foo ? true : 6}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo']);

            var s = tpl.apply([true]);

            expect(s).toBe('Hello true');
        });

        it('should parse true part enclosed in parans with simple format fn', function() {
            var tpl = new BindTemplate('Hello {foo ? (bar:number) : 6}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo', 'bar']);

            var s = tpl.apply([true, 5]);

            expect(s).toBe('Hello 5');
        });

        it('should parse true part enclosed in parans with format fn and args', function() {
            var tpl = new BindTemplate('Hello {foo ? (bar:number("0.00")) : 6}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo', 'bar']);

            var s = tpl.apply([true, 5]);

            expect(s).toBe('Hello 5.00');
        });

        it('should parse true part with basic algebra inside parans', function() {
            var tpl = new BindTemplate('Hello {foo ? (bar + 5 * foo.bar) : 6}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo', 'bar', 'foo.bar']);

            var s = tpl.apply([true, 4, 3]);

            expect(s).toBe('Hello 19');
        });

        it('should parse true part with basic algebra and no parans', function() {
            var tpl = new BindTemplate('Hello {foo ? bar + 5 * foo.bar : 6}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo', 'bar', 'foo.bar']);

            var s = tpl.apply([true, 4, 3]);

            expect(s).toBe('Hello 19');
        });

        it('should parse true part with basic algebra and format fn', function() {
            var tpl = new BindTemplate('Hello {foo ? ( ( bar + 5 * foo.bar:this.fn( 2 ) / 4 ):round:number("0.00") ) : 6}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo', 'bar', 'foo.bar']);

            var s = tpl.apply([true, 4, 3], {
                fn: function(v, factor) {
                    return v * factor;
                }
            });

            expect(s).toBe('Hello 12.00');
        });

        it('should parse true part with nested ternary', function() {
            var tpl = new BindTemplate('Hello {foo ? (bar ? (foo.bar + 9) : "failed") : 6}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo', 'bar', 'foo.bar']);

            var s = tpl.apply([true, 4, 3]);

            expect(s).toBe('Hello 12');
        });

        it('should parse true part with nested ternary and no parans', function() {
            var tpl = new BindTemplate('Hello {foo ? bar ? foo.bar + 9 : "failed" : 6}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo', 'bar', 'foo.bar']);

            var s = tpl.apply([true, 4, 3]);

            expect(s).toBe('Hello 12');
        });

    });

    describe('combined unary and binary operators', function() {
        it('should parse binary and unary -', function() {
            var tpl = new BindTemplate('Hello {foo.bar --5}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar']);

            var s = tpl.apply([10]);

            expect(s).toBe('Hello 15!');
        });

        it('should parse binary + and unary -', function() {
            var tpl = new BindTemplate('Hello {foo.bar +-5}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar']);

            var s = tpl.apply([10]);

            expect(s).toBe('Hello 5!');
        });

        it('should parse binary + and unary ! and -', function() {
            var tpl = new BindTemplate('Hello {foo.bar + !-5}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar']);

            var s = tpl.apply([10]);

            expect(s).toBe('Hello 10!'); // 10 + false
        });

        it('should parse ! operator in front of open paran', function() {
            var tpl = new BindTemplate('Hello {foo.bar + !(bar:number("0.00"))}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar', 'bar']);

            var s = tpl.apply([10, 4]);

            expect(s).toBe('Hello 10!'); // 10 + false
        });

        it('should parse ! operator in front of a token', function() {
            var tpl = new BindTemplate('Hello {foo.bar + !bar}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar', 'bar']);

            var s = tpl.apply([10, false]);

            expect(s).toBe('Hello 11!'); // 10 + true
        });

        it('should parse ! operator in front of a @', function() {
            var tpl = new BindTemplate('Hello {foo.bar + !@Ext.versions.core}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar']);

            var s = tpl.apply([10]);

            expect(s).toBe('Hello 10!'); // 10 + false
        });

    });

    describe('algebra', function() {

        it('should parse basic algebra', function() {
            var tpl = new BindTemplate('{foo:round + 2}');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo']);

            var s = tpl.apply([15.6]);

            expect(s).toBe(18);
        });

        it('should parse operations by priority', function() {
            var tpl = new BindTemplate('Hello {(foo.bar * foo + bar +-test ? 7 : 1):this.thing(3) / bar.foo}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar', 'foo', 'bar', 'test', 'bar.foo']);

            var s = tpl.apply([10, 2, 5, 25, 2], {
                thing: function(v, factor) {
                    return v * factor;
                }
            });

            expect(s).toBe('Hello 1.5!');
        });

        it('should parse operations and apply formulas', function() {
            var tpl = new BindTemplate('Hello {(foo.bar * foo + bar):this.thing(3) / bar.foo}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar', 'foo', 'bar', 'bar.foo']);

            var s = tpl.apply([10, 2, 5, 2], {
                thing: function(v, factor) {
                    return v * factor;
                }
            });

            expect(s).toBe('Hello 37.5!');
        });

        it('should parse operations in formula arguments', function() {
            var tpl = new BindTemplate('Hello {((foo.bar * foo + bar):this.thing(bar + test:this.thing(3)) / bar.foo):number("0.00")}!');

            var tokens = tpl.getTokens();

            expect(tokens).toEqual(['foo.bar', 'foo', 'bar', 'test', 'bar.foo']);

            var s = tpl.apply([10, 2, 5, 7, 25], {
                thing: function(v, factor) {
                    return v * factor;
                }
            });

            expect(s).toBe('Hello 26.00!');
        });

        it('should parse complex operations', function() {
            var tpl = new BindTemplate('Hello {(foo.bar + bar.foo:this.thing):number("0.00")}!');

            var s = tpl.apply([5, 7], {
                thing: function(v) {
                    return v * 2;
                }
            });

            expect(s).toBe('Hello 19.00!');
        });

    });

    describe('default formatters', function() {
        it('should parse', function() {
            var tpl = new BindTemplate('Hello {foo:number} {bar.foo:lowercase}');

            expect(tpl.getTokens()).toEqual(['foo', 'bar.foo']);

            var s = tpl.apply([5, 'SENCHA']);

            expect(s).toBe('Hello 5 sencha');
        });

        it('should parse chained formatters', function() {
            var tpl = new BindTemplate('Hello {foo:lowercase:capitalize} {bar.foo:number("0.00")}');

            expect(tpl.getTokens()).toEqual(['foo', 'bar.foo']);

            var s = tpl.apply(['SENCHA', 23]);

            expect(s).toBe('Hello Sencha 23.00');
        });

        it('should parse nested formatters', function() {
            var tpl = new BindTemplate('Hello {foo:format(bar:pick("First: \\"param\\"", foo.bar:number("0")))}');

            expect(tpl.getTokens()).toEqual(['foo', 'bar', 'foo.bar']);

            var s = tpl.apply(['Result: {0}', false, 5]);

            expect(s).toBe('Hello Result: First: "param"');
        });

        it('should parse complex nested formatters', function() {
            var tpl = new BindTemplate('Hello {foo:format(bar:capitalize:leftPad(5, "x"))}');

            expect(tpl.getTokens()).toEqual(['foo', 'bar']);

            var s = tpl.apply(['there, {0}', 'john']);

            expect(s).toBe('Hello there, xJohn');
        });

        it('should parse nested and chained formatters', function() {
            var tpl = new BindTemplate('Hello {foo.bar:leftPad(!foo.test:pick(bar,foo), "X"):uppercase:ellipsis(8)} there and {foo:capitalize}!');

            expect(tpl.getTokens()).toEqual(['foo.bar', 'foo.test', 'bar', 'foo']);

            var s = tpl.apply(['sencha', true, 10, 'sencha']);

            expect(s).toBe('Hello XXXXS... there and Sencha!');
        });

        it('should parse escaped strings', function() {
            var tpl = new BindTemplate("{foo:leftPad(13, 'You\\'re ok ')}");

            // this expressions will fail: {foo:leftPad("You\", hi!",2)} or {foo:leftPad("(You\")",2)}
            expect(tpl.getTokens()).toEqual(['foo']);

            var s = tpl.apply(['now']);

            expect(s).toBe('You\'re ok now');
        });

        it('should parse more escaped strings', function() {
            var tpl = new BindTemplate('{foo:leftPad(10, "Y\\"): ")}');

            // this expression will fail: {foo:date("Y\"",2)}
            expect(tpl.getTokens()).toEqual(['foo']);

            var s = tpl.apply(['hello']);

            expect(s).toBe('Y"): hello');
        });

        it('should parse arguments', function() {
            var tpl = new BindTemplate('Hello {foo:number("0.00")} {bar.foo:number("0,000.00")}');

            expect(tpl.getTokens()).toEqual(['foo', 'bar.foo']);

            var s = tpl.apply([4554, 4554]);

            expect(s).toBe('Hello 4554.00 4,554.00');
        });

        it('should parse boolean arguments', function() {
            var tpl = new BindTemplate('Hello {foo:toggle("Flex", false)} {bar.foo:defaultValue(true)}');

            expect(tpl.getTokens()).toEqual(['foo', 'bar.foo']);

            var s = tpl.apply(['Flex', undefined]);

            expect(s).toBe('Hello false true');
        });

        it('should parse arguments that are functions', function() {
            var tpl = new BindTemplate('Hello {foo:defaultValue(bar.foo:lowercase)}');

            expect(tpl.getTokens()).toEqual(['foo', 'bar.foo']);

            var s = tpl.apply([undefined, 'THERE']);

            expect(s).toBe('Hello there');
        });

        it('should apply simple formatting', function() {
            var tpl = new BindTemplate('Hello {foo:number} {bar.foo:date("Y-m-d")} ' +
                '-- {foo:number("0.00")}');

            var s = tpl.apply([123.456, new Date(2013, 2, 2)]);

            expect(s).toBe('Hello 123.456 2013-03-02 -- 123.46');
        });

        it('should apply complex formatting', function() {
            // The "," inside a string argument makes splitting on commas and producing an
            // args array early impossible (if we are to respect global references in them
            // as well)... but still needs to work.
            var tpl = new BindTemplate('Hello {foo:number} {bar.foo:date("Y-m-d")} ' +
                '-- {foo:number("0,000.00")}');

            var s = tpl.apply([123456.789, new Date(2013, 2, 2)]);

            expect(s).toBe('Hello 123456.789 2013-03-02 -- 123,456.79');
        });
    });

    describe('scoped formatters', function() {
        it('should parse', function() {
            var tpl = new BindTemplate('Hello {foo:this.fn} {bar.foo:this.fn2}');

            expect(tpl.getTokens()).toEqual(['foo', 'bar.foo']);

            var s = tpl.apply([5, 6], {
                fn: function(v) {
                    return v + 1;
                },
                fn2: function(v) {
                    return v * 2;
                }
            });

            expect(s).toBe('Hello 6 12');
        });

        it('should parse arguments', function() {
            var tpl = new BindTemplate('Hello {foo:this.fn(4)} {bar.foo:this.fn2(20)}');

            expect(tpl.getTokens()).toEqual(['foo', 'bar.foo']);

            var s = tpl.apply([5, 6], {
                fn: function(v, a) {
                    return v + a;
                },
                fn2: function(v, a) {
                    return v * a;
                }
            });

            expect(s).toBe('Hello 9 120');
        });

        it('should apply simple formatting', function() {
            var tpl = new BindTemplate('Hello {foo:number} {bar.foo:date("Y-m-d")} ' +
                '-- {foo:this.number("0.00")}');

            var s = tpl.apply([123.456, new Date(2013, 2, 2)], {
                scale: 2,
                number: function(v, str) {
                    return '[[' + Ext.util.Format.number(v * this.scale, str) + ']]';
                }
            });

            expect(s).toBe('Hello 123.456 2013-03-02 -- [[246.91]]');
        });

        it('should apply complex formatting', function() {
            // This template uses a global reference as an argument. Odd but it works in
            // other templates.
            var tpl = new BindTemplate('Hello {foo:number} {bar.foo:date("Y-m-d")} ' +
                '-- {foo:this.thing(@Ext.versions.core)}');

            var s = tpl.apply([123.456, new Date(2013, 2, 2)], {
                text: '::',
                thing: function(v, str) {
                    return this.text + v + '=' + str + this.text;
                }
            });

            expect(s).toBe('Hello 123.456 2013-03-02 -- ::123.456=' +
                Ext.getVersion('core') + '::');
        });

        it('should apply chained and nested formatting', function() {
            var tpl = new BindTemplate('Hello {!foo.bar:pick(bar:number, "test"):number(\'0,000.00\')}, this is a {foo.test:this.thing("test", !test:pick("\\"man{}\\"",\'(joe)\'))}!');

            var s = tpl.apply([true, 123.456, 'complex', true], {
                text: '::',
                thing: function(v, str, a) {
                    return this.text + v + '=' + str + this.text + ' (' + a + ')';
                }
            });

            expect(s).toBe('Hello 123.46, this is a ::complex=test:: ("man{}")!');
        });
    });

    describe('syntax errors', function() {
        it('should fail when there\'s a format fn without prefixed token', function() {
            var tpl = new BindTemplate('Hello { :number }!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail when @ prefixes an ! operator', function() {
            var tpl = new BindTemplate('Hello {foo.bar + @!Ext.versions.core}!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail when @ prefixes a number', function() {
            var tpl = new BindTemplate('Hello {foo.bar + @5}!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail when @ prefixes a string', function() {
            var tpl = new BindTemplate('Hello {foo.bar + @"test"}!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail when @ prefixes other operators', function() {
            var tpl = new BindTemplate('Hello {foo.bar + @("test"}!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail when there\'s a missing paran', function() {
            var tpl = new BindTemplate('Hello {foo.bar + (foo:number}!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail when specifying an invalid Ext.util.Format fn', function() {
            var tpl = new BindTemplate('Hello {foo.bar + (foo:justTesting}!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail when there is an unexpected operator', function() {
            var tpl = new BindTemplate('Hello {foo.bar + ! $ (foo:number)}!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail when there is an unknown operator', function() {
            var tpl = new BindTemplate('Hello {foo.bar + dd[foo:number]}!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail on unexpected . token', function() {
            var tpl = new BindTemplate('Hello {foo.bar + dd.(foo:number)}!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail on not defined unary * operator', function() {
            var tpl = new BindTemplate('Hello {foo.bar + * 5}!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail on undefined unary in format fn name', function() {
            var tpl = new BindTemplate('Hello {foo.bar:*number}!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail when starting with an unknown operator', function() {
            var tpl = new BindTemplate('Hello { % foo.bar:number }!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail when using open curly inside expression', function() {
            var tpl = new BindTemplate('Hello { { foo.bar:number }!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail when using close curly inside expression', function() {
            var tpl = new BindTemplate('Hello { foo.bar:}number }!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail on wrong literals', function() {
            // eslint-disable-next-line no-useless-escape
            var tpl = new BindTemplate('Hello { foo.bar:this.test("yep\" it fails") }!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail when ending with an operator', function() {
            var tpl = new BindTemplate('Hello {foo.bar:number + }!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail on undefined unary / operator', function() {
            var tpl = new BindTemplate('Hello {foo.bar + / 5}!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail on undefined unary * operator', function() {
            var tpl = new BindTemplate('Hello {foo.bar + * 5}!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail on undefined unary && operator', function() {
            var tpl = new BindTemplate('Hello {foo.bar + && 5}!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail on undefined unary || operator', function() {
            var tpl = new BindTemplate('Hello {foo.bar + || 5}!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail on undefined unary > operator', function() {
            var tpl = new BindTemplate('Hello {foo.bar + > 5}!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail on undefined unary >= operator', function() {
            var tpl = new BindTemplate('Hello {foo.bar + >= 5}!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail on undefined unary < operator', function() {
            var tpl = new BindTemplate('Hello {foo.bar + < 5}!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail on undefined unary <= operator', function() {
            var tpl = new BindTemplate('Hello {foo.bar + <= 5}!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail on undefined unary == operator', function() {
            var tpl = new BindTemplate('Hello {foo.bar + == 5}!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail on undefined unary === operator', function() {
            var tpl = new BindTemplate('Hello {foo.bar + === 5}!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail on undefined unary != operator', function() {
            var tpl = new BindTemplate('Hello {foo.bar + != 5}!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail on undefined unary !== operator', function() {
            var tpl = new BindTemplate('Hello {foo.bar + !== 5}!');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

        it('should fail when the compiled function fails on global objects', function() {
            var tpl = new BindTemplate('Hello {foo.bar + @Something.b}!');

            expect(tpl.getTokens()).toEqual(['foo.bar']);
            expect(function() {
                tpl.apply([3]);
            }).toThrow();

        });

        it('should fail when the compiled function fails on missing scope function', function() {
            var tpl = new BindTemplate('Hello {foo.bar:this.test}!');

            expect(tpl.getTokens()).toEqual(['foo.bar']);
            expect(function() {
                tpl.apply([3]);
            }).toThrow();

        });

        it('should fail when ambiguous ternary provided', function() {
            var tpl = new BindTemplate('Hello {foo ? bar:number : 6}');

            expect(function() {
                tpl.getTokens();
            }).toThrow();

        });

    });

    describe("escaping", function() {
        var esc = '\\',
            old;

        beforeEach(function() {
            old = BindTemplate.prototype.escapes;
            BindTemplate.prototype.escapes = true;
        });

        afterEach(function() {
            BindTemplate.prototype.escapes = old;
        });

        describe("normal characters", function() {
            function escapeify(s) {
                return esc + s.split('').join(esc);
            }

            it("should be able to escape letters", function() {
                var chars = escapeify('abcdefghijklmnopqrstuvwxyz'),
                    tpl = new BindTemplate(chars);

                expect(tpl.getTokens()).toEqual([]);
                expect(tpl.apply([])).toBe('abcdefghijklmnopqrstuvwxyz');

            });

            it("should be able to escape numbers", function() {
                var chars = escapeify('1234567890'),
                    tpl = new BindTemplate(chars);

                expect(tpl.getTokens()).toEqual([]);
                expect(tpl.apply([])).toBe('1234567890');
            });

            it("should be able to escape symbols", function() {
                var chars = escapeify('`~!@#$%^&*()-+?<>.'),
                    tpl = new BindTemplate(chars);

                expect(tpl.getTokens()).toEqual([]);
                expect(tpl.apply([])).toBe('`~!@#$%^&*()-+?<>.');
            });
        });

        describe("slashes", function() {
            it("should be able to escape slashes", function() {
                var tpl = new BindTemplate('Hello \\\\{foo} \\{bar}');

                expect(tpl.getTokens()).toEqual(['foo']);
                expect(tpl.apply(['xxx'])).toBe('Hello \\xxx {bar}');
            });
        });

        describe("expressions", function() {
            it("should be able to escape at the beginning of a sequence", function() {
                var tpl = new BindTemplate('\\{notanexpression} bar');

                expect(tpl.getTokens()).toEqual([]);
                expect(tpl.apply([])).toBe('{notanexpression} bar');
            });

            it("should be able to escape in the middle of a sequence", function() {
                var tpl = new BindTemplate('foo \\{notanexpression} bar');

                expect(tpl.getTokens()).toEqual([]);
                expect(tpl.apply([])).toBe('foo {notanexpression} bar');
            });

            it("should be able to escape at the end of a sequence", function() {
                var tpl = new BindTemplate('foo \\{notanexpression}');

                expect(tpl.getTokens()).toEqual([]);
                expect(tpl.apply([])).toBe('foo {notanexpression}');
            });

            it("should be able to escape one portion at the beginning", function() {
                var tpl = new BindTemplate('\\{notanexpression}{bar}');

                expect(tpl.getTokens()).toEqual(['bar']);
                expect(tpl.apply([1])).toBe('{notanexpression}1');
            });

            it("should be able to escape one portion in the middle", function() {
                var tpl = new BindTemplate('{foo}\\{notanexpression}{bar}');

                expect(tpl.getTokens()).toEqual(['foo', 'bar']);
                expect(tpl.apply([1, 2])).toBe('1{notanexpression}2');
            });

            it("should be able to escape one portion at the end", function() {
                var tpl = new BindTemplate('{foo}\\{notanexpression}');

                expect(tpl.getTokens()).toEqual(['foo']);
                expect(tpl.apply([1])).toBe('1{notanexpression}');
            });

            it("should be able to nest an escape at the start", function() {
                var tpl = new BindTemplate('\\{{foo}} def');

                expect(tpl.getTokens()).toEqual(['foo']);
                expect(tpl.apply([5])).toBe('{5} def');
            });

            it("should be able to nest an escape in the middle", function() {
                var tpl = new BindTemplate('abc \\{{foo}} def');

                expect(tpl.getTokens()).toEqual(['foo']);
                expect(tpl.apply([5])).toBe('abc {5} def');
            });

            it("should be able to nest an escape at the end", function() {
                var tpl = new BindTemplate('abc \\{{foo}}');

                expect(tpl.getTokens()).toEqual(['foo']);
                expect(tpl.apply([5])).toBe('abc {5}');
            });
        });

        describe("literals", function() {
            it("should handle a literal escape at the start", function() {
                var tpl = new BindTemplate('~~{name}xxx\\{bar} more stuff {x + 1}');

                expect(tpl.getTokens()).toEqual([]);
                expect(tpl.apply([])).toBe('{name}xxx\\{bar} more stuff {x + 1}');
            });

            it("should handle a literal escape in the middle", function() {
                var tpl = new BindTemplate('{foo} {bar}~~{foo} {bar} {baz}');

                expect(tpl.getTokens()).toEqual(['foo', 'bar']);
                expect(tpl.apply(['a', 'b'])).toBe('a b{foo} {bar} {baz}');
            });

            it("should handle a literal escape at the end", function() {
                var tpl = new BindTemplate('{foo}{bar}{baz}~~');

                expect(tpl.getTokens()).toEqual(['foo', 'bar', 'baz']);
                expect(tpl.apply(['a', 'b', 'c'])).toBe('abc');
            });

            it("should be able to escape the escape literal at the start", function() {
                var tpl = new BindTemplate('\\~~{foo}{bar}');

                expect(tpl.getTokens()).toEqual(['foo', 'bar']);
                expect(tpl.apply(['a', 'b'])).toBe('~~ab');

                tpl = new BindTemplate('~\\~{foo}{bar}');
                expect(tpl.getTokens()).toEqual(['foo', 'bar']);
                expect(tpl.apply(['a', 'b'])).toBe('~~ab');
            });

            it("should be able to escape the escape literal in the middle", function() {
                var tpl = new BindTemplate('{foo}\\~~{bar}');

                expect(tpl.getTokens()).toEqual(['foo', 'bar']);
                expect(tpl.apply(['a', 'b'])).toBe('a~~b');

                tpl = new BindTemplate('{foo}~\\~{bar}');
                expect(tpl.getTokens()).toEqual(['foo', 'bar']);
                expect(tpl.apply(['a', 'b'])).toBe('a~~b');
            });

            it("should be able to escape the escape literal at the end", function() {
                var tpl = new BindTemplate('{foo}{bar}\\~~');

                expect(tpl.getTokens()).toEqual(['foo', 'bar']);
                expect(tpl.apply(['a', 'b'])).toBe('ab~~');

                tpl = new BindTemplate('{foo}{bar}~\\~');
                expect(tpl.getTokens()).toEqual(['foo', 'bar']);
                expect(tpl.apply(['a', 'b'])).toBe('ab~~');
            });
        });
    });
});

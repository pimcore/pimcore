topSuite("Ext.app.bind.Formula", ['Ext.app.ViewModel'], function() {
    var vm;

    beforeEach(function() {
        vm = new Ext.app.ViewModel({
            // this is a config not an instance so the VM knows it owns the instance
            scheduler: {
                tickDelay: 1000000
            }
        });
    });

    afterEach(function() {
        vm.destroy();
        vm = null;
        expect(Ext.util.Scheduler.instances.length).toBe(0);
    });

    function makeFormula(fn, name) {
        var o = {};

        o[name || 'fn'] = fn;
        vm.setFormulas(o);

        return vm.getRoot().children.fn.formula;
    }

    function fakeFn(s) {
        return {
            toString: function() {
                return s;
            }
        };
    }

    function matchExpr(fn, result) {
        var fake = makeFormula(Ext.emptyFn),
            expr;

        expr = fake.parseFormula({
            toString: function() {
                return fn;
            }
        });

        delete expr.$literal;
        expect(Ext.Object.getKeys(expr)).toEqual(result);
    }

    // we parse out the argument name, so it should give us argName.expression.
    // here, we just want to check whether we get the name correctly
    describe("argument parsing", function() {
        describe("function keyword", function() {
            describe("anonymous function", function() {
                it("should parse an empty parameter", function() {
                    matchExpr("function () { return 1; }", []);
                });

                it("should parse a simple function definition", function() {
                    matchExpr("function (get) { return get('foo'); }", ['foo']);
                });

                it("should parse a var with numbers in the name", function() {
                    matchExpr("function (g2et) { return g2et('foo'); }", ['foo']);
                });

                it("should parse a var starting with _", function() {
                    matchExpr("function (_get) { return _get('foo'); }", ['foo']);
                });

                it("should parse a var starting with $", function() {
                    matchExpr("function ($get) { return $get('foo'); }", ['foo']);
                });

                it("should parse a with multi vars", function() {
                    matchExpr("function (get, some, other, stuff) { return get('foo'); }", ['foo']);
                });

                it("should parse with no spaces between function and parens", function() {
                    matchExpr("function(get) { return get('foo'); }", ['foo']);
                });

                it("should parse with no spaces between parens and curly", function() {
                    matchExpr("function (get){ return get('foo'); }", ['foo']);
                });

                it("should parse with an ending semi colon", function() {
                    matchExpr("function (get) { return get('foo'); };", ['foo']);
                });

                it("should parse with leading spaces", function() {
                    matchExpr("    function (get) { return get('foo'); }", ['foo']);
                });

                it("should parse with trailing spaces", function() {
                    matchExpr("function (get) { return get('foo'); }                ", ['foo']);
                });

                it("should parse comments out", function() {
                    matchExpr("function (get) { /* FOO: return get('bar') */ return get('foo'); }", ['foo']);
                });
            });

            describe("named function", function() {
                it("should parse an empty parameter", function() {
                    matchExpr("function myFn() { return 1; }", []);
                });

                it("should parse a simple function definition", function() {
                    matchExpr("function myFn(get) { return get('foo'); }", ['foo']);
                });

                it("should parse a var with numbers in the name", function() {
                    matchExpr("function myFn(g2et) { return g2et('foo'); }", ['foo']);
                });

                it("should parse a var starting with _", function() {
                    matchExpr("function myFn(_get) { return _get('foo'); }", ['foo']);
                });

                it("should parse a var starting with $", function() {
                    matchExpr("function myFn($get) { return $get('foo'); }", ['foo']);
                });

                it("should parse a with multi vars", function() {
                    matchExpr("function myFn(get, some, other, stuff) { return get('foo'); }", ['foo']);
                });

                it("should parse with spaces between function and parens", function() {
                    matchExpr("function myFn (get) { return get('foo'); }", ['foo']);
                });

                it("should parse with no spaces between parens and curly", function() {
                    matchExpr("function myFn(get){ return get('foo'); }", ['foo']);
                });

                it("should parse with an ending semi colon", function() {
                    matchExpr("function myFn(get) { return get('foo'); };", ['foo']);
                });

                it("should parse with leading spaces", function() {
                    matchExpr("    function myFn(get) { return get('foo'); }", ['foo']);
                });

                it("should parse with trailing spaces", function() {
                    matchExpr("function myFn(get) { return get('foo'); }                ", ['foo']);
                });

                it("should parse with many spaces between the function keyword and the name", function() {
                    matchExpr("function         myFn(get) { return get('foo'); };", ['foo']);
                });

                it("should parse with the function name starting with _", function() {
                    matchExpr("function _myFn(get) { return get('foo'); }", ['foo']);
                });

                it("should parse with the function name starting with $", function() {
                    matchExpr("function $myFn(get) { return get('foo'); }", ['foo']);
                });
            });
        });

        describe("arrow functions", function() {
            describe("without parens", function() {
                it("should parse a simple function definition", function() {
                    matchExpr("get => get('foo')", ['foo']);
                });

                it("should parse a var with numbers in the name", function() {
                    matchExpr("g2et => g2et('foo')", ['foo']);
                });

                it("should parse a var starting with _", function() {
                    matchExpr("_get => _get('foo')", ['foo']);
                });

                it("should parse a var starting with $", function() {
                    matchExpr("$get => $get('foo')", ['foo']);
                });

                it("should parse with spacing between the parameter and the arrow", function() {
                    matchExpr("get     => get('foo')", ['foo']);
                });

                it("should parse with spacing between the arrow and the body", function() {
                    matchExpr("get     => get('foo')", ['foo']);
                });

                it("should parse when using braces in the body", function() {
                    matchExpr("get => { return get('foo'); }", ['foo']);
                });

                it("should parse with an ending semi colon", function() {
                    matchExpr("get => get('foo');", ['foo']);
                });

                it("should parse with leading spaces", function() {
                    matchExpr("          get => get('foo')", ['foo']);
                });

                it("should parse with trailing spaces", function() {
                    matchExpr("get => get('foo')           ", ['foo']);
                });
            });

            describe("with parens", function() {
                it("should parse an empty parameter", function() {
                    matchExpr("() => 1", []);
                });

                it("should parse a simple function definition", function() {
                    matchExpr("(get) => get('foo')", ['foo']);
                });

                it("should parse a var with numbers in the name", function() {
                    matchExpr("(g2et) => g2et('foo')", ['foo']);
                });

                it("should parse a var starting with _", function() {
                    matchExpr("(_get) => _get('foo')", ['foo']);
                });

                it("should parse a var starting with $", function() {
                    matchExpr("($get) => $get('foo')", ['foo']);
                });

                it("should parse a with multi vars", function() {
                    matchExpr("(get, some, other, stuff) => get('foo')", ['foo']);
                });

                it("should parse with spacing between the parameter and the arrow", function() {
                    matchExpr("(get)     => get('foo')", ['foo']);
                });

                it("should parse with spacing between the arrow and the body", function() {
                    matchExpr("(get)     => get('foo')", ['foo']);
                });

                it("should parse when using braces in the body", function() {
                    matchExpr("(get) => { return get('foo'); }", ['foo']);
                });

                it("should parse with an ending semi colon", function() {
                    matchExpr("(get) => get('foo');", ['foo']);
                });

                it("should parse with leading spaces", function() {
                    matchExpr("          (get) => get('foo')", ['foo']);
                });

                it("should parse with trailing spaces", function() {
                    matchExpr("(get) => get('foo')           ", ['foo']);
                });
            });
        });
    });

    describe("recognizing bindings", function() {
        it("should parse a simple variable", function() {
            matchExpr("function (get) { return get('foo'); };", ['foo']);
        });

        it("should only match a variable once", function() {
            matchExpr("function (get) { return get('foo') + get('foo') + get('foo'); };", ['foo']);
        });

        it("should match multiple expressions", function() {
            matchExpr("function (get) { return get('foo') + get('bar') + get('baz'); };", ['foo', 'bar', 'baz']);
        });

        it("should match an expression with a number in it", function() {
            matchExpr("function (get) { return get('foo1'); };", ['foo1']);
        });

        it("should match an expression that starts with an underscore", function() {
            matchExpr("function (get) { return get('_foo'); };", ['_foo']);
        });

        it("should match as a dynamic property", function() {
            matchExpr("function (get) { return someObj[get('foo')]; };", ['foo']);
        });

        it("should match inside parens", function() {
            matchExpr("function (get) { return (get('foo') + 1 + get('bar')); };", ['foo', 'bar']);
        });

        it("should match an expression with double quotes", function() {
            matchExpr('function (get) { return get("foo"); };', ['foo']);
        });

        describe("spacing", function() {
            it("should match leading spaces", function() {
                matchExpr('function (get) { return get(        "foo"); };', ['foo']);
            });

            it("should match trailing spaces", function() {
                matchExpr('function (get) { return get("foo"   ); };', ['foo']);
            });

            it("should match leading & trailing spaces", function() {
                matchExpr('function (get) { return get( "foo" ); };', ['foo']);
            });
        });

        describe("non-matches", function() {
            it("should not match when the identifier has a prefix", function() {
                matchExpr("function (get) { return get('foo') + forget('bar'); };", ['foo']);
            });

            it("should not match when the identifier has a suffix", function() {
                matchExpr("function (get) { return get('foo') + getfor('bar'); };", ['foo']);
            });

            it("should not match when the identifier is a property of another object", function() {
                matchExpr("function (get) { return get('foo') + something.get('bar'); };", ['foo']);
            });
        });

        describe("functions", function() {
            it("should match only the expression part", function() {
                matchExpr("function (get) { return get('foo').substring(0, 3); };", ['foo']);
            });

            it("should match as the sole param to a function", function() {
                matchExpr("function (get) { return someFn(get('foo')) };", ['foo']);
            });

            it("should match as the first param to a function", function() {
                matchExpr("function (get) { return someFn(get('foo'), 1, 2) };", ['foo']);
            });

            it("should match as a middle param to a function", function() {
                matchExpr("function (get) { return someFn(1, get('foo'), 2) };", ['foo']);
            });

            it("should match as the last param to a function", function() {
                matchExpr("function (get) { return someFn(1, 2, get('foo')) };", ['foo']);
            });

            it("should match multiple params to a function", function() {
                matchExpr("function (get) { return someFn(get('foo'), get('bar')) };", ['foo', 'bar']);
            });
        });

        describe("nesting", function() {
            it("should match a nested expression", function() {
                matchExpr("function (get) { return get('foo.bar.baz'); };", ['foo.bar.baz']);
            });

            it("should match multiple nested subpaths", function() {
                matchExpr("function (get) { return get('foo.bar.baz.a') + get('foo.bar.baz.b'); };", ['foo.bar.baz.a', 'foo.bar.baz.b']);
            });

            it("should match paths at different depths", function() {
                matchExpr("function (get) { return get('foo') + get('bar.baz.a.b') + get('some.other.path.x.y'); };", ['foo', 'bar.baz.a.b', 'some.other.path.x.y']);
            });

            it("should match get calls inside get calls", function() {
                matchExpr("function (get) { return (get(get('foo') + get('bar')); };", ['foo', 'bar']);
            });
        });
    });
});

/* global MixinSpecTest, Baz */
(Ext.isIE8 ? xtopSuite : topSuite)("Ext.route.Mixin", function() {
    var Router = Ext.route.Router,
        instance;

    beforeAll(function() {
        Ext.define('MixinSpecTest', {
            mixins: [
                'Ext.route.Mixin'
            ],

            routes: {
                'foo/bar': 'onFooBar',
                'baz/:id': {
                    action: 'onBaz',
                    name: 'baz'
                }
            },

            onFooBar: Ext.emptyFn,
            onBaz: Ext.emptyFn,

            constructor: function(config) {
                this.initConfig(config);
                this.callParent([config]);
            }
        });
    });

    beforeEach(function() {
        instance = new MixinSpecTest();

        Ext.testHelper.hash.init();
    });

    afterEach(function() {
        if (instance) {
            instance.destroy();
            instance = null;
        }

        Ext.testHelper.hash.reset();
    });

    afterAll(function() {
        Ext.undefine('MixinSpecTest');
    });

    describe("connect and disconnect", function() {
        it("should connect both routes", function() {
            expect(Router.getByName('foo/bar').getHandlers().length).toBe(1);
            expect(Router.getByName('baz').getHandlers().length).toBe(1);
        });

        it("should disconnect both routes", function() {
            instance.destroy();
            instance = null;

            expect(Router.getByName('foo/bar').getHandlers().length).toBe(0);
            expect(Router.getByName('baz').getHandlers().length).toBe(0);
        });

        describe("setRoutes", function() {
            it("should disconnect old routes", function() {
                instance.setRoutes(null);

                expect(Router.getByName('foo/bar').getHandlers().length).toBe(0);
                expect(Router.getByName('baz').getHandlers().length).toBe(0);
            });
        });
    });

    describe("redirectTo", function() {
        // this fails randomly on certain browsers due to hash not changing
        it("should set via a string", function() {
            instance.redirectTo('foo');

            waitsFor(function() {
                return Ext.util.History.getHash() === 'foo';
            }, 'Hash never updated');

            runs(function() {
                expect(Ext.util.History.getHash()).toBe('foo');
            });
        });

        it("should set via a model", function() {
            Ext.define('Baz', {
                extend: 'Ext.data.Model',

                fields: ['foo']
            });

            waitsFor(function() {
                // account for loading Ext.data.Model
                return !!window.Baz;
            });

            runs(function() {
                var record = new Baz({
                    id: 1
                });

                instance.redirectTo(record);

                Baz.prototype.schema.clear();

                Ext.undefine('Baz');
            });

            waitsFor(function() {
                return Ext.util.History.getHash() === 'baz/1';
            }, 'Hash never updated');

            runs(function() {
                expect(Ext.util.History.getHash()).toBe('baz/1');
            });
        });

        it("should go back", function() {
            // stack is empty, populate
            Ext.util.History.setHash('foo');
            Ext.util.History.setHash('bar');

            waitsFor(function() {
                return Ext.util.History.getHash() === 'bar';
            }, 'Hash never updated');

            runs(function() {
                instance.redirectTo(-1);
            });

            waitsFor(function() {
                return Ext.util.History.getHash() === 'foo';
            }, 'Hash never updated');

            runs(function() {
                expect(Ext.util.History.getHash()).toBe('foo');
            });
        });

        it("should go forward", function() {
            // stack is empty, populate
            Ext.util.History.setHash('foo');
            Ext.util.History.setHash('bar');

            // go back to 'foo'
            Ext.util.History.back();

            waitsFor(function() {
                return Ext.util.History.getHash() === 'foo';
            }, 'Hash never updated');

            runs(function() {
                instance.redirectTo(1);
            });

            waitsFor(function() {
                return Ext.util.History.getHash() === 'bar';
            }, 'Hash never updated');

            runs(function() {
                expect(Ext.util.History.getHash()).toBe('bar');
            });
        });

        describe("pass an object", function() {
            it("should add non-existent token on empty hash", function() {
                instance.redirectTo({
                    'foo/bar': 'foo/bar'
                });

                waitsFor(function() {
                    return Ext.util.History.getHash() === 'foo/bar';
                }, 'Hash never updated');

                runs(function() {
                    expect(Ext.util.History.getHash()).toBe('foo/bar');
                });
            });

            it("should add non-existent token on populated hash", function() {
                Ext.util.History.setHash('baz/1');

                waitsFor(function() {
                    return Ext.util.History.getHash() === 'baz/1';
                }, 'Hash never updated');

                runs(function() {
                    instance.redirectTo({
                        'foo/bar': 'foo/bar'
                    });
                });

                waitsFor(function() {
                    return Ext.util.History.getHash() === 'baz/1|foo/bar';
                }, 'Hash never updated');

                runs(function() {
                    expect(Ext.util.History.getHash()).toBe('baz/1|foo/bar');
                });
            });

            it("should update existing token with no other tokens", function() {
                Ext.util.History.setHash('baz/1');

                waitsFor(function() {
                    return Ext.util.History.getHash() === 'baz/1';
                }, 'Hash never updated');

                runs(function() {
                    instance.redirectTo({
                        baz: 'baz/2'
                    });
                });

                waitsFor(function() {
                    return Ext.util.History.getHash() === 'baz/2';
                }, 'Hash never updated');

                runs(function() {
                    expect(Ext.util.History.getHash()).toBe('baz/2');
                });
            });

            it("should update existing token with one other token", function() {
                Ext.util.History.setHash('baz/1|foo/bar');

                waitsFor(function() {
                    return Ext.util.History.getHash() === 'baz/1|foo/bar';
                }, 'Hash never updated');

                runs(function() {
                    instance.redirectTo({
                        baz: 'baz/2'
                    });
                });

                waitsFor(function() {
                    return Ext.util.History.getHash() === 'baz/2|foo/bar';
                }, 'Hash never updated');

                runs(function() {
                    expect(Ext.util.History.getHash()).toBe('baz/2|foo/bar');
                });
            });

            it("should update existing token with one man other token", function() {
                Ext.util.History.setHash('users|baz/1|foo/bar');

                waitsFor(function() {
                    return Ext.util.History.getHash() === 'users|baz/1|foo/bar';
                }, 'Hash never updated');

                runs(function() {
                    instance.redirectTo({
                        baz: 'baz/2'
                    });
                });

                waitsFor(function() {
                    return Ext.util.History.getHash() === 'users|baz/2|foo/bar';
                }, 'Hash never updated');

                runs(function() {
                    expect(Ext.util.History.getHash()).toBe('users|baz/2|foo/bar');
                });
            });

            it("should remove token from hash with a single token", function() {
                Ext.util.History.setHash('foo/bar');

                waitsFor(function() {
                    return Ext.util.History.getHash() === 'foo/bar';
                }, 'Hash never updated');

                runs(function() {
                    instance.redirectTo({
                        'foo/bar': null
                    });
                });

                waitsFor(function() {
                    return Ext.util.History.getHash() === '';
                }, 'Hash never updated');

                runs(function() {
                    expect(Ext.util.History.getHash()).toBe('');
                });
            });

            it("should remove token from hash with multiple tokens", function() {
                Ext.util.History.setHash('users|baz/1|foo/bar');

                waitsFor(function() {
                    return Ext.util.History.getHash() === 'users|baz/1|foo/bar';
                }, 'Hash never updated');

                runs(function() {
                    instance.redirectTo({
                        baz: null
                    });
                });

                waitsFor(function() {
                    return Ext.util.History.getHash() === 'users|foo/bar';
                }, 'Hash never updated');

                runs(function() {
                    expect(Ext.util.History.getHash()).toBe('users|foo/bar');
                });
            });
        });

        describe("force", function() {
            function createSpecs(opt) {
                describe(opt === true ? "force with boolean" : "force with object", function() {
                    it("should force without current match hash", function() {
                        instance.redirectTo('foo', opt);

                        waitsFor(function() {
                            return Ext.util.History.getHash() === 'foo';
                        }, 'Hash never updated to foo');

                        runs(function() {
                            expect(Ext.util.History.getHash()).toBe('foo');
                        });
                    });

                    it("should force with current matched hash", function() {
                        instance.redirectTo('bar', opt);

                        waitsFor(function() {
                            return Ext.util.History.getHash() === 'bar';
                        }, 'Hash never updated to bar');

                        runs(function() {
                            var spy = spyOn(Ext.route.Router, 'onStateChange');

                            instance.redirectTo('bar', opt);

                            expect(spy).toHaveBeenCalled();
                        });
                    });
                });
            }

            createSpecs(true); // test backwards compat
            createSpecs({
                force: true
            });
        });

        describe("replace current resource", function() {
            it("should replace with a string", function() {
                instance.redirectTo('foo');

                waitsFor(function() {
                    return Ext.util.History.getHash() === 'foo';
                }, 'Hash never updated to foo');

                runs(function() {
                    instance.redirectTo('bar', {
                        replace: true
                    });
                });

                waitsFor(function() {
                    return Ext.util.History.getHash() === 'bar';
                }, 'Hash never updated to bar');

                runs(function() {
                    expect(Ext.util.History.getHash()).toBe('bar');
                });
            });

            it("should replace with a model", function() {
                Ext.define('Baz', {
                    extend: 'Ext.data.Model',

                    fields: ['foo']
                });

                waitsFor(function() {
                    // account for loading Ext.data.Model
                    return !!window.Baz;
                });

                runs(function() {
                    var record = new Baz({
                        id: 1
                    });

                    instance.redirectTo(record, {
                        replace: true
                    });

                    Baz.prototype.schema.clear();

                    Ext.undefine('Baz');
                });

                waitsFor(function() {
                    return Ext.util.History.getHash() === 'baz/1';
                }, 'Hash never updated');

                runs(function() {
                    expect(Ext.util.History.getHash()).toBe('baz/1');
                });
            });

            it("should replace with an object", function() {
                Ext.util.History.setHash('baz/1');

                waitsFor(function() {
                    return Ext.util.History.getHash() === 'baz/1';
                }, 'Hash never updated');

                runs(function() {
                    instance.redirectTo({
                        'foo/bar': 'foo/bar'
                    }, {
                        replace: true
                    });
                });

                waitsFor(function() {
                    return Ext.util.History.getHash() === 'baz/1|foo/bar';
                }, 'Hash never updated');

                runs(function() {
                    expect(Ext.util.History.getHash()).toBe('baz/1|foo/bar');
                });
            });
        });
    });
});

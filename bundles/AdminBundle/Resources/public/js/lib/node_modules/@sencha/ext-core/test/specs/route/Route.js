topSuite("Ext.route.Route", ['Ext.app.Controller'], function() {
    var actionExecuted = false,
        beforeExecuted = false,
        numArgs = 0,
        numBeforeArgs = 0,
        token = 'foo/bar',
        controller,
        beforeSpy, beforeBlockSpy, actionSpy;

    function promiseHasBeenResolved(promise) {
        var resolved = spyOn({
                test: Ext.emptyFn
            }, 'test'),
            rejected = spyOn({
                test: Ext.emptyFn
            }, 'test');

        promise.then(resolved, rejected);

        waitsForSpy(resolved, 'Promise was never resolved');

        runs(function() {
            expect(resolved).toHaveBeenCalled();
            expect(rejected).not.toHaveBeenCalled();
        });
    }

    function promiseHasBeenRejected(promise) {
        var resolved = spyOn({
                test: Ext.emptyFn
            }, 'test'),
            rejected = spyOn({
                test: Ext.emptyFn
            }, 'test');

        promise.then(resolved, rejected);

        waitsForSpy(rejected, 'Promise was never rejected');

        runs(function() {
            expect(rejected).toHaveBeenCalled();
            expect(resolved).not.toHaveBeenCalled();
        });
    }

    beforeEach(function() {
        controller = new Ext.app.Controller({
            beforeHandleRoute: function() {
                numBeforeArgs += arguments.length;
                beforeExecuted = true;

                var action = arguments[arguments.length - 1];

                action.resume();
            },

            beforeHandleRouteBlock: function() {
                numBeforeArgs += arguments.length;
                beforeExecuted = true;

                var action = arguments[arguments.length - 1];

                action.stop(); // stop the current route
            },

            handleRoute: function() {
                numArgs = arguments.length;
                actionExecuted = true;
            }
        });

        beforeSpy = spyOn(controller, 'beforeHandleRoute').andCallThrough();
        beforeBlockSpy = spyOn(controller, 'beforeHandleRouteBlock').andCallThrough();
        actionSpy = spyOn(controller, 'handleRoute').andCallThrough();
    });

    afterEach(function() {
        controller.destroy();

        controller = null;
        actionExecuted = false;
        beforeExecuted = false;
        numArgs = 0;
        numBeforeArgs = 0;
    });

    describe("should recognize tokens", function() {
        it("recognize 'foo/bar'", function() {
            var route = new Ext.route.Route({
                controller: controller,
                action: 'handleRoute',
                url: token
            });

            expect(route.recognize(token)).toBeTruthy();
        });

        describe("optional parameters", function() {
            it("recognize 'foo/:id'", function() {
                // :id is a param
                var route = new Ext.route.Route({
                    controller: controller,
                    action: 'handleRoute',
                    url: 'foo/:id'
                });

                expect(route.recognize('foo/123')).toBeTruthy();
            });

            it("recognize 'foo/:id' using condition for :id", function() {
                var route = new Ext.route.Route({
                    controller: controller,
                    action: 'handleRoute',
                    url: 'foo:id',
                    conditions: {
                        // makes :id param optional
                        ':id': '(?:(?:/){1}([%a-zA-Z0-9\-\_\s,]+))?'
                    }
                });

                expect(route.recognize('foo/123')).toBeTruthy();
            });

            it("recognize 'foo/:id' using condition for :id but without colon", function() {
                var route = new Ext.route.Route({
                    controller: controller,
                    action: 'handleRoute',
                    url: 'foo:id',
                    conditions: {
                        // makes :id param optional
                        'id': '(?:(?:/){1}([%a-zA-Z0-9\-\_\s,]+))?'
                    }
                });

                expect(route.recognize('foo/123')).toBeTruthy();
            });
        });
    });

    describe("fire action", function() {
        it("should fire action", function() {
            var route = new Ext.route.Route({
                    url: token,
                    handlers: [
                        {
                            action: 'handleRoute',
                            scope: controller
                        }
                    ]
                }),
                recognized = route.recognize(token),
                promise = route.execute(token, recognized);

            promiseHasBeenResolved(promise);

            runs(function() {
                expect(actionExecuted).toEqual(true);
            });
        });

        it("should fire action using caseInsensitve", function() {
            var route = new Ext.route.Route({
                    url: token,
                    caseInsensitive: true,
                    handlers: [
                        {
                            action: 'handleRoute',
                            scope: controller
                        }
                    ]
                }),
                recognized = route.recognize(token),
                promise = route.execute(token, recognized);

            promiseHasBeenResolved(promise);

            runs(function() {
                expect(actionExecuted).toEqual(true);
            });
        });
    });

    describe("handle before action", function() {
        it("should continue action execution", function() {
            var route = new Ext.route.Route({
                    url: token,
                    handlers: [
                        {
                            action: 'handleRoute',
                            before: 'beforeHandleRoute',
                            scope: controller
                        }
                    ]
                }),
                recognized = route.recognize(token),
                promise = route.execute(token, recognized);

            promiseHasBeenResolved(promise);

            runs(function() {
                expect(beforeExecuted && actionExecuted).toEqual(true);
            });
        });

        it("should block action execution", function() {
            var route = new Ext.route.Route({
                    url: token,
                    handlers: [
                        {
                            action: 'handleRoute',
                            before: 'beforeHandleRouteBlock',
                            scope: controller
                        }
                    ]
                }),
                recognized = route.recognize(token),
                promise = route.execute(token, recognized);

            promiseHasBeenRejected(promise);

            runs(function() {
                expect(beforeExecuted && !actionExecuted).toEqual(true);
            });
        });
    });

    describe("number of arguments", function() {
        it("with a before action", function() {
            var route = new Ext.route.Route({
                    url: 'foo/:bar',
                    handlers: [
                        {
                            action: 'handleRoute',
                            before: 'beforeHandleRoute',
                            scope: controller
                        }
                    ]
                }),
                recognized = route.recognize(token),
                promise = route.execute(token, recognized);

            promiseHasBeenResolved(promise);

            runs(function() {
                expect(numBeforeArgs + numArgs).toBe(3);
            });
        });

        it("without a before action", function() {
            var route = new Ext.route.Route({
                    url: 'foo/:bar',
                    handlers: [
                        {
                            action: 'handleRoute',
                            scope: controller
                        }
                    ]
                }),
                recognized = route.recognize(token),
                promise = route.execute(token, recognized);

            promiseHasBeenResolved(promise);

            runs(function() {
                expect(numBeforeArgs + numArgs).toBe(1);
            });
        });
    });

    describe("controller activity", function() {
        it("should not execute if the controller is inactive", function() {
            var route = new Ext.route.Route({
                    url: token,
                    handlers: [
                        {
                            action: 'handleRoute',
                            scope: controller
                        }
                    ]
                }),
                recognize = route.recognize(token),
                promise;

            controller.deactivate();

            promise = route.execute(token, recognize);

            promiseHasBeenResolved(promise);

            runs(function() {
                expect(actionExecuted).toBeFalsy();
            });
        });

        it("should recognize if the controller is inactive & the allowInactive flag is set", function() {
            var route = new Ext.route.Route({
                    url: token,
                    allowInactive: true,
                    handlers: [
                        {
                            action: 'handleRoute',
                            scope: controller
                        }
                    ]
                }),
                recognize = route.recognize(token),
                promise;

            controller.deactivate();

            promise = route.execute(token, recognize);

            promiseHasBeenResolved(promise);

            runs(function() {
                expect(actionExecuted).toBeTruthy();
            });
        });
    });

    describe("onExit", function() {
        var route;

        beforeEach(function() {
            route = new Ext.route.Route({
                url: token,
                handlers: [
                    {
                        exit: 'handleRoute',
                        scope: controller
                    }
                ]
            });

            route.lastToken = 'foo';
        });

        it("should execute exit handler", function() {
            route.onExit();

            expect(actionExecuted).toBeTruthy();
            expect(actionSpy).toHaveBeenCalledWith('foo');
        });

        it("should not execute if the controller is inactive", function() {
            controller.deactivate();

            route.onExit();

            expect(actionExecuted).toBeFalsy();
            expect(actionSpy).not.toHaveBeenCalled();
        });

        it("should recognize if the controller is inactive & the allowInactive flag is set", function() {
            route.setAllowInactive(true);

            route.onExit();

            expect(actionExecuted).toBeTruthy();
            expect(actionSpy).toHaveBeenCalledWith('foo');
        });
    });

    describe("beforeroute event", function() {
        var route;

        beforeEach(function() {
            route = new Ext.route.Route({
                url: token,
                handlers: [
                    {
                        action: 'handleRoute',
                        scope: controller
                    }
                ]
            });
        });

        afterEach(function() {
            if (route) {
                route.destroy();
            }
        });

        describe("using Ext.on", function() {
            it("should fire event", function() {
                var fn = spyOn({
                        test: Ext.emptyFn
                    }, 'test'),
                    recognize = route.recognize(token),
                    promise;

                Ext.on('beforeroute', fn, null, { single: true });

                promise = route.execute(token, recognize);

                promiseHasBeenResolved(promise);

                runs(function() {
                    expect(fn).toHaveBeenCalled();
                });
            });

            it("should execute before added in event listener", function() {
                var fn = spyOn({
                        test: function(action) {
                            action.resume();
                        }
                    }, 'test').andCallThrough(),
                    recognize = route.recognize(token),
                    promise;

                Ext.on('beforeroute', function(action) {
                    action.before(fn);
                }, null, { single: true });

                promise = route.execute(token, recognize);

                promiseHasBeenResolved(promise);

                runs(function() {
                    expect(fn).toHaveBeenCalled();
                });
            });

            it("should execute action added in event listener", function() {
                var fn = spyOn({
                        test: Ext.emptyFn
                    }, 'test'),
                    recognize = route.recognize(token),
                    promise;

                Ext.on('beforeroute', function(action) {
                    action.action(fn);
                }, null, { single: true });

                promise = route.execute(token, recognize);

                promiseHasBeenResolved(promise);

                runs(function() {
                    expect(fn).toHaveBeenCalled();
                });
            });

            it("should not execute before if return false", function() {
                var fn = spyOn({
                        test: function(action) {
                            action.resume();
                        }
                    }, 'test').andCallThrough(),
                    recognize = route.recognize(token),
                    promise;

                Ext.on('beforeroute', function(action) {
                    action.before(fn);

                    return false;
                }, null, { single: true });

                promise = route.execute(token, recognize);

                promiseHasBeenRejected(promise);

                runs(function() {
                    expect(fn).not.toHaveBeenCalled();
                });
            });

            it("should not execute action if return false", function() {
                var fn = spyOn({
                        test: Ext.emptyFn
                    }, 'test'),
                    recognize = route.recognize(token),
                    promise;

                Ext.on('beforeroute', function(action) {
                    action.action(fn);

                    return false;
                }, null, { single: true });

                promise = route.execute(token, recognize);

                promiseHasBeenRejected(promise);

                runs(function() {
                    expect(fn).not.toHaveBeenCalled();
                });
            });
        });

        describe("using event domain in controller", function() {
            var controller;

            afterEach(function() {
                if (controller) {
                    controller.destroy();
                    controller = null;
                }
            });

            it("should be listenable", function() {
                var controller = new Ext.app.Controller({
                        listen: {
                            global: {
                                beforeroute: 'onBeforeRoute'
                            }
                        },

                        onBeforeRoute: Ext.emptyFn
                    }),
                    fn = spyOn(controller, 'onBeforeRoute'),
                    recognize = route.recognize(token),
                    promise = route.execute(token, recognize);

                promiseHasBeenResolved(promise);

                runs(function() {
                    expect(fn).toHaveBeenCalled();
                });
            });

            it("should execute before added in event listener", function() {
                var fn = spyOn({
                        test: function(action) {
                            action.resume();
                        }
                    }, 'test').andCallThrough(),
                    controller = new Ext.app.Controller({
                        listen: {
                            global: {
                                beforeroute: 'onBeforeRoute'
                            }
                        },

                        onBeforeRoute: function(action) {
                            action.before(fn);
                        }
                    }),
                    recognize = route.recognize(token),
                    promise = route.execute(token, recognize);

                promiseHasBeenResolved(promise);

                runs(function() {
                    expect(fn).toHaveBeenCalled();
                });
            });

            it("should execute action added in event listener", function() {
                var fn = spyOn({
                        test: Ext.emptyFn
                    }, 'test'),
                    controller = new Ext.app.Controller({
                        listen: {
                            global: {
                                beforeroute: 'onBeforeRoute'
                            }
                        },

                        onBeforeRoute: function(action) {
                            action.action(fn);
                        }
                    }),
                    recognize = route.recognize(token),
                    promise = route.execute(token, recognize);

                promiseHasBeenResolved(promise);

                runs(function() {
                    expect(fn).toHaveBeenCalled();
                });
            });

            it("should not execute action when an added before stops the action", function() {
                var fn = spyOn({
                        test: Ext.emptyFn
                    }, 'test'),
                    controller = new Ext.app.Controller({
                        listen: {
                            global: {
                                beforeroute: 'onBeforeRoute'
                            }
                        },

                        onBeforeRoute: function(action) {
                            action
                                .before(function(action) {
                                    action.stop();
                                })
                                .action(fn);
                        }
                    }),
                    recognize = route.recognize(token),
                    promise = route.execute(token, recognize);

                promiseHasBeenRejected(promise);

                runs(function() {
                    expect(fn).not.toHaveBeenCalled();
                });
            });

            it("should not execute before if return false", function() {
                var fn = spyOn({
                        test: function(action) {
                            action.resume();
                        }
                    }, 'test').andCallThrough(),
                    controller = new Ext.app.Controller({
                        listen: {
                            global: {
                                beforeroute: 'onBeforeRoute'
                            }
                        },

                        onBeforeRoute: function(action) {
                            action.before(fn);

                            return false;
                        }
                    }),
                    recognize = route.recognize(token),
                    promise = route.execute(token, recognize);

                promiseHasBeenRejected(promise);

                runs(function() {
                    expect(fn).not.toHaveBeenCalled();
                });
            });

            it("should not execute action if return false", function() {
                var fn = spyOn({
                        test: Ext.emptyFn
                    }, 'test'),
                    controller = new Ext.app.Controller({
                        listen: {
                            global: {
                                beforeroute: 'onBeforeRoute'
                            }
                        },

                        onBeforeRoute: function(action) {
                            action.action(fn);

                            return false;
                        }
                    }),
                    recognize = route.recognize(token),
                    promise = route.execute(token, recognize);

                promiseHasBeenRejected(promise);

                runs(function() {
                    expect(fn).not.toHaveBeenCalled();
                });
            });
        });
    });

    describe("beforerouteexit event", function() {
        var route;

        beforeEach(function() {
            route = new Ext.route.Route({
                url: token,
                handlers: [
                    {
                        exit: 'handleRoute',
                        scope: controller
                    }
                ]
            });

            route.lastToken = 'foo';
        });

        afterEach(function() {
            if (route) {
                route.destroy();
            }
        });

        describe("using Ext.on", function() {
            it("should fire event", function() {
                var fn = spyOn({
                    test: Ext.emptyFn
                }, 'test');

                Ext.on('beforerouteexit', fn, null, { single: true });

                route.onExit();

                expect(fn).toHaveBeenCalled();
            });

            it("should execute before added in event listener", function() {
                var fn = spyOn({
                    test: function(lastToken, action) {
                        expect(lastToken).toBe('foo');

                        action.resume();
                    }
                }, 'test').andCallThrough();

                Ext.on('beforerouteexit', function(action) {
                    action.before(fn);
                }, null, { single: true });

                route.onExit();

                expect(fn).toHaveBeenCalled();
            });

            it("should execute action added in event listener", function() {
                var fn = spyOn({
                    test: Ext.emptyFn
                }, 'test');

                Ext.on('beforerouteexit', function(action) {
                    action.action(fn);
                }, null, { single: true });

                route.onExit();

                expect(fn).toHaveBeenCalled();
            });

            it("should not execute before if return false", function() {
                var fn = spyOn({
                        test: function(lastToken, action) {
                            action.resume();
                        }
                    }, 'test').andCallThrough();

                Ext.on('beforerouteexit', function(action) {
                    action.before(fn);

                    return false;
                }, null, { single: true });

                route.onExit();

                expect(fn).not.toHaveBeenCalled();
            });

            it("should not execute action if return false", function() {
                var fn = spyOn({
                    test: Ext.emptyFn
                }, 'test');

                Ext.on('beforerouteexit', function(action) {
                    action.action(fn);

                    return false;
                }, null, { single: true });

                route.onExit();

                expect(fn).not.toHaveBeenCalled();
            });
        });

        describe("using event domain in controller", function() {
            var controller;

            afterEach(function() {
                if (controller) {
                    controller.destroy();
                    controller = null;
                }
            });

            it("should be listenable", function() {
                var controller = new Ext.app.Controller({
                        listen: {
                            global: {
                                beforerouteexit: 'onRouteExit'
                            }
                        },

                        onRouteExit: Ext.emptyFn
                    }),
                    fn = spyOn(controller, 'onRouteExit');

                route.onExit();

                expect(fn).toHaveBeenCalled();
            });

            it("should execute before added in event listener", function() {
                var fn = spyOn({
                        test: function(lastToken, action) {
                            expect(lastToken).toBe('foo');

                            action.resume();
                        }
                    }, 'test').andCallThrough(),
                    controller = new Ext.app.Controller({
                        listen: {
                            global: {
                                beforerouteexit: 'onBeforeRouteExit'
                            }
                        },

                        onBeforeRouteExit: function(action) {
                            action.before(fn);
                        }
                    });

                route.onExit();

                expect(fn).toHaveBeenCalled();
            });

            it("should execute action added in event listener", function() {
                var fn = spyOn({
                        test: Ext.emptyFn
                    }, 'test'),
                    controller = new Ext.app.Controller({
                        listen: {
                            global: {
                                beforerouteexit: 'onBeforeRouteExit'
                            }
                        },

                        onBeforeRouteExit: function(action) {
                            action.action(fn);
                        }
                    });

                route.onExit();

                expect(fn).toHaveBeenCalled();
            });

            it("should not execute action when an added before stops the action", function() {
                var fn = spyOn({
                        test: Ext.emptyFn
                    }, 'test'),
                    controller = new Ext.app.Controller({
                        listen: {
                            global: {
                                beforerouteexit: 'onBeforeRouteExit'
                            }
                        },

                        onBeforeRouteExit: function(action) {
                            action
                                .before(function(lastToken, action) {
                                    expect(lastToken).toBe('foo');

                                    action.stop();
                                })
                                .action(fn);
                        }
                    });

                route.onExit();

                expect(fn).not.toHaveBeenCalled();
            });

            it("should not execute before if return false", function() {
                var fn = spyOn({
                        test: function(action) {
                            action.resume();
                        }
                    }, 'test').andCallThrough(),
                    controller = new Ext.app.Controller({
                        listen: {
                            global: {
                                beforerouteexit: 'onBeforeRouteExit'
                            }
                        },

                        onBeforeRouteExit: function(action) {
                            action.before(fn);

                            return false;
                        }
                    });

                route.onExit();

                expect(fn).not.toHaveBeenCalled();
            });

            it("should not execute action if return false", function() {
                var fn = spyOn({
                        test: Ext.emptyFn
                    }, 'test'),
                    controller = new Ext.app.Controller({
                        listen: {
                            global: {
                                beforerouteexit: 'onBeforeRouteExit'
                            }
                        },

                        onBeforeRouteExit: function(action) {
                            action.action(fn);

                            return false;
                        }
                    });

                route.onExit();

                expect(fn).not.toHaveBeenCalled();
            });
        });
    });

    describe('types', function() {
        var route;

        afterEach(function() {
            if (route) {
                route.destroy();
            }
        });

        describe('debug checks', function() {
            it('should throw if multiple same named parameters', function() {
                var fn = function() {
                    new Ext.route.Route({
                        url: 'foo/:{bar:alpha}/:{bar:alpha}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    });
                };

                expect(fn).toThrow('"bar" already defined in route "foo/:{bar:alpha}/:{bar:alpha}"');
            });

            it('should throw if type is unknown', function() {
                var fn = function() {
                    new Ext.route.Route({
                        url: 'foo/:{bar:foo}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    });
                };

                expect(fn).toThrow('Unknown parameter type "foo" in route "foo/:{bar:foo}"');
            });

            it('should throw if parameter mismatch', function() {
                var fn = function() {
                    new Ext.route.Route({
                        url: ':foo/:{bar:alpha}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    });
                };

                expect(fn).toThrow('URL parameter mismatch. Positional url parameter found while in named mode.');
            });
        });

        describe('conditions', function() {
            it('should still allow conditions in named mode', function() {
                var route = new Ext.route.Route({
                        url: 'foo/:{bar:alpha}/:{baz}',
                        conditions: {
                            baz: '([0-9a-zA-Z\.]+)'
                        },
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    hash = 'foo/abc/def',
                    recognized = route.recognize(hash),
                    promise = route.execute(hash, recognized);

                expect(recognized.urlParams).toEqual({
                    bar: 'abc',
                    baz: 'def'
                });

                promiseHasBeenResolved(promise);

                runs(function() {
                    expect(actionSpy).toHaveBeenCalledWith({
                        bar: 'abc',
                        baz: 'def'
                    });
                });
            });

            it('should still not recognize if condition is not matched', function() {
                var route = new Ext.route.Route({
                        url: 'foo/:{bar:alpha}/:{baz}',
                        conditions: {
                            baz: '([0-9]+)'
                        },
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    hash = 'foo/abc/def',
                    recognized = route.recognize(hash);

                expect(recognized).toBe(false);
            });

            it('should allow parse function in condition', function() {
                var route = new Ext.route.Route({
                        url: 'foo/:{bar:alpha}/:{baz}',
                        conditions: {
                            baz: {
                                re: '([0-9\.]+)',
                                parse: function(value) {
                                    return parseFloat(value);
                                }
                            }
                        },
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    hash = 'foo/abc/1.2',
                    recognized = route.recognize(hash),
                    promise = route.execute(hash, recognized);

                expect(recognized.urlParams).toEqual({
                    bar: 'abc',
                    baz: 1.2
                });

                promiseHasBeenResolved(promise);

                runs(function() {
                    expect(actionSpy).toHaveBeenCalledWith({
                        bar: 'abc',
                        baz: 1.2
                    });
                });
            });

            it('should allow split in condition', function() {
                var route = new Ext.route.Route({
                        url: 'view/:{view}',
                        conditions: {
                            view: {
                                re: '([a-z]+-[0-9]+)',
                                split: '-',
                                parse: function(values) {
                                    values[1] = parseFloat(values[1]);

                                    return values;
                                }
                            }
                        },
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    hash = 'view/ticket-12345',
                    recognized = route.recognize(hash),
                    promise = route.execute(hash, recognized);

                expect(recognized.urlParams).toEqual({
                    view: ['ticket', 12345]
                });

                promiseHasBeenResolved(promise);

                runs(function() {
                    expect(actionSpy).toHaveBeenCalledWith({
                        view: ['ticket', 12345]
                    });
                });
            });
        });

        describe('alpha', function() {
            it('should recognize all formats', function() {
                var route = new Ext.route.Route({
                        url: ':{bar:alpha}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    tests = [
                        'a',
                        'bc',
                        'def'
                    ],
                    length = tests.length,
                    i, test, recognized;

                for (i = 0; i < length; i++) {
                    test = tests[i];
                    recognized = route.recognize(test);

                    expect(recognized).toBeTruthy();
                    expect(recognized.urlParams).toEqual({
                        bar: test
                    });
                }
            });

            it('should not recognize invalid characters', function() {
                var route = new Ext.route.Route({
                        url: ':{bar:alpha}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    tests = [
                        'a&b',
                        'b*c',
                        '@sencha',
                        '#1',
                        '15%',
                        '10_0',
                        '10.1.1',
                        '1',
                        '1.1',
                        '.1',
                        '123.1234567890.1357902468',
                        '.54321.123',
                        'a1-b2'
                    ],
                    length = tests.length,
                    i, test, recognized;

                for (i = 0; i < length; i++) {
                    test = tests[i];
                    recognized = route.recognize(test);

                    expect(recognized).toBe(false);
                }
            });

            it('should match a single parameter', function() {
                var route = new Ext.route.Route({
                        url: 'foo/:{bar:alpha}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    hash = 'foo/abc',
                    recognized = route.recognize(hash),
                    promise = route.execute(hash, recognized);

                expect(recognized.urlParams).toEqual({
                    bar: 'abc'
                });

                promiseHasBeenResolved(promise);

                runs(function() {
                    expect(actionSpy).toHaveBeenCalledWith({
                        bar: 'abc'
                    });
                });
            });

            it('should not match alpha', function() {
                var route = new Ext.route.Route({
                        url: 'foo/:{bar:alpha}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    hash = 'foo/a1bc',
                    recognized = route.recognize(hash);

                expect(recognized).toBe(false);
            });

            it('should match multiple parameters', function() {
                var route = new Ext.route.Route({
                        url: 'foo/:{bar:alpha}/:{baz:alpha}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    hash = 'foo/abc/def',
                    recognized = route.recognize(hash),
                    promise = route.execute(hash, recognized);

                expect(recognized.urlParams).toEqual({
                    bar: 'abc',
                    baz: 'def'
                });

                promiseHasBeenResolved(promise);

                runs(function() {
                    expect(actionSpy).toHaveBeenCalledWith({
                        bar: 'abc',
                        baz: 'def'
                    });
                });
            });

            it('should not match match if one parameter is invalid', function() {
                var route = new Ext.route.Route({
                        url: 'foo/:{bar:alpha}/:{baz:alpha}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    hash = 'foo/abc/d1ef',
                    recognized = route.recognize(hash);

                expect(recognized).toBe(false);
            });
        });

        describe('alphanum', function() {
            var numRe = /^[0-9.]+$/;

            it('should recognize all formats', function() {
                var route = new Ext.route.Route({
                        url: ':{bar:alphanum}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    tests = [
                        'a',
                        'bc',
                        'def',
                        '1',
                        '15',
                        '100',
                        '10.1',
                        '1234567890.1357902468',
                        '.54321',
                        'a1b2'
                    ],
                    length = tests.length,
                    i, test, recognized;

                for (i = 0; i < length; i++) {
                    test = tests[i];
                    recognized = route.recognize(test);

                    expect(recognized).toBeTruthy();
                    expect(recognized.urlParams).toEqual({
                        bar: numRe.test(test) ? parseFloat(test) : test
                    });
                }
            });

            it('should not recognize invalid characters', function() {
                var route = new Ext.route.Route({
                        url: ':{bar:alphanum}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    tests = [
                        'a&b',
                        'b*c',
                        '@sencha',
                        '#1',
                        '15%',
                        '10_0',
                        '10.1.1',
                        '123.1234567890.1357902468',
                        '.54321.123',
                        'a1-b2'
                    ],
                    length = tests.length,
                    i, test, recognized;

                for (i = 0; i < length; i++) {
                    test = tests[i];
                    recognized = route.recognize(test);

                    expect(recognized).toBe(false);
                }
            });

            it('should match and execute with a single parameter', function() {
                var route = new Ext.route.Route({
                        url: 'foo/:{bar:alphanum}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    hash = 'foo/abc',
                    recognized = route.recognize(hash),
                    promise = route.execute(hash, recognized);

                expect(recognized.urlParams).toEqual({
                    bar: 'abc'
                });

                promiseHasBeenResolved(promise);

                runs(function() {
                    expect(actionSpy).toHaveBeenCalledWith({
                        bar: 'abc'
                    });
                });
            });

            it('should match and execute with a single parameter as number', function() {
                var route = new Ext.route.Route({
                        url: 'foo/:{bar:alphanum}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    hash = 'foo/10.0',
                    recognized = route.recognize(hash),
                    promise = route.execute(hash, recognized);

                expect(recognized.urlParams).toEqual({
                    bar: 10
                });

                promiseHasBeenResolved(promise);

                runs(function() {
                    expect(actionSpy).toHaveBeenCalledWith({
                        bar: 10
                    });
                });
            });

            it('should match and execute with multiple parameters', function() {
                var route = new Ext.route.Route({
                        url: 'foo/:{bar:alphanum}/:{baz:alphanum}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    hash = 'foo/abc/123',
                    recognized = route.recognize(hash),
                    promise = route.execute(hash, recognized);

                expect(recognized.urlParams).toEqual({
                    bar: 'abc',
                    baz: 123
                });

                promiseHasBeenResolved(promise);

                runs(function() {
                    expect(actionSpy).toHaveBeenCalledWith({
                        bar: 'abc',
                        baz: 123
                    });
                });
            });

            it('should not match match if one parameter is invalid', function() {
                var route = new Ext.route.Route({
                        url: 'foo/:{bar:alphanum}/:{baz:alphanum}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    hash = 'foo/ab.c/def',
                    recognized = route.recognize(hash);

                expect(recognized).toBe(false);
            });
        });

        describe('num', function() {
            var numRe = /^[0-9.]+$/;

            it('should recognize all formats', function() {
                var route = new Ext.route.Route({
                        url: ':{bar:num}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    tests = [
                        '1',
                        '15',
                        '100',
                        '10.1',
                        '1234567890.1357902468',
                        '.54321'
                    ],
                    length = tests.length,
                    i, test, recognized;

                for (i = 0; i < length; i++) {
                    test = tests[i];
                    recognized = route.recognize(test);

                    expect(recognized).toBeTruthy();
                    expect(recognized.urlParams).toEqual({
                        bar: numRe.test(test) ? parseFloat(test) : test
                    });
                }
            });

            it('should not recognize invalid characters', function() {
                var route = new Ext.route.Route({
                        url: ':{bar:num}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    tests = [
                        'abc',
                        'a&b',
                        'b*c',
                        '@sencha',
                        '#1',
                        '15%',
                        '10_0',
                        '10.1.1',
                        '123.1234567890.1357902468',
                        '.54321.123',
                        'a1-b2'
                    ],
                    length = tests.length,
                    i, test, recognized;

                for (i = 0; i < length; i++) {
                    test = tests[i];
                    recognized = route.recognize(test);

                    expect(recognized).toBe(false);
                }
            });

            it('should match and execute with a single parameter', function() {
                var route = new Ext.route.Route({
                        url: 'foo/:{bar:num}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    hash = 'foo/12',
                    recognized = route.recognize(hash),
                    promise = route.execute(hash, recognized);

                expect(recognized.urlParams).toEqual({
                    bar: 12
                });

                promiseHasBeenResolved(promise);

                runs(function() {
                    expect(actionSpy).toHaveBeenCalledWith({
                        bar: 12
                    });
                });
            });

            it('should match and execute with a single parameter as number', function() {
                var route = new Ext.route.Route({
                        url: 'foo/:{bar:num}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    hash = 'foo/10.0',
                    recognized = route.recognize(hash),
                    promise = route.execute(hash, recognized);

                expect(recognized.urlParams).toEqual({
                    bar: 10
                });

                promiseHasBeenResolved(promise);

                runs(function() {
                    expect(actionSpy).toHaveBeenCalledWith({
                        bar: 10
                    });
                });
            });

            it('should match and execute with multiple parameters', function() {
                var route = new Ext.route.Route({
                        url: 'foo/:{bar:num}/:{baz:num}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    hash = 'foo/123/40.5',
                    recognized = route.recognize(hash),
                    promise = route.execute(hash, recognized);

                expect(recognized.urlParams).toEqual({
                    bar: 123,
                    baz: 40.5
                });

                promiseHasBeenResolved(promise);

                runs(function() {
                    expect(actionSpy).toHaveBeenCalledWith({
                        bar: 123,
                        baz: 40.5
                    });
                });
            });

            it('should not match match if one parameter is invalid', function() {
                var route = new Ext.route.Route({
                        url: 'foo/:{bar:num}/:{baz:num}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    hash = 'foo/10.3/d',
                    recognized = route.recognize(hash);

                expect(recognized).toBe(false);
            });
        });

        describe('...', function() {
            it('should match single args value', function() {
                var route = new Ext.route.Route({
                        url: 'foo:{bar...}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    hash = 'foo/baz',
                    recognized = route.recognize(hash),
                    promise = route.execute(hash, recognized);

                expect(recognized.urlParams).toEqual({
                    bar: ['baz']
                });

                promiseHasBeenResolved(promise);

                runs(function() {
                    expect(actionSpy).toHaveBeenCalledWith({
                        bar: ['baz']
                    });
                });
            });

            it('should not match args value (should be optional)', function() {
                var route = new Ext.route.Route({
                        url: 'foo:{bar...}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    hash = 'foo',
                    recognized = route.recognize(hash),
                    promise = route.execute(hash, recognized);

                expect(recognized.urlParams).toEqual({
                    bar: undefined
                });

                promiseHasBeenResolved(promise);

                runs(function() {
                    expect(actionSpy).toHaveBeenCalledWith({
                        bar: undefined
                    });
                });
            });

            it('should match single args value with another param', function() {
                var route = new Ext.route.Route({
                        url: 'foo/:{id}:{bar...}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    hash = 'foo/123/baz',
                    recognized = route.recognize(hash),
                    promise = route.execute(hash, recognized);

                expect(recognized.urlParams).toEqual({
                    id: '123',
                    bar: ['baz']
                });

                promiseHasBeenResolved(promise);

                runs(function() {
                    expect(actionSpy).toHaveBeenCalledWith({
                        id: '123',
                        bar: ['baz']
                    });
                });
            });

            it('should match single args value with multiple other params', function() {
                var route = new Ext.route.Route({
                        url: 'foo/:{id}/:{view}:{args...}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    hash = 'foo/123/dashboard/bar',
                    recognized = route.recognize(hash),
                    promise = route.execute(hash, recognized);

                expect(recognized.urlParams).toEqual({
                    id: '123',
                    view: 'dashboard',
                    args: ['bar']
                });

                promiseHasBeenResolved(promise);

                runs(function() {
                    expect(actionSpy).toHaveBeenCalledWith({
                        id: '123',
                        view: 'dashboard',
                        args: ['bar']
                    });
                });
            });

            it('should match two args values', function() {
                var route = new Ext.route.Route({
                        url: 'foo:{args...}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    hash = 'foo/bar/baz',
                    recognized = route.recognize(hash),
                    promise = route.execute(hash, recognized);

                expect(recognized.urlParams).toEqual({
                    args: ['bar', 'baz']
                });

                promiseHasBeenResolved(promise);

                runs(function() {
                    expect(actionSpy).toHaveBeenCalledWith({
                        args: ['bar', 'baz']
                    });
                });
            });

            it('should match two args values with another param', function() {
                var route = new Ext.route.Route({
                        url: 'foo/:{id}:{args...}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    hash = 'foo/123/bar/baz',
                    recognized = route.recognize(hash),
                    promise = route.execute(hash, recognized);

                expect(recognized.urlParams).toEqual({
                    id: '123',
                    args: ['bar', 'baz']
                });

                promiseHasBeenResolved(promise);

                runs(function() {
                    expect(actionSpy).toHaveBeenCalledWith({
                        id: '123',
                        args: ['bar', 'baz']
                    });
                });
            });

            it('should match two args value with multiple other params', function() {
                var route = new Ext.route.Route({
                        url: 'foo/:{id}/:{view}:{args...}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    hash = 'foo/123/dashboard/bar/baz',
                    recognized = route.recognize(hash),
                    promise = route.execute(hash, recognized);

                expect(recognized.urlParams).toEqual({
                    id: '123',
                    view: 'dashboard',
                    args: ['bar', 'baz']
                });

                promiseHasBeenResolved(promise);

                runs(function() {
                    expect(actionSpy).toHaveBeenCalledWith({
                        id: '123',
                        view: 'dashboard',
                        args: ['bar', 'baz']
                    });
                });
            });

            it('should match many args values', function() {
                var route = new Ext.route.Route({
                        url: 'foo:{args...}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    hash = 'foo/bar/baz/foobar/barbaz',
                    recognized = route.recognize(hash),
                    promise = route.execute(hash, recognized);

                expect(recognized.urlParams).toEqual({
                    args: ['bar', 'baz', 'foobar', 'barbaz']
                });

                promiseHasBeenResolved(promise);

                runs(function() {
                    expect(actionSpy).toHaveBeenCalledWith({
                        args: ['bar', 'baz', 'foobar', 'barbaz']
                    });
                });
            });

            it('should match many args values with another param', function() {
                var route = new Ext.route.Route({
                        url: 'foo/:{id}:{args...}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    hash = 'foo/123/bar/baz/456/barbaz',
                    recognized = route.recognize(hash),
                    promise = route.execute(hash, recognized);

                expect(recognized.urlParams).toEqual({
                    id: '123',
                    args: ['bar', 'baz', 456, 'barbaz']
                });

                promiseHasBeenResolved(promise);

                runs(function() {
                    expect(actionSpy).toHaveBeenCalledWith({
                        id: '123',
                        args: ['bar', 'baz', 456, 'barbaz']
                    });
                });
            });

            it('should match many args value with multiple other params', function() {
                var route = new Ext.route.Route({
                        url: 'foo/:{id:num}/:{view}:{args...}',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller
                            }
                        ]
                    }),
                    hash = 'foo/123/dashboard/456/baz/foobar/barbaz',
                    recognized = route.recognize(hash),
                    promise = route.execute(hash, recognized);

                expect(recognized.urlParams).toEqual({
                    id: 123,
                    view: 'dashboard',
                    args: [456, 'baz', 'foobar', 'barbaz']
                });

                promiseHasBeenResolved(promise);

                runs(function() {
                    expect(actionSpy).toHaveBeenCalledWith({
                        id: 123,
                        view: 'dashboard',
                        args: [456, 'baz', 'foobar', 'barbaz']
                    });
                });
            });
        });
    });

    describe('optional group', function() {
        it('should match with value in optional group', function() {
            var route = new Ext.route.Route({
                    url: 'user/:{id:num}(/request/:{req:num})',
                    handlers: [
                        {
                            action: 'handleRoute',
                            scope: controller
                        }
                    ]
                }),
                hash = 'user/1234/request/9999',
                recognized = route.recognize(hash),
                promise = route.execute(hash, recognized);

            expect(recognized.urlParams).toEqual({
                id: 1234,
                req: 9999
            });

            promiseHasBeenResolved(promise);

            runs(function() {
                expect(actionSpy).toHaveBeenCalledWith({
                    id: 1234,
                    req: 9999
                });
            });
        });

        it('should match with no value in optional group', function() {
            var route = new Ext.route.Route({
                    url: 'user/:{id:num}(/request/:{req:num})',
                    handlers: [
                        {
                            action: 'handleRoute',
                            scope: controller
                        }
                    ]
                }),
                hash = 'user/1234',
                recognized = route.recognize(hash),
                promise = route.execute(hash, recognized);

            expect(recognized.urlParams).toEqual({
                id: 1234,
                req: undefined
            });

            promiseHasBeenResolved(promise);

            runs(function() {
                expect(actionSpy).toHaveBeenCalledWith({
                    id: 1234,
                    req: undefined
                });
            });
        });

        it('should handle multiple optional groups and no values within either', function() {
            var route = new Ext.route.Route({
                    url: 'user(/:{id:num})/foo(/request/:{req:num})',
                    handlers: [
                        {
                            action: 'handleRoute',
                            scope: controller
                        }
                    ]
                }),
                hash = 'user/foo',
                recognized = route.recognize(hash),
                promise = route.execute(hash, recognized);

            expect(recognized.urlParams).toEqual({
                id: undefined,
                req: undefined
            });

            promiseHasBeenResolved(promise);

            runs(function() {
                expect(actionSpy).toHaveBeenCalledWith({
                    id: undefined,
                    req: undefined
                });
            });
        });

        it('should handle multiple optional groups with one value in a group', function() {
            var route = new Ext.route.Route({
                    url: 'user(/:{id:num})/foo(/request/:{req:num})',
                    handlers: [
                        {
                            action: 'handleRoute',
                            scope: controller
                        }
                    ]
                }),
                hash = 'user/foo/request/8765',
                recognized = route.recognize(hash),
                promise = route.execute(hash, recognized);

            expect(recognized.urlParams).toEqual({
                id: undefined,
                req: 8765
            });

            promiseHasBeenResolved(promise);

            runs(function() {
                expect(actionSpy).toHaveBeenCalledWith({
                    id: undefined,
                    req: 8765
                });
            });
        });

        it('should handle multiple optional groups with values in both groups', function() {
            var route = new Ext.route.Route({
                    url: 'user(/:{id:num})/foo(/request/:{req:num})',
                    handlers: [
                        {
                            action: 'handleRoute',
                            scope: controller
                        }
                    ]
                }),
                hash = 'user/1234/foo/request/8765',
                recognized = route.recognize(hash),
                promise = route.execute(hash, recognized);

            expect(recognized.urlParams).toEqual({
                id: 1234,
                req: 8765
            });

            promiseHasBeenResolved(promise);

            runs(function() {
                expect(actionSpy).toHaveBeenCalledWith({
                    id: 1234,
                    req: 8765
                });
            });
        });
    });

    describe('single', function() {
        function buildSuite(single, rejectedAfter) {
            describe(String(single), function() {
                it('should remove handler from route', function() {
                    var route = new Ext.route.Route({
                        url: 'foo',
                        handlers: [
                            {
                                action: 'handleRoute',
                                scope: controller,
                                single: single
                            }
                        ]
                    });

                    expect(route.getHandlers().length).toBe(1);

                    route.execute();

                    expect(route.getHandlers().length).toBe(0);
                });

                it('should remove handler from route with resolved before', function() {
                    var route = new Ext.route.Route({
                        url: 'foo',
                        handlers: [
                            {
                                action: 'handleRoute',
                                before: 'beforeHandleRoute',
                                scope: controller,
                                single: single
                            }
                        ]
                    });

                    expect(route.getHandlers().length).toBe(1);

                    route.execute();

                    expect(route.getHandlers().length).toBe(0);
                });

                it('should not remove handler from route with rejected before', function() {
                    var route = new Ext.route.Route({
                        url: 'foo',
                        handlers: [
                            {
                                action: 'handleRoute',
                                before: 'beforeHandleRouteBlock',
                                scope: controller,
                                single: single
                            }
                        ]
                    });

                    expect(route.getHandlers().length).toBe(1);

                    route.execute();

                    expect(route.getHandlers().length).toBe(rejectedAfter);
                });
            });
        }

        buildSuite(true, 1);
        buildSuite('after', 1);
        buildSuite('before', 0);
    });
});

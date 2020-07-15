/* global undefinedFn */
topSuite("Ext.route.Router", ['Ext.app.Controller', 'Ext.util.History'], function() {
    var Router = Ext.route.Router,
        actionExecuted = false,
        beforeExecuted = false,
        numArgs = 0,
        numBeforeArgs = 0,
        token = 'foo/bar',
        token2 = 'foo2/:id',
        controller, other, deferred;

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
        deferred = new Ext.Deferred();
        controller = new Ext.app.Controller({
            beforeHandleRoute: function() {
                numBeforeArgs += arguments.length;
                beforeExecuted = true;

                return new Ext.Promise(function(resolve, reject) {
                    resolve();
                });
            },

            beforeHandleRouteBlock: function() {
                numBeforeArgs += arguments.length;
                beforeExecuted = true;

                return new Ext.Promise(function(resolve, reject) {
                    reject();
                });
            },

            beforeHandleRouteError: function() {
                undefinedFn();
            },

            handleRoute: function() {
                numArgs += arguments.length;
                actionExecuted = true;
            }
        });

        other = new Ext.app.Controller({
            handleRoute: Ext.emptyFn
        });
    });

    afterEach(function() {
        if (!controller.isDestroyed) {
            controller.destroy();
        }

        if (!other.isDestroyed) {
            other.destroy();
        }

        deferred = null;
        other = null;
        controller = null;
        actionExecuted = false;
        beforeExecuted = false;
        numArgs = 0;
        numBeforeArgs = 0;

        Router.routes = {};

        Router.setQueueRoutes(true);
    });

    it("should init Ext.util.History", function() {
        expect(Ext.util.History.ready).toBeTruthy();
    });

    describe("should connect route", function() {
        it("connect simple route", function() {
            Router.connect('foo/bar', 'handleRoute', controller);
            Router.connect('foo/bar', 'handleRoute', controller);

            expect(Router.routes['foo/bar'].getHandlers().length).toBe(2);
        });

        it("connect complex route", function() {
            Router.connect('foo/bar', {
                action: 'handleRoute',
                before: 'beforeHandleRoute',
                controller: controller
            });
            Router.connect('foo/bar', {
                action: 'handleRoute',
                before: 'beforeHandleRoute',
                controller: controller
            });
            Router.connect('foo/bar', {
                action: 'handleRoute',
                before: 'beforeHandleRoute',
                controller: controller
            });

            expect(Router.routes['foo/bar'].getHandlers().length).toBe(3);
        });

        it("should create route with conditions", function() {
            Router.connect('foo/:bar', {
                action: 'handleRoute',
                before: 'beforeHandleRoute',
                controller: controller,
                conditions: {
                    ':bar': '(bar|baz)'
                }
            });

            expect(Router.routes['foo/:bar'].matcherRegex.test('foo/bar')).toBeTruthy();
            expect(Router.routes['foo/:bar'].matcherRegex.test('foo/unmatched')).toBeFalsy();
        });

        it("connect using draw method", function() {
            Router.draw(function(map) {
                map.connect('foo/bar', {
                    controller: controller,
                    action: 'handleRoute'
                });
                map.connect('foo/bar', {
                    controller: controller,
                    action: 'handleRoute'
                });
            });

            expect(Router.routes['foo/bar'].getHandlers().length).toBe(2);
        });
    });

    describe("clear routes", function() {
        it("should clear routes on Router.clear()", function() {
            Router.connect('foo/bar', 'handleRoute', controller);
            Router.connect('foo/baz', 'handleRoute', controller);

            Router.clear();

            expect(Ext.Object.isEmpty(Router.routes)).toBeTruthy();
        });

        it("should disconnect routes for a controller", function() {
            Router.connect('foo/bar', 'handleRoute', controller);
            Router.connect('foo/bar', 'handleRoute', other);

            Router.disconnect(other);
            expect(Ext.Object.getSize(Router.routes)).toBe(1);
        });

        it("should disconnect routes on controller destroy", function() {
            Router.connect('foo/bar', 'handleRoute', controller);
            Router.connect('foo/bar', 'handleRoute', other);

            other.destroy();

            expect(Ext.Object.getSize(Router.routes)).toBe(1);
        });
    });

    describe("should recognize token", function() {
        it("recognize 'foo/bar'", function() {
            Router.connect(token, 'handleRoute', controller);
            // connect a route that will not match
            Router.connect(token + '/boom', 'handleRoute', controller);

            expect(Router.recognize(token)).toBeDefined();
        });
    });

    describe("unmatchedroute event", function() {
        it("should fire on application", function() {
            var app = Router.application,
                newApp = new Ext.util.Observable(),
                fn = spyOn(newApp, 'fireEvent');

            Router.connect('foo', 'handleRoute', controller);
            Router.application = newApp;

            Router.onStateChange('bar');

            waitsForSpy(fn);

            runs(function() {
                expect(fn).toHaveBeenCalledWith('unmatchedroute', 'bar');

                // restore if any were previously set
                Router.application = app;
            });
        });

        it("should listen using Ext.on", function() {
            var fn = spyOn({
                test: Ext.emptyFn
            }, 'test');

            Ext.on('unmatchedroute', fn, null, {
                single: true
            });

            Router.connect('foo', 'handleRoute', controller);

            Router.onStateChange('bar');

            waitsForSpy(fn);

            runs(function() {
                expect(fn).toHaveBeenCalledWith('bar', {
                    single: true
                });
            });
        });

        it("should listen in controller", function() {
            var controller = new Ext.app.Controller({
                listen: {
                    global: {
                        unmatchedroute: 'onUnmatchedRoute'
                    }
                },

                onUnmatchedRoute: Ext.emptyFn
            }),
                fn = spyOn(controller, 'onUnmatchedRoute');

            Router.connect('foo', 'handleRoute', controller);

            Router.onStateChange('bar');

            waitsForSpy(fn);

            runs(function() {
                expect(fn).toHaveBeenCalledWith('bar');
            });
        });
    });

    it("should execute multiple tokens", function() {
        // action should have 0 arguments
        Router.connect(token, 'handleRoute', controller);

        // before should have 2 arguments, action should have 1
        Router.connect(token2, {
            action: 'handleRoute',
            before: 'beforeHandleRoute'
        }, controller);

        Router.onStateChange('foo/bar|foo2/2');

        deferred.resolve();

        promiseHasBeenResolved(deferred.promise);

        runs(function() {
            expect(numBeforeArgs + numArgs).toBe(3);
        });
    });

    it("should execute on History change", function() {
        Router.setQueueRoutes(false);

        Router.connect('foo/bar', 'handleRoute', controller);

        Router.onStateChange(token);

        deferred.resolve();

        promiseHasBeenResolved(deferred.promise);

        runs(function() {
            expect(actionExecuted).toBeTruthy();
        });
    });

    describe("global before handler", function() {
        describe("single before handler", function() {
            it("should continue route execution using action argument", function() {
                Router.connect('*', {
                    before: 'beforeHandleRoute'
                }, controller);

                Router.connect('foo/bar', 'handleRoute', controller);

                Router.onStateChange(token);

                deferred.resolve();

                promiseHasBeenResolved(deferred.promise);

                runs(function() {
                    expect(actionExecuted).toBeTruthy();
                });
            });

            it("should stop route execution action argument", function() {
                Router.connect('*', {
                    before: 'beforeHandleRouteBlock'
                }, controller);

                Router.connect('foo/bar', 'handleRoute', controller);

                Router.onStateChange(token);

                deferred.reject();

                promiseHasBeenRejected(deferred.promise);

                runs(function() {
                    expect(actionExecuted).toBeFalsy();
                });
            });
        });

        describe("multiple before handler", function() {
            it("should continue route execution when all resume", function() {
                Router.connect('*', {
                    before: 'beforeHandleRoute'
                }, controller);

                Router.connect('*', {
                    before: function(action) {
                        action.resume();
                    }
                }, controller);

                Router.connect('foo/bar', 'handleRoute', controller);

                Router.onStateChange(token);

                deferred.resolve();

                promiseHasBeenResolved(deferred.promise);

                runs(function() {
                    expect(actionExecuted).toBeTruthy();
                });
            });

            it("should stop route execution when first handler stops", function() {
                Router.connect('*', {
                    before: 'beforeHandleRouteBlock'
                }, controller);

                Router.connect('*', {
                    before: function(action) {
                        action.resume();
                    }
                }, controller);

                Router.connect('foo/bar', 'handleRoute', controller);

                Router.onStateChange(token);

                deferred.reject();

                promiseHasBeenRejected(deferred.promise);

                runs(function() {
                    expect(actionExecuted).toBeFalsy();
                });
            });

            it("should stop route execution when second handler stops", function() {
                Router.connect('*', {
                    before: function(action) {
                        action.resume();
                    }
                }, controller);

                Router.connect('*', {
                    before: 'beforeHandleRouteBlock'
                }, controller);

                Router.connect('foo/bar', 'handleRoute', controller);

                Router.onStateChange(token);

                deferred.reject();

                promiseHasBeenRejected(deferred.promise);

                runs(function() {
                    expect(actionExecuted).toBeFalsy();
                });
            });
        });
    });

    describe("suspend", function() {
        afterEach(function() {
            Router.resume(true);
        });

        it("should be suspended", function() {
            Router.suspend();

            expect(Router.isSuspended).toBeTruthy();
        });

        it("should be create suspend queue", function() {
            Router.suspend();

            expect(Router.suspendedQueue).toEqual([]);
        });

        it("should not create suspend queue", function() {
            Router.suspend(false);

            expect(Router.suspendedQueue).toBeFalsy();
        });

        it("should add token to suspendedQueue", function() {
            Router.suspend();

            Router.connect(token, 'handleRoute', controller);

            Router.onStateChange(token);

            expect(Router.suspendedQueue.length).toBe(1);
        });

        it("should add multiple tokens to suspendedQueue", function() {
            Router.suspend();

            Router.connect(token, 'handleRoute', controller);

            Router.onStateChange(token + '|foo2/1');

            expect(Router.suspendedQueue.length).toBe(2);
        });

        it("should not add token to suspendedQueue", function() {
            Router.suspend(false);

            Router.connect(token, 'handleRoute', controller);

            Router.onStateChange(token);

            expect(Router.suspendedQueue).toBeFalsy();
        });
    });

    describe("resume", function() {
        it("should execute suspended tokens", function() {
            Router.suspend();

            Router.connect(token, 'handleRoute', controller);

            Router.onStateChange(token);

            Router.resume();

            deferred.resolve();

            promiseHasBeenResolved(deferred.promise);

            runs(function() {
                expect(actionExecuted).toBeTruthy();
                expect(Router.isSuspended).toBeFalsy();
                expect(Router.suspendedQueue).toBeFalsy();
                expect(Router.isSuspended).toBeFalsy();
            });
        });

        it("should not execute suspended tokens", function() {
            Router.suspend();

            Router.connect(token, 'handleRoute', controller);

            Router.onStateChange(token);

            Router.resume(true);

            expect(actionExecuted).toBeFalsy();
            expect(Router.isSuspended).toBeFalsy();
            expect(Router.suspendedQueue).toBeFalsy();
        });

        it("should handle having no suspendedQueue", function() {
            Router.suspend(false);

            Router.connect(token, 'handleRoute', controller);

            Router.onStateChange(token);

            Router.resume();

            expect(actionExecuted).toBeFalsy();
            expect(Router.isSuspended).toBeFalsy();
            expect(Router.suspendedQueue).toBeFalsy();
        });
    });

    describe("beforeroutes event", function() {
        describe("using Ext.on", function() {
            it("should fire event", function() {
                var fn = spyOn({
                    test: Ext.emptyFn
                }, 'test');

                Ext.on('beforeroutes', fn, null, {
                    single: true
                });

                Router.onStateChange(token);

                waitsForSpy(fn);

                runs(function() {
                    expect(fn).toHaveBeenCalled();
                });
            });

            it("should execute before added in event listener", function() {
                var fn = spyOn({
                    test: function(action) {
                        action.resume();
                    }
                }, 'test').andCallThrough();

                Ext.on('beforeroutes', function(action) {
                    action.before(fn);
                }, null, {
                    single: true
                });

                Router.onStateChange(token);

                waitsForSpy(fn);

                runs(function() {
                    expect(fn).toHaveBeenCalled();
                });
            });

            it("should execute action added in event listener", function() {
                var fn = spyOn({
                    test: Ext.emptyFn
                }, 'test');

                Ext.on('beforeroutes', function(action) {
                    action.action(fn);
                }, null, {
                    single: true
                });

                Router.onStateChange(token);

                waitsForSpy(fn);

                runs(function() {
                    expect(fn).toHaveBeenCalled();
                });
            });

            it("should not execute before if return false", function() {
                var fn = spyOn({
                    test: function(action) {
                        action.resume();
                    }
                }, 'test').andCallThrough();

                Ext.on('beforeroutes', function(action) {
                    action.before(fn);

                    deferred.reject();

                    return false;
                }, null, {
                    single: true
                });

                Router.onStateChange(token);

                promiseHasBeenRejected(deferred.promise);

                runs(function() {
                    expect(fn).not.toHaveBeenCalled();
                });
            });

            it("should not execute action if return false", function() {
                var fn = spyOn({
                    test: Ext.emptyFn
                }, 'test');

                Ext.on('beforeroutes', function(action) {
                    action.action(fn);

                    deferred.reject();

                    return false;
                }, null, {
                    single: true
                });

                Router.onStateChange(token);

                promiseHasBeenRejected(deferred.promise);

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
                            beforeroutes: 'onBeforeRoute'
                        }
                    },

                    onBeforeRoute: Ext.emptyFn
                }),
                    fn = spyOn(controller, 'onBeforeRoute');

                Router.onStateChange(token);

                waitsForSpy(fn);

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
                                beforeroutes: 'onBeforeRoute'
                            }
                        },

                        onBeforeRoute: function(action) {
                            action.before(fn);
                        }
                    });

                Router.onStateChange(token);

                waitsForSpy(fn);

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
                                beforeroutes: 'onBeforeRoute'
                            }
                        },

                        onBeforeRoute: function(action) {
                            action.action(fn);
                        }
                    });

                Router.onStateChange(token);

                waitsForSpy(fn);

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
                                beforeroutes: 'onBeforeRoute'
                            }
                        },

                        onBeforeRoute: function(action) {
                            action.before(function(action) {
                                action.stop();

                                deferred.reject();
                            })
                                .action(fn);
                        }
                    });

                Router.onStateChange(token);

                promiseHasBeenRejected(deferred.promise);

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
                                beforeroutes: 'onBeforeRoute'
                            }
                        },

                        onBeforeRoute: function(action) {
                            action.before(fn);

                            deferred.reject();

                            return false;
                        }
                    });

                Router.onStateChange(token);

                promiseHasBeenRejected(deferred.promise);

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
                                beforeroutes: 'onBeforeRoute'
                            }
                        },

                        onBeforeRoute: function(action) {
                            action.action(fn);

                            deferred.reject();

                            return false;
                        }
                    });

                Router.onStateChange(token);

                promiseHasBeenRejected(deferred.promise);

                runs(function() {
                    expect(fn).not.toHaveBeenCalled();
                });
            });
        });
    });

    describe('hashbang', function() {
        it('should set hashbang to true on History', function() {
            Router.setHashbang(true);

            expect(Ext.util.History.hashbang).toBe(true);
        });
    });

    describe('lazy', function() {
        it('should execute on connection', function() {
            var spy = spyOn(controller, 'handleRoute').andCallThrough();

            Ext.util.History.currentToken = 'foo';
            Ext.util.History.ready = true;

            Router.connect('foo', {
                action: 'handleRoute',
                lazy: true
            }, controller);

            waitsForSpy(spy);

            runs(function() {
                expect(spy).toHaveBeenCalled();
                expect(actionExecuted).toBeTruthy();

                delete Ext.util.History.currentToken;
                delete Ext.util.History.ready;
            });
        });
    });

    describe('handle rejected before', function() {
        xit('should handle an error thrown in before', function() {
            var before = spyOn(controller, 'beforeHandleRouteError').andCallThrough(),
                rejector = spyOn(Router, 'onRouteRejection').andCallThrough(),
                raise = spyOn(Ext, 'raise').andCallThrough(),
                action = spyOn(controller, 'handleRoute');

            Router.connect('foo/bar', {
                before: before,
                action: action
            }, controller);

            Router.onStateChange(token);

            waitsForSpy(before);
            waitsForSpy(rejector);
            waitsForSpy(raise);

            runs(function() {
                expect(action).not.toHaveBeenCalled();
            });
        });
    });
});

topSuite("Ext.route.Action", ['Ext.app.Controller'], function() {
    var instance;

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

    afterEach(function() {
        if (instance) {
            instance.destroy();
            instance = null;
        }
    });

    describe("constructing", function() {
        it("should have array of before functions passing a single object", function() {
            instance = new Ext.route.Action({
                befores: {
                    fn: Ext.emptyFn,
                    scope: {}
                }
            });

            expect(instance.getBefores().length).toBe(1);
        });

        it("should have array of before functions passing multiple objects", function() {
            instance = new Ext.route.Action({
                befores: [{
                    fn: Ext.emptyFn,
                    scope: {}
                }, {
                    fn: Ext.emptyFn,
                    scope: {}
                }]
            });

            expect(instance.getBefores().length).toBe(2);
        });

        it("should have array of action functions passing a single object", function() {
            instance = new Ext.route.Action({
                actions: {
                    fn: Ext.emptyFn,
                    scope: {}
                }
            });

            expect(instance.getActions().length).toBe(1);
        });

        it("should have array of action functions passing multiple objects", function() {
            instance = new Ext.route.Action({
                actions: [{
                    fn: Ext.emptyFn,
                    scope: {}
                }, {
                    fn: Ext.emptyFn,
                    scope: {}
                }]
            });

            expect(instance.getActions().length).toBe(2);
        });
    });

    describe("run", function() {
        var actionExecuted = 0,
            beforeExecuted = 0,
            numArgs = 0,
            numBeforeArgs = 0,
            token = 'foo/bar',
            controller;

        beforeEach(function() {
            controller = new Ext.app.Controller({
                beforeHandleRoute: function() {
                    numBeforeArgs += arguments.length;
                    beforeExecuted++;

                    var action = arguments[arguments.length - 1];

                    action.resume();
                },

                beforeHandleRouteBlock: function() {
                    numBeforeArgs += arguments.length;
                    beforeExecuted++;

                    var action = arguments[arguments.length - 1];

                    action.stop(); // stop the current route
                },

                handleRoute: function() {
                    numArgs = arguments.length;
                    actionExecuted++;
                }
            });
        });

        afterEach(function() {
            controller.destroy();

            controller = null;
            actionExecuted = 0;
            beforeExecuted = 0;
            numArgs = 0;
            numBeforeArgs = 0;
        });

        it("should return promise", function() {
            instance = new Ext.route.Action({
                actions: {
                    fn: 'handleRoute',
                    scope: controller
                }
            });

            expect(instance.run() instanceof Ext.promise.Promise).toBeTruthy();
        });

        it("should resolve promise", function() {
            var promise;

            instance = new Ext.route.Action({
                actions: {
                    fn: 'handleRoute',
                    scope: controller
                }
            });

            promise = instance.run();

            promiseHasBeenResolved(promise);
        });

        it("should reject promise", function() {
            var promise;

            instance = new Ext.route.Action({
                befores: {
                    fn: 'beforeHandleRouteBlock',
                    scope: controller
                }
            });

            promise = instance.run();

            promiseHasBeenRejected(promise);
        });

        it("should be destroyed after running", function() {
            var promise;

            instance = new Ext.route.Action({
                actions: {
                    fn: 'handleRoute',
                    scope: controller
                }
            });

            promise = instance.run();

            promiseHasBeenResolved(promise);

            runs(function() {
                expect(instance.destroyed).toBeTruthy();
            });
        });

        it("should run a single before function", function() {
            var promise;

            instance = new Ext.route.Action({
                befores: {
                    fn: 'beforeHandleRoute',
                    scope: controller
                }
            });

            promise = instance.run();

            promiseHasBeenResolved(promise);

            runs(function() {
                expect(beforeExecuted).toBe(1);
            });
        });

        it("should run multiple before functions", function() {
            var promise;

            instance = new Ext.route.Action({
                befores: [{
                    fn: 'beforeHandleRoute',
                    scope: controller
                }, {
                    fn: 'beforeHandleRoute',
                    scope: controller
                }]
            });

            promise = instance.run();

            promiseHasBeenResolved(promise);

            runs(function() {
                expect(beforeExecuted).toBe(2);
            });
        });

        it("should run a single action function", function() {
            var promise;

            instance = new Ext.route.Action({
                actions: {
                    fn: 'handleRoute',
                    scope: controller
                }
            });

            promise = instance.run();

            promiseHasBeenResolved(promise);

            runs(function() {
                expect(actionExecuted).toBe(1);
            });
        });

        it("should run multiple action functions", function() {
            var promise;

            instance = new Ext.route.Action({
                actions: [{
                    fn: 'handleRoute',
                    scope: controller
                }, {
                    fn: 'handleRoute',
                    scope: controller
                }]
            });

            promise = instance.run();

            promiseHasBeenResolved(promise);

            runs(function() {
                expect(actionExecuted).toBe(2);
            });
        });
    });

    describe("before", function() {
        describe("as a config", function() {
            it("should add before as an object", function() {
                instance = new Ext.route.Action({
                    befores: {}
                });

                expect(instance.getBefores().length).toBe(1);
            });

            it("should add before as an array of objects", function() {
                instance = new Ext.route.Action({
                    befores: [{}, {}]
                });

                expect(instance.getBefores().length).toBe(2);
            });

            it("should run before", function() {
                var fn = spyOn({
                    test: function(action) {
                        action.resume();
                    }
                }, 'test').andCallThrough();

                instance = new Ext.route.Action({
                    befores: {
                        fn: fn
                    }
                });

                instance.run();

                expect(fn).toHaveBeenCalled();
            });
        });

        describe("added using method", function() {
            it("should add before when no stack exists", function() {
                instance = new Ext.route.Action();

                instance.before(function() {});

                expect(instance.getBefores().length).toBe(1);
            });

            it("should add before to empty before stack", function() {
                instance = new Ext.route.Action({
                    befores: []
                });

                instance.before(function() {});

                expect(instance.getBefores().length).toBe(1);
            });

            it("should add before to non-empty before stack", function() {
                instance = new Ext.route.Action({
                    befores: [{}]
                });

                instance.before(function() {});

                expect(instance.getBefores().length).toBe(2);
            });

            describe("run added before", function() {
                it("should run before when stack was empty", function() {
                    var fn = spyOn({
                            test: function(action) {
                                action.resume();
                            }
                        }, 'test').andCallThrough(),
                        promise;

                    instance = new Ext.route.Action();

                    instance.before(fn);

                    promise = instance.run();

                    promiseHasBeenResolved(promise);

                    runs(function() {
                        expect(fn).toHaveBeenCalled();
                    });
                });

                it("should run before when stack not empty", function() {
                    var fn = spyOn({
                            test: function(action) {
                                action.resume();
                            }
                        }, 'test').andCallThrough(),
                        promise;

                    instance = new Ext.route.Action({
                        befores: {
                            fn: function(action) {
                                action.resume();
                            }
                        }
                    });

                    instance.before(fn);

                    promise = instance.run();

                    promiseHasBeenResolved(promise);

                    runs(function() {
                        expect(fn).toHaveBeenCalled();
                    });
                });

                it("should not run before when stack not empty", function() {
                    var fn = spyOn({
                            test: function(action) {
                                action.resume();
                            }
                        }, 'test').andCallThrough(),
                        promise;

                    instance = new Ext.route.Action({
                        befores: {
                            fn: function(action) {
                                action.stop();
                            }
                        }
                    });

                    instance.before(fn);

                    promise = instance.run();

                    promiseHasBeenRejected(promise);

                    runs(function() {
                        expect(fn).not.toHaveBeenCalled();
                    });
                });

                // before inception
                it("should run before when added in a before", function() {
                    var fn = spyOn({
                            test: function(action) {
                                action.resume();
                            }
                        }, 'test').andCallThrough(),
                        promise;

                    instance = new Ext.route.Action({
                        befores: {
                            fn: function(action) {
                                action.before(fn).resume();
                            }
                        }
                    });

                    promise = instance.run();

                    promiseHasBeenResolved(promise);

                    runs(function() {
                        expect(fn).toHaveBeenCalled();
                    });
                });

                it("should not run before when added in a before that will stop", function() {
                    var fn = spyOn({
                            test: function(action) {
                                action.resume();
                            }
                        }, 'test').andCallThrough(),
                        promise;

                    instance = new Ext.route.Action({
                        befores: {
                            fn: function(action) {
                                action.before(fn).stop();
                            }
                        }
                    });

                    promise = instance.run();

                    promiseHasBeenRejected(promise);

                    runs(function() {
                        expect(fn).not.toHaveBeenCalled();
                    });
                });
            });

            describe("run added before with promises", function() {
                it("should run before when stack was empty", function() {
                    var fn = spyOn({
                            test: function(action) {
                                return new Ext.Promise(function(resolve) {
                                    resolve();
                                });
                            }
                        }, 'test').andCallThrough(),
                        promise;

                    instance = new Ext.route.Action();

                    instance.before(fn);

                    promise = instance.run();

                    promiseHasBeenResolved(promise);

                    runs(function() {
                        expect(fn).toHaveBeenCalled();
                    });
                });

                it("should run before when stack not empty", function() {
                    var fn = spyOn({
                            test: function(action) {
                                return new Ext.Promise(function(resolve) {
                                    resolve();
                                });
                            }
                        }, 'test').andCallThrough(),
                        promise;

                    instance = new Ext.route.Action({
                        befores: {
                            fn: function(action) {
                                return new Ext.Promise(function(resolve) {
                                    resolve();
                                });
                            }
                        }
                    });

                    instance.before(fn);

                    promise = instance.run();

                    promiseHasBeenResolved(promise);

                    runs(function() {
                        expect(fn).toHaveBeenCalled();
                    });
                });

                it("should not run before when stack not empty", function() {
                    var fn = spyOn({
                            test: function(action) {
                                return new Ext.Promise(function(resolve) {
                                    resolve();
                                });
                            }
                        }, 'test').andCallThrough(),
                        promise;

                    instance = new Ext.route.Action({
                        befores: {
                            fn: function(action) {
                                return new Ext.Promise(function(resolve, reject) {
                                    reject();
                                });
                            }
                        }
                    });

                    instance.before(fn);

                    promise = instance.run();

                    promiseHasBeenRejected(promise);

                    runs(function() {
                        expect(fn).not.toHaveBeenCalled();
                    });
                });

                it("should run before when added in a before", function() {
                    var fn = spyOn({
                            test: function(action) {
                                return new Ext.Promise(function(resolve) {
                                    resolve();
                                });
                            }
                        }, 'test').andCallThrough(),
                        promise;

                    instance = new Ext.route.Action({
                        befores: {
                            fn: function(action) {
                                action.before(fn);

                                return new Ext.Promise(function(resolve) {
                                    resolve();
                                });
                            }
                        }
                    });

                    promise = instance.run();

                    promiseHasBeenResolved(promise);

                    runs(function() {
                        expect(fn).toHaveBeenCalled();
                    });
                });

                it("should not run before when added in a before that will stop", function() {
                    var fn = spyOn({
                            test: function(action) {
                                return new Ext.Promise(function(resolve) {
                                    resolve();
                                });
                            }
                        }, 'test').andCallThrough(),
                        promise;

                    instance = new Ext.route.Action({
                        befores: {
                            fn: function(action) {
                                action.before(fn);

                                return new Ext.Promise(function(resolve, reject) {
                                    reject();
                                });
                            }
                        }
                    });

                    promise = instance.run();

                    promiseHasBeenRejected(promise);

                    runs(function() {
                        expect(fn).not.toHaveBeenCalled();
                    });
                });
            });
        });
    });

    describe("action", function() {
        describe("as a config", function() {
            it("should add action as an object", function() {
                instance = new Ext.route.Action({
                    actions: {}
                });

                expect(instance.getActions().length).toBe(1);
            });

            it("should add action as an array of objects", function() {
                instance = new Ext.route.Action({
                    actions: [{}, {}]
                });

                expect(instance.getActions().length).toBe(2);
            });

            it("should run action", function() {
                var fn = spyOn({
                    test: Ext.emptyFn
                }, 'test');

                instance = new Ext.route.Action({
                    actions: {
                        fn: fn
                    }
                });

                instance.run();

                expect(fn).toHaveBeenCalled();
            });
        });

        describe("added using method", function() {
            it("should add action when no stack exists", function() {
                instance = new Ext.route.Action();

                instance.action(function() {});

                expect(instance.getActions().length).toBe(1);
            });

            it("should add action to empty action stack", function() {
                instance = new Ext.route.Action({
                    actions: []
                });

                instance.action(function() {});

                expect(instance.getActions().length).toBe(1);
            });

            it("should add action to non-empty action stack", function() {
                instance = new Ext.route.Action({
                    actions: [{}]
                });

                instance.action(function() {});

                expect(instance.getActions().length).toBe(2);
            });

            describe("run added action", function() {
                it("should run action when stack was empty", function() {
                    var fn = spyOn({
                            test: Ext.emptyFn
                        }, 'test'),
                        promise;

                    instance = new Ext.route.Action();

                    instance.action(fn);

                    promise = instance.run();

                    promiseHasBeenResolved(promise);

                    runs(function() {
                        expect(fn).toHaveBeenCalled();
                    });
                });

                it("should run action when stack not empty", function() {
                    var fn = spyOn({
                            test: Ext.emptyFn
                        }, 'test'),
                        promise;

                    instance = new Ext.route.Action({
                        actions: {
                            fn: Ext.emptyFn
                        }
                    });

                    instance.action(fn);

                    promise = instance.run();

                    promiseHasBeenResolved(promise);

                    runs(function() {
                        expect(fn).toHaveBeenCalled();
                    });
                });

                it("should run action when added in a before", function() {
                    var fn = spyOn({
                            test: Ext.emptyFn
                        }, 'test'),
                        promise;

                    instance = new Ext.route.Action({
                        befores: {
                            fn: function(action) {
                                action.action(fn).resume();
                            }
                        }
                    });

                    promise = instance.run();

                    promiseHasBeenResolved(promise);

                    runs(function() {
                        expect(fn).toHaveBeenCalled();
                    });
                });

                it("should not run action when added in a before and action is stopped", function() {
                    var fn = spyOn({
                            test: Ext.emptyFn
                        }, 'test'),
                        promise;

                    instance = new Ext.route.Action({
                        befores: {
                            fn: function(action) {
                                action.action(fn).stop();
                            }
                        }
                    });

                    promise = instance.run();

                    promiseHasBeenRejected(promise);

                    runs(function() {
                        expect(fn).not.toHaveBeenCalled();
                    });
                });
            });
        });
    });

    describe("then", function() {
        it("should execute resolve function", function() {
            var resolve = spyOn({
                    test: Ext.emptyFn
                }, 'test'),
                promise;

            instance = new Ext.route.Action();

            instance.then(resolve);

            promise = instance.run();

            promiseHasBeenResolved(promise);

            runs(function() {
                expect(resolve).toHaveBeenCalled();
            });
        });

        it("should execute reject function", function() {
            var resolve = spyOn({
                    test: Ext.emptyFn
                }, 'test'),
                reject = spyOn({
                    test: Ext.emptyFn
                }, 'test'),
                promise;

            instance = new Ext.route.Action({
                befores: {
                    fn: function(action) {
                        action.stop();
                    }
                }
            });

            instance.then(resolve, reject);

            promise = instance.run();

            promiseHasBeenRejected(promise);

            runs(function() {
                expect(resolve).not.toHaveBeenCalled();
                expect(reject).toHaveBeenCalled();
            });
        });
    });
});

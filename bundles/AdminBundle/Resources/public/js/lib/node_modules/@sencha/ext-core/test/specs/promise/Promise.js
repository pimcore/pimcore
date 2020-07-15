/*
 Adapted from:
 Copyright (c) 2013 [DeftJS Framework Contributors](http://deftjs.org)
 Open source under the [MIT License](http://en.wikipedia.org/wiki/MIT_License).
 */

/* global Promise, assert, Logger */
/* eslint-disable no-sparse-arrays */

topSuite('Ext.promise.Promise', ['Ext.Promise', 'Ext.Deferred', 'Ext.promise.*'], function() {
    var Deferred = Ext.Deferred,
        ExtPromise = Ext.promise.Promise,
        hasNativePromise = !!window.Promise,
        targetScope = {},
        deferred, promise, extLog;

    if (Object.freeze) {
        Object.freeze(targetScope);
    }

    beforeEach(function() {
        // Prevent raised errors from polluting the console
        extLog = Ext.log;
        Ext.log = Ext.emptyFn;

        deferred = promise = null;
    });

    afterEach(function() {
        Ext.log = extLog;

        deferred = promise = extLog = null;
    });

    function eventuallyResolvesTo(promise, value, equals) {
        var doneSpy = jasmine.createSpy().andCallFake(function(v) {
                result = v;
            }),
            result;

        promise.then(doneSpy);

        waitsForSpy(doneSpy);

        runs(function() {
            if (equals) {
                expect(result).toEqual(value);
            }
            else {
                expect(result).toBe(value);
            }
        });
    }

    function eventuallyRejectedWith(promise, error, message) {
        var done = false,
            result, reason;

        promise.then(function(v) {
            result = v;
            done = true;
        }, function(v) {
            reason = v;
            done = true;
        });

        waitsFor(function() {
            return done;
        });

        runs(function() {
            expect(result).toBe(undefined);

            if (typeof error === 'string') {
                expect(reason).toBe(error);
            }
            else {
                expect(reason instanceof error).toBe(true);

                if (message) {
                    expect(reason.message).toBe(message);
                }
            }
        });
    }

    function formatValue(value) {
        var formattedValues;

        if (value instanceof ExtPromise) {
            return 'Promise';
        }

        if (value instanceof Deferred) {
            return 'Deferred';
        }

        if (value instanceof Ext.ClassManager.get('Ext.Base')) {
            return Ext.ClassManager.getName(value);
        }

        if (Ext.isArray(value)) {
            formattedValues = Ext.Array.map(value, formatValue);

            return "[" + (formattedValues.join(', ')) + "]";
        }

        if (Ext.isObject(value)) {
            return 'Object';
        }

        if (Ext.isString(value)) {
            return '"' + value + '"';
        }

        return '' + value;
    }

    describe('resolved()', function() {
        var values = [void 0, null, false, 0, 1, 'expected value', [1, 2, 3], {}, new Error('error message')];

        describe('returns a Promise that will resolve with the specified value', function() {
            Ext.each(values, function(value) {
                it(formatValue(value), function() {
                    promise = Deferred.resolved(value);

                    expect(promise instanceof ExtPromise).toBe(true);

                    eventuallyResolvesTo(promise, value);
               });
            });
        });

        describe('returns a Promise that will resolve with the resolved value for the specified Promise when it resolves', function() {
            Ext.each(values, function(value) {
                it(formatValue(value), function() {
                    var deferred = new Deferred();

                    deferred.resolve(value);

                    promise = Deferred.resolved(deferred.promise);

                    expect(promise).not.toBe(deferred.promise);
                    expect(promise instanceof ExtPromise).toBe(true);

                    eventuallyResolvesTo(promise, value);
                });
            });
        });

        describe('returns a Promise that will reject with the error associated with the specified Promise when it rejects', function() {
            it('Error: error message', function() {
                deferred = new Deferred();

                deferred.reject(new Error('error message'));

                promise = Deferred.resolved(deferred.promise);

                expect(promise).not.toBe(deferred.promise);
                expect(promise instanceof ExtPromise).toBe(true);

                eventuallyRejectedWith(promise, Error, 'error message');
            });
        });

        describe('returns a new Promise that will adapt the specified untrusted (aka third-party) then-able', function() {
            var MockThirdPartyPromise = function() {};

            MockThirdPartyPromise.prototype.then = function(successCallback, failureCallback) {
                this.successCallback = successCallback;
                this.failureCallback = failureCallback;

                switch (this.state) {
                    case 'resolved':
                        this.successCallback(this.value);
                        break;
                    case 'rejected':
                        this.failureCallback(this.value);
                }
            };

            MockThirdPartyPromise.prototype.resolve = function(value) {
                this.value = value;
                this.state = 'resolved';

                if (this.successCallback != null) {
                    this.successCallback(this.value);
                }
            };

            MockThirdPartyPromise.prototype.reject = function(value) {
                this.value = value;
                this.state = 'rejected';

                if (this.failureCallback != null) {
                    this.failureCallback(this.value);
                }
            };

            it('resolves when resolved', function() {
                var mockThirdPartyPromise = new MockThirdPartyPromise();

                mockThirdPartyPromise.resolve('expected value');

                promise = Deferred.resolved(mockThirdPartyPromise);

                expect(promise).not.toBe(mockThirdPartyPromise);
                expect(promise instanceof ExtPromise).toBe(true);

                eventuallyResolvesTo(promise, 'expected value');
            });

            it('rejects when rejected', function() {
                var mockThirdPartyPromise = new MockThirdPartyPromise();

                mockThirdPartyPromise.reject('error message');

                promise = Deferred.resolved(mockThirdPartyPromise);

                expect(promise).not.toBe(mockThirdPartyPromise);
                expect(promise instanceof ExtPromise).toBe(true);

                eventuallyRejectedWith(promise, 'error message');
            });
        });
    });

    describe('nesting promises', function() {
        it('should resolve when returning a resolved deferred', function() {
            promise = Deferred.resolved('initial').then(function(v) {
                var other = new Deferred();

                other.resolve(v + 'ok');

                return other.promise;
            });
            eventuallyResolvesTo(promise, 'initialok');
        });

        if (hasNativePromise) {
            it('should resolve when returning a resolved native', function() {
                promise = Deferred.resolved('initial').then(function(v) {
                    return Promise.resolve(v + 'ok');
                });
                eventuallyResolvesTo(promise, 'initialok');
            });
        }

        it('should reject when returning a rejected deferred', function() {
            promise = Deferred.resolved('initial').then(function(v) {
                var other = new Deferred();

                other.reject(v + 'ok');

                return other.promise;
            });
            eventuallyRejectedWith(promise, 'initialok');
        });

        if (hasNativePromise) {
            it('should resolve when returning a rejected native', function() {
                promise = Deferred.resolved('initial').then(function(v) {
                    return Promise.reject(v + 'ok');
                });
                eventuallyRejectedWith(promise, 'initialok');
            });
        }
    });

    describe('Promise.is()', function() {
        describe('returns true for a Promise or then()-able', function() {
            it('Promise', function() {
                promise = new Deferred().promise;
                expect(ExtPromise.is(promise)).toBe(true);
            });

            it('returns true for any then()-able', function() {
                promise = {
                    then: function() {}
                };

                expect(ExtPromise.is(promise)).toBe(true);
            });

            if (hasNativePromise) {
                it('returns true for a native Promise', function() {
                    var p = new Promise(function() {});

                    expect(ExtPromise.is(p)).toBe(true);
                });
            }
        });

        describe('returns false for non-promises', function() {
            var values = [void 0, null, false, 0, 1, 'value', [1, 2, 3], {}, new Error('error message')];

            Ext.each(values, function(value) {
                it(formatValue(value), function() {
                    expect(ExtPromise.is(value)).toBe(false);
                });
            });
        });
    });

    describe('all()', function() {
        describe('returns a new Promise that will resolve with the resolved values for the specified Array of Promises(s) or values.', function() {
            it('Empty Array', function() {
                var value = [];

                promise = ExtPromise.all(value);

                expect(promise instanceof ExtPromise).toBe(true);

                eventuallyResolvesTo(promise, value, true);
            });

            it('Array with one value', function() {
                var value = ['expected value'];

                promise = ExtPromise.all(value);

                expect(promise instanceof ExtPromise).toBe(true);

                eventuallyResolvesTo(promise, value, true);
            });

            it('Array of values', function() {
                var value = [1, 2, 3];

                promise = ExtPromise.all(value);

                expect(promise instanceof ExtPromise).toBe(true);

                eventuallyResolvesTo(promise, value, true);
            });

            it('Sparse Array', function() {
                var value = [, 2, , 4, 5];

                promise = ExtPromise.all(value);

                expect(promise instanceof ExtPromise).toBe(true);

                eventuallyResolvesTo(promise, value, true);
            });

            it('Array with one resolved Promise', function() {
                promise = ExtPromise.all([
                    Deferred.resolved('expected value')
                ]);

                expect(promise instanceof ExtPromise).toBe(true);

                eventuallyResolvesTo(promise, ['expected value'], true);
            });

            it('Array of resolved Promises', function() {
                promise = ExtPromise.all([
                    Deferred.resolved(1),
                    Deferred.resolved(2),
                    Deferred.resolved(3)
                ]);

                expect(promise instanceof ExtPromise).toBe(true);

                eventuallyResolvesTo(promise, [1, 2, 3], true);
            });
        });

        describe('returns a new Promise that will resolve with the resolved values for the specified resolved Promise of an Array of Promises(s) or values.', function() {
            it('Promise of an empty Array', function() {
                promise = ExtPromise.all(Deferred.resolved([]));

                expect(promise instanceof ExtPromise).toBe(true);

                eventuallyResolvesTo(promise, [], true);
            });

            it('Promise of an Array with one value', function() {
                promise = ExtPromise.all(Deferred.resolved(['expected value']));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, ['expected value'], true);
            });

            it('Promise of an Array of values', function() {
                promise = ExtPromise.all(Deferred.resolved([1, 2, 3]));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [1, 2, 3], true);
            });

            it('Promise of a sparse Array', function() {
                promise = ExtPromise.all(Deferred.resolved([, 2, , 4, 5]));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [, 2, , 4, 5], true);
            });

            it('Promise of an Array with one resolved Promise', function() {
                promise = ExtPromise.all(Deferred.resolved([Deferred.resolved('expected value')]));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, ['expected value'], true);
            });

            it('Promise of an Array of resolved Promises', function() {
                promise = ExtPromise.all(Deferred.resolved([Deferred.resolved(1), Deferred.resolved(2), Deferred.resolved(3)]));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [1, 2, 3], true);
            });
        });

        describe('returns a new Promise that will reject with the error associated with the first Promise in the specified Array of Promise(s) or value(s) that rejects', function() {
            it('Array with one rejected Promise', function() {
                promise = ExtPromise.all([Deferred.rejected(new Error('error message'))]);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Array of resolved Promises and a rejected Promise', function() {
                promise = ExtPromise.all([Deferred.resolved(1), Deferred.rejected(new Error('error message')), Deferred.resolved(3)]);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Array of values, pending and resolved Promises and a rejected Promise', function() {
                promise = ExtPromise.all([1, 2,
                    Deferred.rejected(new Error('error message')),
                    Deferred.resolved(4),
                    new Deferred().promise
                ]);

                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });
        });

        describe('returns a new Promise that will reject with the error associated with the first Promise in the specified resolved Promise of an Array of Promise(s) or value(s) that rejects', function() {
            it('Promise of an Array with one rejected Promise', function() {
                promise = ExtPromise.all(Deferred.resolved([Deferred.rejected(new Error('error message'))]));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Promise of an Array of resolved Promises and a rejected Promise', function() {
                promise = ExtPromise.all(Deferred.resolved([
                        Deferred.resolved(1),
                        Deferred.rejected(new Error('error message')),
                        Deferred.resolved(3)
                    ]));

                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Promise of an Array of values, pending and resolved Promises and a rejected Promise', function() {
                promise = ExtPromise.all(Deferred.resolved([
                    1, 2,
                    Deferred.rejected(new Error('error message')),
                    Deferred.resolved(4),
                    new Deferred().promise
                ]));

                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });
        });

        describe('returns a new Promise that will reject with the error associated with the rejected Promise of an Array of Promise(s) or value(s)', function() {
            it('Error: error message', function() {
                promise = ExtPromise.all(Deferred.rejected(new Error('error message')));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });
        });

        describe('throws an Error if anything other than Array or Promise of an Array is specified', function() {
            it('no parameters', function() {
                expect(function() {
                    return ExtPromise.all();
                }).toThrow('Invalid parameter: expected an Array or Promise of an Array.');
            });

            it('a single non-Array parameter', function() {
                expect(function() {
                    return ExtPromise.all(1);
                }).toThrow('Invalid parameter: expected an Array or Promise of an Array.');
            });

            it('multiple non-Array parameters', function() {
                expect(function() {
                    return ExtPromise.all(1, 2, 3);
                }).toThrow('Invalid parameter: expected an Array or Promise of an Array.');
            });
        });
    });

    describe('any()', function() {
        function eventuallyResolvesToOneOf(promise, values) {
            var done = false,
                result;

            promise.then(function(v) {
                result = v;
                done = true;
            });

            waitsFor(function() {
                return done;
            });

            runs(function() {
                expect(Ext.Array.indexOf(values, result)).not.toBe(-1);
            });
        }

        describe('returns a new Promise that will resolve once any one of the specified Array of Promises(s) or values have resolved.', function() {
            it('Array with one value', function() {
                promise = Deferred.any(['expected value']);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 'expected value', true);
            });

            it('Array of values', function() {
                promise = Deferred.any([1, 2, 3]);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesToOneOf(promise, [1, 2, 3]);
            });

            it('Sparse Array', function() {
                promise = Deferred.any([, 2, , 4, 5]);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesToOneOf(promise, [2, 4, 5]);
            });

            it('Array with one resolved Promise', function() {
                promise = Deferred.any([Deferred.resolved('expected value')]);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 'expected value', true);
            });

            it('Array of resolved Promises', function() {
                promise = Deferred.any([Deferred.resolved(1), Deferred.resolved(2), Deferred.resolved(3)]);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesToOneOf(promise, [1, 2, 3]);
            });

            it('Array of rejected Promises and one resolved Promise', function() {
                promise = Deferred.any([Deferred.rejected('error message'), Deferred.resolved('expected value'), Deferred.rejected('error message')]);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 'expected value', true);
            });

            it('Array of pending and rejected Promises and one resolved Promise', function() {
                promise = Deferred.any([new Deferred().promise, Deferred.resolved('expected value'), Deferred.rejected('error message')]);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 'expected value', true);
            });

            it('Array of pending and rejected Promises and multiple resolved Promises', function() {
                promise = Deferred.any([new Deferred().promise, Deferred.resolved(1), Deferred.rejected('error message'), Deferred.resolved(2)]);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesToOneOf(promise, [1, 2]);
            });
        });

        describe('returns a new Promise that will resolve once any one of the specified resolved Promise of an Array of Promises(s) or values have resolved.', function() {
            it('Promise of an Array with one value', function() {
                promise = Deferred.any(Deferred.resolved(['expected value']));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 'expected value', true);
            });

            it('Promise of an Array of values', function() {
                promise = Deferred.any(Deferred.resolved([1, 2, 3]));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesToOneOf(promise, [1, 2, 3]);
            });

            it('Promise of a sparse Array', function() {
                promise = Deferred.any(Deferred.resolved([, 2, , 4, 5]));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesToOneOf(promise, [2, 4, 5]);
            });

            it('Promise of an Array with one resolved Promise', function() {
                promise = Deferred.any(Deferred.resolved([Deferred.resolved('expected value')]));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 'expected value', true);
            });

            it('Promise of an Array of resolved Promise', function() {
                promise = Deferred.any(Deferred.resolved([Deferred.resolved(1), Deferred.resolved(2), Deferred.resolved(3)]));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesToOneOf(promise, [1, 2, 3]);
            });

            it('Promise of an Array of rejected Promises and one resolved Promise', function() {
                promise = Deferred.any(Deferred.resolved([Deferred.rejected('error message'), Deferred.resolved('expected value'), Deferred.rejected('error message')]));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 'expected value', true);
            });

            it('Promise of an Array of pending and rejected Promises and one resolved Promise', function() {
                promise = Deferred.any(Deferred.resolved([new Deferred().promise, Deferred.resolved('expected value'), Deferred.rejected('error message')]));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 'expected value', true);
            });

            it('Promise of an Array of pending and rejected Promises and multiple resolved Promises', function() {
                promise = Deferred.any(Deferred.resolved([new Deferred().promise, Deferred.resolved(1), Deferred.rejected('error message'), Deferred.resolved(2)]));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesToOneOf(promise, [1, 2]);
            });
        });

        describe('returns a new Promise that will reject if none of the specified Array of Promises(s) or values resolves.', function() {
            it('Empty Array', function() {
                promise = Deferred.any([]);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'No Promises were resolved.');
            });

            it('Array with one rejected Promise', function() {
                promise = Deferred.any([Deferred.rejected('error message')]);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'No Promises were resolved.');
            });

            it('Array of rejected Promises', function() {
                promise = Deferred.any([Deferred.rejected('error message'), Deferred.rejected('error message'), Deferred.rejected('error message')]);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'No Promises were resolved.');
            });
        });

        describe('returns a new Promise that will reject if none of the specified resolved Promise of an Array of Promises(s) or values resolves.', function() {
            it('Promise of an empty Array', function() {
                promise = Deferred.any(Deferred.resolved([]));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'No Promises were resolved.');
            });

            it('Promise of an Array with one rejected Promise', function() {
                promise = Deferred.any(Deferred.resolved([Deferred.rejected('error message')]));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'No Promises were resolved.');
            });

            it('Promise of an Array of rejected Promises', function() {
                promise = Deferred.any(Deferred.resolved([Deferred.rejected('error message'), Deferred.rejected('error message'), Deferred.rejected('error message')]));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'No Promises were resolved.');
            });
        });

        describe('returns a new Promise that will reject with the error associated with the rejected Promise of an Array of Promise(s) or value(s)', function() {
            it('Error: error message', function() {
                promise = Deferred.any(Deferred.rejected(new Error('error message')));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });
        });

        describe('throws an Error if anything other than Array or Promise of an Array is specified', function() {
            it('no parameters', function() {
                expect(function() {
                    return Deferred.any();
                }).toThrow('Invalid parameter: expected an Array or Promise of an Array.');
            });

            it('a single non-Array parameter', function() {
                expect(function() {
                    return Deferred.any(1);
                }).toThrow('Invalid parameter: expected an Array or Promise of an Array.');
            });

            it('multiple non-Array parameters', function() {
                expect(function() {
                    return Deferred.any(1, 2, 3);
                }).toThrow('Invalid parameter: expected an Array or Promise of an Array.');
            });
        });
    });

    describe('some()', function() {
        function eventuallyResolvesToSomeOf(promise, length, values) {
            var done = false,
                result;

            promise.then(function(v) {
                result = v;
                done = true;
            });

            waitsFor(function() {
                return done;
            });

            runs(function() {
                expect(result.length).toBe(length);

                var map = {};

                for (var i = 0; i < result.length; ++i) {
                    var index = Ext.Array.indexOf(values, result[i]);

                    expect(index).not.toBe(-1);
                    expect(map[index]).not.toBe(true);
                    map[index] = true;
                }
            });
        }

        describe('returns a new Promise that will resolve once the specified number of the specified Array of Promises(s) or values have resolved.', function() {
            it('Array with one value', function() {
                promise = Deferred.some(['expected value'], 1);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, ['expected value'], true);
            });

            it('Array of values', function() {
                promise = Deferred.some([1, 2, 3], 2);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesToSomeOf(promise, 2, [1, 2, 3]);
            });

            it('Sparse Array', function() {
                promise = Deferred.some([, 2, , 4, 5], 2);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesToSomeOf(promise, 2, [2, 4, 5]);
            });

            it('Array with one resolved Promise', function() {
                promise = Deferred.some([Deferred.resolved('expected value')], 1);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, ['expected value'], true);
            });

            it('Array of resolved Promises', function() {
                promise = Deferred.some([Deferred.resolved(1), Deferred.resolved(2), Deferred.resolved(3)], 2);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesToSomeOf(promise, 2, [1, 2, 3]);
            });

            it('Array of rejected Promises and one resolved Promise', function() {
                promise = Deferred.some([Deferred.rejected('error message'), Deferred.resolved('expected value'), Deferred.rejected('error message')], 1);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, ['expected value'], true);
            });

            it('Array of pending and rejected Promises and one resolved Promise', function() {
                promise = Deferred.some([new Deferred().promise, Deferred.resolved('expected value'), Deferred.rejected('error message')], 1);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, ['expected value'], true);
            });

            it('Array of rejected Promises and multiple resolved Promises', function() {
                promise = Deferred.some([Deferred.rejected('error message'), Deferred.resolved(1), Deferred.rejected('error message'), Deferred.resolved(2)], 2);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesToSomeOf(promise, 2, [1, 2]);
            });

            it('Array of pending and rejected Promises and multiple resolved Promises', function() {
                promise = Deferred.some([new Deferred().promise, Deferred.resolved(1), Deferred.rejected('error message'), Deferred.resolved(2)], 2);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesToSomeOf(promise, 2, [1, 2]);
            });
        });

        describe('returns a new Promise that will resolve once the specified number of the specified resolved Promise of an Array of Promises(s) or values have resolved.', function() {
            it('Promise of an Array with one value', function() {
                promise = Deferred.some(Deferred.resolved(['expected value']), 1);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, ['expected value'], true);
            });

            it('Promise of an Array of values', function() {
                promise = Deferred.some(Deferred.resolved([1, 2, 3]), 2);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesToSomeOf(promise, 2, [1, 2, 3]);
            });

            it('Promise of a sparse Array', function() {
                promise = Deferred.some(Deferred.resolved([, 2, , 4, 5]), 2);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesToSomeOf(promise, 2, [2, 4, 5]);
            });

            it('Promise of an Array with one resolved Promise', function() {
                promise = Deferred.some(Deferred.resolved([Deferred.resolved('expected value')]), 1);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, ['expected value'], true);
            });

            it('Promise of an Array of resolved Promises', function() {
                promise = Deferred.some(Deferred.resolved([Deferred.resolved(1), Deferred.resolved(2), Deferred.resolved(3)]), 2);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesToSomeOf(promise, 2, [1, 2, 3]);
            });

            it('Promise of an Array of rejected Promises and one resolved Promise', function() {
                promise = Deferred.some(Deferred.resolved([Deferred.rejected('error message'), Deferred.resolved('expected value'), Deferred.rejected('error message')]), 1);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, ['expected value'], true);
            });

            it('Promise of an Array of pending and rejected Promises and one resolved Promise', function() {
                promise = Deferred.some(Deferred.resolved([new Deferred().promise, Deferred.resolved('expected value'), Deferred.rejected('error message')]), 1);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, ['expected value'], true);
            });

            it('Promise of an Array of rejected Promises and multiple resolved Promises', function() {
                promise = Deferred.some(Deferred.resolved([Deferred.rejected('error message'), Deferred.resolved(1), Deferred.rejected('error message'), Deferred.resolved(2)]), 2);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesToSomeOf(promise, 2, [1, 2]);
            });

            it('Promise of an Array of pending and rejected Promises and multiple resolved Promises', function() {
                promise = Deferred.some(Deferred.resolved([new Deferred().promise, Deferred.resolved(1), Deferred.rejected('error message'), Deferred.resolved(2)]), 2);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesToSomeOf(promise, 2, [1, 2]);
            });
        });

        describe('returns a new Promise that will reject if too few of the specified Array of Promises(s) or values resolves.', function() {
            it('Empty Array with one resolved value requested', function() {
                promise = Deferred.some([], 1);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'Too few Promises were resolved.');
            });

            it('Empty Array with multiple resolved values requested', function() {
                promise = Deferred.some([], 2);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'Too few Promises were resolved.');
            });

            it('Array with one rejected Promise with one resolved value requested', function() {
                promise = Deferred.some([Deferred.rejected('error message')], 1);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'Too few Promises were resolved.');
            });

            it('Array with one rejected Promise with multiple resolved values requested', function() {
                promise = Deferred.some([Deferred.rejected('error message')], 2);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'Too few Promises were resolved.');
            });

            it('Array of rejected Promises with one resolved value requested', function() {
                promise = Deferred.some([Deferred.rejected('error message'), Deferred.rejected('error message'), Deferred.rejected('error message')], 1);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'Too few Promises were resolved.');
            });

            it('Array of rejected Promises with multiple resolved values requested', function() {
                promise = Deferred.some([Deferred.rejected('error message'), Deferred.rejected('error message'), Deferred.rejected('error message')], 2);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'Too few Promises were resolved.');
            });
        });

        describe('returns a new Promise that will reject if too few of the specified resolved Promise of an Array of Promises(s) or values resolves.', function() {
            it('Promise of an empty Array with one resolved value requested', function() {
                promise = Deferred.some(Deferred.resolved([]), 1);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'Too few Promises were resolved.');
            });

            it('Promise of an empty Array with multiple resolved values requested', function() {
                promise = Deferred.some(Deferred.resolved([]), 2);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'Too few Promises were resolved.');
            });

            it('Promise of an Array with one rejected Promise with one resolved value requested', function() {
                promise = Deferred.some(Deferred.resolved([Deferred.rejected('error message')]), 1);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'Too few Promises were resolved.');
            });

            it('Promise of an Array with one rejected Promise with multiple resolved values requested', function() {
                promise = Deferred.some(Deferred.resolved([Deferred.rejected('error message')]), 2);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'Too few Promises were resolved.');
            });

            it('Promise of an Array of rejected Promises with one resolved value requested', function() {
                promise = Deferred.some(Deferred.resolved([Deferred.rejected('error message'), Deferred.rejected('error message'), Deferred.rejected('error message')]), 1);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'Too few Promises were resolved.');
            });

            it('Promise of an Array of rejected Promises with multiple resolved values requested', function() {
                promise = Deferred.some(Deferred.resolved([Deferred.rejected('error message'), Deferred.rejected('error message'), Deferred.rejected('error message')]), 2);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'Too few Promises were resolved.');
            });
        });

        describe('returns a new Promise that will reject with the error associated with the rejected Promise of an Array of Promise(s) or value(s)', function() {
            it('Error: error message', function() {
                promise = Deferred.some(Deferred.rejected(new Error('error message')), 2);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });
        });

        describe('throws an Error if anything other than Array or Promise of an Array is specified', function() {
            it('no parameters', function() {
                expect(function() {
                    return Deferred.some();
                }).toThrow('Invalid parameter: expected an Array or Promise of an Array.');
            });

            it('a single non-Array parameter', function() {
                expect(function() {
                    return Deferred.some(1);
                }).toThrow('Invalid parameter: expected an Array or Promise of an Array.');
            });

            it('multiple non-Array parameters', function() {
                expect(function() {
                    return Deferred.some(1, 2, 3);
                }).toThrow('Invalid parameter: expected an Array or Promise of an Array.');
            });

            it('a single Array parameter', function() {
                expect(function() {
                    return Deferred.some([1, 2, 3]);
                }).toThrow('Invalid parameter: expected a positive integer.');
            });

            it('a single Array parameter and a non-numeric value', function() {
                expect(function() {
                    return Deferred.some([1, 2, 3], 'value');
                }).toThrow('Invalid parameter: expected a positive integer.');
            });
        });
    }); // some

    describe('delay()', function() {
        // We have to be careful testing timing due to load during test runs on the
        // build system. We basically ensure that delays are at least the specified
        // amount but also allow for sloppy IE timers (+/- 16ms).
        describe('returns a new Promise that will resolve after the specified delay', function() {
            it('0 ms delay', function() {
                promise = Deferred.delay(0);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, void 0, true);
            });

            it('value with 100 ms delay', function() {
                promise = Deferred.delay(100);
                var start = Ext.now();

                expect(promise instanceof ExtPromise).toBe(true);

                promise = promise.then(function(value) {
                    expect(Ext.now() - start).toBeGE(84);

                    return value;
                });

                eventuallyResolvesTo(promise, void 0, true);
            });
        });

        describe('returns a new Promise that will resolve with the specified Promise or value after the specified delay', function() {
            it('value with 0 ms delay', function() {
                promise = Deferred.delay('expected value', 0);
                expect(promise instanceof ExtPromise).toBe(true);

                eventuallyResolvesTo(promise, 'expected value', true);
            });

            it('resolved Promise with 0 delay', function() {
                promise = Deferred.delay(Deferred.resolved('expected value'), 0);
                expect(promise instanceof ExtPromise).toBe(true);

                eventuallyResolvesTo(promise, 'expected value', true);
            });

            it('value with 100 ms delay', function() {
                promise = Deferred.delay('expected value', 100);

                var start = Ext.now();

                expect(promise instanceof ExtPromise).toBe(true);

                promise = promise.then(function(value) {
                    expect(Ext.now() - start).toBeGE(84);

                    return value;
                });

                eventuallyResolvesTo(promise, 'expected value', true);
            });

            it('resolved Promise with 100 ms delay', function() {
                promise = Deferred.delay(Deferred.resolved('expected value'), 100);

                var start = Ext.now();

                expect(promise instanceof ExtPromise).toBe(true);

                promise = promise.then(function(value) {
                    expect(Ext.now() - start).toBeGE(84);

                    return value;
                });

                eventuallyResolvesTo(promise, 'expected value', true);
            });
        });

        describe('returns a new Promise that will reject with the error associated with the specified rejected Promise after the specified delay', function() {
            it('rejected Promise with 100 ms delay', function() {
                promise = Deferred.delay(Deferred.rejected(new Error('error message')), 100);

                var start = Ext.now();

                expect(promise instanceof ExtPromise).toBe(true);

                promise = promise.then(function(value) {
                    return value;
                }, function(error) {
                    expect(Ext.now() - start).toBeGE(84);
                    throw error;
                });

                eventuallyRejectedWith(promise, Error, 'error message');
            });
        });
    }); // delay

    describe('timeout()', function() {
        describe('returns a new Promise that will resolve with the specified Promise or value if it resolves before the specified timeout', function() {
            it('value with 100 ms timeout', function() {
                promise = Deferred.timeout('expected value', 100);

                expect(promise instanceof ExtPromise).toBe(true);

                eventuallyResolvesTo(promise, 'expected value', true);
            });

            it('Promise that resolves in 50 ms with a 100 ms timeout', function() {
                promise = Deferred.timeout(Deferred.delay('expected value', 50), 100);

                expect(promise instanceof ExtPromise).toBe(true);

                eventuallyResolvesTo(promise, 'expected value', true);
            });
        });

        describe('returns a new Promise that will reject with the error associated with the specified rejected Promise if it rejects before the specified timeout', function() {
            it('Promise that rejects in 50 ms with a 100 ms timeout', function() {
                promise = Deferred.timeout(Deferred.delay(Deferred.rejected(new Error('error message')), 50), 100);

                expect(promise instanceof ExtPromise).toBe(true);

                eventuallyRejectedWith(promise, Error, 'error message');
            });
        });

        describe('returns a new Promise that will reject after the specified timeout if the specified Promise or value has not yet resolved or rejected', function() {
            var delayedPromise;

            afterEach(function() {
                Ext.undefer(delayedPromise.owner.timeoutId);
            });

            it('Promise that resolves in 100 ms with a 50 ms timeout', function() {
                delayedPromise = Deferred.delay('expected value', 100);
                promise = Deferred.timeout(delayedPromise, 50);

                expect(promise instanceof ExtPromise).toBe(true);

                eventuallyRejectedWith(promise, Error, 'Promise timed out.');
            });

            it('Promise that rejects in 50 ms with a 100 ms timeout', function() {
                delayedPromise = Deferred.delay(Deferred.rejected(new Error('error message')), 100);
                promise = Deferred.timeout(delayedPromise, 50);

                expect(promise instanceof ExtPromise).toBe(true);

                eventuallyRejectedWith(promise, Error, 'Promise timed out.');
            });
        });
    }); // timeout

    describe('memoize()', function() {
        function fib(n) {
            return (n < 2) ? n : fib(n - 1) + fib(n - 2);
        }

        function fibonacci(n) {
            ++fibonacci.calls;
            fibonacci.scope = this;

            return fib(n);
        }

        beforeEach(function() {
            fibonacci.calls = 0;
            delete fibonacci.scope;
        });

        describe('returns a new function that wraps the specified function, caching results for previously processed inputs, and returns a Promise that will resolve with the result value', function() {
            it('value', function() {
                var memoFn = Deferred.memoize(fibonacci);

                promise = ExtPromise.all([memoFn(12), memoFn(12)]).then(function(value) {
                    expect(fibonacci.calls).toBe(1);

                    return value;
                }, function(error) {
                    throw error;
                });

                eventuallyResolvesTo(promise, [fib(12), fib(12)], true);
            });

            it('resolved Promise', function() {
                var memoFn = Deferred.memoize(fibonacci);

                promise = ExtPromise.all([memoFn(Deferred.resolved(12)), memoFn(Deferred.resolved(12))]).then(function(value) {
                    expect(fibonacci.calls).toBe(1);

                    return value;
                }, function(error) {
                    throw error;
                });

                eventuallyResolvesTo(promise, [fib(12), fib(12)], true);
            });
        });

        describe('executes the wrapped function in the optionally specified scope', function() {
            it('optional scope omitted', function() {
                var memoFn = Deferred.memoize(fibonacci);

                promise = memoFn(12).then(function(value) {
                    expect(fibonacci.calls).toBe(1);

                    // eslint-disable-next-line eqeqeq
                    expect(fibonacci.scope == window).toBe(true); // IE needs == not ===

                    return value;
                }, function(error) {
                    throw error;
                });

                eventuallyResolvesTo(promise, fib(12), true);
            });

            it('scope specified', function() {
                var memoFn = Deferred.memoize(fibonacci, targetScope);

                promise = memoFn(12).then(function(value) {
                    expect(fibonacci.calls).toBe(1);
                    expect(fibonacci.scope).toBe(targetScope);

                    return value;
                }, function(error) {
                    throw error;
                });

                eventuallyResolvesTo(promise, fib(12), true);
            });
        });

        describe('returns a new function that wraps the specified function and returns a Promise that will reject with the associated error when the wrapper function is called with a rejected Promise', function() {
            it('rejected Promise', function() {
                var memoFn = Deferred.memoize(fibonacci);

                promise = memoFn(Deferred.rejected(new Error('error message')));

                eventuallyRejectedWith(promise, Error, 'error message');
            });
        });
    }); // memoize

    describe('map()', function() {
        function doubleFunction(value, index, array) {
            expect(arguments.length).toBe(3);
            expect(array instanceof Array).toBe(true);
            expect(index).toBeGE(0);
            expect(index).toBeLT(array.length);

            return value * 2;
        }

        function doublePromiseFunction(value, index, array) {
            expect(arguments.length).toBe(3);
            expect(array instanceof Array).toBe(true);
            expect(index).toBeGE(0);
            expect(index).toBeLT(array.length);

            return Deferred.resolved(value * 2);
        }

        function rejectFunction(value, index, array) {
            expect(arguments.length).toBe(3);
            expect(array instanceof Array).toBe(true);
            expect(index).toBeGE(0);
            expect(index).toBeLT(array.length);

            return Deferred.rejected(new Error('error message'));
        }

        describe('returns a new Promise that will resolve with an Array of the mapped values for the specified Array of Promise(s) or value(s)', function() {
            it('Empty Array', function() {
                promise = Deferred.map([], doubleFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [], true);
            });

            it('Array with one value', function() {
                promise = Deferred.map([1], doubleFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [2], true);
            });

            it('Array of values', function() {
                promise = Deferred.map([1, 2, 3], doubleFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [2, 4, 6], true);
            });

            it('Sparse Array', function() {
                promise = Deferred.map([, 2, , 4, 5], doubleFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [, 4, , 8, 10], true);
            });

            it('Array with one resolved Promise', function() {
                promise = Deferred.map([Deferred.resolved(1)], doubleFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [2], true);
            });

            it('Array of resolved Promises', function() {
                promise = Deferred.map([Deferred.resolved(1), Deferred.resolved(2), Deferred.resolved(3)], doubleFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [2, 4, 6], true);
            });

            it('Array of values and resolved Promises', function() {
                promise = Deferred.map([1, Deferred.resolved(2), Deferred.resolved(3), 4], doubleFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [2, 4, 6, 8], true);
            });
        });

        describe('returns a new Promise that will resolve with an Array of the mapped values for the specified resolved Promise of an Array of Promise(s) or value(s)', function() {
            it('Promise of an empty Array', function() {
                promise = Deferred.map(Deferred.resolved([]), doubleFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [], true);
            });

            it('Promise of an Array with one value', function() {
                promise = Deferred.map(Deferred.resolved([1]), doubleFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [2], true);
            });

            it('Promise of an Array of values', function() {
                promise = Deferred.map(Deferred.resolved([1, 2, 3]), doubleFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [2, 4, 6], true);
            });

            it('Promise of a sparse Array', function() {
                promise = Deferred.map(Deferred.resolved([, 2, , 4, 5]), doubleFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [, 4, , 8, 10], true);
            });

            it('Promise of an Array with one resolved Promise', function() {
                promise = Deferred.map(Deferred.resolved([Deferred.resolved(1)]), doubleFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [2], true);
            });

            it('Promise of an Array of resolved Promises', function() {
                promise = Deferred.map(Deferred.resolved([Deferred.resolved(1), Deferred.resolved(2), Deferred.resolved(3)]), doubleFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [2, 4, 6], true);
            });

            it('Promise of an Array of values and resolved Promises', function() {
                promise = Deferred.map(Deferred.resolved([1, Deferred.resolved(2), Deferred.resolved(3), 4]), doubleFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [2, 4, 6, 8], true);
            });
        });

        describe('returns a new Promise that will resolve with an Array of the resolved mapped Promises values for the specified Array of Promise(s) or value(s)', function() {
            it('Empty Array', function() {
                promise = Deferred.map([], doublePromiseFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [], true);
            });

            it('Array with one value', function() {
                promise = Deferred.map([1], doublePromiseFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [2], true);
            });

            it('Array of values', function() {
                promise = Deferred.map([1, 2, 3], doublePromiseFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [2, 4, 6], true);
            });

            it('Sparse Array', function() {
                promise = Deferred.map([, 2, , 4, 5], doublePromiseFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [, 4, , 8, 10], true);
            });

            it('Array with one resolved Promise', function() {
                promise = Deferred.map([Deferred.resolved(1)], doublePromiseFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [2], true);
            });

            it('Array of resolved Promises', function() {
                promise = Deferred.map([Deferred.resolved(1), Deferred.resolved(2), Deferred.resolved(3)], doublePromiseFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [2, 4, 6], true);
            });

            it('Array of values and resolved Promises', function() {
                promise = Deferred.map([1, Deferred.resolved(2), Deferred.resolved(3), 4], doublePromiseFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [2, 4, 6, 8], true);
            });
        });

        describe('returns a new Promise that will resolve with an Array of the resolved mapped Promises values for the specified resolved Promise of an Array of Promise(s) or value(s)', function() {
            it('Promise of an empty Array', function() {
                promise = Deferred.map(Deferred.resolved([]), doublePromiseFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [], true);
            });

            it('Promise of an Array with one value', function() {
                promise = Deferred.map(Deferred.resolved([1]), doublePromiseFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [2], true);
            });

            it('Promise of an Array of values', function() {
                promise = Deferred.map(Deferred.resolved([1, 2, 3]), doublePromiseFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [2, 4, 6], true);
            });

            it('Promise of a sparse Array', function() {
                promise = Deferred.map(Deferred.resolved([, 2, , 4, 5]), doublePromiseFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [, 4, , 8, 10], true);
            });

            it('Promise of an Array with one resolved Promise', function() {
                promise = Deferred.map(Deferred.resolved([Deferred.resolved(1)]), doublePromiseFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [2], true);
            });

            it('Promise of an Array of resolved Promises', function() {
                promise = Deferred.map(Deferred.resolved([Deferred.resolved(1), Deferred.resolved(2), Deferred.resolved(3)]), doublePromiseFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [2, 4, 6], true);
            });

            it('Promise of an Array of values and resolved Promises', function() {
                promise = Deferred.map(Deferred.resolved([1, Deferred.resolved(2), Deferred.resolved(3), 4]), doublePromiseFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, [2, 4, 6, 8], true);
            });
        });

        describe('returns a new Promise that will reject with the error associated with the first Promise in the specified Array of Promise(s) or value(s) that rejects', function() {
            it('Array with one rejected Promise', function() {
                promise = Deferred.map([Deferred.rejected(new Error('error message'))], doubleFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Array of values and a rejected Promise', function() {
                promise = Deferred.map([1, Deferred.rejected(new Error('error message')), 3], doubleFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Array of resolved Promises and a rejected Promise', function() {
                promise = Deferred.map([Deferred.resolved(1), Deferred.rejected(new Error('error message')), Deferred.resolved(3)], doubleFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Array of values, pending and resolved Promises and a rejected Promise', function() {
                promise = Deferred.map([1, 2, Deferred.rejected(new Error('error message')), Deferred.resolved(4), new Deferred().promise], doubleFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });
        });

        describe('returns a new Promise that will reject with the error associated with the first Promise in the specified resolved Promise of an Array of Promise(s) or value(s) that rejects', function() {
            it('Promise of an Array with one rejected Promise', function() {
                promise = Deferred.map(Deferred.resolved([Deferred.rejected(new Error('error message'))]), doubleFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Promise of an Array of values and a rejected Promise', function() {
                promise = Deferred.map(Deferred.resolved([1, Deferred.rejected(new Error('error message')), 3]), doubleFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Promise of an Array of resolved Promises and a rejected Promise', function() {
                promise = Deferred.map(Deferred.resolved([Deferred.resolved(1), Deferred.rejected(new Error('error message')), Deferred.resolved(3)]), doubleFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Promise of an Array of values, pending and resolved Promises and a rejected Promise', function() {
                promise = Deferred.map(Deferred.resolved([1, 2, Deferred.rejected(new Error('error message')), Deferred.resolved(4), new Deferred().promise]), doubleFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });
        });

        describe('returns a new Promise that will reject with the error associated with the first mapped Promise value in the specified Array of Promise(s) or value(s) that rejects', function() {
            it('Array with one value', function() {
                promise = Deferred.map([1], rejectFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Array of values', function() {
                promise = Deferred.map([1, 2, 3], rejectFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Sparse Array', function() {
                promise = Deferred.map([, 2, , 4, 5], rejectFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Array with one resolved Promise', function() {
                promise = Deferred.map([Deferred.resolved(1)], rejectFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Array of resolved Promises', function() {
                promise = Deferred.map([Deferred.resolved(1), Deferred.resolved(2), Deferred.resolved(3)], rejectFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Array of values and resolved Promises', function() {
                promise = Deferred.map([1, Deferred.resolved(2), Deferred.resolved(3), 4], rejectFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });
        });

        describe('returns a new Promise that will reject with the error associated with the first mapped Promise value in the specified resolved Promise of an Array of Promise(s) or value(s) that rejects', function() {
            it('Promise of an Array with one value', function() {
                promise = Deferred.map(Deferred.resolved([1]), rejectFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Promise of an Array of values', function() {
                promise = Deferred.map(Deferred.resolved([1, 2, 3]), rejectFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Promise of a sparse Array', function() {
                promise = Deferred.map(Deferred.resolved([, 2, , 4, 5]), rejectFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Promise of an Array with one resolved Promise', function() {
                promise = Deferred.map(Deferred.resolved([Deferred.resolved(1)]), rejectFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Promise of an Array of resolved Promises', function() {
                promise = Deferred.map(Deferred.resolved([Deferred.resolved(1), Deferred.resolved(2), Deferred.resolved(3)]), rejectFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Promise of an Array of values and resolved Promises', function() {
                promise = Deferred.map(Deferred.resolved([1, Deferred.resolved(2), Deferred.resolved(3), 4]), rejectFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });
        });

        describe('returns a new Promise that will reject with the error associated with the rejected Promise of an Array of Promise(s) or value(s)', function() {
            it('Error: error message', function() {
                promise = Deferred.map(Deferred.rejected(new Error('error message')), doubleFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });
        });

        describe('throws an Error if anything other than an Array or Promise of an Array and a function are specified', function() {
            it('no parameters', function() {
                expect(function() {
                    return Deferred.map();
                }).toThrow('Invalid parameter: expected an Array or Promise of an Array.');
            });

            it('a single non-Array parameter', function() {
                expect(function() {
                    return Deferred.map(1);
                }).toThrow('Invalid parameter: expected an Array or Promise of an Array.');
            });

            it('multiple non-Array parameters', function() {
                expect(function() {
                    return Deferred.map(1, 2, 3);
                }).toThrow('Invalid parameter: expected an Array or Promise of an Array.');
            });

            it('an Array and no function', function() {
                expect(function() {
                    return Deferred.map([1, 2, 3]);
                }).toThrow('Invalid parameter: expected a function.');
            });

            it('a Promise of an Array and no function', function() {
                expect(function() {
                    return Deferred.map(Deferred.resolved([1, 2, 3]));
                }).toThrow('Invalid parameter: expected a function.');
            });

            it('an Array and a non-function parameter', function() {
                expect(function() {
                    return Deferred.map([1, 2, 3], 'not a function');
                }).toThrow('Invalid parameter: expected a function.');
            });

            it('a Promise of a non-function parameter', function() {
                expect(function() {
                    return Deferred.map(Deferred.resolved([1, 2, 3], 'not a function'));
                }).toThrow('Invalid parameter: expected a function.');
            });
        });
    }); // map

    describe('race', function() {
        var promises;

        beforeEach(function() {
            promises = [];
        });

        afterEach(function() {
            for (var i = 0; i < promises.length; i++) {
                Ext.undefer(promises[i].owner.timeoutId);
            }

            promises = null;
        });

        function makeTimeoutPromise(timeout, value, isResolve) {
            var deferred = new Deferred();

            deferred.timeoutId = Ext.defer(function() {
                Ext.undefer(deferred.timeoutId);

                if (isResolve) {
                    deferred.resolve(value);
                }
                else {
                    deferred.reject(value);
                }
            }, timeout);

            promises.push(deferred.promise);

            return deferred.promise;
        }

        function makeResolve(timeout, value) {
            return makeTimeoutPromise(timeout, value, true);
        }

        function makeReject(timeout, reason) {
            return makeTimeoutPromise(timeout, reason, false);
        }

        describe('empty array', function() {
            it('should never resolve/reject', function() {
                var spy = jasmine.createSpy();

                promise = Deferred.race([]).then(spy, spy);
                waits(100);
                runs(function() {
                    expect(spy).not.toHaveBeenCalled();
                });
            });
        });

        describe('resolved only', function() {
            it('should resolve a single promise', function() {
                promise = Deferred.race([makeResolve(1, 'foo')]);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 'foo');
            });

            it('should resolve the first promise to resolve', function() {
                promise = Deferred.race([
                    makeResolve(200, 'foo'),
                    makeResolve(100, 'bar'),
                    makeResolve(300, 'baz')
                ]);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 'bar');
            });
        });

        describe('rejected only', function() {
            it('should reject a single promise', function() {
                promise = Deferred.race([makeReject(1, 'foo')]);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, 'foo');
            });

            it('should reject the first promise to reject', function() {
                promise = Deferred.race([
                    makeReject(200, 'foo'),
                    makeReject(100, 'bar'),
                    makeReject(300, 'baz')
                ]);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, 'bar');
            });
        });

        describe('mixed', function() {
            it('should resolve the first promise', function() {
                promise = Deferred.race([
                    makeResolve(200, 'a'),
                    makeReject(200, 'b'),
                    makeResolve(100, 'c'),
                    makeReject(150, 'd')
                ]);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 'c');
            });

            it('should reject the first promise', function() {
                promise = Deferred.race([
                    makeReject(200, 'a'),
                    makeResolve(200, 'b'),
                    makeReject(100, 'c'),
                    makeResolve(150, 'd')
                ]);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, 'c');
            });
        });

        describe('throws an Error if anything other than an Array or Promise of an Array and a function are specified', function() {
            it('a single non-Array parameter', function() {
                expect(function() {
                    return Deferred.race(1);
                }).toThrow('Invalid parameter: expected an Array.');
            });
        });
    });

    describe('reduce()', function() {
        function sumFunction(previousValue, currentValue, index, array) {
            expect(arguments.length).toBe(4);
            expect(array instanceof Array).toBe(true);
            expect(index).toBeGE(0);
            expect(index).toBeLT(array.length);

            return previousValue + currentValue;
        }

        function sumPromiseFunction(previousValue, currentValue, index, array) {
            expect(arguments.length).toBe(4);
            expect(array instanceof Array).toBe(true);
            expect(index).toBeGE(0);
            expect(index).toBeLT(array.length);

            return Deferred.resolved(previousValue + currentValue);
        }

        function rejectFunction(previousValue, currentValue, index, array) {
            expect(arguments.length).toBe(4);
            expect(array instanceof Array).toBe(true);
            expect(index).toBeGE(0);
            expect(index).toBeLT(array.length);

            return Deferred.rejected(new Error('error message'));
        }

        describe('returns a Promise that will resolve with the value obtained by reducing the specified Array of Promise(s) or value(s) using the specified function and initial value', function() {
            it('Empty Array and an initial value', function() {
                promise = Deferred.reduce([], sumFunction, 0);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 0, true);
            });

            it('Empty Array and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce([], sumFunction, Deferred.resolved(0));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 0, true);
            });

            it('Array with one value', function() {
                promise = Deferred.reduce([1], sumFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 1, true);
            });

            it('Array with one value and an initial value', function() {
                promise = Deferred.reduce([1], sumFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 11, true);
            });

            it('Array with one value and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce([1], sumFunction, Deferred.resolved(10));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 11, true);
            });

            it('Array of values', function() {
                promise = Deferred.reduce([1, 2, 3, 4], sumFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 10, true);
            });

            it('Array of values and an initial value', function() {
                promise = Deferred.reduce([1, 2, 3, 4], sumFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 20, true);
            });

            it('Array of values and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce([1, 2, 3, 4], sumFunction, Deferred.resolved(10));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 20, true);
            });

            it('Sparse Array', function() {
                promise = Deferred.reduce([, 2, , 4, 5], sumFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 11, true);
            });

            it('Sparse Array and an initial value', function() {
                promise = Deferred.reduce([, 2, , 4, 5], sumFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 21, true);
            });

            it('Sparse Array and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce([, 2, , 4, 5], sumFunction, Deferred.resolved(10));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 21, true);
            });

            it('Array with one resolved Promise', function() {
                promise = Deferred.reduce([Deferred.resolved(1)], sumFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 1, true);
            });

            it('Array with one resolved Promise and an initial value', function() {
                promise = Deferred.reduce([Deferred.resolved(1)], sumFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 11, true);
            });

            it('Array with one resolved Promise and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce([Deferred.resolved(1)], sumFunction, Deferred.resolved(10));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 11, true);
            });

            it('Array of resolved Promises', function() {
                promise = Deferred.reduce([Deferred.resolved(1), Deferred.resolved(2), Deferred.resolved(3)], sumFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 6, true);
            });

            it('Array of resolved Promises and an initial value', function() {
                promise = Deferred.reduce([Deferred.resolved(1), Deferred.resolved(2), Deferred.resolved(3)], sumFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 16, true);
            });

            it('Array of resolved Promises and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce([Deferred.resolved(1), Deferred.resolved(2), Deferred.resolved(3)], sumFunction, Deferred.resolved(10));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 16, true);
            });

            it('Array of values and resolved Promises', function() {
                promise = Deferred.reduce([1, Deferred.resolved(2), 3, Deferred.resolved(4)], sumFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 10, true);
            });

            it('Array of values and resolved Promises and an initial value', function() {
                promise = Deferred.reduce([1, Deferred.resolved(2), 3, Deferred.resolved(4)], sumFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 20, true);
            });

            it('Array of values and resolved Promises and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce([1, Deferred.resolved(2), 3, Deferred.resolved(4)], sumFunction, Deferred.resolved(10));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 20, true);
            });
        });

        describe('returns a Promise that will resolve with the value obtained by reducing the specified resolved Promise of an Array of Promise(s) or value(s) using the specified function and initial value', function() {
            it('Promise of an empty Array and an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([]), sumFunction, 0);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 0, true);
            });

            it('Promise of an empty Array and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([]), sumFunction, Deferred.resolved(0));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 0, true);
            });

            it('Promise of an Array with one value', function() {
                promise = Deferred.reduce(Deferred.resolved([1]), sumFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 1, true);
            });

            it('Promise of an Array with one value and an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([1]), sumFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 11, true);
            });

            it('Promise of an Array with one value and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([1]), sumFunction, Deferred.resolved(10));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 11, true);
            });

            it('Promise of an Array of values', function() {
                promise = Deferred.reduce(Deferred.resolved([1, 2, 3, 4]), sumFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 10, true);
            });

            it('Promise of an Array of values and an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([1, 2, 3, 4]), sumFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 20, true);
            });

            it('Promise of an Array of values and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([1, 2, 3, 4]), sumFunction, Deferred.resolved(10));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 20, true);
            });

            it('Promise of a sparse Array', function() {
                promise = Deferred.reduce(Deferred.resolved([, 2, , 4, 5]), sumFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 11, true);
            });

            it('Promise of a sparse Array and an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([, 2, , 4, 5]), sumFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 21, true);
            });

            it('Promise of a sparse Array and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([, 2, , 4, 5]), sumFunction, Deferred.resolved(10));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 21, true);
            });

            it('Promise of an Array with one resolved Promise', function() {
                promise = Deferred.reduce(Deferred.resolved([Deferred.resolved(1)]), sumFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 1, true);
            });

            it('Promise of an Array with one resolved Promise and an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([Deferred.resolved(1)]), sumFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 11, true);
            });

            it('Promise of an Array with one resolved Promise and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([Deferred.resolved(1)]), sumFunction, Deferred.resolved(10));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 11, true);
            });

            it('Promise of an Array of resolved Promises', function() {
                promise = Deferred.reduce(Deferred.resolved([Deferred.resolved(1), Deferred.resolved(2), Deferred.resolved(3)]), sumFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 6, true);
            });

            it('Promise of an Array of resolved Promises and an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([Deferred.resolved(1), Deferred.resolved(2), Deferred.resolved(3)]), sumFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 16, true);
            });

            it('Promise of an Array of resolved Promises and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([Deferred.resolved(1), Deferred.resolved(2), Deferred.resolved(3)]), sumFunction, Deferred.resolved(10));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 16, true);
            });

            it('Promise of an Array of values and resolved Promises', function() {
                promise = Deferred.reduce(Deferred.resolved([1, Deferred.resolved(2), 3, Deferred.resolved(4)]), sumFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 10, true);
            });

            it('Promise of an Array of values and resolved Promises and an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([1, Deferred.resolved(2), 3, Deferred.resolved(4)]), sumFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 20, true);
            });

            it('Promise of an Array of values and resolved Promises and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([1, Deferred.resolved(2), 3, Deferred.resolved(4)]), sumFunction, Deferred.resolved(10));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 20, true);
            });
        });

        describe('returns a Promise that will resolve with the resolved Promise value obtained by reducing the specified Array of Promise(s) or value(s) using the specified function and initial value', function() {
            it('Empty Array and an initial value', function() {
                promise = Deferred.reduce([], sumPromiseFunction, 0);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 0, true);
            });

            it('Empty Array and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce([], sumPromiseFunction, Deferred.resolved(0));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 0, true);
            });

            it('Array with one value', function() {
                promise = Deferred.reduce([1], sumPromiseFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 1, true);
            });

            it('Array with one value and an initial value', function() {
                promise = Deferred.reduce([1], sumPromiseFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 11, true);
            });

            it('Array with one value and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce([1], sumPromiseFunction, Deferred.resolved(10));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 11, true);
            });

            it('Array of values', function() {
                promise = Deferred.reduce([1, 2, 3, 4], sumPromiseFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 10, true);
            });

            it('Array of values and an initial value', function() {
                promise = Deferred.reduce([1, 2, 3, 4], sumPromiseFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 20, true);
            });

            it('Array of values and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce([1, 2, 3, 4], sumPromiseFunction, Deferred.resolved(10));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 20, true);
            });

            it('Sparse Array', function() {
                promise = Deferred.reduce([, 2, , 4, 5], sumPromiseFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 11, true);
            });

            it('Sparse Array and an initial value', function() {
                promise = Deferred.reduce([, 2, , 4, 5], sumPromiseFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 21, true);
            });

            it('Sparse Array and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce([, 2, , 4, 5], sumPromiseFunction, Deferred.resolved(10));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 21, true);
            });

            it('Array with one resolved Promise', function() {
                promise = Deferred.reduce([Deferred.resolved(1)], sumPromiseFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 1, true);
            });

            it('Array with one resolved Promise and an initial value', function() {
                promise = Deferred.reduce([Deferred.resolved(1)], sumPromiseFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 11, true);
            });

            it('Array with one resolved Promise and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce([Deferred.resolved(1)], sumPromiseFunction, Deferred.resolved(10));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 11, true);
            });

            it('Array of resolved Promises', function() {
                promise = Deferred.reduce([Deferred.resolved(1), Deferred.resolved(2), Deferred.resolved(3)], sumPromiseFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 6, true);
            });

            it('Array of resolved Promises and an initial value', function() {
                promise = Deferred.reduce([Deferred.resolved(1), Deferred.resolved(2), Deferred.resolved(3)], sumPromiseFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 16, true);
            });

            it('Array of resolved Promises and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce([Deferred.resolved(1), Deferred.resolved(2), Deferred.resolved(3)], sumPromiseFunction, Deferred.resolved(10));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 16, true);
            });

            it('Array of values and resolved Promises', function() {
                promise = Deferred.reduce([1, Deferred.resolved(2), 3, Deferred.resolved(4)], sumPromiseFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 10, true);
            });

            it('Array of values and resolved Promises and an initial value', function() {
                promise = Deferred.reduce([1, Deferred.resolved(2), 3, Deferred.resolved(4)], sumPromiseFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 20, true);
            });

            it('Array of values and resolved Promises and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce([1, Deferred.resolved(2), 3, Deferred.resolved(4)], sumPromiseFunction, Deferred.resolved(10));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 20, true);
            });
        });

        describe('returns a Promise that will resolve with the resolved Promise value obtained by reducing the specified resolved Promise of an Array of Promise(s) or value(s) using the specified function and initial value', function() {
            it('Promise of an empty Array and an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([]), sumPromiseFunction, 0);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 0, true);
            });

            it('Promise of an empty Array and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([]), sumPromiseFunction, Deferred.resolved(0));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 0, true);
            });

            it('Promise of an Array with one value', function() {
                promise = Deferred.reduce(Deferred.resolved([1]), sumPromiseFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 1, true);
            });

            it('Promise of an Array with one value and an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([1]), sumPromiseFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 11, true);
            });

            it('Promise of an Array with one value and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([1]), sumPromiseFunction, Deferred.resolved(10));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 11, true);
            });

            it('Promise of an Array of values', function() {
                promise = Deferred.reduce(Deferred.resolved([1, 2, 3, 4]), sumPromiseFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 10, true);
            });

            it('Promise of an Array of values and an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([1, 2, 3, 4]), sumPromiseFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 20, true);
            });

            it('Promise of an Array of values and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([1, 2, 3, 4]), sumPromiseFunction, Deferred.resolved(10));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 20, true);
            });

            it('Promise of a sparse Array', function() {
                promise = Deferred.reduce(Deferred.resolved([, 2, , 4, 5]), sumPromiseFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 11, true);
            });

            it('Promise of a sparse Array and an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([, 2, , 4, 5]), sumPromiseFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 21, true);
            });

            it('Promise of a sparse Array and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([, 2, , 4, 5]), sumPromiseFunction, Deferred.resolved(10));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 21, true);
            });

            it('Promise of an Array with one resolved Promise', function() {
                promise = Deferred.reduce(Deferred.resolved([Deferred.resolved(1)]), sumPromiseFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 1, true);
            });

            it('Promise of an Array with one resolved Promise and an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([Deferred.resolved(1)]), sumPromiseFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 11, true);
            });

            it('Promise of an Array with one resolved Promise and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([Deferred.resolved(1)]), sumPromiseFunction, Deferred.resolved(10));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 11, true);
            });

            it('Promise of an Array of resolved Promises', function() {
                promise = Deferred.reduce(Deferred.resolved([Deferred.resolved(1), Deferred.resolved(2), Deferred.resolved(3)]), sumPromiseFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 6, true);
            });

            it('Promise of an Array of resolved Promises and an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([Deferred.resolved(1), Deferred.resolved(2), Deferred.resolved(3)]), sumPromiseFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 16, true);
            });

            it('Promise of an Array of resolved Promises and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([Deferred.resolved(1), Deferred.resolved(2), Deferred.resolved(3)]), sumPromiseFunction, Deferred.resolved(10));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 16, true);
            });

            it('Promise of an Array of values and resolved Promises', function() {
                promise = Deferred.reduce(Deferred.resolved([1, Deferred.resolved(2), 3, Deferred.resolved(4)]), sumPromiseFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 10, true);
            });

            it('Promise of an Array of values and resolved Promises and an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([1, Deferred.resolved(2), 3, Deferred.resolved(4)]), sumPromiseFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 20, true);
            });

            it('Promise of an Array of values and resolved Promises and a resolved Promise of an initial value', function() {
                promise = Deferred.reduce(Deferred.resolved([1, Deferred.resolved(2), 3, Deferred.resolved(4)]), sumPromiseFunction, Deferred.resolved(10));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 20, true);
            });
        });

        describe('returns a new Promise that will reject with the error associated with the first Promise in the specified Array of Promise(s) or value(s) that rejects', function() {
            it('Array with one rejected Promise', function() {
                promise = Deferred.reduce([Deferred.rejected(new Error('error message'))], sumFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Array of values and a rejected Promise', function() {
                promise = Deferred.reduce([1, Deferred.rejected(new Error('error message')), 3], sumFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Array of resolved Promises and a rejected Promise', function() {
                promise = Deferred.reduce([Deferred.resolved(1), Deferred.rejected(new Error('error message')), Deferred.resolved(3)], sumFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Array of values, pending and resolved Promises and a rejected Promise', function() {
                promise = Deferred.reduce([1, 2, Deferred.rejected(new Error('error message')), Deferred.resolved(4), new Deferred().promise], sumFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });
        });

        describe('returns a new Promise that will reject with the error associated with the first Promise in the specified resolved Promise of an Array of Promise(s) or value(s) that rejects', function() {
            it('Promise of an Array with one rejected Promise', function() {
                promise = Deferred.reduce(Deferred.resolved([Deferred.rejected(new Error('error message'))]), sumFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Promise of an Array of values and a rejected Promise', function() {
                promise = Deferred.reduce(Deferred.resolved([1, Deferred.rejected(new Error('error message')), 3]), sumFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Promise of an Array of resolved Promises and a rejected Promise', function() {
                promise = Deferred.reduce(Deferred.resolved([Deferred.resolved(1), Deferred.rejected(new Error('error message')), Deferred.resolved(3)]), sumFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Promise of an Array of values, pending and resolved Promises and a rejected Promise', function() {
                promise = Deferred.reduce(Deferred.resolved([1, 2, Deferred.rejected(new Error('error message')), Deferred.resolved(4), new Deferred().promise]), sumFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });
        });

        describe('returns a new Promise that will reject with the error associated with the rejected Promise of an Array of Promise(s) or value(s)', function() {
            it('Error: error message', function() {
                promise = Deferred.reduce(Deferred.rejected(new Error('error message')), sumFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });
        });

        describe('returns a new Promise that will reject with the error associated with the first rejected Promise returned by the specified function for the the specified Array of Promise(s) or value(s)', function() {
            it('Array with one value', function() {
                promise = Deferred.reduce([1], rejectFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Array of values', function() {
                promise = Deferred.reduce([1, 2, 3], rejectFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Sparse Array', function() {
                promise = Deferred.reduce([, 2, , 4, 5], rejectFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Array with one resolved Promise', function() {
                promise = Deferred.reduce([Deferred.resolved(1)], rejectFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Array of resolved Promises', function() {
                promise = Deferred.reduce([Deferred.resolved(1), Deferred.resolved(2), Deferred.resolved(3)], rejectFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Array of values and resolved Promises', function() {
                promise = Deferred.reduce([1, Deferred.resolved(2), Deferred.resolved(3), 4], rejectFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });
        });

        describe('returns a new Promise that will reject with the error associated with the first rejected Promise returned by the specified function for the the specified resolved Promise of an Array of Promise(s) or value(s)', function() {
            it('Promise of an Array with one value', function() {
                promise = Deferred.reduce(Deferred.resolved([1]), rejectFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Promise of an Array of values', function() {
                promise = Deferred.reduce(Deferred.resolved([1, 2, 3]), rejectFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Promise of a sparse Array', function() {
                promise = Deferred.reduce(Deferred.resolved([, 2, , 4, 5]), rejectFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Promise of an Array with one resolved Promise', function() {
                promise = Deferred.reduce(Deferred.resolved([Deferred.resolved(1)]), rejectFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Promise of an Array of resolved Promises', function() {
                promise = Deferred.reduce(Deferred.resolved([Deferred.resolved(1), Deferred.resolved(2), Deferred.resolved(3)]), rejectFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });

            it('Promise of an Array of values and resolved Promises', function() {
                promise = Deferred.reduce(Deferred.resolved([1, Deferred.resolved(2), Deferred.resolved(3), 4]), rejectFunction, 10);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });
        });

        describe('returns a new Promise that will reject with the error associated with the rejected Promise of an initial value', function() {
            it('Error: error message', function() {
                promise = Deferred.reduce([1, 2, 3], sumFunction, Deferred.rejected(new Error('error message')));
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'error message');
            });
        });

        describe('returns a new Promise that will reject if reduce is attempted on an empty Array with no initial value specified', function() {
            it('Empty Array', function() {
                promise = Deferred.reduce([], sumFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, TypeError);
            });

            it('Promise of an empty Array', function() {
                promise = Deferred.reduce(Deferred.resolved([]), sumFunction);
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, TypeError);
            });
        });

        describe('throws an Error if anything other than an Array or Promise of an Array and a function are specified as the first two parameters', function() {
            it('no parameters', function() {
                expect(function() {
                    return Deferred.reduce();
                }).toThrow('Invalid parameter: expected an Array or Promise of an Array.');
            });

            it('a single non-Array parameter', function() {
                expect(function() {
                    return Deferred.reduce(1);
                }).toThrow('Invalid parameter: expected an Array or Promise of an Array.');
            });

            it('multiple non-Array parameters', function() {
                expect(function() {
                    return Deferred.reduce(1, 2, 3);
                }).toThrow('Invalid parameter: expected an Array or Promise of an Array.');
            });

            it('an Array and no function', function() {
                expect(function() {
                    return Deferred.reduce([1, 2, 3]);
                }).toThrow('Invalid parameter: expected a function.');
            });

            it('a Promise of an Array and no function', function() {
                expect(function() {
                    return Deferred.reduce(Deferred.resolved([1, 2, 3]));
                }).toThrow('Invalid parameter: expected a function.');
            });

            it('an Array and a non-function parameter', function() {
                expect(function() {
                    return Deferred.reduce([1, 2, 3], 'not a function');
                }).toThrow('Invalid parameter: expected a function.');
            });

            it('a Promise of a non-function parameter', function() {
                expect(function() {
                    return Deferred.reduce(Deferred.resolved([1, 2, 3], 'not a function'));
                }).toThrow('Invalid parameter: expected a function.');
            });
        });
    }); // reduce

    describe('then()', function() {
        describe('with a progress handler', function() {
            var progressHandler;

            describe('attaches a progress handler that will be called on progress updates', function() {
                it('called with progress update when updated', function() {
                    progressHandler = jasmine.createSpy();
                    deferred = new Deferred();
                    promise = deferred.promise;

                    promise.then(null, null, progressHandler);

                    deferred.update('progress');

                    waitsForSpy(progressHandler);
                    runs(function() {
                        expect(progressHandler.callCount).toBe(1);
                        expect(progressHandler.calls[0].args).toEqual(['progress']);
                    });
                });

                it('called with progress update in specified scope when updated', function() {
                    progressHandler = jasmine.createSpy();
                    deferred = new Deferred();
                    promise = deferred.promise;

                    promise.then(null, null, progressHandler, targetScope);

                    deferred.update('progress');

                    waitsForSpy(progressHandler);
                    runs(function() {
                        expect(progressHandler.callCount).toBe(1);
                        expect(progressHandler.calls[0].args).toEqual(['progress']);
                        expect(progressHandler.calls[0].scope).toBe(targetScope);
                    });
                });
            });

            describe('propagates transformed progress updates that originate from this Promise', function() {
                it('propagates progress updates to subsequent Promises in the chain if a progress handler is omitted', function() {
                    progressHandler = jasmine.createSpy();
                    deferred = new Deferred();
                    promise = deferred.promise;

                    promise.then().then(null, null, progressHandler);

                    deferred.update('progress');

                    waitsForSpy(progressHandler);
                    runs(function() {
                        expect(progressHandler.callCount).toBe(1);
                        expect(progressHandler.calls[0].args).toEqual(['progress']);
                    });
                });

                it('propagates transformed progress updates to subsequent Promises in the chain if a progress handler transforms the progress update', function() {
                    // var deferred, progressHandler, promise, transformedProgressHandler, transformedTransformedProgressHandler;

                    // progressHandler = sinon.stub().returns('transformed progress');
                    progressHandler = jasmine.createSpy();
                    progressHandler.andReturn('transformed progress');

                    var transformedProgressHandler = jasmine.createSpy();

                    transformedProgressHandler.andReturn('transformed transformed progress');

                    var transformedTransformedProgressHandler = jasmine.createSpy();

                    deferred = new Deferred();
                    promise = deferred.promise;

                    promise.then(null, null, progressHandler)
                        .then(null, null, transformedProgressHandler)
                        .then(null, null, transformedTransformedProgressHandler);

                    deferred.update('progress');

                    waitsForSpy(progressHandler);
                    runs(function() {

                        expect(progressHandler.callCount).toBe(1);
                        expect(progressHandler.calls[0].args).toEqual(['progress']);

                        expect(transformedProgressHandler.callCount).toBe(1);
                        expect(transformedProgressHandler.calls[0].args).toEqual(['transformed progress']);

                        expect(transformedTransformedProgressHandler.callCount).toBe(1);
                        expect(transformedTransformedProgressHandler.calls[0].args).toEqual(['transformed transformed progress']);
                    });
                });
            });
        });

        describe('with parameters specified via a configuration object', function() {
            describe('attaches an onResolved callback to this Promise that will be called when it resolves', function() {
                describe('when only a success handler is specified', function() {
                    it('called with resolved value when resolved', function() {
                        var onResolved = jasmine.createSpy();

                        promise = Deferred.resolved('resolved value');

                        promise.then({
                            success: onResolved
                        });

                        waitsForSpy(onResolved);

                        runs(function() {
                            expect(onResolved.callCount).toBe(1);
                            expect(onResolved.calls[0].args).toEqual(['resolved value']);
                        });
                    });

                    it('called with resolved value in the specified scope when resolved', function() {
                        var onResolved = jasmine.createSpy();

                        promise = Deferred.resolved('resolved value');

                        promise.then({
                            success: onResolved,
                            scope: targetScope
                        });

                        waitsForSpy(onResolved);

                        runs(function() {
                            expect(onResolved.callCount).toBe(1);
                            expect(onResolved.calls[0].args).toEqual(['resolved value']);
                            expect(onResolved.calls[0].scope).toBe(targetScope);
                        });
                    });
                });

                describe('when success, failure and progress handlers are specified', function() {
                    it('called with resolved value when resolved', function() {
                        var onResolved = jasmine.createSpy();

                        var onRejected = jasmine.createSpy();

                        var onProgress = jasmine.createSpy();

                        promise = Deferred.resolved('resolved value');

                        promise.then({
                            success: onResolved,
                            failure: onRejected,
                            progress: onProgress
                        });

                        waitsForSpy(onResolved);

                        runs(function() {
                            expect(onResolved.callCount).toBe(1);
                            expect(onResolved.calls[0].args).toEqual(['resolved value']);

                            expect(onRejected.callCount).toBe(0);
                            expect(onProgress.callCount).toBe(0);
                        });
                    });

                    it('called with resolved value in the specified scope when resolved', function() {
                        var onResolved = jasmine.createSpy();

                        var onRejected = jasmine.createSpy();

                        var onProgress = jasmine.createSpy();

                        promise = Deferred.resolved('resolved value');

                        promise.then({
                            success: onResolved,
                            failure: onRejected,
                            progress: onProgress,
                            scope: targetScope
                        });

                        waitsForSpy(onResolved);

                        runs(function() {
                            expect(onResolved.callCount).toBe(1);
                            expect(onResolved.calls[0].args).toEqual(['resolved value']);
                            expect(onResolved.calls[0].scope).toBe(targetScope);

                            expect(onRejected.callCount).toBe(0);
                            expect(onProgress.callCount).toBe(0);
                        });
                    });
                });
            });

            describe('attaches an onRejected callback to this Promise that will be called when it rejects', function() {
                describe('when only a failure handler is specified', function() {
                    it('called with rejection reason when rejected', function() {
                        var onRejected = jasmine.createSpy();

                        promise = Deferred.rejected('rejection reason');

                        promise.then({
                            failure: onRejected
                        });

                        waitsForSpy(onRejected);

                        runs(function() {
                            expect(onRejected.callCount).toBe(1);
                            expect(onRejected.calls[0].args).toEqual(['rejection reason']);
                        });
                    });

                    it('called with rejection reason in specified scope when rejected', function() {
                        var onRejected = jasmine.createSpy();

                        promise = Deferred.rejected('rejection reason');

                        promise.then({
                            failure: onRejected,
                            scope: targetScope
                        });

                        waitsForSpy(onRejected);

                        runs(function() {
                            expect(onRejected.callCount).toBe(1);
                            expect(onRejected.calls[0].args).toEqual(['rejection reason']);
                            expect(onRejected.calls[0].scope).toBe(targetScope);
                        });
                    });
                });

                describe('when success, failure and progress handlers are specified', function() {
                    it('called with rejection reason when rejected', function() {
                        var onResolved = jasmine.createSpy();

                        var onRejected = jasmine.createSpy();

                        var onProgress = jasmine.createSpy();

                        promise = Deferred.rejected('rejection reason');

                        promise.then({
                            success: onResolved,
                            failure: onRejected,
                            progress: onProgress
                        });

                        waitsForSpy(onRejected);

                        runs(function() {
                            expect(onResolved.callCount).toBe(0);

                            expect(onRejected.callCount).toBe(1);
                            expect(onRejected.calls[0].args).toEqual(['rejection reason']);

                            expect(onProgress.callCount).toBe(0);
                        });
                    });

                    it('called with rejection reason in specified scope when rejected', function() {
                        var onProgress, onRejected, onResolved;

                        onResolved = jasmine.createSpy();
                        onRejected = jasmine.createSpy();
                        onProgress = jasmine.createSpy();
                        promise = Deferred.rejected('rejection reason');
                        promise.then({
                            success: onResolved,
                            failure: onRejected,
                            progress: onProgress,
                            scope: targetScope
                        });

                        waitsForSpy(onRejected);

                        runs(function() {
                            expect(onRejected.callCount).toBe(1);
                            expect(onRejected.calls[0].args).toEqual(['rejection reason']);
                            expect(onRejected.calls[0].scope).toBe(targetScope);

                            expect(onProgress.callCount).toBe(0);
                        });
                    });
                });
            });

            describe('attaches an onProgress callback to this Promise that will be called when it resolves', function() {
                describe('when only a progress handler is specified', function() {
                    it('called with progress update when updated', function() {
                        var onProgress = jasmine.createSpy();

                        deferred = new Deferred();
                        promise = deferred.promise;

                        promise.then({
                            progress: onProgress
                        });

                        deferred.update('progress');
                        waitsForSpy(onProgress);

                        runs(function() {
                            expect(onProgress.callCount).toBe(1);
                            expect(onProgress.calls[0].args).toEqual(['progress']);
                        });
                    });

                    it('called with progress update in specified scope when updated', function() {
                        var onProgress = jasmine.createSpy();

                        deferred = new Deferred();
                        promise = deferred.promise;

                        promise.then({
                            progress: onProgress,
                            scope: targetScope
                        });

                        deferred.update('progress');
                        waitsForSpy(onProgress);

                        runs(function() {
                            expect(onProgress.callCount).toBe(1);
                            expect(onProgress.calls[0].args).toEqual(['progress']);
                            expect(onProgress.calls[0].scope).toBe(targetScope);
                        });
                    });
                });

                describe('when success, failure and progress handlers are specified', function() {
                    it('called with progress update when updated', function() {
                        var onResolved = jasmine.createSpy();

                        var onRejected = jasmine.createSpy();

                        var onProgress = jasmine.createSpy();

                        deferred = new Deferred();
                        promise = deferred.promise;

                        promise.then({
                            success: onResolved,
                            failure: onRejected,
                            progress: onProgress
                        });

                        deferred.update('progress');
                        waitsForSpy(onProgress);

                        runs(function() {
                            expect(onProgress.callCount).toBe(1);
                            expect(onProgress.calls[0].args).toEqual(['progress']);
                            expect(onResolved.callCount).toBe(0);
                            expect(onRejected.callCount).toBe(0);
                        });
                    });

                    it('called with progress update in specified scope when updated', function() {
                        var onResolved = jasmine.createSpy();

                        var onRejected = jasmine.createSpy();

                        var onProgress = jasmine.createSpy();

                        deferred = new Deferred();
                        promise = deferred.promise;

                        promise.then({
                            success: onResolved,
                            failure: onRejected,
                            progress: onProgress,
                            scope: targetScope
                        });

                        deferred.update('progress');
                        waitsForSpy(onProgress);

                        runs(function() {
                            expect(onResolved.callCount).toBe(0);
                            expect(onRejected.callCount).toBe(0);
                            expect(onProgress.callCount).toBe(1);
                            expect(onProgress.calls[0].args).toEqual(['progress']);
                            expect(onProgress.calls[0].scope).toBe(targetScope);
                        });
                    });
                });
            });
        });
    }); // then

    // Cannot use catch as a method name in older browsers, so use the bracketed form
    describe('catch()', function() {
        describe('attaches a callback that will be called if this Promise is rejected', function() {
            describe('with parameters specified via function arguments', function() {
                it('called if rejected', function() {
                    var onRejected = jasmine.createSpy();

                    var error = new Error('error message');

                    promise = Deferred.rejected(error);

                    promise['catch'](onRejected);

                    waitsForSpy(onRejected);
                    runs(function() {
                        promise.then(null, function() {
                            expect(onRejected.callCount).toBe(1);
                            expect(onRejected.calls[0].args.length).toBe(1);
                            expect(onRejected.calls[0].args[0]).toBe(error);
                        });
                    });
                });

                it('called in specified scope if rejected', function() {
                    var onRejected = jasmine.createSpy();

                    var error = new Error('error message');

                    promise = Deferred.rejected(error);

                    promise['catch'](onRejected, targetScope);

                    waitsForSpy(onRejected);

                    runs(function() {
                        expect(onRejected.callCount).toBe(1);
                        expect(onRejected.calls[0].args.length).toBe(1);
                        expect(onRejected.calls[0].args[0]).toBe(error);
                        expect(onRejected.calls[0].scope).toBe(targetScope);
                    });
                });

                it('not called if resolved', function() {
                    var onRejected = jasmine.createSpy();

                    var onResolved = jasmine.createSpy();

                    promise = Deferred.resolved('value');

                    promise['catch'](onRejected);

                    promise.then(onResolved);

                    waitsForSpy(onResolved);

                    runs(function() {
                        expect(onRejected.callCount).toBe(0);
                    });
                });
            });

            describe('with parameters specified via a configuration object', function() {
                it('called if rejected', function() {
                    var onRejected = jasmine.createSpy();

                    var error = new Error('error message');

                    promise = Deferred.rejected(error);

                    promise['catch']({
                        fn: onRejected
                    });

                    waitsForSpy(onRejected);
                    runs(function() {
                        expect(onRejected.callCount).toBe(1);
                        expect(onRejected.calls[0].args.length).toBe(1);
                        expect(onRejected.calls[0].args[0]).toBe(error);
                    });
                });

                it('called in specified scope if rejected', function() {
                    var onRejected = jasmine.createSpy();

                    var onFailure = jasmine.createSpy();

                    var error = new Error('error message');

                    promise = Deferred.rejected(error);

                    promise['catch']({
                        fn: onRejected,
                        scope: targetScope
                    });

                    promise.then(null, onFailure);

                    waitsForSpy(onFailure);

                    runs(function() {
                        expect(onRejected.callCount).toBe(1);
                        expect(onRejected.calls[0].args.length).toBe(1);
                        expect(onRejected.calls[0].args[0]).toBe(error);
                        expect(onRejected.calls[0].scope).toBe(targetScope);
                    });
                });

                it('not called if resolved', function() {
                    var onRejected = jasmine.createSpy();

                    var onResolved = jasmine.createSpy();

                    promise = Deferred.resolved('value');

                    promise['catch']({
                        fn: onRejected
                    });
                    promise.then(onResolved);

                    waitsForSpy(onResolved);

                    runs(function() {
                        expect(onRejected.callCount).toBe(0);
                    });
                });
            });
        });

        describe('returns a Promise of the transformed future value', function() {
            it('resolves with the returned value if callback returns a value', function() {
                var onRejected = function() {
                    return 'returned value';
                };

                promise = Deferred.rejected(new Error('error message'))['catch'](onRejected);

                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 'returned value', true);
            });

            it('resolves with the resolved value if callback returns a Promise that resolves with value', function() {
                var onRejected = function() {
                    return Deferred.resolved('resolved value');
                };

                promise = Deferred.rejected(new Error('error message'))['catch'](onRejected);

                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 'resolved value', true);
            });

            it('rejects with the thrown Error if callback throws an Error', function() {
                var onRejected = function() {
                    throw new Error('thrown error message');
                };

                promise = Deferred.rejected(new Error('error message'))['catch'](onRejected);

                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'thrown error message');
            });

            it('rejects with the rejection reason if callback returns a Promise that rejects with a reason', function() {
                var onRejected = function() {
                    return Deferred.rejected(new Error('rejection reason'));
                };

                promise = Deferred.rejected(new Error('original error message'))['catch'](onRejected);

                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'rejection reason');
            });
        });
    }); // catch

    describe('otherwise()', function() {
        describe('attaches a callback that will be called if this Promise is rejected', function() {
            describe('with parameters specified via function arguments', function() {
                it('called if rejected', function() {
                    var onRejected = jasmine.createSpy();

                    var error = new Error('error message');

                    promise = Deferred.rejected(error);

                    promise.otherwise(onRejected);

                    waitsForSpy(onRejected);
                    runs(function() {
                        promise.then(null, function() {
                            expect(onRejected.callCount).toBe(1);
                            expect(onRejected.calls[0].args.length).toBe(1);
                            expect(onRejected.calls[0].args[0]).toBe(error);
                        });
                    });
                });

                it('called in specified scope if rejected', function() {
                    var onRejected = jasmine.createSpy();

                    var error = new Error('error message');

                    promise = Deferred.rejected(error);

                    promise.otherwise(onRejected, targetScope);

                    waitsForSpy(onRejected);

                    runs(function() {
                        expect(onRejected.callCount).toBe(1);
                        expect(onRejected.calls[0].args.length).toBe(1);
                        expect(onRejected.calls[0].args[0]).toBe(error);
                        expect(onRejected.calls[0].scope).toBe(targetScope);
                    });
                });

                it('not called if resolved', function() {
                    var onRejected = jasmine.createSpy();

                    var onResolved = jasmine.createSpy();

                    promise = Deferred.resolved('value');

                    promise.otherwise(onRejected);

                    promise.then(onResolved);

                    waitsForSpy(onResolved);

                    runs(function() {
                        expect(onRejected.callCount).toBe(0);
                    });
                });
            });

            describe('with parameters specified via a configuration object', function() {
                it('called if rejected', function() {
                    var onRejected = jasmine.createSpy();

                    var error = new Error('error message');

                    promise = Deferred.rejected(error);

                    promise.otherwise({
                        fn: onRejected
                    });

                    waitsForSpy(onRejected);
                    runs(function() {
                        expect(onRejected.callCount).toBe(1);
                        expect(onRejected.calls[0].args.length).toBe(1);
                        expect(onRejected.calls[0].args[0]).toBe(error);
                    });
                });

                it('called in specified scope if rejected', function() {
                    var onRejected = jasmine.createSpy();

                    var onFailure = jasmine.createSpy();

                    var error = new Error('error message');

                    promise = Deferred.rejected(error);

                    promise.otherwise({
                        fn: onRejected,
                        scope: targetScope
                    });

                    promise.then(null, onFailure);

                    waitsForSpy(onFailure);

                    runs(function() {
                        expect(onRejected.callCount).toBe(1);
                        expect(onRejected.calls[0].args.length).toBe(1);
                        expect(onRejected.calls[0].args[0]).toBe(error);
                        expect(onRejected.calls[0].scope).toBe(targetScope);
                    });
                });

                it('not called if resolved', function() {
                    var onRejected = jasmine.createSpy();

                    var onResolved = jasmine.createSpy();

                    promise = Deferred.resolved('value');

                    promise.otherwise({
                        fn: onRejected
                    });
                    promise.then(onResolved);

                    waitsForSpy(onResolved);

                    runs(function() {
                        expect(onRejected.callCount).toBe(0);
                    });
                });
            });
        });

        describe('returns a Promise of the transformed future value', function() {
            it('resolves with the returned value if callback returns a value', function() {
                var onRejected = function() {
                    return 'returned value';
                };

                promise = Deferred.rejected(new Error('error message')).otherwise(onRejected);

                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 'returned value', true);
            });

            it('resolves with the resolved value if callback returns a Promise that resolves with value', function() {
                var onRejected = function() {
                    return Deferred.resolved('resolved value');
                };

                promise = Deferred.rejected(new Error('error message')).otherwise(onRejected);

                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 'resolved value', true);
            });

            it('rejects with the thrown Error if callback throws an Error', function() {
                var onRejected = function() {
                    throw new Error('thrown error message');
                };

                promise = Deferred.rejected(new Error('error message')).otherwise(onRejected);

                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'thrown error message');
            });

            it('rejects with the rejection reason if callback returns a Promise that rejects with a reason', function() {
                var onRejected = function() {
                    return Deferred.rejected(new Error('rejection reason'));
                };

                promise = Deferred.rejected(new Error('original error message')).otherwise(onRejected);

                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'rejection reason');
            });
        });
    }); // otherwise

    describe('always()', function() {
        describe('attaches a callback to this Promise that will be called when it resolves or rejects', function() {
            describe('with parameters specified via function arguments', function() {
                it('called with no parameters when resolved', function() {
                    var onComplete = jasmine.createSpy();

                    var onResolve = jasmine.createSpy();

                    promise = Deferred.resolved('value');

                    promise.always(onComplete);
                    promise.then(onResolve);

                    waitsForSpy(onResolve);
                    runs(function() {
                        expect(onComplete.callCount).toBeGT(0);
                    });
                });

                it('called with no parameters in the specified scope when resolved', function() {
                    var onComplete = jasmine.createSpy();

                    var onResolve = jasmine.createSpy();

                    promise = Deferred.resolved('value');

                    promise.always(onComplete, targetScope);
                    promise.then(onResolve);

                    waitsForSpy(onResolve);

                    runs(function() {
                        expect(onComplete.callCount).toBeGT(0);
                    });
                });

                it('called with no parameters when rejected', function() {
                    var onComplete = jasmine.createSpy();

                    var onRejected = jasmine.createSpy();

                    promise = Deferred.rejected(new Error('error message'));

                    promise.always(onComplete);
                    promise.then(null, onRejected);

                    waitsForSpy(onRejected);
                    runs(function() {
                        expect(onComplete.callCount).toBeGT(0);
                    });
                });

                it('called with no parameters in the specified scope when rejected', function() {
                    var onComplete = jasmine.createSpy();

                    var onRejected = jasmine.createSpy();

                    promise = Deferred.rejected(new Error('error message'));

                    promise.always(onComplete, targetScope);

                    promise.then(null, onRejected);

                    waitsForSpy(onRejected);

                    runs(function() {
                        expect(onComplete.callCount).toBeGT(0);
                    });
                });
            });

            describe('with parameters specified via a configuration object', function() {
                it('called with no parameters when resolved', function() {
                    var onComplete = jasmine.createSpy();

                    var onResolved = jasmine.createSpy();

                    promise = Deferred.resolved('value');

                    promise.always({
                        fn: onComplete
                    });

                    promise.then(onResolved);

                    waitsForSpy(onResolved);

                    runs(function() {
                        expect(onComplete.callCount).toBeGT(0);
                    });
                });

                it('called with no parameters in the specified scope when resolved', function() {
                    var onComplete = jasmine.createSpy();

                    var onResolved = jasmine.createSpy();

                    promise = Deferred.resolved('value');

                    promise.always({
                        fn: onComplete,
                        scope: targetScope
                    });

                    promise.then(onResolved);

                    waitsForSpy(onResolved);

                    runs(function() {
                        expect(onComplete.callCount).toBeGT(0);
                    });
                });

                it('called with no parameters when rejected', function() {
                    var onComplete = jasmine.createSpy();

                    var onFailure = jasmine.createSpy();

                    promise = Deferred.rejected(new Error('error message'));

                    promise.always({
                        fn: onComplete
                    });
                    promise.then(null, onFailure);

                    waitsForSpy(onFailure);

                    runs(function() {
                        expect(onComplete.callCount).toBeGT(0);
                    });
                });

                it('called with no parameters in the specified scope when rejected', function() {
                    var onComplete = jasmine.createSpy();

                    var onFailure = jasmine.createSpy();

                    promise = Deferred.rejected(new Error('error message'));

                    promise.always({
                        fn: onComplete,
                        scope: targetScope
                    });
                    promise.then(null, onFailure);

                    waitsForSpy(onFailure);
                    runs(function() {
                        expect(onComplete.callCount).toBeGT(0);
                    });
                });
            });
        });

        describe('return a new "pass-through" Promise that resolves with the original value or rejects with the original reason', function() {
            it('if the originating Promise resolves, ignores value returned by callback', function() {
                function onComplete() {
                    return 'callback return value';
                }

                promise = Deferred.resolved('resolved value').always(onComplete);

                expect(promise instanceof ExtPromise).toBe(true);

                eventuallyResolvesTo(promise, 'resolved value', true);
            });

            xit('if the originating Promise resolves, ignores and later rethrows Error thrown by callback', function(done) {
                function onComplete() {
                    throw new Error('callback error message');
                }

                promise = Deferred.resolved('resolved value').always(onComplete);

                expect(promise instanceof ExtPromise).toBe(true);

                promise.then(function(v) {
                    expect(v).toBe('resolved value');
                }, function(reason) {
                    expect(reason.message).toBe('callback error message');
                    done();
                });

                /*
                TODO - Not sure the right conversion of this:
        assert.eventuallyThrows(new Error('callback error message'), function(error) {
          if (error) {
            throw error;
          }
          return promise.should.eventually.equal('resolved value').then(function(value) {
            return done();
          }, function(reason) {
            return done(reason);
          });
        }, 100);
                */
            });

            it('if the originating Promise rejects, ignores value returned by callback', function() {
                function onComplete() {
                    return 'callback return value';
                }

                promise = Deferred.rejected(new Error('rejection reason')).always(onComplete);

                expect(promise instanceof ExtPromise).toBe(true);

                eventuallyRejectedWith(promise, Error, 'rejection reason');
            });

            xit('if the originating Promise rejects, ignores and later rethrows Error thrown by callback', function(done) {
                var me = this;

                function onComplete() {
                    throw new Error('callback error message');
                }

                promise = Deferred.rejected(new Error('rejection reason')).always(onComplete);

                expect(promise instanceof ExtPromise).toBe(true);

                promise.then(function(v) {
                    me.fail('should reject');
                    done();
                }, function(reason) {
                    expect(reason.message).toBe('rejection value');
                    // ?? expect(reason.message).toBe('callback error message');
                    done();
                });

                /*
                TODO - Not sure the right conversion of this:
        assert.eventuallyThrows(new Error('callback error message'), function(error) {
          if (error) {
            throw error;
          }
          return promise.should.be.rejectedWith(Error, 'rejection reason').then(function(value) {
            return done();
          }, function(reason) {
            return done(reason);
          });
        }, 100);
                 */
            });
        });
    });  // always

    // TODO - not sure the conversion for these
    xdescribe('done()', function() {
        describe('terminates a Promise chain, ensuring that unhandled rejections will be thrown as Errors', function() {
            it('rethrows the rejection as an error if the originating Promise rejects', function(done) {
                promise = Deferred.rejected(new Error('rejection reason')).done();

                assert.eventuallyThrows(new Error('rejection reason'), done, 100);
            });
            it('rethrows the rejection as an error if an ancestor Promise rejects and that rejection is unhandled', function(done) {

                this.slow(250);
                promise = Deferred.rejected(new Error('rejection reason')).then(function(value) {
                    return value;
                }).done();
                assert.eventuallyThrows(new Error('rejection reason'), done, 100);
            });
        });
    });

    describe('cancel()', function() {
        describe('cancels a Promise if it is still pending, triggering a rejection with a CancellationError that will propagate to any Promises originating from that Promise', function() {
            it('rejects a pending Promise with a CancellationError', function() {
                promise = new Deferred().promise;
                promise.cancel();

                eventuallyRejectedWith(promise, Ext.promise.Promise.CancellationError);
            });

            it('rejects a pending Promise with a CancellationError with a reason', function() {
                promise = new Deferred().promise;
                promise.cancel('cancellation reason');

                eventuallyRejectedWith(promise, Ext.promise.Promise.CancellationError, 'cancellation reason');
            });

            it('ignores attempts to cancel a fulfilled Promise', function() {
                promise = Deferred.resolved('resolved value');
                promise.cancel();

                eventuallyResolvesTo(promise, 'resolved value', true);
            });

            it('ignores attempts to cancel a rejected Promise', function() {
                promise = Deferred.rejected(new Error('rejection reason'));
                promise.cancel();

                eventuallyRejectedWith(promise, Error, 'rejection reason');
            });

            it('propagates rejection with that CancellationError to Promises that originate from the cancelled Promise', function() {
                promise = new Deferred().promise;
                promise.cancel('cancellation reason');

                eventuallyRejectedWith(promise.then(),
                    Ext.promise.Promise.CancellationError, 'cancellation reason');
            });
        });
    });

    xdescribe('log()', function() {
        describe('logs the resolution or rejection of this Promise using Logger.log()', function() {
            beforeEach(function() {
                return jasmine.createSpy(Logger, 'log');
            });
            afterEach(function() {
                return Logger.log.restore();
            });
            it('logs a fulfilled promise', function(done) {
                var promise, value;

                value = 'resolved value';
                promise = Deferred.resolved(value).log();
                expect(promise instanceof ExtPromise).toBe(true);
                promise.always(function() {
                    var error;

                    try {
                        expect(Logger.log).to.be.calledOnce.and.calledWith("Promise resolved with value: " + value);
                        done();
                    }
                    catch (e) {
                        this.fail(e);
                        done(e);
                    }
                });
            });
            it('logs a fulfilled promise, with the optional name specified', function(done) {
                var promise, value;

                value = 'resolved value';
                promise = Deferred.resolved(value).log('Test Promise');
                expect(promise instanceof ExtPromise).toBe(true);
                promise.always(function() {
                    var error;

                    try {
                        expect(Logger.log).to.be.calledOnce.and.calledWith("Test Promise resolved with value: " + value);
                        done();
                    }
                    catch (e) {
                        this.fail(e);
                        done();
                    }
                });
            });
            it('logs a rejected promise', function(done) {
                var promise, reason;

                reason = new Error('rejection reason');
                promise = Deferred.rejected(reason).log();
                expect(promise instanceof ExtPromise).toBe(true);
                promise.always(function() {
                    var error;

                    try {
                        expect(Logger.log).to.be.calledOnce.and.calledWith("Promise rejected with reason: " + reason);
                        done();
                    }
                    catch (e) {
                        this.fail(e);
                        done();
                    }
                });
            });
            it('logs a rejected promise, with the optional name specified', function(done) {
                var promise, reason;

                reason = new Error('rejection reason');
                promise = Deferred.rejected(reason).log('Test Promise');
                expect(promise instanceof ExtPromise).toBe(true);
                promise.always(function() {
                    var error;

                    try {
                        expect(Logger.log).to.be.calledOnce.and.calledWith("Test Promise rejected with reason: " + reason);
                        done();
                    }
                    catch (e) {
                        this.fail(e);
                        done();
                    }
                });
            });
        });

        describe('return a new "pass-through" Promise that resolves with the original value or rejects with the original reason', function() {
            it('resolves if the originating Promise resolves', function() {

                promise = Deferred.resolved('resolved value').log();
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyResolvesTo(promise, 'resolved value', true);
            });
            it('rejects if the originating Promise rejects', function() {

                promise = Deferred.rejected(new Error('rejection reason')).log();
                expect(promise instanceof ExtPromise).toBe(true);
                eventuallyRejectedWith(promise, Error, 'rejection reason');
            });
        });
    }); // log

    describe('Extras', function() {
        function verifyScope(fn, expectedScope) {
            return function() {
                expect(this).toBe(expectedScope);

                return fn.apply(this, arguments);
            };
        }

        function verifyArgs(fn, expectedArgs) {
            return function() {
                var args = Ext.Array.slice(arguments, 0);

                expect(args).toEqual(expectedArgs);

                return fn.apply(this, arguments);
            };
        }

        describe('sequence()', function() {
            var fn1 = function() {
                ++fn1.callCount;
                expect(fn2.callCount).toBe(0);
                expect(fn3.callCount).toBe(0);

                return 1;
            };

            var fn2 = function() {
                ++fn2.callCount;
                expect(fn1.callCount).toBe(1);
                expect(fn3.callCount).toBe(0);

                return 2;
            };

            var fn3 = function() {
                ++fn3.callCount;
                expect(fn1.callCount).toBe(1);
                expect(fn2.callCount).toBe(1);

                return 3;
            };

            beforeEach(function() {
                fn1.callCount = fn2.callCount = fn3.callCount = 0;
            });

            describe('returns a new Promise that will resolve with an Array of the results returned by calling the specified functions in sequence order', function() {
                it('Empty Array', function() {
                    var fns = [];

                    promise = Deferred.sequence(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [], true);
                });

                it('Empty Array with the optional scope specified', function() {
                    var fns = [];

                    promise = Deferred.sequence(fns, targetScope);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [], true);
                });

                it('Empty Array with the optional scope and arguments specified', function() {
                    var args = ['a', 'b', 'c'];

                    var fns = [];

                    promise = Deferred.sequence(fns, targetScope, 'a', 'b', 'c');

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [], true);
                });

                it('Array with one function', function() {
                    var fns = [fn1];

                    promise = Deferred.sequence(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1], true);
                });

                it('Array with one function with the optional scope specified', function() {
                    var fns = [verifyScope(fn1, targetScope)];

                    promise = Deferred.sequence(fns, targetScope);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1], true);
                });

                it('Array with one function with the optional scope and arguments specified', function() {
                    var args, fns;

                    args = ['a', 'b', 'c'];
                    fns = [verifyArgs(verifyScope(fn1, targetScope), args)];
                    promise = Deferred.sequence(fns, targetScope, 'a', 'b', 'c');
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1], true);
                });
                it('Array of two functions', function() {
                    var fns;

                    fns = [fn1, fn2];
                    promise = Deferred.sequence(fns);
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1, 2], true);
                });
                it('Array of two functions with the optional scope specified', function() {
                    var fns;

                    fns = [verifyScope(fn1, targetScope), verifyScope(fn2, targetScope)];
                    promise = Deferred.sequence(fns, targetScope);
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1, 2], true);
                });
                it('Array of two functions with the optional scope and arguments specified', function() {
                    var args, fns;

                    args = ['a', 'b', 'c'];
                    fns = [verifyArgs(verifyScope(fn1, targetScope), args), verifyArgs(verifyScope(fn2, targetScope), args)];
                    promise = Deferred.sequence(fns, targetScope, 'a', 'b', 'c');
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1, 2], true);
                });
                it('Array of three functions', function() {
                    var fns;

                    fns = [fn1, fn2, fn3];
                    promise = Deferred.sequence(fns);
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1, 2, 3], true);
                });
                it('Array of three functions with the optional scope specified', function() {
                    var fns;

                    fns = [verifyScope(fn1, targetScope), verifyScope(fn2, targetScope), verifyScope(fn3, targetScope)];
                    promise = Deferred.sequence(fns, targetScope);
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1, 2, 3], true);
                });
                it('Array of three functions with the optional scope and arguments specified', function() {
                    var args, fns;

                    args = ['a', 'b', 'c'];
                    fns = [verifyArgs(verifyScope(fn1, targetScope), args), verifyArgs(verifyScope(fn2, targetScope), args), verifyArgs(verifyScope(fn3, targetScope), args)];
                    promise = Deferred.sequence(fns, targetScope, 'a', 'b', 'c');
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1, 2, 3], true);
                });
            });

            describe('returns a new Promise that will resolve with an Array of the results returned by calling the specified resolved Promise of an Array of functions in sequence order', function() {
                it('Promise of an empty Array', function() {
                    var fns;

                    fns = [];
                    promise = Deferred.sequence(Deferred.resolved(fns));
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [], true);
                });
                it('Promise of an empty Array with the optional scope specified', function() {
                    var fns;

                    fns = [];
                    promise = Deferred.sequence(Deferred.resolved(fns), targetScope);
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [], true);
                });
                it('Promise of an empty Array with the optional scope and arguments specified', function() {
                    var args, fns;

                    args = ['a', 'b', 'c'];
                    fns = [];
                    promise = Deferred.sequence(fns, targetScope, 'a', 'b', 'c');
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [], true);
                });
                it('Promise of an Array with one function', function() {
                    var fns = [fn1];

                    promise = Deferred.sequence(Deferred.resolved(fns));
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1], true);
                });
                it('Promise of an Array with one function with the optional scope specified', function() {
                    var fns;

                    fns = [verifyScope(fn1, targetScope)];
                    promise = Deferred.sequence(Deferred.resolved(fns), targetScope);
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1], true);
                });
                it('Promise of an Array with one function with the optional scope and arguments specified', function() {
                    var args, fns;

                    args = ['a', 'b', 'c'];
                    fns = [verifyArgs(verifyScope(fn1, targetScope), args)];
                    promise = Deferred.sequence(Deferred.resolved(fns), targetScope, 'a', 'b', 'c');
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1], true);
                });
                it('Promise of an Array of two functions', function() {
                    var fns;

                    fns = [fn1, fn2];
                    promise = Deferred.sequence(Deferred.resolved(fns));
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1, 2], true);
                });
                it('Promise of an Array of two functions with the optional scope specified', function() {
                    var fns;

                    fns = [verifyScope(fn1, targetScope), verifyScope(fn2, targetScope)];
                    promise = Deferred.sequence(Deferred.resolved(fns), targetScope);
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1, 2], true);
                });
                it('Promise of an Array of two functions with the optional scope and arguments specified', function() {
                    var args, fns;

                    args = ['a', 'b', 'c'];
                    fns = [verifyArgs(verifyScope(fn1, targetScope), args), verifyArgs(verifyScope(fn2, targetScope), args)];
                    promise = Deferred.sequence(Deferred.resolved(fns), targetScope, 'a', 'b', 'c');
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1, 2], true);
                });
                it('Promise of an Array of three functions', function() {
                    var fns;

                    fns = [fn1, fn2, fn3];
                    promise = Deferred.sequence(Deferred.resolved(fns));
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1, 2, 3], true);
                });
                it('Promise of an Array of three functions with the optional scope specified', function() {
                    var fns;

                    fns = [verifyScope(fn1, targetScope), verifyScope(fn2, targetScope), verifyScope(fn3, targetScope)];
                    promise = Deferred.sequence(Deferred.resolved(fns), targetScope);
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1, 2, 3], true);
                });
                it('Promise of an Array of three functions with the optional scope and arguments specified', function() {
                    var args, fns;

                    args = ['a', 'b', 'c'];
                    fns = [verifyArgs(verifyScope(fn1, targetScope), args), verifyArgs(verifyScope(fn2, targetScope), args), verifyArgs(verifyScope(fn3, targetScope), args)];
                    promise = Deferred.sequence(Deferred.resolved(fns), targetScope, 'a', 'b', 'c');
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1, 2, 3], true);
                });
            });

            describe('returns a new Promise that will reject with the Error associated with the specified rejected Promise of an Array of functions', function() {
                it('Error: error message', function() {

                    promise = Deferred.sequence(Deferred.rejected(new Error('error message')));
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'error message');
                });
            });

            describe('returns a new Promise that will reject with the associated Error if any of the specified Array of functions throws an Error', function() {
                function brokenFn() {
                    throw new Error('Error message');
                }

                it('Array with one function that throws an Error', function() {
                    var fns;

                    fns = [brokenFn];
                    promise = Deferred.sequence(fns);
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });
                it('Array with one function and one function that throws an Error', function() {
                    var fns;

                    fns = [fn1, brokenFn];
                    promise = Deferred.sequence(fns);
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });
                it('Array with two functions and one function that throws an Error', function() {
                    var fns;

                    fns = [fn1, fn2, brokenFn];
                    promise = Deferred.sequence(fns);
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });
            });

            describe('returns a new Promise that will reject with the associated Error if any of the specified Promise of an Array of functions throws an Error', function() {
                function brokenFn() {
                    throw new Error('Error message');
                }

                it('Promise of an Array with one function that throws an Error', function() {
                    var fns;

                    fns = [brokenFn];
                    promise = Deferred.sequence(Deferred.resolved(fns));
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });

                it('Promise of an Array with one function and one function that throws an Error', function() {
                    var fns;

                    fns = [fn1, brokenFn];
                    promise = Deferred.sequence(Deferred.resolved(fns));
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });

                it('Promise of an Array with two functions and one function that throws an Error', function() {
                    var fns;

                    fns = [fn1, fn2, brokenFn];
                    promise = Deferred.sequence(Deferred.resolved(fns));
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });
            });

            describe('returns a new Promise that will reject with the associated Error if any of the specified Array of functions returns a rejected Promise', function() {
                function rejectFn() {
                    return Deferred.rejected(new Error('Error message'));
                }

                it('Array with one function that returns a rejected Promise', function() {
                    var fns;

                    fns = [rejectFn];
                    promise = Deferred.sequence(fns);
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });

                it('Array with one function and one function that returns a rejected Promise', function() {
                    var fns;

                    fns = [fn1, rejectFn];
                    promise = Deferred.sequence(fns);
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });

                it('Array with two functions and one function that returns a rejected Promise', function() {
                    var fns;

                    fns = [fn1, fn2, rejectFn];
                    promise = Deferred.sequence(fns);
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });
            });

            describe('returns a new Promise that will reject with the associated Error if any of the specified Promise of an Array of functions returns a rejected Promise', function() {
                function rejectFn() {
                    return Deferred.rejected(new Error('Error message'));
                }

                it('Promise of an Array with one function that returns a rejected Promise', function() {
                    var fns;

                    fns = [rejectFn];
                    promise = Deferred.sequence(Deferred.resolved(fns));
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });

                it('Promise of an Array with one function and one function that returns a rejected Promise', function() {
                    var fns;

                    fns = [fn1, rejectFn];
                    promise = Deferred.sequence(Deferred.resolved(fns));
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });

                it('Promise of an Array with two functions and one function that returns a rejected Promise', function() {
                    var fns;

                    fns = [fn1, fn2, rejectFn];
                    promise = Deferred.sequence(Deferred.resolved(fns));
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });
            });

            describe('returns a new Promise that will reject with the associated Error if any of the items in the specified Array is not a function', function() {
                it('Array with one non-function value', function() {
                    var fns;

                    fns = [1];
                    promise = Deferred.sequence(fns);
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Invalid parameter: expected a function.');
                });

                it('Array with one function and one non-function value', function() {
                    var fns;

                    fns = [fn1, 1];
                    promise = Deferred.sequence(fns);
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Invalid parameter: expected a function.');
                });

                it('Array with two functions and one non-function value', function() {
                    var fns;

                    fns = [fn1, fn2, 1];
                    promise = Deferred.sequence(fns);
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Invalid parameter: expected a function.');
                });
            });

            describe('returns a new Promise that will reject with the associated Error if any of the items in the specified resolved Promise of an Array is not a function ', function() {
                it('Promise of an Array with one non-function value', function() {
                    var fns;

                    fns = [1];
                    promise = Deferred.sequence(Deferred.resolved(fns));
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Invalid parameter: expected a function.');
                });

                it('Promise of an Array with one function and one non-function value', function() {
                    var fns;

                    fns = [fn1, 1];
                    promise = Deferred.sequence(Deferred.resolved(fns));
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Invalid parameter: expected a function.');
                });

                it('Promise of an Array with two functions and one non-function value', function() {
                    var fns;

                    fns = [fn1, fn2, 1];
                    promise = Deferred.sequence(Deferred.resolved(fns));
                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Invalid parameter: expected a function.');
                });
            });

            describe('throws an Error if anything other than Array or Promise of an Array is specified as the first parameter', function() {
                it('No parameters', function() {
                    expect(function() {
                        return Deferred.sequence();
                    }).toThrow('Invalid parameter: expected an Array or Promise of an Array.');
                });

                it('A non-Array parameter', function() {
                    expect(function() {
                        return Deferred.sequence(1);
                    }).toThrow('Invalid parameter: expected an Array or Promise of an Array.');
                });
            });
        }); // sequence

        describe('parallel()', function() {
            var fn1 = function() {
                ++fn1.callCount;

                return 1;
            };

            var fn2 = function() {
                ++fn2.callCount;

                return 2;
            };

            var fn3 = function() {
                ++fn3.callCount;

                return 3;
            };

            beforeEach(function() {
                fn1.callCount = fn2.callCount = fn3.callCount = 0;
            });

            describe('returns a new Promise that will resolve with an Array of the results returned by calling the specified functions in parallel', function() {
                it('Empty Array', function() {
                    var fns = [];

                    promise = Deferred.parallel(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [], true);
                });

                it('Empty Array with the optional scope specified', function() {
                    var fns = [];

                    promise = Deferred.parallel(fns, targetScope);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [], true);
                });

                it('Empty Array with the optional scope and arguments specified', function() {
                    var args = ['a', 'b', 'c'];

                    var fns = [];

                    promise = Deferred.parallel(fns, targetScope, 'a', 'b', 'c');

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [], true);
                });

                it('Array with one function', function() {
                    var fns = [fn1];

                    promise = Deferred.parallel(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1], true);
                });

                it('Array with one function with the optional scope specified', function() {
                    var fns = [verifyScope(fn1, targetScope)];

                    promise = Deferred.parallel(fns, targetScope);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1], true);
                });

                it('Array with one function with the optional scope and arguments specified', function() {
                    var args = ['a', 'b', 'c'];

                    var fns = [verifyArgs(verifyScope(fn1, targetScope), args)];

                    promise = Deferred.parallel(fns, targetScope, 'a', 'b', 'c');

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1], true);
                });

                it('Array of two functions', function() {
                    var fns = [fn1, fn2];

                    promise = Deferred.parallel(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1, 2], true);
                });

                it('Array of two functions with the optional scope specified', function() {
                    var fns = [verifyScope(fn1, targetScope), verifyScope(fn2, targetScope)];

                    promise = Deferred.parallel(fns, targetScope);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1, 2], true);
                });

                it('Array of two functions with the optional scope and arguments specified', function() {
                    var args = ['a', 'b', 'c'];

                    var fns = [verifyArgs(verifyScope(fn1, targetScope), args), verifyArgs(verifyScope(fn2, targetScope), args)];

                    promise = Deferred.parallel(fns, targetScope, 'a', 'b', 'c');

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1, 2], true);
                });

                it('Array of three functions', function() {
                    var fns = [fn1, fn2, fn3];

                    promise = Deferred.parallel(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1, 2, 3], true);
                });

                it('Array of three functions with the optional scope specified', function() {
                    var fns = [verifyScope(fn1, targetScope), verifyScope(fn2, targetScope), verifyScope(fn3, targetScope)];

                    promise = Deferred.parallel(fns, targetScope);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1, 2, 3], true);
                });

                it('Array of three functions with the optional scope and arguments specified', function() {
                    var args = ['a', 'b', 'c'];

                    var fns = [verifyArgs(verifyScope(fn1, targetScope), args), verifyArgs(verifyScope(fn2, targetScope), args), verifyArgs(verifyScope(fn3, targetScope), args)];

                    promise = Deferred.parallel(fns, targetScope, 'a', 'b', 'c');

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1, 2, 3], true);
                });
            });

            describe('returns a new Promise that will resolve with an Array of the results returned by calling the specified resolved Promise of an Array of functions in parallel', function() {
                it('Promise of an empty Array', function() {
                    var fns = [];

                    promise = Deferred.parallel(Deferred.resolved(fns));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [], true);
                });

                it('Promise of an empty Array with the optional scope specified', function() {
                    var fns = [];

                    promise = Deferred.parallel(Deferred.resolved(fns), targetScope);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [], true);
                });

                it('Promise of an empty Array with the optional scope and arguments specified', function() {
                    var args = ['a', 'b', 'c'];

                    var fns = [];

                    promise = Deferred.parallel(fns, targetScope, 'a', 'b', 'c');

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [], true);
                });

                it('Promise of an Array with one function', function() {
                    var fns = [fn1];

                    promise = Deferred.parallel(Deferred.resolved(fns));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1], true);
                });

                it('Promise of an Array with one function with the optional scope specified', function() {
                    var fns = [verifyScope(fn1, targetScope)];

                    promise = Deferred.parallel(Deferred.resolved(fns), targetScope);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1], true);
                });

                it('Promise of an Array with one function with the optional scope and arguments specified', function() {
                    var args = ['a', 'b', 'c'];

                    var fns = [verifyArgs(verifyScope(fn1, targetScope), args)];

                    promise = Deferred.parallel(Deferred.resolved(fns), targetScope, 'a', 'b', 'c');

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1], true);
                });

                it('Promise of an Array of two functions', function() {
                    var fns = [fn1, fn2];

                    promise = Deferred.parallel(Deferred.resolved(fns));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1, 2], true);
                });

                it('Promise of an Array of two functions with the optional scope specified', function() {
                    var fns = [verifyScope(fn1, targetScope), verifyScope(fn2, targetScope)];

                    promise = Deferred.parallel(Deferred.resolved(fns), targetScope);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1, 2], true);
                });

                it('Promise of an Array of two functions with the optional scope and arguments specified', function() {
                    var args = ['a', 'b', 'c'];

                    var fns = [verifyArgs(verifyScope(fn1, targetScope), args), verifyArgs(verifyScope(fn2, targetScope), args)];

                    promise = Deferred.parallel(Deferred.resolved(fns), targetScope, 'a', 'b', 'c');

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1, 2], true);
                });

                it('Promise of an Array of three functions', function() {
                    var fns = [fn1, fn2, fn3];

                    promise = Deferred.parallel(Deferred.resolved(fns));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1, 2, 3], true);
                });

                it('Promise of an Array of three functions with the optional scope specified', function() {
                    var fns = [verifyScope(fn1, targetScope), verifyScope(fn2, targetScope), verifyScope(fn3, targetScope)];

                    promise = Deferred.parallel(Deferred.resolved(fns), targetScope);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1, 2, 3], true);
                });

                it('Promise of an Array of three functions with the optional scope and arguments specified', function() {
                    var args = ['a', 'b', 'c'];

                    var fns = [verifyArgs(verifyScope(fn1, targetScope), args), verifyArgs(verifyScope(fn2, targetScope), args), verifyArgs(verifyScope(fn3, targetScope), args)];

                    promise = Deferred.parallel(Deferred.resolved(fns), targetScope, 'a', 'b', 'c');

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, [1, 2, 3], true);
                });
            });

            describe('returns a new Promise that will reject with the Error associated with the specified rejected Promise of an Array of functions', function() {
                it('Error: error message', function() {
                    promise = Deferred.parallel(Deferred.rejected(new Error('error message')));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'error message');
                });
            });

            describe('returns a new Promise that will reject with the associated Error if any of the specified Array of functions throws an Error', function() {
                function brokenFn() {
                    throw new Error('Error message');
                }

                it('Array with one function that throws an Error', function() {
                    var fns = [brokenFn];

                    promise = Deferred.parallel(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });

                it('Array with one function and one function that throws an Error', function() {
                    var fns = [fn1, brokenFn];

                    promise = Deferred.parallel(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });

                it('Array with two functions and one function that throws an Error', function() {
                    var fns = [fn1, fn2, brokenFn];

                    promise = Deferred.parallel(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });
            });

            describe('returns a new Promise that will reject with the associated Error if any of the specified Promise of an Array of functions throws an Error', function() {
                function brokenFn() {
                    throw new Error('Error message');
                }

                it('Promise of an Array with one function that throws an Error', function() {
                    var fns = [brokenFn];

                    promise = Deferred.parallel(Deferred.resolved(fns));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });

                it('Promise of an Array with one function and one function that throws an Error', function() {
                    var fns = [fn1, brokenFn];

                    promise = Deferred.parallel(Deferred.resolved(fns));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });

                it('Promise of an Array with two functions and one function that throws an Error', function() {
                    var fns = [fn1, fn2, brokenFn];

                    promise = Deferred.parallel(Deferred.resolved(fns));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });
            });

            describe('returns a new Promise that will reject with the associated Error if any of the specified Array of functions returns a rejected Promise', function() {
                function rejectFn() {
                    return Deferred.rejected(new Error('Error message'));
                }

                it('Array with one function that returns a rejected Promise', function() {
                    var fns = [rejectFn];

                    promise = Deferred.parallel(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });

                it('Array with one function and one function that returns a rejected Promise', function() {
                    var fns = [fn1, rejectFn];

                    promise = Deferred.parallel(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });

                it('Array with two functions and one function that returns a rejected Promise', function() {
                    var fns = [fn1, fn2, rejectFn];

                    promise = Deferred.parallel(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });
            });

            describe('returns a new Promise that will reject with the associated Error if any of the specified Promise of an Array of functions returns a rejected Promise', function() {
                function rejectFn() {
                    return Deferred.rejected(new Error('Error message'));
                }

                it('Promise of an Array with one function that returns a rejected Promise', function() {
                    var fns = [rejectFn];

                    promise = Deferred.parallel(Deferred.resolved(fns));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });

                it('Promise of an Array with one function and one function that returns a rejected Promise', function() {
                    var fns = [fn1, rejectFn];

                    promise = Deferred.parallel(Deferred.resolved(fns));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });

                it('Promise of an Array with two functions and one function that returns a rejected Promise', function() {
                    var fns = [fn1, fn2, rejectFn];

                    promise = Deferred.parallel(Deferred.resolved(fns));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });
            });

            describe('returns a new Promise that will reject with the associated Error if any of the items in the specified Array is not a function', function() {
                it('Array with one non-function value', function() {
                    var fns = [1];

                    promise = Deferred.parallel(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Invalid parameter: expected a function.');
                });

                it('Array with one function and one non-function value', function() {
                    var fns = [fn1, 1];

                    promise = Deferred.parallel(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Invalid parameter: expected a function.');
                });

                it('Array with two functions and one non-function value', function() {
                    var fns = [fn1, fn2, 1];

                    promise = Deferred.parallel(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Invalid parameter: expected a function.');
                });
            });

            describe('returns a new Promise that will reject with the associated Error if any of the items in the specified resolved Promise of an Array is not a function ', function() {
                it('Promise of an Array with one non-function value', function() {
                    var fns = [1];

                    promise = Deferred.parallel(Deferred.resolved(fns));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Invalid parameter: expected a function.');
                });

                it('Promise of an Array with one function and one non-function value', function() {
                    var fns = [fn1, 1];

                    promise = Deferred.parallel(Deferred.resolved(fns));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Invalid parameter: expected a function.');
                });

                it('Promise of an Array with two functions and one non-function value', function() {
                    var fns = [fn1, fn2, 1];

                    promise = Deferred.parallel(Deferred.resolved(fns));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Invalid parameter: expected a function.');
                });
            });

            describe('throws an Error if anything other than Array or Promise of an Array is specified as the first parameter', function() {
                it('No parameters', function() {
                    expect(function() {
                        return Deferred.parallel();
                    }).toThrow('Invalid parameter: expected an Array or Promise of an Array.');
                });

                it('A non-Array parameter', function() {
                    expect(function() {
                        return Deferred.parallel(1);
                    }).toThrow('Invalid parameter: expected an Array or Promise of an Array.');
                });
            });
        }); // parallel

        describe('pipeline()', function() {
            function createAppenderFn(v) {
                return function(x) {
                    return x ? x + v : v;
                };
            }

            describe('returns a new Promise that will resolve with the result returned by calling the specified Array of functions as a pipeline', function() {
                it('Empty Array', function() {
                    var fns = [];

                    promise = Deferred.pipeline(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, void 0, true);
                });

                it('Empty Array with an initial value', function() {
                    var fns = [];

                    promise = Deferred.pipeline(fns, 'initial value');

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, 'initial value', true);
                });

                it('Empty Array with an initial value and scope', function() {
                    var fns = [];

                    promise = Deferred.pipeline(fns, 'initial value');

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, 'initial value', true);
                });

                it('Array with one function', function() {
                    var fns = [createAppenderFn('a')];

                    promise = Deferred.pipeline(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, 'a', true);
                });

                it('Array with one function with an initial value', function() {
                    var fns = [createAppenderFn('b')];

                    promise = Deferred.pipeline(fns, 'a');

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, 'ab', true);
                });

                it('Array with one function with an initial value and scope', function() {
                    var fns = [verifyScope(createAppenderFn('b'), targetScope)];

                    promise = Deferred.pipeline(fns, 'a', targetScope);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, 'ab', true);
                });

                it('Array of two functions', function() {
                    var fns = [createAppenderFn('a'), createAppenderFn('b')];

                    promise = Deferred.pipeline(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, 'ab', true);
                });

                it('Array of two functions with an initial value', function() {
                    var fns = [createAppenderFn('b'), createAppenderFn('c')];

                    promise = Deferred.pipeline(fns, 'a');

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, 'abc', true);
                });

                it('Array of two functions with an initial value and scope', function() {
                    var fns = [verifyScope(createAppenderFn('b'), targetScope), verifyScope(createAppenderFn('c'), targetScope)];

                    promise = Deferred.pipeline(fns, 'a', targetScope);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, 'abc', true);
                });

                it('Array of three functions', function() {
                    var fns = [createAppenderFn('a'), createAppenderFn('b'), createAppenderFn('c')];

                    promise = Deferred.pipeline(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, 'abc', true);
                });

                it('Array of three functions with an initial value', function() {
                    var fns = [createAppenderFn('b'), createAppenderFn('c'), createAppenderFn('d')];

                    promise = Deferred.pipeline(fns, 'a');

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, 'abcd', true);
                });

                it('Array of three functions with an initial value and scope', function() {
                    var fns = [verifyScope(createAppenderFn('b'), targetScope), verifyScope(createAppenderFn('c'), targetScope), verifyScope(createAppenderFn('d'), targetScope)];

                    promise = Deferred.pipeline(fns, 'a', targetScope);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, 'abcd', true);
                });
            });

            describe('returns a new Promise that will resolve with the result returned by calling the specified Promise of an Array of functions as a pipeline', function() {
                it('Promise of an empty Array', function() {
                    var fns = [];

                    promise = Deferred.pipeline(Deferred.resolved(fns));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, void 0, true);
                });

                it('Promise of an empty Array with an initial value', function() {
                    var fns = [];

                    promise = Deferred.pipeline(Deferred.resolved(fns), 'initial value');

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, 'initial value', true);
                });

                it('Promise of an empty Array with an initial value and scope', function() {
                    var fns = [];

                    promise = Deferred.pipeline(Deferred.resolved(fns), 'initial value');

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, 'initial value', true);
                });

                it('Promise of an Array with one function', function() {
                    var fns = [createAppenderFn('a')];

                    promise = Deferred.pipeline(Deferred.resolved(fns));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, 'a', true);
                });

                it('Promise of an Array with one function with an initial value', function() {
                    var fns = [createAppenderFn('b')];

                    promise = Deferred.pipeline(Deferred.resolved(fns), 'a');

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, 'ab', true);
                });

                it('Promise of an Array with one function with an initial value and scope', function() {
                    var fns = [verifyScope(createAppenderFn('b'), targetScope)];

                    promise = Deferred.pipeline(Deferred.resolved(fns), 'a', targetScope);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, 'ab', true);
                });

                it('Promise of an Array of two functions', function() {
                    var fns = [createAppenderFn('a'), createAppenderFn('b')];

                    promise = Deferred.pipeline(Deferred.resolved(fns));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, 'ab', true);
                });

                it('Promise of an Array of two functions with an initial value', function() {
                    var fns = [createAppenderFn('b'), createAppenderFn('c')];

                    promise = Deferred.pipeline(Deferred.resolved(fns), 'a');

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, 'abc', true);
                });

                it('Promise of an Array of two functions with an initial value and scope', function() {
                    var fns = [verifyScope(createAppenderFn('b'), targetScope), verifyScope(createAppenderFn('c'), targetScope)];

                    promise = Deferred.pipeline(Deferred.resolved(fns), 'a', targetScope);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, 'abc', true);
                });

                it('Promise of an Array of three functions', function() {
                    var fns = [createAppenderFn('a'), createAppenderFn('b'), createAppenderFn('c')];

                    promise = Deferred.pipeline(Deferred.resolved(fns));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, 'abc', true);
                });

                it('Promise of an Array of three functions with an initial value', function() {
                    var fns = [createAppenderFn('b'), createAppenderFn('c'), createAppenderFn('d')];

                    promise = Deferred.pipeline(Deferred.resolved(fns), 'a');

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, 'abcd', true);
                });

                it('Promise of an Array of three functions with an initial value and scope', function() {
                    var fns = [verifyScope(createAppenderFn('b'), targetScope), verifyScope(createAppenderFn('c'), targetScope), verifyScope(createAppenderFn('d'), targetScope)];

                    promise = Deferred.pipeline(Deferred.resolved(fns), 'a', targetScope);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyResolvesTo(promise, 'abcd', true);
                });
            });

            describe('returns a new Promise that will reject with the Error associated with the specified rejected Promise of an Array of functions', function() {
                it('Error: error message', function() {
                    promise = Deferred.pipeline(Deferred.rejected(new Error('error message')));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'error message');
                });
            });

            describe('returns a new Promise that will reject with the associated Error if any of the specified Array of functions throws an Error', function() {
                function brokenFn() {
                    throw new Error('Error message');
                }

                it('Array with one function that throws an Error', function() {
                    var fns = [brokenFn];

                    promise = Deferred.pipeline(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });

                it('Array with one function and one function that throws an Error', function() {
                    var fns = [createAppenderFn('a'), brokenFn];

                    promise = Deferred.pipeline(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });

                it('Array with two functions and one function that throws an Error', function() {
                    var fns = [createAppenderFn('a'), createAppenderFn('b'), brokenFn];

                    promise = Deferred.pipeline(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });
            });

            describe('returns a new Promise that will reject with the associated Error if any of the specified Promise of an Array of functions throws an Error', function() {
                function brokenFn() {
                    throw new Error('Error message');
                }

                it('Promise of an Array with one function that throws an Error', function() {
                    var fns = [brokenFn];

                    promise = Deferred.pipeline(Deferred.resolved(fns));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });

                it('Promise of an Array with one function and one function that throws an Error', function() {
                    var fns = [createAppenderFn('a'), brokenFn];

                    promise = Deferred.pipeline(Deferred.resolved(fns));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });

                it('Promise of an Array with two functions and one function that throws an Error', function() {
                    var fns = [createAppenderFn('a'), createAppenderFn('b'), brokenFn];

                    promise = Deferred.pipeline(Deferred.resolved(fns));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });
            });

            describe('returns a new Promise that will reject with the associated Error if any of the specified Array of functions returns a rejected Promise', function() {
                function rejectFn() {
                    return Deferred.rejected(new Error('Error message'));
                }

                it('Array with one function that returns a rejected Promise', function() {
                    var fns = [rejectFn];

                    promise = Deferred.pipeline(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });

                it('Array with one function and one function that returns a rejected Promise', function() {
                    var fns = [createAppenderFn('a'), rejectFn];

                    promise = Deferred.pipeline(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });

                it('Array with two functions and one function that returns a rejected Promise', function() {
                    var fns = [createAppenderFn('a'), createAppenderFn('b'), rejectFn];

                    promise = Deferred.pipeline(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });
            });

            describe('returns a new Promise that will reject with the associated Error if any of the specified Promise of an Array of functions returns a rejected Promise', function() {
                function rejectFn() {
                    return Deferred.rejected(new Error('Error message'));
                }

                it('Array with one function that returns a rejected Promise', function() {
                    var fns = [rejectFn];

                    promise = Deferred.pipeline(Deferred.resolved(fns));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });

                it('Array with one function and one function that returns a rejected Promise', function() {
                    var fns = [createAppenderFn('a'), rejectFn];

                    promise = Deferred.pipeline(Deferred.resolved(fns));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });

                it('Array with two functions and one function that returns a rejected Promise', function() {
                    var fns = [createAppenderFn('a'), createAppenderFn('b'), rejectFn];

                    promise = Deferred.pipeline(Deferred.resolved(fns));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Error message');
                });
            });

            describe('returns a new Promise that will reject with the associated Error if any of the items in the specified Array is not a function', function() {
                it('Array with one non-function value', function() {
                    var fns = [1];

                    promise = Deferred.pipeline(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Invalid parameter: expected a function.');
                });

                it('Array with one function and one non-function value', function() {
                    var fns = [createAppenderFn('a'), 1];

                    promise = Deferred.pipeline(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Invalid parameter: expected a function.');
                });

                it('Array with two functions and one non-function value', function() {
                    var fns = [createAppenderFn('a'), createAppenderFn('b'), 1];

                    promise = Deferred.pipeline(fns);

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Invalid parameter: expected a function.');
                });
            });

            describe('returns a new Promise that will reject with the associated Error if any of the items in the specified resolved Promise of an Array is not a function ', function() {
                it('Promise of an Array with one non-function value', function() {
                    var fns = [1];

                    promise = Deferred.pipeline(Deferred.resolved(fns));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Invalid parameter: expected a function.');
                });

                it('Promise of an Array with one function and one non-function value', function() {
                    var fns = [createAppenderFn('a'), 1];

                    promise = Deferred.pipeline(Deferred.resolved(fns));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Invalid parameter: expected a function.');
                });

                it('Promise of an Array with two functions and one non-function value', function() {
                    var fns = [createAppenderFn('a'), createAppenderFn('b'), 1];

                    promise = Deferred.pipeline(Deferred.resolved(fns));

                    expect(promise instanceof ExtPromise).toBe(true);
                    eventuallyRejectedWith(promise, Error, 'Invalid parameter: expected a function.');
                });
            });

            describe('throws an Error if anything other than Array or Promise of an Array is specified as the first parameter', function() {
                it('No parameters', function() {
                    expect(function() {
                        return Deferred.pipeline();
                    }).toThrow('Invalid parameter: expected an Array or Promise of an Array.');
                });

                it('A non-Array parameter', function() {
                    expect(function() {
                        return Deferred.pipeline(1);
                    }).toThrow('Invalid parameter: expected an Array or Promise of an Array.');
                });
            });
        });  // pipeline
    });  // Extras
});

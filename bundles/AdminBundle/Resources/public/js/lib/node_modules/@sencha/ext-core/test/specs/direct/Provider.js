topSuite("Ext.direct.Provider", ['Ext.direct.*'], function() {
    var ajaxSpy, provider, connectSpy, disconnectSpy;

    function makeProvider(config) {
        provider = new Ext.direct.Provider(config);

        return provider;
    }

    function makeRequest(config) {
        var abortSpy = jasmine.createSpy('abort');

        var request = Ext.apply({
            id: Ext.id(),
            abort: abortSpy
        }, config);

        return request;
    }

    beforeEach(function() {
        ajaxSpy = spyOn(Ext.Ajax, 'request').andCallFake(function(request) {
            return request;
        });

        makeProvider();

        spyOn(provider, 'doConnect');
        spyOn(provider, 'doDisconnect');

        connectSpy = jasmine.createSpy('connect');
        disconnectSpy = jasmine.createSpy('disconnect');

        provider.on('connect', connectSpy);
        provider.on('disconnect', disconnectSpy);
    });

    afterEach(function() {
        if (provider) {
            provider.destroy();
        }

        Ext.direct.Manager.clearAllMethods();

        ajaxSpy = provider = connectSpy = disconnectSpy = null;
    });

    describe("ids", function() {
        it("should auto-assign id when not configured with one", function() {
            expect(/^provider-/.test(provider.id)).toBe(true);
        });

        it("should not assign auto id when configured", function() {
            provider.destroy();

            makeProvider({ id: 'foo' });

            expect(provider.id).toBe('foo');
        });
    });

    describe("destroy", function() {
        var destroySpy;

        beforeEach(function() {
            spyOn(provider, 'disconnect').andCallThrough();

            provider.destroy();
        });

        it("should disconnect when called first time", function() {
            expect(provider.disconnect).toHaveBeenCalled();
        });

        it("should force disconnect", function() {
            var args = provider.disconnect.mostRecentCall.args;

            expect(args[0]).toBe(true);
        });

        it("should set destroyed flag", function() {
            expect(provider.destroyed).toBe(true);
        });

        it("should not disconnect when called more than once", function() {
            provider.destroy();

            expect(provider.disconnect.callCount).toBe(1);
        });
    });

    describe("isConnected", function() {
        it("should return false when subscribers === 0", function() {
            expect(provider.isConnected()).toBe(false);
        });

        it("should return true when subscribers > 0", function() {
            provider.subscribers = 1;

            expect(provider.isConnected()).toBe(true);
        });
    });

    describe("connect", function() {
        describe("first time", function() {
            beforeEach(function() {
                provider.connect();
            });

            it("should call doConnect", function() {
                expect(provider.doConnect).toHaveBeenCalled();
            });

            it("should fire connect event", function() {
                expect(connectSpy).toHaveBeenCalled();
            });

            it("should increment subscribers", function() {
                expect(provider.subscribers).toBe(1);
            });
        });

        describe("after first time", function() {
            beforeEach(function() {
                provider.subscribers = 1;
                provider.connect();
            });

            it("should not call doConnect", function() {
                expect(provider.doConnect).not.toHaveBeenCalled();
            });

            it("should not fire connect event", function() {
                expect(connectSpy).not.toHaveBeenCalled();
            });

            it("should increment subscribers", function() {
                expect(provider.subscribers).toBe(2);
            });
        });
    });

    describe("disconnect", function() {
        describe("when subscribers == 2", function() {
            beforeEach(function() {
                provider.subscribers = 2;
            });

            describe("not forced", function() {
                beforeEach(function() {
                    provider.disconnect();
                });

                it("should not call doDisconnect", function() {
                    expect(provider.doDisconnect).not.toHaveBeenCalled();
                });

                it("should not fire disconnect event", function() {
                    expect(disconnectSpy).not.toHaveBeenCalled();
                });

                it("should decrement subscribers", function() {
                    expect(provider.subscribers).toBe(1);
                });
            });

            describe("forced", function() {
                beforeEach(function() {
                    provider.disconnect(true);
                });

                it("should call doDisconnect", function() {
                    expect(provider.doDisconnect).toHaveBeenCalled();
                });

                it("should fire disconnect event", function() {
                    expect(disconnectSpy).toHaveBeenCalled();
                });

                it("should reset subscribers to 0", function() {
                    expect(provider.subscribers).toBe(0);
                });
            });
        });

        describe("when subscribers == 1", function() {
            beforeEach(function() {
                provider.subscribers = 1;
                provider.disconnect();
            });

            it("should call doDisconnect", function() {
                expect(provider.doDisconnect).toHaveBeenCalled();
            });

            it("should fire disconnect event", function() {
                expect(disconnectSpy).toHaveBeenCalled();
            });

            it("should decrement subscribers", function() {
                expect(provider.subscribers).toBe(0);
            });
        });

        describe("when subscribers == 0", function() {
            describe("not forced", function() {
                beforeEach(function() {
                    provider.disconnect();
                });

                it("should not call doDisconnect", function() {
                    expect(provider.doDisconnect).not.toHaveBeenCalled();
                });

                it("should not fire disconnect event", function() {
                    expect(disconnectSpy).not.toHaveBeenCalled();
                });

                it("should not decrement subscribers", function() {
                    expect(provider.subscribers).toBe(0);
                });
            });

            describe("forced", function() {
                beforeEach(function() {
                    provider.disconnect(true);
                });

                it("should call doDisconnect", function() {
                    expect(provider.doDisconnect).toHaveBeenCalled();
                });
            });
        });
    });

    describe("Ajax requests", function() {
        var request;

        beforeEach(function() {
            provider.doDisconnect.andCallThrough();

            request = makeRequest();
            provider.sendAjaxRequest(request);
        });

        afterEach(function() {
            request = null;
        });

        it("should add request to pending upon sending", function() {
            expect(provider.requests[request.id]).toBe(request);
            expect(Ext.Object.getKeys(provider.requests).length).toBe(1);
        });

        it("should remove request from pending when data is received", function() {
            provider.onData({}, true, { request: request });

            expect(provider.requests[request.id]).not.toBeDefined();
            expect(Ext.Object.getKeys(provider.requests).length).toBe(0);
        });

        it("should abort and remove pending requests upon destruction", function() {
            expect(request.abort).not.toHaveBeenCalled();

            provider.destroy();

            expect(Ext.Object.getKeys(provider.requests).length).toBe(0);
            expect(request.abort).toHaveBeenCalled();
        });
    });
});

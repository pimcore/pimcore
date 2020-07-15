topSuite('Ext.util.History', function() {
    var HistoryUtil = Ext.util.History;

    beforeEach(function() {
        Ext.testHelper.hash.init();
    });

    afterEach(function() {
        Ext.testHelper.hash.reset();
    });

    function createSetAddReplaceSuites(fn) {
        describe(fn, function() {
            function createSuites(hashbang) {
                describe(hashbang ? 'hashbang' : 'hash', function() {
                    var manualTest = hashbang ? '#!foo' : '#foo';

                    beforeEach(function() {
                        HistoryUtil.hashbang = hashbang;
                    });

                    function createSpecs(name, input) {
                        (Ext.isIE8 && fn === 'replace' ? xit : it)(name, function() {
                            HistoryUtil[fn](input);

                            expect(HistoryUtil.win.location.hash).toBe(manualTest);
                            expect(HistoryUtil.currentToken).toBe('foo');
                            expect(HistoryUtil.getHash('foo'));
                            expect(HistoryUtil.getToken('foo'));
                        });
                    }

                    createSpecs('should set hash', 'foo');
                    createSpecs('should set hash prefixed with #', '#foo');
                    createSpecs('should set hash prefixed with #!', '#!foo');
                });
            }

            createSuites();
            createSuites(true);
        });
    }

    describe('construction', function() {
        it('should be instantiated', function() {
            expect(HistoryUtil.isInstance).toBe(true);
        });

        it('should be observable', function() {
            expect(HistoryUtil.isObservable).toBe(true);
        });
    });

    createSetAddReplaceSuites('setHash');
    createSetAddReplaceSuites('add');
    createSetAddReplaceSuites('replace');

    describe('getHash', function() {
        it('should be an empty string', function() {
            expect(HistoryUtil.getHash()).toBe('');
        });

        it('should not return prefixed with #', function() {
            HistoryUtil.win.location.hash = '#foo';

            expect(HistoryUtil.getHash()).toBe('foo');
        });

        it('should not return prefixed with #!', function() {
            HistoryUtil.win.location.hash = '#!foo';

            expect(HistoryUtil.getHash()).toBe('foo');
        });
    });

    describe('getToken', function() {
        it('should be an empty string', function() {
            expect(HistoryUtil.getToken()).toBe('');
        });

        it('should not return prefixed with #', function() {
            HistoryUtil.setHash('#foo');

            expect(HistoryUtil.getToken()).toBe('foo');
        });

        it('should not return prefixed with #!', function() {
            HistoryUtil.setHash('#!foo');

            expect(HistoryUtil.getToken()).toBe('foo');
        });
    });

    describe('handleStateChange', function() {
        it('should set currentToken', function() {
            HistoryUtil.handleStateChange('foo');

            expect(HistoryUtil.currentToken).toBe('foo');
        });

        it('should set currentToken with ! prefixed', function() {
            HistoryUtil.handleStateChange('!foo');

            expect(HistoryUtil.currentToken).toBe('foo');
        });

        it('should fire change event', function() {
            var spy = spyOn({
                test: Ext.emptyFn
            }, 'test');

            HistoryUtil.on('change', spy, {
                single: true
            });

            HistoryUtil.handleStateChange('foo');

            expect(spy).toHaveBeenCalledWith('foo');
        });

        it('should fire change event prefixed with !', function() {
            var spy = spyOn({
                test: Ext.emptyFn
            }, 'test');

            HistoryUtil.on('change', spy, {
                single: true
            });

            HistoryUtil.handleStateChange('!foo');

            expect(spy).toHaveBeenCalledWith('foo');
        });
    });

    describe('onHashChange', function() {
        it('should set currentToken', function() {
            HistoryUtil.add('foo');
            HistoryUtil.onHashChange();

            expect(HistoryUtil.currentToken).toBe('foo');
        });

        it('should set currentToken with ! prefixed', function() {
            HistoryUtil.add('!foo');
            HistoryUtil.onHashChange();

            expect(HistoryUtil.currentToken).toBe('foo');
        });

        it('should fire change event', function() {
            var spy = spyOn({
                test: Ext.emptyFn
            }, 'test');

            HistoryUtil.on('change', spy, {
                single: true
            });

            HistoryUtil.add('foo');
            HistoryUtil.onHashChange();

            expect(spy).toHaveBeenCalledWith('foo');
        });

        it('should fire change event prefixed with !', function() {
            var spy = spyOn({
                test: Ext.emptyFn
            }, 'test');

            HistoryUtil.on('change', spy, {
                single: true
            });

            HistoryUtil.add('!foo');
            HistoryUtil.onHashChange();

            expect(spy).toHaveBeenCalledWith('foo');
        });
    });

    (Ext.isIE8 ? xdescribe : describe)('back', function() {
        it('should not have a hash anymore', function() {
            HistoryUtil.add('foo');

            HistoryUtil.back();

            expect(HistoryUtil.getHash()).toBe('');
        });

        it('should handle replace on no browser stack', function() {
            expect(HistoryUtil.getHash()).toBe('');

            var set = HistoryUtil.replace('foo');

            HistoryUtil.back();

            expect(set).toBe(true);
            expect(HistoryUtil.getHash()).toBe('foo');
        });

        it('should go back to last hash', function() {
            HistoryUtil.add('foo');
            HistoryUtil.add('bar');

            expect(HistoryUtil.getHash()).toBe('bar');

            HistoryUtil.back();

            expect(HistoryUtil.getHash()).toBe('foo');
        });
    });

    (Ext.isIE8 ? xdescribe : describe)('forward', function() {
        it('should go forward', function() {
            HistoryUtil.add('foo');

            HistoryUtil.back();

            expect(HistoryUtil.getHash()).toBe('');

            HistoryUtil.forward();

            expect(HistoryUtil.getHash()).toBe('foo');
        });

        it('should go back to last hash', function() {
            HistoryUtil.add('foo');
            HistoryUtil.add('bar');

            expect(HistoryUtil.getHash()).toBe('bar');

            HistoryUtil.back();

            expect(HistoryUtil.getHash()).toBe('foo');

            HistoryUtil.forward();

            expect(HistoryUtil.getHash()).toBe('bar');
        });
    });
});

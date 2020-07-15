topSuite("Ext.util.TaskRunner", [
    'Ext.GlobalEvents'
], function() {
    var spy, runner, task;

    describe("idle event", function() {
        var calls;

        function onIdle() {
            var timer = Ext.Timer.firing;

            if (timer && !timer.ours) {
                var s = timer.creator;

                if (timer.runner) {
                    Ext.each(timer.runner.fired, function(task) {
                        s += '\n-----------------------';
                        s += 'Task:';
                        s += task.creator;
                        s += '\n-----------------------';
                    });
                }

                expect(s).toBe('not running');
            }
            else {
                expect(new Error().stack).toBe('not called');
            }
        }

        beforeEach(function() {
            Ext.on('idle', onIdle);
            calls = [];
        });

        afterEach(function() {
            Ext.un('idle', onIdle);
            calls = null;

            if (runner) {
                runner.destroy();
            }

            task = runner = spy = null;
        });

        // https://sencha.jira.com/browse/EXTJS-19133
        // IE8 does not allow capturing stack trace so always fails
        // This test is also fails consistently on tablets
        (Ext.isIE8 || Ext.isiOS || Ext.isAndroid ? xit : it)("it should not fire idle event when configured", function() {
            runs(function() {
                runner = new Ext.util.TaskRunner({
                    fireIdleEvent: false
                });

                task = runner.newTask({
                    fireIdleEvent: false,
                    interval: 10,
                    run: Ext.emptyFn
                });

                task.start();

                var timer = Ext.Timer.get(runner.timerId);

                if (timer) {
                    timer.ours = true;
                }
            });

            // This should be enough to trip the event, happens fairly often in IE
            waits(300);

            runs(function() {
                expect(calls).toEqual([]);
            });
        });
    });

    describe("args", function() {
        beforeEach(function() {
            spy = jasmine.createSpy();
            runner = new Ext.util.TaskRunner();
        });

        afterEach(function() {
            if (runner) {
                runner.destroy();
            }

            task = runner = spy = null;
        });

        it("should pass the args Array as parameters of the run method", function() {
            task = runner.newTask({
                interval: 10,
                run: spy,
                args: ['Foo'],
                repeat: 1
            });

            task.start();

            waitsFor(function() {
                return spy.callCount;
            });

            runs(function() {
                expect(spy.mostRecentCall.args).toEqual(['Foo']);
            });
        });

        it("should add the current count when configured with addCountToArgs true", function() {
            task = runner.newTask({
                interval: 10,
                run: spy,
                addCountToArgs: true,
                args: ['Foo'],
                repeat: 1
            });

            task.start();

            waitsFor(function() {
                return spy.callCount;
            });

            runs(function() {
                expect(spy.mostRecentCall.args).toEqual(['Foo', 1]);
            });
        });

        it("should respect the repeat number when configured with args", function() {
            task = runner.newTask({
                interval: 10,
                run: spy,
                args: ['Foo'],
                repeat: 2
            });

            task.start();

            waitsFor(function() {
                return task.stopped;
            });

            runs(function() {
                expect(spy.callCount).toBe(2);
            });
        });
    });
});

// BelongsTo is not a real class, but is an alternate way of declaring ManyToOne.
// The purpose of these tests is to check that they set everything up correctly,
// functionality tested in ManyToOne.
// false in dependencies means don't attempt to load "Ext.data.schema.BelongsTo"
topSuite("Ext.data.schema.BelongsTo", [false, 'Ext.data.ArrayStore'], function() {

    var Thread, Post, User;

    function defineThread(options) {
        var cfg = {
            extend: 'Ext.data.Model',
            fields: ['id', 'title']
        };

        if (options) {
            cfg = Ext.apply(cfg, options);
        }

        Thread = Ext.define('spec.Thread', cfg);
    }

    function definePost(options) {
        var cfg = {
            extend: 'Ext.data.Model',
            fields: ['id', 'title']
        };

        if (options) {
            cfg = Ext.apply(cfg, options);
        }

        Post = Ext.define('spec.Post', cfg);
    }

    function defineUser(options) {
        var cfg = {
            extend: 'Ext.data.Model',
            fields: ['id', 'title']
        };

        if (options) {
            cfg = Ext.apply(cfg, options);
        }

        User = Ext.define('spec.User', cfg);
    }

    beforeEach(function() {
        MockAjaxManager.addMethods();
        Ext.data.Model.schema.setNamespace('spec');
    });

    afterEach(function() {
        if (Post) {
            Ext.undefine('spec.Post');
            Post = null;
        }

        if (User) {
            Ext.undefine('spec.User');
        }

        if (Thread) {
            Ext.undefine('spec.Thread');
            Thread = null;
        }

        Ext.data.Model.schema.clear(true);
        MockAjaxManager.removeMethods();
    });

    function expectFn(name, o) {
        o = o || Post;
        expect(Ext.isFunction(o.prototype[name])).toBe(true);
    }

    function expectNotFn(name, o) {
        o = o || Post;
        expect(Ext.isFunction(o.prototype[name])).toBe(false);
    }

    describe("declarations", function() {
        describe("Configuration of model only", function() {
            it("should accept a string", function() {
                defineThread();
                definePost({
                    belongsTo: 'Thread'
                });
                expectFn('getThread');
                expectFn('setThread');
                expectFn('posts', Thread);
            });

            it("should accept an array of strings", function() {
                defineThread();
                defineUser();
                definePost({
                    belongsTo: ['Thread', 'User']
                });
                expectFn('getThread');
                expectFn('setThread');
                expectFn('getUser');
                expectFn('setUser');
                expectFn('posts', Thread);
                expectFn('posts', User);
            });

            it("should accept an object", function() {
                defineThread();
                definePost({
                    belongsTo: {
                        type: 'Thread'
                    }
                });
                expectFn('getThread');
                expectFn('setThread');
                expectFn('posts', Thread);
            });

            it("should accept an array of objects", function() {
                defineThread();
                defineUser();
                definePost({
                    belongsTo: [{
                        type: 'Thread'
                    }, {
                        type: 'User'
                    }]
                });
                expectFn('getThread');
                expectFn('setThread');
                expectFn('getUser');
                expectFn('setUser');
                expectFn('posts', Thread);
                expectFn('posts', User);
            });
        });

        describe("extra configurations", function() {
            describe("role", function() {
                it("should add the specified role", function() {
                    defineThread();
                    definePost({
                        belongsTo: {
                            type: 'Thread',
                            role: 'discussion'
                        }
                    });

                    expectFn('getDiscussion');
                    expectFn('setDiscussion');
                    expectFn('discussionPosts', Thread);
                });
            });

            describe("getterName", function() {
                it("should allow a custom getterName", function() {
                    defineThread();
                    definePost({
                        belongsTo: {
                            type: 'Thread',
                            getterName: 'getDiscussion'
                        }
                    });
                    expectFn('getDiscussion');
                    expectFn('setThread');
                    expectFn('posts', Thread);
                });
            });

            describe("setterName", function() {
                it("should allow a custom setterName", function() {
                    defineThread();
                    definePost({
                        belongsTo: {
                            type: 'Thread',
                            setterName: 'setDiscussion'
                        }
                    });
                    expectFn('setDiscussion');
                    expectFn('getThread');
                    expectFn('posts', Thread);
                });
            });

            describe("inverse", function() {
                it("should be able to declare inverse configs", function() {
                    defineThread();
                    definePost({
                        belongsTo: {
                            type: 'Thread',
                            inverse: {
                                role: 'comments'
                            }
                        }
                    });

                    expectFn('getThread');
                    expectFn('setThread');
                    expectFn('comments', Thread);
                    expectNotFn('posts', Thread);
                });
            });

            describe("parent", function() {
                it("should be able to set parent", function() {
                    defineThread();
                    definePost({
                        belongsTo: {
                            parent: 'Thread'
                        }
                    });

                    expectFn('getThread');
                    expectFn('setThread');
                    expectFn('posts', Thread);
                });
            });
        });

        describe("timing", function() {
            describe("when the owner class already exists", function() {
                it("should setup methods on both classes", function() {
                    defineThread();
                    definePost({
                        belongsTo: 'Thread'
                    });
                    expectFn('getThread');
                    expectFn('setThread');
                    expectFn('posts', Thread);
                });
            });

            describe("when the owner class does not exist", function() {
                it("should setup methods on both classes when the owner arrives", function() {
                    definePost({
                        belongsTo: 'Thread'
                    });
                    expectNotFn('getThread');
                    expectNotFn('setThread');
                    defineThread();
                    expectFn('getThread');
                    expectFn('setThread');
                    expectFn('posts', Thread);
                });
            });
        });
    });

    describe("instance related configs", function() {
        describe("associationKey", function() {
            it("should apply the associationKey for loading nested data", function() {
                defineThread();
                definePost({
                    belongsTo: {
                        type: 'Thread',
                        associationKey: 'discussion'
                    }
                });

                var post = Post.load(1);

                Ext.Ajax.mockCompleteWithData({
                    id: 1,
                    thread: {
                        id: 101
                    }
                });
                // Key is discussion, should be nothing
                expect(post.getThread()).toBeNull();

                post = Post.load(2);
                Ext.Ajax.mockCompleteWithData({
                    id: 2,
                    discussion: {
                        id: 101
                    }
                });
                expect(post.getThread().id).toBe(101);
            });
        });

        describe("parent", function() {
            it("should drop the child records when the owner is dropped", function() {
                defineThread();
                definePost({
                    belongsTo: {
                        parent: 'Thread'
                    }
                });

                var thread = new Thread({ id: 1 }),
                    post = new Post({ id: 101 });

                thread.posts().add(post);
                thread.drop();
                expect(post.dropped).toBe(true);
            });
        });
    });

    describe("legacy functionality", function() {
        describe("foreignKey", function() {
            it("should recognize a default foreignKey as entity_id", function() {
                defineThread();
                definePost({
                    fields: ['id', 'name', 'thread_id'],
                    belongsTo: 'Thread'
                });

                var thread = new Thread({ id: 1 }),
                    post = new Post({ id: 101 });

                thread.posts().add(post);
                expect(post.get('thread_id')).toBe(1);
            });

            it("should recognize a custom foreignKey as entity_id", function() {
                defineThread();
                definePost({
                    fields: ['id', 'name', 'customField'],
                    belongsTo: {
                        type: 'Thread',
                        foreignKey: 'customField'
                    }
                });

                var thread = new Thread({ id: 1 }),
                    post = new Post({ id: 101 });

                thread.posts().add(post);
                expect(post.get('customField')).toBe(1);
            });
        });

        it("should use the name parameter as the role", function() {
            defineThread();
            definePost({
                belongsTo: {
                    model: 'Thread',
                    name: 'discussion'
                }
            });
            expectFn('getDiscussion');
            expectFn('setDiscussion');
            expectFn('discussionPosts', Thread);
        });

        it("should use the associatedName parameter as the role", function() {
            defineThread();
            definePost({
                belongsTo: {
                    model: 'Thread',
                    associatedName: 'discussion'
                }
            });
            expectFn('getDiscussion');
            expectFn('setDiscussion');
            expectFn('discussionPosts', Thread);
        });

        it("should respect a role config when using model", function() {
            defineThread();
            definePost({
                belongsTo: {
                    model: 'Thread',
                    role: 'discussion'
                }
            });

            expectFn('getDiscussion');
            expectFn('setDiscussion');
            expectFn('discussionPosts', Thread);
        });
    });
});

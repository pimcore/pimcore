// HasMany is not a real class, but is an alternate way of declaring ManyToOne
// The purpose of these tests is to check that they set everything up correctly,
// functionality tested in ManyToOne.
// false in dependencies means don't attempt to load "Ext.data.schema.HasMany"
topSuite("Ext.data.schema.HasMany", [false, 'Ext.data.ArrayStore'], function() {

    var Thread, Post, Vote;

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

    function defineVote(options) {
        var cfg = {
            extend: 'Ext.data.Model',
            fields: ['id', 'title']
        };

        if (options) {
            cfg = Ext.apply(cfg, options);
        }

        Vote = Ext.define('spec.Vote', cfg);
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

        if (Vote) {
            Ext.undefine('spec.Vote');
        }

        if (Thread) {
            Ext.undefine('spec.Thread');
            Thread = null;
        }

        Ext.data.Model.schema.clear(true);
        MockAjaxManager.removeMethods();
    });

    function expectFn(name, o) {
        o = o || Thread;
        expect(Ext.isFunction(o.prototype[name])).toBe(true);
    }

    function expectNotFn(name, o) {
        o = o || Thread;
        expect(Ext.isFunction(o.prototype[name])).toBe(false);
    }

    describe("declarations", function() {
        describe("Configuration of model only", function() {
            it("should accept a string", function() {
                definePost();
                defineThread({
                    hasMany: 'Post'
                });
                expectFn('posts');
                expectFn('getThread', Post);
                expectFn('setThread', Post);
            });

            it("should accept an array of strings", function() {
                definePost();
                defineVote();
                defineThread({
                    hasMany: ['Post', 'Vote']
                });
                expectFn('posts');
                expectFn('votes');
                expectFn('getThread', Post);
                expectFn('setThread', Post);
                expectFn('getThread', Vote);
                expectFn('setThread', Vote);
            });

            it("should accept an object", function() {
                definePost();
                defineThread({
                    hasMany: {
                        type: 'Post'
                    }
                });
                expectFn('posts');
                expectFn('getThread', Post);
                expectFn('setThread', Post);
            });

            it("should accept an array of objects", function() {
                definePost();
                defineVote();
                defineThread({
                    hasMany: [{
                        type: 'Post'
                    }, {
                        type: 'Vote'
                    }]
                });
                expectFn('posts');
                expectFn('votes');
                expectFn('getThread', Post);
                expectFn('setThread', Post);
                expectFn('getThread', Vote);
                expectFn('setThread', Vote);
            });
        });

        describe("extra configurations", function() {
            describe("role", function() {
                it("should add the specified role", function() {
                    definePost();
                    defineThread({
                        hasMany: {
                            type: 'Post',
                            role: 'comments'
                        }
                    });

                    expectFn('comments');
                    expectNotFn('posts');
                    expectFn('getThread', Post);
                    expectFn('setThread', Post);
                });
            });

            describe("getterName", function() {
                it("should allow a custom getterName", function() {
                    definePost();
                    defineThread({
                        hasMany: {
                            type: 'Post',
                            getterName: 'comments'
                        }
                    });
                    expectFn('comments');
                    expectNotFn('posts');
                    expectFn('getThread', Post);
                    expectFn('setThread', Post);
                });
            });

            describe("inverse", function() {
                it("should be able to declare inverse configs", function() {
                    definePost();
                    defineThread({
                        hasMany: {
                            type: 'Post',
                            inverse: {
                                role: 'discussion'
                            }
                        }
                    });

                    expectFn('discussionPosts');
                    expectNotFn('posts');
                    expectFn('getDiscussion', Post);
                    expectFn('setDiscussion', Post);
                });
            });

            describe("child", function() {
                it("should be able to set child", function() {
                    definePost();
                    defineThread({
                        hasMany: {
                            child: 'Post'
                        }
                    });

                    expectFn('posts');
                    expectFn('getThread', Post);
                    expectFn('setThread', Post);
                });
            });
        });

        describe("timing", function() {
            describe("when the many class already exists", function() {
                it("should setup methods on both classes", function() {
                    definePost();
                    defineThread({
                        hasMany: 'Post'
                    });
                    expectFn('posts');
                    expectFn('getThread', Post);
                    expectFn('setThread', Post);
                });
            });

            describe("when the many class does not exist", function() {
                it("should setup methods on both classes when the many arrives", function() {
                    defineThread({
                        hasMany: 'Post'
                    });
                    expectNotFn('posts');
                    definePost();
                    expectFn('posts');
                    expectFn('getThread', Post);
                    expectFn('setThread', Post);
                });
            });
        });
    });

    describe("instance related configs", function() {
        describe("storeConfig", function() {
            it("should apply a storeConfig", function() {
                definePost();
                defineThread({
                    hasMany: {
                        type: 'Post',
                        storeConfig: {
                            autoSync: true
                        }
                    }
                });

                var thread = new Thread();

                expect(thread.posts().getAutoSync()).toBe(true);
            });
        });

        describe("associationKey", function() {
            it("should apply the associationKey for loading nested data", function() {
                definePost();
                defineThread({
                    hasMany: {
                        type: 'Post',
                        associationKey: 'comments'
                    }
                });

                var thread = Thread.load(1);

                Ext.Ajax.mockCompleteWithData({
                    id: 1,
                    posts: [{
                        id: 101
                    }, {
                        id: 102
                    }]
                });
                // Key is comments, should be nothing
                expect(thread.posts().getCount()).toBe(0);

                thread = Thread.load(2);
                Ext.Ajax.mockCompleteWithData({
                    id: 2,
                    comments: [{
                        id: 201
                    }, {
                        id: 202
                    }]
                });
                expect(thread.posts().getCount()).toBe(2);
            });
        });

        describe("child", function() {
            it("should drop the many records when the owner is dropped", function() {
                definePost();
                defineThread({
                    hasMany: {
                        child: 'Post'
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

    describe("references", function() {
        var thread;

        afterEach(function() {
            thread = null;
        });

        beforeEach(function() {
            definePost();
            defineThread({
                hasMany: 'Post'
            });

            thread = Thread.load(1);
            Ext.Ajax.mockCompleteWithData({
                id: 1,
                posts: [{
                    id: 101
                }, {
                    id: 102
                }]
            });
        });

        it("should have a reference to the parent record on load", function() {
            var posts = thread.posts();

            expect(posts.getAt(0).getThread()).toBe(thread);
            expect(posts.getAt(1).getThread()).toBe(thread);
        });

        it("should have a reference to the parent record on add", function() {
            var posts = thread.posts();

            posts.add({
                id: 103
            });
            expect(posts.getAt(2).getThread()).toBe(thread);
        });

        it("should clear the reference to the parent on remove", function() {
            var posts = thread.posts(),
                post = posts.getAt(0);

            posts.remove(post);
            expect(post.getThread()).toBeNull();
        });

        it("should clear the reference to the parent on removeAll", function() {
            var posts = thread.posts(),
                all = posts.getRange();

            posts.removeAll();
            expect(all[0].getThread()).toBeNull();
            expect(all[1].getThread()).toBeNull();
        });
    });

    describe("legacy functionality", function() {
        describe("foreignKey", function() {
            it("should recognize a default foreignKey as entity_id", function() {
                definePost({
                    fields: ['id', 'name', 'thread_id']
                });
                defineThread({
                    hasMany: 'Post'
                });

                var thread = new Thread({ id: 1 }),
                    post = new Post({ id: 101 });

                thread.posts().add(post);
                expect(post.get('thread_id')).toBe(1);
            });

            it("should recognize a custom foreignKey as entity_id", function() {
                definePost({
                    fields: ['id', 'name', 'customField']
                });
                defineThread({
                    hasMany: {
                        type: 'Post',
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
            definePost();
            defineThread({
                hasMany: {
                    model: 'Post',
                    name: 'comments'
                }
            });
            expectFn('comments');
            expectFn('getThread', Post);
            expectFn('setThread', Post);
        });

        it("should use the associatedName parameter as the role", function() {
            definePost();
            defineThread({
                hasMany: {
                    model: 'Post',
                    associatedName: 'comments'
                }
            });
            expectFn('comments');
        });

        it("should accept a storeConfig when given a name", function() {
            definePost();
            defineThread({
                hasMany: {
                    model: 'Post',
                    name: 'comments',
                    storeConfig: {
                        trackRemoved: false
                    }
                }
            });
            var thread = new Thread();

            expect(thread.comments().getTrackRemoved()).toBe(false);
        });

        it("should accept a storeConfig when given an associationKey", function() {
            definePost();
            defineThread({
                hasMany: {
                    model: 'Post',
                    associationKey: 'someValue',
                    storeConfig: {
                        trackRemoved: false
                    }
                }
            });
            var thread = new Thread();

            expect(thread.posts().getTrackRemoved()).toBe(false);
        });

        it("should respect a role config when using model", function() {
            definePost();
            defineThread({
                hasMany: {
                    model: 'Post',
                    role: 'comments'
                }
            });

            expectFn('comments');
            expectFn('getThread', Post);
            expectFn('setThread', Post);
        });
    });

});

// HasOne is not a real class, but is an alternate way of declaring OneToOne.
// The purpose of these tests is to check that they set everything up correctly,
// functionality tested in OneToOne.
// false in dependencies means don't attempt to load "Ext.data.schema.HasOne"
topSuite("Ext.data.schema.HasOne", [false, 'Ext.data.ArrayStore'], function() {

    var Key, User, Avatar;

    function defineUser(options) {
        var cfg = {
            extend: 'Ext.data.Model',
            fields: ['id', 'name']
        };

        if (options) {
            cfg = Ext.apply(cfg, options);
        }

        User = Ext.define('spec.User', cfg);
    }

    function defineKey(options) {
        var cfg = {
            extend: 'Ext.data.Model',
            fields: ['id', 'salt']
        };

        if (options) {
            cfg = Ext.apply(cfg, options);
        }

        Key = Ext.define('spec.Key', cfg);
    }

    function defineAvatar(options) {
        var cfg = {
            extend: 'Ext.data.Model',
            fields: ['id', 'src']
        };

        if (options) {
            cfg = Ext.apply(cfg, options);
        }

        Avatar = Ext.define('spec.Avatar', cfg);
    }

    beforeEach(function() {
        MockAjaxManager.addMethods();
        Ext.data.Model.schema.setNamespace('spec');
    });

    afterEach(function() {
        if (Avatar) {
            Ext.undefine('spec.Avatar');
            Avatar = null;
        }

        if (Key) {
            Ext.undefine('spec.Key');
        }

        if (User) {
            Ext.undefine('spec.User');
            User = null;
        }

        Ext.data.Model.schema.clear(true);
        MockAjaxManager.removeMethods();
    });

    function expectFn(name, o) {
        o = o || User;
        expect(Ext.isFunction(o.prototype[name])).toBe(true);
    }

    function expectNotFn(name, o) {
        o = o || User;
        expect(Ext.isFunction(o.prototype[name])).toBe(false);
    }

    describe("declarations", function() {
        describe("Configuration of model only", function() {
            it("should accept a string", function() {
                defineKey();
                defineUser({
                    hasOne: 'Key'
                });
                expectFn('getKey');
                expectFn('setKey');
                expectFn('getUser', Key);
                expectFn('setUser', Key);
            });

            it("should accept an array of strings", function() {
                defineKey();
                defineAvatar();
                defineUser({
                    hasOne: ['Key', 'Avatar']
                });
                expectFn('getKey');
                expectFn('setKey');
                expectFn('getUser', Key);
                expectFn('setUser', Key);
                expectFn('getUser', Avatar);
                expectFn('setUser', Avatar);
            });

            it("should accept an object", function() {
                defineKey();
                defineUser({
                    hasOne: {
                        type: 'Key'
                    }
                });
                expectFn('getKey');
                expectFn('setKey');
                expectFn('getUser', Key);
                expectFn('setUser', Key);
            });

            it("should accept an array of objects", function() {
                defineKey();
                defineAvatar();
                defineUser({
                    hasOne: [{
                        type: 'Key'
                    }, {
                        type: 'Avatar'
                    }]
                });
                expectFn('getKey');
                expectFn('setKey');
                expectFn('getUser', Key);
                expectFn('setUser', Key);
                expectFn('getUser', Avatar);
                expectFn('setUser', Avatar);
            });
        });

        describe("extra configurations", function() {
            describe("role", function() {
                it("should add the specified role", function() {
                    defineKey();
                    defineUser({
                        hasOne: {
                            type: 'Key',
                            role: 'authenticator'
                        }
                    });

                    expectFn('getAuthenticator');
                    expectFn('setAuthenticator');
                    expectFn('getAuthenticatorUser', Key);
                    expectFn('setAuthenticatorUser', Key);
                });
            });

            describe("getterName", function() {
                it("should allow a custom getterName", function() {
                    defineKey();
                    defineUser({
                        hasOne: {
                            type: 'Key',
                            getterName: 'getAuthenticator'
                        }
                    });
                    expectFn('getAuthenticator');
                    expectFn('setKey');
                    expectFn('getUser', Key);
                    expectFn('setUser', Key);
                });
            });

            describe("setterName", function() {
                it("should allow a custom setterName", function() {
                    defineKey();
                    defineUser({
                        hasOne: {
                            type: 'Key',
                            setterName: 'setAuthenticator'
                        }
                    });
                    expectFn('getKey');
                    expectFn('setAuthenticator');
                    expectFn('getUser', Key);
                    expectFn('setUser', Key);
                });
            });

            describe("inverse", function() {
                it("should be able to declare inverse configs", function() {
                    defineKey();
                    defineUser({
                        hasOne: {
                            type: 'Key',
                            inverse: {
                                role: 'owner'
                            }
                        }
                    });
                    expectFn('getKey');
                    expectFn('setKey');
                    expectFn('getOwner', Key);
                    expectFn('setOwner', Key);
                });
            });

            describe("child", function() {
                it("should be able to set child", function() {
                    defineKey();
                    defineUser({
                        hasOne: {
                            child: 'Key'
                        }
                    });
                    expectFn('getKey');
                    expectFn('setKey');
                    expectFn('getUser', Key);
                    expectFn('setUser', Key);
                });
            });
        });

        describe("timing", function() {
            describe("when the owner class already exists", function() {
                it("should setup methods on both classes", function() {
                    defineKey();
                    defineUser({
                        hasOne: 'Key'
                    });
                    expectFn('getKey');
                    expectFn('setKey');
                    expectFn('getUser', Key);
                    expectFn('setUser', Key);
                });
            });

            describe("when the owner class does not exist", function() {
                it("should setup methods on both classes when the owner arrives", function() {
                    defineUser({
                        hasOne: 'Key'
                    });
                    expectNotFn('getKey');
                    expectNotFn('setKey');
                    defineKey();
                    expectFn('getKey');
                    expectFn('setKey');
                    expectFn('getUser', Key);
                    expectFn('setUser', Key);
                });
            });
        });
    });

    describe("instance related configs", function() {
        describe("associationKey", function() {
            it("should apply the associationKey for loading nested data", function() {
                defineKey();
                defineUser({
                    hasOne: {
                        type: 'Key',
                        associationKey: 'authenticator'
                    }
                });

                var user = User.load(1);

                Ext.Ajax.mockCompleteWithData({
                    id: 1,
                    key: {
                        id: 101
                    }
                });
                // Key is discussion, should be nothing
                expect(user.getKey()).toBeNull();

                user = User.load(2);
                Ext.Ajax.mockCompleteWithData({
                    id: 2,
                    authenticator: {
                        id: 101
                    }
                });
                expect(user.getKey().id).toBe(101);
            });
        });

        describe("child", function() {
            it("should drop the child records when the owner is dropped", function() {
                defineKey();
                defineUser({
                    hasOne: {
                        child: 'Key'
                    }
                });

                var user = new User({ id: 1 }),
                    key = new Key({ id: 101 });

                user.setKey(key);
                user.drop();
                expect(key.dropped).toBe(true);
            });
        });
    });

    describe("legacy functionality", function() {
        describe("foreignKey", function() {
            it("should recognize a default foreignKey as entity_id", function() {
                defineKey();
                defineUser({
                    fields: ['id', 'name', 'key_id'],
                    hasOne: 'Key'
                });

                var user = new User({ id: 1 }),
                    key = new Key({ id: 101 });

                user.setKey(key);
                expect(user.get('key_id')).toBe(101);
            });

            it("should recognize a custom foreignKey as entity_id", function() {
                defineKey();
                defineUser({
                    fields: ['id', 'name', 'customField'],
                    hasOne: {
                        type: 'Key',
                        foreignKey: 'customField'
                    }
                });

                var user = new User({ id: 1 }),
                    key = new Key({ id: 101 });

                user.setKey(key);
                expect(user.get('customField')).toBe(101);
            });
        });

        it("should use the name parameter as the role", function() {
            defineKey();
            defineUser({
                hasOne: {
                    model: 'Key',
                    name: 'authenticator'
                }
            });
            expectFn('getAuthenticator');
            expectFn('setAuthenticator');
            expectFn('getAuthenticatorUser', Key);
            expectFn('setAuthenticatorUser', Key);
        });

        it("should use the associatedName parameter as the role", function() {
            defineKey();
            defineUser({
                hasOne: {
                    model: 'Key',
                    associatedName: 'authenticator'
                }
            });
            expectFn('getAuthenticator');
            expectFn('setAuthenticator');
            expectFn('getAuthenticatorUser', Key);
            expectFn('setAuthenticatorUser', Key);
        });

        it("should respect a role config when using model", function() {
            defineKey();
            defineUser({
                hasOne: {
                    model: 'Key',
                    role: 'authenticator'
                }
            });

            expectFn('getAuthenticator');
            expectFn('setAuthenticator');
            expectFn('getAuthenticatorUser', Key);
            expectFn('setAuthenticatorUser', Key);
        });
    });
});

/**
 * This class is the root stub for managing a `ViewModel`.
 * @private
 */
Ext.define('Ext.app.bind.RootStub', {
    extend: 'Ext.app.bind.AbstractStub',
    requires: [
        'Ext.app.bind.LinkStub',
        'Ext.app.bind.Stub'
    ],

    isRootStub: true,

    depth: 0,

    createRootChild: function(name, direct) {
        var me = this,
            owner = me.owner,
            ownerData = owner.getData(),
            children = me.children,
            previous = children && children[name],
            parentStub = previous ? null : me,
            parentVM, stub;

        if (direct || ownerData.hasOwnProperty(name) || !(parentVM = owner.getParent())) {
            stub = new Ext.app.bind.Stub(owner, name, parentStub);
        }
        else {
            stub = new Ext.app.bind.LinkStub(owner, name, parentStub);
            stub.link('{' + name + '}', parentVM);
        }

        if (previous) {
            previous.graft(stub);
        }

        return stub;
    },

    createStubChild: function(name) {
        return this.createRootChild(name, true);
    },

    descend: function(path, index) {
        var me = this,
            children = me.children,
            pos = index || 0,
            name = path[pos++],
            ret = (children && children[name]) || me.createRootChild(name);

        if (pos < path.length) {
            ret = ret.descend(path, pos);
        }

        return ret;
    },

    getFullName: function() {
        return this.fullName || (this.fullName = this.owner.id + ':');
    },

    // The root Stub is associated with the owner's "data" object

    getDataObject: function() {
        return this.owner.data;
    },

    getRawValue: function() {
        return this.owner.data;
    },

    getValue: function() {
        return this.owner.data;
    },

    isDescendantOf: function() {
        return false;
    },

    set: function(value, preventClimb) {
        //<debug>
        if (!value || value.constructor !== Object) {
            Ext.raise('Only an object can be set at the root');
        }
        //</debug>

        /* eslint-disable-next-line vars-on-top */
        var me = this,
            children = me.children || (me.children = {}),
            owner = me.owner,
            data = owner.data,
            parentVM = owner.getParent(),
            stub, v, key, setSelf, created;

        for (key in value) {
            //<debug>
            if (key.indexOf('.') >= 0) {
                Ext.raise('Value names cannot contain dots');
            }
            //</debug>

            // Setting the value.
            // Ensure the Stub exists for the name, and set its value.
            v = value[key];

            if (v !== undefined) {
                stub = children[key];
                setSelf = preventClimb || !me.shouldClimb(key);

                if (!stub) {
                    stub = me.createRootChild(key, setSelf);
                    created = true;
                }
                else if (setSelf && stub.isLinkStub && !stub.getLinkFormulaStub()) {
                    stub = me.insertChild(key);
                }

                if (!created || !data.hasOwnProperty(value)) {
                    owner.invalidateChildLinks(key);
                }

                stub.set(v, setSelf);
            }
            // Clearing the value. Delete the data item
            // Invalidate the Stub if it exists.
            else if (data.hasOwnProperty(key)) {
                delete data[key];

                stub = children[key];

                if (stub) {
                    if (!stub.isLinkStub && parentVM) {
                        stub = me.createRootChild(key);
                    }

                    owner.invalidateChildLinks(key, true);
                    stub.invalidate(true);
                }
            }
        }
    },

    schedule: Ext.emptyFn,

    unschedule: Ext.emptyFn,

    privates: {
        checkAvailability: function() {
            // Always available
            return true;
        },

        insertChild: function(name) {
            return this.createRootChild(name, true);
        },

        invalidateChildLink: function(name, clear) {
            var children = this.children,
                stub = children && children[name];

            if (stub && stub.isLinkStub && !stub.getLinkFormulaStub()) {
                stub = this.createRootChild(name);

                if (clear) {
                    stub.invalidate(true);
                }

                this.owner.invalidateChildLinks(name, clear);
            }
        },

        shouldClimb: function(name) {
            var parent = this.owner.getParent();

            while (parent) {
                if (parent.getData().hasOwnProperty(name)) {
                    return true;
                }

                parent = parent.getParent();
            }

            return false;
        }
    }
});

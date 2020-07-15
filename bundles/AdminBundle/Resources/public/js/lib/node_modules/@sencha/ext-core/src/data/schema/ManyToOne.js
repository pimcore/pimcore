/**
 * **This class is never created directly. It should be constructed through associations
 * in `Ext.data.Model`.**
 *
 * Declares a relationship between a single entity type and multiple related entities.
 * The relationship can be declared as a keyed or keyless relationship.
 *
 *     // Keyed
 *     Ext.define('Customer', {
 *         extend: 'Ext.data.Model',
 *         fields: ['id', 'name']
 *     });
 *
 *     Ext.define('Ticket', {
 *         extend: 'Ext.data.Model',
 *         fields: ['id', 'title', {
 *             name: 'customerId',
 *             reference: 'Customer'
 *         }]
 *     });
 *
 *     // Keyless
 *     Ext.define('Customer', {
 *         extend: 'Ext.data.Model',
 *         fields: ['id', 'name'],
 *         hasMany: 'Ticket'
 *     });
 *
 *     Ext.define('Ticket', {
 *         extend: 'Ext.data.Model',
 *         fields: ['id', 'title']
 *     });
 *
 *     // Generated methods
 *     var customer = new Customer();
 *     customer.tickets();
 *
 *     var ticket = new Ticket();
 *     ticket.getCustomer();
 *     ticket.setCustomer();
 *
 * By declaring a keyed relationship, extra functionality is gained that maintains
 * the key field in the model as changes are made to the association. 
 * 
 * For available configuration options, see {@link Ext.data.schema.Reference}.
 * The "one" record type will have a generated {@link Ext.data.schema.Association#storeGetter}.
 * The "many" record type will have a {@link Ext.data.schema.Association#recordGetter getter}
 * and {@link Ext.data.schema.Association#recordSetter setter}.
 */
Ext.define('Ext.data.schema.ManyToOne', {
    extend: 'Ext.data.schema.Association',

    isManyToOne: true,

    isToOne: true,

    kind: 'many-to-one',

    Left: Ext.define(null, {
        extend: 'Ext.data.schema.Role',

        isMany: true,

        onDrop: function(rightRecord, session) {
            var me = this,
                store = me.getAssociatedItem(rightRecord),
                leftRecords, len, i, id;

            if (store) {
                // Removing will cause the foreign key to be set to null.
                leftRecords = store.removeAll();

                if (leftRecords && me.inverse.owner) {
                    // If we're a child, we need to destroy all the "tickets"
                    for (i = 0, len = leftRecords.length; i < len; ++i) {
                        leftRecords[i].drop();
                    }
                }

                store.destroy();
                rightRecord[me.getStoreName()] = null;
            }
            else if (session) {
                leftRecords = session.getRefs(rightRecord, me);

                if (leftRecords) {
                    for (id in leftRecords) {
                        leftRecords[id].drop();
                    }
                }
            }
        },

        onIdChanged: function(rightRecord, oldId, newId) {
            var fieldName = this.association.getFieldName(),
                store = this.getAssociatedItem(rightRecord),
                leftRecords, i, len, filter;

            if (store) {
                filter = store.getFilters().get(this.$roleFilterId);

                if (filter) {
                    filter.setValue(newId);
                }

                // A session will automatically handle this updating. If we don't have a field
                // then there's nothing to do here.
                if (!rightRecord.session && fieldName) {
                    leftRecords = store.getDataSource().items;

                    for (i = 0, len = leftRecords.length; i < len; ++i) {
                        leftRecords[i].set(fieldName, newId);
                    }
                }
            }
        },

        processUpdate: function(session, associationData) {
            var me = this,
                entityType = me.inverse.cls,
                items = associationData.R,
                id, rightRecord, store, leftRecords;

            if (items) {
                for (id in items) {
                    rightRecord = session.peekRecord(entityType, id);

                    if (rightRecord) {
                        leftRecords = session.getEntityList(me.cls, items[id]);
                        store = me.getAssociatedItem(rightRecord);

                        if (store) {
                            store.loadData(leftRecords);
                            store.complete = true;
                        }
                        else {
                            // We don't have a store. Create it and add the records.
                            rightRecord[me.getterName](null, null, leftRecords);
                        }
                    }
                    else {
                        session.onInvalidAssociationEntity(entityType, id);
                    }
                }
            }
        },

        findRecords: function(session, rightRecord, leftRecords, allowInfer) {
            var ret = leftRecords,
                refs = session.getRefs(rightRecord, this, true),
                field = this.association.field,
                fieldName, leftRecord, id, i, len, seen;

            if (field && (refs || allowInfer)) {
                fieldName = field.name;
                ret = [];

                if (leftRecords) {
                    seen = {};

                    // Loop over the records returned by the server and
                    // check they all still belong. If the session doesn't have any prior knowledge
                    // and we're allowed to infer the parent id (via nested loading), only do so if
                    // we explicitly have an id specified
                    for (i = 0, len = leftRecords.length; i < len; ++i) {
                        leftRecord = leftRecords[i];
                        id = leftRecord.id;

                        if (refs && refs[id]) {
                            ret.push(leftRecord);
                        }
                        else if (allowInfer && leftRecord.data[fieldName] === undefined) {
                            ret.push(leftRecord);
                            leftRecord.data[fieldName] = rightRecord.id;
                            session.updateReference(leftRecord, field, rightRecord.id, undefined);
                        }

                        seen[id] = true;
                    }
                }

                // Loop over the expected set and include any missing records.
                if (refs) {
                    for (id in refs) {
                        if (!seen || !seen[id]) {
                            ret.push(refs[id]);
                        }
                    }
                }
            }

            return ret;
        },

        processLoad: function(store, rightRecord, leftRecords, session) {
            var ret = leftRecords;

            if (session) {
                // Allow infer here, we only get called when loading an associated store
                ret = this.findRecords(session, rightRecord, leftRecords, true);
            }

            this.onLoadMany(rightRecord, ret, session);

            return ret;
        },

        adoptAssociated: function(rightRecord, session) {
            var store = this.getAssociatedItem(rightRecord),
                leftRecords, i, len;

            if (store) {
                store.setSession(session);
                leftRecords = store.getData().items;

                for (i = 0, len = leftRecords.length; i < len; ++i) {
                    session.adopt(leftRecords[i]);
                }
            }
        },

        createGetter: function() {
            var me = this;

            return function(options, scope, leftRecords) {
                // 'this' refers to the Model instance inside this function
                return me.getAssociatedStore(this, options, scope, leftRecords, true);
            };
        },

        createSetter: null, // no setter for an isMany side

        onAddToMany: function(store, leftRecords) {
            var rightRecord = store.getAssociatedEntity();

            if (this.association.field) {
                this.syncFK(leftRecords, rightRecord, false);
            }
            else {
                this.setInstances(rightRecord, leftRecords);
            }
        },

        onLoadMany: function(rightRecord, leftRecords, session) {
            this.setInstances(rightRecord, leftRecords, session);
        },

        onRemoveFromMany: function(store, leftRecords) {
            if (this.association.field) {
                this.syncFK(leftRecords, store.getAssociatedEntity(), true);
            }
            else {
                this.setInstances(null, leftRecords);
            }
        },

        read: function(rightRecord, node, fromReader, readOptions) {
            var me = this,
                // We use the inverse role here since we're setting ourselves
                // on the other record
                instanceName = me.inverse.getInstanceName(),
                leftRecords = me.callParent([rightRecord, node, fromReader, readOptions]),
                store, len, i;

            if (leftRecords) {
                // Create the store and dump the data
                store = rightRecord[me.getterName](null, null, leftRecords);
                // Inline associations should *not* arrive on the "data" object:
                delete rightRecord.data[me.role];

                leftRecords = store.getData().items;

                for (i = 0, len = leftRecords.length; i < len; ++i) {
                    leftRecords[i][instanceName] = rightRecord;
                }
            }
        },

        setInstances: function(rightRecord, leftRecords, session) {
            var instanceName = this.inverse.getInstanceName(),
                id = rightRecord ? rightRecord.getId() : null,
                field = this.association.field,
                len = leftRecords.length,
                i, leftRecord, oldId, data, name;

            for (i = 0; i < len; ++i) {
                leftRecord = leftRecords[i];
                leftRecord[instanceName] = rightRecord;

                if (field) {
                    name = field.name;
                    data = leftRecord.data;
                    oldId = data[name];

                    if (oldId !== id) {
                        data[name] = id;

                        if (session) {
                            session.updateReference(leftRecord, field, id, oldId);
                        }
                    }
                }
            }
        },

        syncFK: function(leftRecords, rightRecord, clearing) {
            // We are called to set things like the FK (ticketId) of an array of Comment
            // entities. The best way to do that is call the setter on the Comment to set
            // the Ticket. Since we are setting the Ticket, the name of that setter is on
            // our inverse role.
            var foreignKeyName = this.association.getFieldName(),
                inverse = this.inverse,
                setter = inverse.setterName, // setTicket
                instanceName = inverse.getInstanceName(),
                i = leftRecords.length,
                id = rightRecord.getId(),
                different, leftRecord, val;

            while (i-- > 0) {
                leftRecord = leftRecords[i];
                different = !leftRecord.isEqual(id, leftRecord.get(foreignKeyName));

                val = clearing ? null : rightRecord;

                if (different !== clearing) {
                    // clearing === true
                    //      different === true  :: leave alone (not associated anymore)
                    //   ** different === false :: null the value (no longer associated)
                    //
                    // clearing === false
                    //   ** different === true  :: set the value (now associated)
                    //      different === false :: leave alone (already associated)
                    //
                    leftRecord.changingKey = true;
                    leftRecord[setter](val);
                    leftRecord.changingKey = false;
                }
                else {
                    // Ensure we set the instance, we may only have the key
                    leftRecord[instanceName] = val;
                }
            }
        }
    }),

    Right: Ext.define(null, {
        extend: 'Ext.data.schema.Role',

        left: false,
        side: 'right',

        onDrop: function(leftRecord, session) {
            // By virtue of being dropped, this record will be removed
            // from any stores it belonged to. The only case we have
            // to worry about is if we have a session but were not yet
            // part of any stores, so we need to clear the foreign key.
            var field = this.association.field;

            if (field) {
                leftRecord.set(field.name, null);
            }

            leftRecord[this.getInstanceName()] = null;
        },

        createGetter: function() {
            // As the target of the FK (say "ticket" for the Comment entity) this
            // getter is responsible for getting the entity referenced by the FK value.
            var me = this;

            return function(options, scope) {
                // 'this' refers to the Comment instance inside this function
                return me.doGetFK(this, options, scope);
            };
        },

        createSetter: function() {
            var me = this;

            return function(rightRecord, options, scope) {
                // 'this' refers to the Comment instance inside this function
                return me.doSetFK(this, rightRecord, options, scope);
            };
        },

        checkMembership: function(session, leftRecord) {
            var field = this.association.field,
                store;

            if (field) {
                store = this.getSessionStore(session, leftRecord.get(field.name));

                // Check we're not in the middle of an add to the store.
                if (store && !store.contains(leftRecord)) {
                    store.add(leftRecord);
                }
            }
        },

        onValueChange: function(leftRecord, session, newValue, oldValue) {
            // If we have a session, we may be able to find the new store this belongs to
            // If not, the best we can do is to remove the record from the associated store/s.
            var me = this,
                instanceName = me.getInstanceName(),
                cls = me.cls,
                hasNewValue, joined, store, i, associated, rightRecord;

            if (!leftRecord.changingKey) {
                hasNewValue = newValue || newValue === 0;

                if (!hasNewValue) {
                    leftRecord[instanceName] = null;
                }

                if (session) {
                    // Find the store that holds this record and remove it if possible.
                    store = me.getSessionStore(session, oldValue);

                    if (store) {
                        store.remove(leftRecord);
                    }

                    // If we have a new value, try and find it and push it into the new store.
                    if (hasNewValue) {
                        store = me.getSessionStore(session, newValue);

                        if (store && !store.isLoading()) {
                            store.add(leftRecord);
                        }

                        if (cls) {
                            rightRecord = session.peekRecord(cls, newValue);
                        }

                        // Setting to undefined is important so that we can load the record later.
                        leftRecord[instanceName] = rightRecord || undefined;
                    }
                }
                else {
                    joined = leftRecord.joined;

                    if (joined) {
                        // Loop backwards because the store remove may cause unjoining, which means 
                        // removal from the joined array.
                        for (i = joined.length - 1; i >= 0; i--) {
                            store = joined[i];

                            if (store.isStore) {
                                associated = store.getAssociatedEntity();

                                if (associated && associated.self === me.cls &&
                                    associated.getId() === oldValue) {
                                    store.remove(leftRecord);
                                }
                            }
                        }
                    }
                }
            }

            if (me.owner && newValue === null) {
                me.association.schema.queueKeyCheck(leftRecord, me);
            }
        },

        checkKeyForDrop: function(leftRecord) {
            var field = this.association.field;

            if (leftRecord.get(field.name) === null) {
                leftRecord.drop();
            }
        },

        getSessionStore: function(session, value) {
            // May not have the cls loaded yet
            var cls = this.cls,
                rec;

            if (cls) {
                rec = session.peekRecord(cls, value);

                if (rec) {
                    return this.inverse.getAssociatedItem(rec);
                }
            }
        },

        read: function(leftRecord, node, fromReader, readOptions) {
            var rightRecords = this.callParent([leftRecord, node, fromReader, readOptions]),
                rightRecord;

            if (rightRecords) {
                rightRecord = rightRecords[0];

                if (rightRecord) {
                    leftRecord[this.getInstanceName()] = rightRecord;
                    delete leftRecord.data[this.role];
                }
            }
        }
    })
});

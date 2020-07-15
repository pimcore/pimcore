/**
 * This class is used internally by `{@link Ext.data.Session#getSaveBatch}` and is
 * not intended for direct use. It can be studied as an example of implementing a visitor
 * to pass to `{@link Ext.data.Session#visitData}`.
 * @protected
 * @since 5.0.0
 */
Ext.define('Ext.data.session.BatchVisitor', {
    map: null,

    constructor: function(batch) {
        this.batch = batch;
    },

    getBatch: function(sort) {
        var map = this.map,
            batch = this.batch,
            bucket, entity, name, operation, operationType,
            proxy, batchActions, records, len, i;

        if (map) {
            if (!batch) {
                batch = new Ext.data.Batch();
            }

            for (name in map) {
                bucket = map[name];
                entity = bucket.entity; // the entity class
                proxy = entity.getProxy();
                batchActions = proxy.getBatchActions();

                delete bucket.entity; // so we don't think its an operation

                for (operationType in bucket) {
                    if (batchActions) {
                        operation = proxy.createOperation(operationType, {
                            records: bucket[operationType]
                        });
                        operation.entityType = entity;
                        batch.add(operation);
                    }
                    else {
                        records = bucket[operationType];

                        for (i = 0, len = records.length; i < len; ++i) {
                            operation = proxy.createOperation(operationType, {
                                records: [records[i]]
                            });
                            operation.entityType = entity;
                            batch.add(operation);
                        }
                    }
                }
            }
        }

        if (batch && sort !== false) {
            batch.sort();
        }

        return batch;
    },

    onDirtyRecord: function(record) {
        var me = this,
            operation = record.phantom ? 'create' : (record.dropped ? 'destroy' : 'update'),
            name = record.$className,
            map = (me.map || (me.map = {})),
            bucket = (map[name] || (map[name] = {
                entity: record.self
            }));

        //  User: {
        //      entity: User,
        //      create: [
        //          { id: 20, name: 'Don' }
        //      ]
        //  }
        bucket = bucket[operation] || (bucket[operation] = []);
        bucket.push(record);
    }
});

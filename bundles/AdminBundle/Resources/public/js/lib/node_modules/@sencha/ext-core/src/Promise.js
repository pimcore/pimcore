/**
 * This class provides an API compatible implementation of the ECMAScript 6 Promises API
 * (providing an implementation as necessary for browsers that do not natively support the
 * `Promise` class).
 *
 * This class will use the native `Promise` implementation if one is available. The
 * native implementation, while standard, does not provide all of the features of the
 * Ext JS Promises implementation.
 *
 * To use the Ext JS enhanced Promises implementation, see `{@link Ext.Deferred}` for
 * creating enhanced promises and additional static utility methods.
 *
 * Typical usage:
 *
 *      function getAjax (url) {
 *          // The function passed to Ext.Promise() is called immediately to start
 *          // the asynchronous action.
 *          //
 *          return new Ext.Promise(function (resolve, reject) {
 *              Ext.Ajax.request({
 *                  url: url,
 *
 *                  success: function (response) {
 *                      // Use the provided "resolve" method to deliver the result.
 *                      //
 *                      resolve(response.responseText);
 *                  },
 *
 *                  failure: function (response) {
 *                      // Use the provided "reject" method to deliver error message.
 *                      //
 *                      reject(response.status);
 *                  }
 *              });
 *          });
 *      }
 *
 *      getAjax('http://stuff').then(function (content) {
 *          // content is responseText of ajax response
 *      });
 *
 * To adapt the Ext JS `{@link Ext.data.Store store}` to use a Promise, you might do
 * something like this:
 *
 *      loadCompanies: function() {
 *          var companyStore = this.companyStore;
 *
 *          return new Ext.Promise(function (resolve, reject) {
 *              companyStore.load({
 *                  callback: function(records, operation, success) {
 *                      if (success) {
 *                          // Use the provided "resolve" method  to drive the promise:
 *                          resolve(records);
 *                      }
 *                      else {
 *                          // Use the provided "reject" method  to drive the promise:
 *                          reject("Error loading Companies.");
 *                      }
 *                  }
 *              });
 *          });
 *      }
 *
 * @since 6.0.0
 */
Ext.define('Ext.Promise', function() {
/* eslint-disable indent */
var Polyfiller;

return {
    requires: [
        'Ext.promise.Promise'
    ],

    statics: {
        _ready: function() {
            // We can cache this now that our requires are met
            Polyfiller = Ext.promise.Promise;
        },

        /**
         * Returns a new Promise that will only resolve once all the specified
         * `promisesOrValues` have resolved.
         *
         * The resolution value will be an Array containing the resolution value of each
         * of the `promisesOrValues`.
         *
         * @param {Mixed[]/Ext.Promise[]/Ext.Promise} promisesOrValues An Array of values
         * or Promises, or a Promise of an Array of values or Promises.
         *
         * @return {Ext.Promise} A Promise of an Array of the resolved values.
         * @static
         */
        all: function() {
            return Polyfiller.all.apply(Polyfiller, arguments);
        },

        /**
         * Returns a promise that resolves or rejects as soon as one of the promises in the
         * array resolves or rejects, with the value or reason from that promise.
         * @param {Ext.promise.Promise[]} promises The promises.
         * @return {Ext.promise.Promise} The promise to be resolved when the race completes.
         *
         * @static
         * @since 6.5.0
         */
        race: function() {
            return Polyfiller.race.apply(Polyfiller, arguments);
        },

        /**
         * Convenience method that returns a new Promise rejected with the specified
         * reason.
         *
         * @param {Error} reason Rejection reason.
         * @return {Ext.Promise} The rejected Promise.
         * @static
         */
        reject: function(reason) {
            var deferred = new Ext.promise.Deferred();

            deferred.reject(reason);

            return deferred.promise;
        },

        /**
         * Returns a new Promise that either
         *
         *  * Resolves immediately for the specified value, or
         *  * Resolves or rejects when the specified promise (or third-party Promise or
         *    then()-able) is resolved or rejected.
         *
         * @param {Mixed} value A Promise (or third-party Promise or then()-able)
         * or value.
         * @return {Ext.Promise} A Promise of the specified Promise or value.
         * @static
         */
        resolve: function(value) {
            var deferred = new Ext.promise.Deferred();

            deferred.resolve(value);

            return deferred.promise;
        }
    },

    constructor: function(action) {
        var deferred = new Ext.promise.Deferred();

        action(deferred.resolve.bind(deferred), deferred.reject.bind(deferred));

        return deferred.promise;
    }
};
},
function(ExtPromise) {
    var P = Ext.global.Promise;

    if (P && P.resolve && !Ext.useExtPromises) {
        Ext.Promise = P;
    }
    else {
        ExtPromise._ready();
    }
});

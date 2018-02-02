pimcore.registerNS("pimcore.error.ValidationException");

pimcore.error.ValidationException = function (message) {
    this.message = message;
    if ("captureStackTrace" in Error) { // V8's native method, fallback otherwise
        Error.captureStackTrace(this, pimcore.error.ValidationException);
    } else {
        this.stack = (new Error()).stack;
    }
}

pimcore.error.ValidationException.prototype = Object.create(Error.prototype);
pimcore.error.ValidationException.prototype.name = "ValidationException";
pimcore.error.ValidationException.prototype.constructor = pimcore.error.ValidationException;


pimcore.error.ActionCancelledException = function (message) {
    this.message = message;
    if ("captureStackTrace" in Error) { // V8's native method, fallback otherwise
        Error.captureStackTrace(this, pimcore.error.ActionCancelledException);
    } else {
        this.stack = (new Error()).stack;
    }
}
pimcore.error.ActionCancelledException.prototype = Object.create(Error.prototype);
pimcore.error.ActionCancelledException.prototype.name = "ActionCancelledException";
pimcore.error.ActionCancelledException.prototype.constructor = pimcore.error.ActionCancelledException;

function array_merge () {
    // http://kevin.vanzonneveld.net
    // +   original by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Nate
    // +   input by: josh
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // *     example 1: arr1 = {"color": "red", 0: 2, 1: 4}
    // *     example 1: arr2 = {0: "a", 1: "b", "color": "green", "shape": "trapezoid", 2: 4}
    // *     example 1: array_merge(arr1, arr2)
    // *     returns 1: {"color": "green", 0: 2, 1: 4, 2: "a", 3: "b", "shape": "trapezoid", 4: 4}
    // *     example 2: arr1 = []
    // *     example 2: arr2 = {1: "data"}
    // *     example 2: array_merge(arr1, arr2)
    // *     returns 2: {0: "data"}
    
    var args = Array.prototype.slice.call(arguments),
                            retObj = {}, k, j = 0, i = 0, retArr = true;
    
    for (i=0; i < args.length; i++) {
        if (!(args[i] instanceof Array)) {
            retArr=false;
            break;
        }
    }
    
    if (retArr) {
        retArr = [];
        for (i=0; i < args.length; i++) {
            retArr = retArr.concat(args[i]);
        }
        return retArr;
    }
    var ct = 0;
    
    for (i=0, ct=0; i < args.length; i++) {
        if (args[i] instanceof Array) {
            for (j=0; j < args[i].length; j++) {
                retObj[ct++] = args[i][j];
            }
        } else {
            for (k in args[i]) {
                if (args[i].hasOwnProperty(k)) {
                    if (parseInt(k, 10)+'' === k) {
                        retObj[ct++] = args[i][k];
                    } else {
                        retObj[k] = args[i][k];
                    }
                }
            }
        }
    }
    return retObj;
}

function array_merge_recursive (arr1, arr2){
    // http://kevin.vanzonneveld.net
    // +   original by: Subhasis Deb
    // +      input by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // -    depends on: array_merge
    // *     example 1: arr1 = {'color': {'favourite': 'read'}, 0: 5}
    // *     example 1: arr2 = {0: 10, 'color': {'favorite': 'green', 0: 'blue'}}
    // *     example 1: array_merge_recursive(arr1, arr2)
    // *     returns 1: {'color': {'favorite': {0: 'red', 1: 'green'}, 0: 'blue'}, 1: 5, 1: 10}

    var idx = '';

    if ((arr1 && (arr1 instanceof Array)) && (arr2 && (arr2 instanceof Array))) {
        for (idx in arr2) {
            arr1.push(arr2[idx]);
        }
    } else if ((arr1 && (arr1 instanceof Object)) && (arr2 && (arr2 instanceof Object))) {
        for (idx in arr2) {
            if (idx in arr1) {
                if (typeof arr1[idx] == 'object' && typeof arr2 == 'object') {
                    arr1[idx] = this.array_merge(arr1[idx], arr2[idx]);
                } else {
                    arr1[idx] = arr2[idx];
                }
            } else {
                arr1[idx] = arr2[idx];
            }
        }
    }
    
    return arr1;
}
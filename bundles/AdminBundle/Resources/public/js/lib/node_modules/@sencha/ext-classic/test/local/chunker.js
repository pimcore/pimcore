var Test = Test || {};

(function() {
'use strict';

// We only want to do this special chunking for REALLY slow browsers.
// Note that we assume driver capabilities for these browsers to define
// a significant number of chunks, definitely greater than 5 or so defined
// below. There are no checks for this anywhere in the code, so if IE8 has
// 5 chunks defined, it will only run the ones below. Careful!
// "isIOS" is correct. 
if (Test.browser.isIE9m || Test.browser.isIOS || Test.browser.isAndroid) {
    Test.chunks = [
        [
            'specs/grid/grid-general.js'
        ],
        [
            'specs/grid/grid-grouping.js', // 27s
            'specs/grid/grid-events.js', // 26s
            'specs/grid/grid-celledit.js', // 23s
            'specs/grid/grid-aria.js', // 2.5s
            'specs/grid/grid-general-buffered-no-preserve-scroll.js', // 1s
            'specs/grid/grid-general-bufffered-preserve-scroll.js', // 3s
            'specs/grid/grid-general-locking-from-no-locking.js', // 2s
            'specs/grid/grid-general-locking.js', // 9s
            'specs/grid/grid-general-paging-buffered-renderer.js', // 0.3s
            'specs/grid/grid-general-window.js', // 0.3s
            'specs/grid/grid-keys.js' // 5s
        ],
        [
            'specs/grid/grid-columns.js', // 43s
            'specs/grid/grid-moving-columns.js', // 34s
            'specs/grid/grid-rowedit.js', // 34s
            'specs/grid/grid-view.js', // 2s
            'specs/grid/grid-widgets.js' // 6s
        ]
    ];
}
else {
    // Even in fast browsers grid-general is a beast
    Test.chunks = [
        [
            'specs/grid/grid-general.js'
        ],
        [
            'specs/grid/grid-grouping.js', // 27s
            'specs/grid/grid-events.js', // 26s
            'specs/grid/grid-celledit.js', // 23s
            'specs/grid/grid-aria.js', // 2.5s
            'specs/grid/grid-general-buffered-no-preserve-scroll.js', // 1s
            'specs/grid/grid-general-bufffered-preserve-scroll.js', // 3s
            'specs/grid/grid-general-locking-from-no-locking.js', // 2s
            'specs/grid/grid-general-locking.js', // 9s
            'specs/grid/grid-general-paging-buffered-renderer.js', // 0.3s
            'specs/grid/grid-general-window.js', // 0.3s
            'specs/grid/grid-keys.js' // 5s
        ],
        [
            'specs/grid/grid-columns.js', // 43s
            'specs/grid/grid-moving-columns.js', // 34s
            'specs/grid/grid-rowedit.js', // 34s
            'specs/grid/grid-view.js', // 2s
            'specs/grid/grid-widgets.js' // 6s
        ]
    ];
}

// TODO This function is duplicated in Modern toolkit. Refactor and combine.
Test.chunker = function(array, chunkNo, numChunks) {
    var chunks = Test.chunks,
        urls = array.slice(),
        result = [],
        chunk, found, url, size, i, len, j, jlen, k, klen;

    // If we're passed a chunk number that we have a definition for, it's easy
    if (chunks[chunkNo]) {
        chunks = chunks[chunkNo];

        URLS:
        for (i = 0, len = urls.length; i < len; i++) {
            url = urls[i];

            CHUNK:
            for (j = 0, jlen = chunks.length; j < jlen; j++) {
                if (url.indexOf(chunks[j]) !== -1) {
                    result.push(url);

                    if (result.length === chunks.length) {
                        break URLS;
                    }

                    break CHUNK;
                }
            }
        }

        // ¯\_(ツ)_/¯
        if (!result || !result.length) {
            return false;
        }

        return result;
    }

    // If that's the rest, we need to remove URLs mentioned in special chunks first
    for (i = 0, len = urls.length; i < len; i++) {
        url = urls[i];
        found = false;

        CHUNK:
        for (j = 0, jlen = chunks.length; j < jlen; j++) {
            chunk = chunks[j];

            for (k = 0, klen = chunk.length; k < klen; k++) {
                if (url.indexOf(chunk[k]) !== -1) {
                    found = true;
                    break CHUNK;
                }
            }
        }

        if (!found) {
            result.push(url);
        }
    }

    urls = result;
    result = [];

    // Then fall back to the default splitting algorithm
    size = Math.ceil(urls.length / numChunks);

    while (urls.length) {
        result.push(urls.splice(0, size));
    }

    chunk = result[chunkNo];

    return chunk && chunk.length ? chunk : false;
};
})();

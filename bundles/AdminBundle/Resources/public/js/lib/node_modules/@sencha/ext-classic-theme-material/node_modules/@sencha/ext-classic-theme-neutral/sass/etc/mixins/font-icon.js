exports.init = function(runtime) {
    runtime.register({
        // This function parses arguments from all formats accepted by the font-icon()
        // sass mixin and returns an array that always contains 4 elements in the following
        // order: character, font-size, font-family, rotation
        parseFontIconArgs: function(glyph) {
            var newItems = [null, null, null, null],
                items, item, len;

            if (glyph.$isFashionList) {
                items = glyph.items;
                len = items.length;

                newItems[0] = items[0];

                if (len === 2) {
                    item = items[1];

                    if (item.$isFashionNumber) {
                        if (item.unit) {
                            newItems[1] = item;
                        }
                        else {
                            newItems[3] = item;
                        }
                    }
                    else {
                        newItems[2] = item;
                    }
                }
                else if (len === 3) {
                    if (items[1].$isFashionNumber) {
                        newItems[1] = items[1];

                        if (items[2].$isFashionNumber) {
                            newItems[3] = items[2];
                        }
                        else {
                            newItems[2] = items[2];
                        }
                    }
                    else {
                        newItems[2] = items[1];
                        newItems[3] = items[2];
                    }
                }
                else {
                    newItems[1] = items[1];
                    newItems[2] = items[2];
                    newItems[3] = items[3];
                }
            }
            else {
                newItems[0] = glyph;
            }

            return new Fashion.List(newItems);
        }
    });
};

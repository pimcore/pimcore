topSuite("Ext.draw.sprite.Image", ['Ext.draw.Container'], function() {
    beforeEach(function() {
        // Silence warnings regarding Sencha download server
        spyOn(Ext.log, 'warn');
    });

    describe("hitTest", function() {
        var sprite, surface, container;

        beforeEach(function() {
            container = new Ext.draw.Container({
                renderTo: Ext.getBody()
            });
            surface = new Ext.draw.Surface();
            sprite = new Ext.draw.sprite.Image({
                globalAlpha: 0.8,
                y: 50,
                x: 50,
                height: 50,
                width: 50,
                src: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAyCAYAAAAeP4ixAAAAA' +
                'XNSR0IArs4c6QAAAAlwSFlzAAALEwAACxMBAJqcGAAAActpVFh0WE1MOmNvbS5hZG9iZS54bXAAA' +
                'AAAADx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IlhNUCBDb3JlI' +
                'DUuNC4wIj4KICAgPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyL' +
                'zIyLXJkZi1zeW50YXgtbnMjIj4KICAgICAgPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIKI' +
                'CAgICAgICAgICAgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIgogICAgI' +
                'CAgICAgICB4bWxuczp0aWZmPSJodHRwOi8vbnMuYWRvYmUuY29tL3RpZmYvMS4wLyI+CiAgICAgI' +
                'CAgIDx4bXA6Q3JlYXRvclRvb2w+QWRvYmUgSW1hZ2VSZWFkeTwveG1wOkNyZWF0b3JUb29sPgogI' +
                'CAgICAgICA8dGlmZjpPcmllbnRhdGlvbj4xPC90aWZmOk9yaWVudGF0aW9uPgogICAgICA8L3JkZ' +
                'jpEZXNjcmlwdGlvbj4KICAgPC9yZGY6UkRGPgo8L3g6eG1wbWV0YT4KKS7NPQAACxhJREFUaAXFW' +
                'WlsVNcZPe/N5jEGL6w1S1hj9i2BmApIpEolStugqkRuVRoJVY0qlC5UlfKjSqrmb6U2bdKIoJa0a' +
                'RCIkopFlEAWtaFpw1YMgbSsxmDZxgbvM2/mzVt6vvvmgkMyM/YYzLXu3Ddv7vKd73zLvdeGz4Iii' +
                '+97MAxTjXY9G5bdinh0LEJmvMgZix8WSFHE+P4g2ntO4ErbNkTDFVkQReumCEmCIeHBjvQRCClMJ' +
                'NLNaLi+Bd3JX2DJtBYCGYX+AAc791D6DwqIWKFhGGq91s7DaGj/Hs3pHGZO2IvS2ASCcPl7aCjyF' +
                'D12EKZ1G8SVtl0437qai0ZREpmGqrLFWQECkEVLM4SBAwairb6x/S003niKZrSKDPQgEl6MWKQqE' +
                'CHL1hDkKXrogIAou4eB613/Ioh1iIVrAd+itzTDNEayBuZ0//gACgLRzptMt+JS23qEzdkEkGZ1C' +
                'K0ajtsCCb1SNGtFq3UIAwsC0XniSvsbcN0Gar+SAovg4thjkcq8g0SqORCh+JQ0BAjB0LxAhA0pX' +
                'YlzuNn3HMKh5QTRSyYMFWZ9P6RYaOt+L5jtPn7mBQIKLKW164CyGwEgD6J4xjDWJJ1+KZo6nkVT+' +
                '8cqy3uey9+H38hyAtE5I5XpICPbmbHFN1IUUgAEJQgCoGlVY8eHT6Ol4yJMM6RyjUc2dfLMdr+nT' +
                'U4g2nV7k5dgu0fpG+UEQW3fAsEevslqozsxFlamHm98MAsnLhyAnbHY3yRnBoYLUB4ggcS9qfPqI' +
                'TCl/kqlmLQ02zHQmehBLDoXhjkdb/3nCWw99hROtRxC2kneAiTs3UuGcgLR0cqyryjP8MiGFOUfY' +
                'l5ZaizbY+QiIoN5xY9gdOUSdPr7sev8Grxe/w3UNweAZL57yVBOIIHQLjLOdQVAwm1QAh9R8YyUJ' +
                'NMeMgoVpzIcOPErzDWTUBmbj077bfzl0hpsPUVAdzB0t00uB5BA3eITjtdHAUuJQaJVACJgBbR/n' +
                'ztgmgzfsxO8sgT8kMf3SYK7qgBVZQHtJKDfn/46TrYc/JTJCaC7UXIA0VOLyTBX0KHvBCFQPcqQz' +
                'rALZ3FG9MGNWnxnwiVAjznG9RNUxLVbgLoyh7Dj8uN47fRanOgHSFbTOUuvPNg2LxDTDCNqckPoO' +
                '5xXIlTgIyADJqvn0bSY5VNl3chEkgqEwz6iY11dAcReGa8JIWMSRkcX0OTexTYCeuX013Cy9X0FQ' +
                'nxoKOzkACImJDo34IaqkVLCEQj/JACn6Qs9IQtt0Q50sjqmw/2WCQ3CZf+gEqyAyo53uNG0ydBtQ' +
                'O9jy8Uv4Q9nNqHDalURrlgwBhOfSPyZIhNKLjhyfS8OtKzFOIZXGDcZcmk2hguTvsBHXG3i9pFxI' +
                'MQNsEnnN8UaqR4JzXIIk1bCnjTyRRYTlxKVAGXsPwY3mINiHLNx9hlMr5ynmJG1B1Ny9lYLc6aJp' +
                'TU0C+633ARrBt2sFmWwnVBQKVmG1WEfhzoRVuRZsyLf5dmh9PpZvosPOX4KKbcBI8Nz1Rw/PzMf5' +
                '26eUgocLDM5gQSqBMaXTsWEyDpm7kZ4bikyrsHFuctitViZRlQVMAJYqgicof+oZ/mefSfvNRjdu' +
                'ohwvmaa20yUmaV48exiNPXKLntwPpMTiE5ekVAMSyq/hQ7u3G23jKHT5cKARUmEGQEgYBiF2VL47' +
                'HcN6nbrB8wJmCwwDd7xwxx/k8JPBoMgfvPJjzh/sCsY6G4gJxCxTwEjZdHYx1BlzkQy8z+k3TjBE' +
                'AQDmTIxCibA0gQhYAJAAuo2WwJUCZ1l8DbYAJz6jaaWdFtRHlqEg737sL9hp1pbOVXwlPczp7PrU' +
                'drpP7y2D69eehJfKJnPKNZAlDE6PH2ny0My6XM7b9A86OxUTYj45VkcXTu+BAHl9P0cXtYgRro9/' +
                'Ye/u+JHfHb9GFrda9ix7CqqR04ekPPnZUQW0tc/KyY9gUdGbkRz8gx8dwp9JqVYkbOiMjWykhSTu' +
                '1UZpoUhYUsqGbOVf/VnLfvMICEmGLAoxjUSR8n4u008B7Foy1BfcnwUZETGaVY6km14/shy3HQbE' +
                'Q/XwDKuwbFjZEXCccBIiKpXjGhm+F2YkdAcMCLK4aSsEoaJj/OTDbaSdqV6iKGHAaAstBDbV/wdF' +
                'SU8XhOsViq7fKYUZERG6AhSVToOzy15Dx5Xa7HOwXRnwPIzytktKjLB931s+6RlTZCJRNafkmyVL' +
                '2mGsq3NVkU+3ZIZy7VQgjl4O3EaFzr1MYJo85QBAZHxGsyUihn4VW0DqkO1eKf7Y4T9qdRsBRKZj' +
                'BK6jwL3ZoH0ByUmJ0Eizd9UzQIJzC5rYgKGASFFesRkhbWz7WflqaB5DerKVIOZVDEVv171Nyyof' +
                'wU/ufoC4hRukTOHQSABCx108DA3ioGJhamqiMlgQHuK8NnkdkBlfD6rDK92CsFWxmGIyvB9mrsG2' +
                '0sDTQTSdg7+gmBMPvPisMEVDaY8XolNK57H0S/+G3Wj6vBR539R33cVMW8SjyXl6KXN9Iq5sYrZW' +
                'Yyx0qYUI2SGzzbfSStMpbglsgxWsmK1xZE4GQOOAxcbG5GyUwWFHJCzf94s/bWTdtI4cukjbDmxG' +
                'duad4AGjonxGjowd8RGFzN2CCVUmTASJjOyLzPIklwPKlZoOG4mAqcninQb6w0eHdwIGp1rWFa2F' +
                'Ide2IOKMrkzyO3wgzKt/oB0BJGIFgvHsLrmUdTOqMUPLm3EjpNb8FLTm4hTqfNK5vLuJckdc7fYl' +
                'SDgNATDBGhyvwY7Ai/BrX5vCGkqPs0QlhLzE6BuiGyQMgIoVIoGoicWUxNNyV+UgB6pWYWHZizH+' +
                'svPYO+JrTjY+EdMjAAV0WXMJ2makQWX2c/JVnH8jDg4FZKm8CnJPZS9RKhKd2HMqApeDBYWs3APL' +
                'XGeVjkvtawBhQnooQdXYcH05fjKxe/ir0e2Ytfl11FNQFUElPTkXGKryMSbMnWTnBYw2YjmkJUQr' +
                '5pIJKaVT+YNDW21QBGe71oRQJohj+dgYWj57JV48dub8WbdYSwdvwF7bhxDj00z80vp/G42HH8ah' +
                'E3nj3g0ux6gpnoW/3URzusfAuCuAtEaUYC46RKGAkBR1M5diV9ueA2H1/8D8dB4HO+rR5gHK8vlb' +
                'rofEw5B2NwehwVIFzB/xjw1rcyVr9wTIHrBOwFFwhGsXLga2zfuw+NVa3GSYCJenEB4IKM5ZaRlD' +
                'TMh3kjx9obXBXNnztHT5W3vKRC9cn9ALhmYMHoCXtrwW5pXlGZmw+R5X4Nw6CvjjBHobDmPH6/+K' +
                'aZUT1HTGBLx8pRhAaLXF0AhJhEB88CEKdj+1T+j/fp5VGKEYsInCJ9mVcobS8nqdWvW0efktob3A' +
                '/zLV4YViBZEa3f1kpUA7/660rz05jbYpVlNMctxofEkvv/NZ7Fs4cNqiCigULk/QLLaHVM5FnVTv' +
                '8N/Ip3DCI93aGJi3HyC7rHp6R8q9hQbAwByV/JIIW3d+bvWsITV8SPHqXwR5glzZng0Ptl1HLt37' +
                '8GD02bxnOLzxDkwXQ+s152SDPG7DqUubb83QfWzTIoQxJ7jeHnz77D2y0+qd4UNSnULPjjpsBeai' +
                '1rzZleHX7PhYb+6braP0fBf/tOrfvALb4KzfQYqnCStYS8Ok4WUD479U7Kcj9Xwdx/ae0uOwYKQg' +
                'cMOhKFXCdzT1+tj3Vh/w8+e8S9euTgkEDK46PNIP+sc8CPXU6dDi/v17ft3Ynr1A3is9lE1XkUnu' +
                'agokC9yLTasQESIlJ1G643rGFc5BqVx+QcSb03o9AONTmrA53z8H0eVMzcGH0Y4AAAAAElFTkSuQ' +
                'mCC'
            });
            surface.add(sprite);
            container.add(surface);
        });

        afterEach(function() {
            Ext.destroy(sprite, surface, container);
        });

        it("should return an object with the 'sprite' property set to the sprite itself, " +
            "if the sprite is visible and its bounding box is hit", function() {
            // Testing hitTest method of the abstract Sprite class.
            // Even though, (10,10) is not inside the circle, it's inside it's bounding box.
            var result = Ext.draw.sprite.Sprite.prototype.hitTest.call(sprite, [60, 70]);

            expect(result && result.sprite).toBe(sprite);
        });

        it("should return null, if the sprite's bounding box is hit, but the sprite is not visible", function() {
            var originalMethod = sprite.isVisible;

            sprite.isVisible = function() {
 return false;
};
            var result = Ext.draw.sprite.Sprite.prototype.hitTest.call(sprite, [60, 70]);

            expect(result).toBe(null);
            sprite.isVisible = originalMethod;
        });

        it("should return null, if the sprite is visible, but it's bounding box is not hit", function() {
            var result = Ext.draw.sprite.Sprite.prototype.hitTest.call(sprite, [210, 200]);

            expect(result).toBe(null);
        });
    });

});

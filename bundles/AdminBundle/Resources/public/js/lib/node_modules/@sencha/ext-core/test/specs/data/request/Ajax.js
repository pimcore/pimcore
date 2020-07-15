topSuite("Ext.data.request.Ajax", function() {
    describe("parseStatus", function() {
        var parseStatus = Ext.data.request.Ajax.parseStatus;

        it("should should exist as a static method", function() {
            expect(parseStatus).not.toBeNull();
        });

        it("should succeed with status 200-299 or 304", function() {
            var result, i;

            for (i = 200; i < 300; i++) {
                result = parseStatus(i);
                expect(result.success).toBeTruthy();
            }
        });

        it("should succeed with status 0 when responseText exists", function() {
            var result = parseStatus(0, { responseText: 'foo' });

            expect(result.success).toBeTruthy();
        });

        it("should fail with status 0 when responseText is empty", function() {
            var result = parseStatus(0);

            expect(result.success).toBeFalsy();
        });

        it("should succeed with status 304", function() {
            var result = parseStatus(304);

            expect(result.success).toBeTruthy();
        });

        it("should succeed with status 1223", function() {
            var result = parseStatus(1223);

            expect(result.success).toBeTruthy();
        });
    });
});

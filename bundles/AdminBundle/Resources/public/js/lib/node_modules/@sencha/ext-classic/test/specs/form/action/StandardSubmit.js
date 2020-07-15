topSuite("Ext.form.action.StandardSubmit", function() {

    it("should be registered in the action manager under the alias 'formaction.standardsubmit'", function() {
        var inst = Ext.ClassManager.instantiateByAlias('formaction.standardsubmit', {});

        expect(inst instanceof Ext.form.action.StandardSubmit).toBeTruthy();
    });

    // TODO specs will need to intercept and prevent the form submit.
});

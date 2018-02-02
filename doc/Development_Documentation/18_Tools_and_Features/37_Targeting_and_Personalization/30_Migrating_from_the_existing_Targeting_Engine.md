# Migrating from the existing Targeting Engine

If you used the existing frontend-based targeting engine before, unfortunately there is no automatic migration path to the
new system. Altough it will be possible to handle similar scenarios as in the old engine, the core dropped a couple of
conditions which are quite complex to handle out of the box. However you might be able to re-create those conditions 
if needed as the targeting engine is completely extendable.


## Personas are now Target Groups

In the previous implementation the entity which was used to target content for was named `Persona`. This entity was renamed
to `Target Group` in the new implementation, but as the used data is the same it will automatically be migrated when updating
Pimcore. Note that a target group does not define any entry conditions anymore and this data will be dropped when running
the update.


## Targeting Rules

Unfortunately, targeting rules can't be migrated at all as they follow a new format and have new and different actions 
and conditions. Delete any existing rules and start from scratch. You can delete all of your existing rules by truncating
the `targeting_rules` DB table:

```sql
TRUNCATE TABLE targeting_rules;
```

If you want to script rule creation for an automated migration you can take a look at the [Demo Advanced](https://github.com/pimcore/demo-ecommerce/blob/master/src/AppBundle/Command/CreateTargetingDataCommand.php)
which provides a script to create its targeting rules and data in a CLI command.


## Personalized Content

If you already created personalized content on your documents, it depends on the used [Editable Naming Strategy](../../03_Documents/13_Editable_Naming_Strategies.md)
if you need to migrate your document elements. If you are using the old `legacy` naming scheme no migration is necessary,
however if using the new `nested` naming scheme which was introduced with Pimcore 5, you'll need to migrate your content.

This has the following background: due to the way element names are built, personalized content inside block elements for
a target group with the ID `3` was stored as something like:

    persona_-3-_content:2.persona_-3-_teaserblock:3.persona_-3-_productteaser
    
As this is redundant and makes element names very long quickly we decided to change this to a format only including the
personalization information once. As a result, it is necessary to migrate your content in order to change the above to:

    persona_-3-_content:2.teaserblock:3.productteaser
    
As you can see, the prefix `persona_-<id>-_` was kept to make sure the legacy naming scheme still works. This will be one
of the few parts where a target group is still referenced as the term "persona".

To migrate your content to the shorter format, Pimcore provides a CLI command which will migrate all document elements
in one go. Just call the following command:

    $ bin/console pimcore:targeting:migrate-element-names
    
<div class="alert alert-warning">
The migration command is potentially dangerous. Use with care and run with <code>--dry-run</code> to check what would be done.
In any case make sure you have a proper backup!
</div>

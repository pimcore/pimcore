# Modifying Pimcore Permissions Based On Workflow Places

As listed in the [configuration details section](./01_Configuration_Details.md) it's possible to modify the Pimcore 
element permissions based on the current workflow place.

It's possible to add multiple permission configurations based on conditions. The first entry where the condition is 
valid will be used.

##### Configuration Examples

Pimcore admins are allowed to publish and delete the object but for all other users it will be suppressed:

```yaml

   places:
      closed:
         permissions:
           - condition: is_fully_authenticated() and 'ROLE_PIMCORE_ADMIN' in roles
             publish: true
             delete: true
           - publish: false
             delete: false
```

Pimcore admins are allowed to modify (save, publish,...) the object but for all other users the save and delete button 
will be hidden. `modify` is a short hand for save, publish, unpublish, delete and rename:

```yaml

   places:
      closed:
         permissions:
           - condition: is_fully_authenticated() and 'ROLE_PIMCORE_ADMIN' in roles
             modify: true
           - modify: false
```

> If multiple places provide a valid permission configuration the one with the highest priority will be used. 
> The priority is based on the workflow priority (the workflow with the higher priority setting will win). Within a 
> single workflow the order within the places section of the configuration file will be used.

# Custom View Example Configuration

```yaml
#var/config/custom-views/87705013-edb9-c9ec-0f5e-c3ee45ca4459.yaml

pimcore:
    custom_views:
        definitions:
            87705013-edb9-c9ec-0f5e-c3ee45ca4459:
                name: Events
                treetype: object
                position: left
                rootfolder: /Events
                showroot: false
                sort: 0
                treeContextMenu:
                    object:
                        items:
                            add: true
                            addFolder: true
                            importCsv: true
                            cut: true
                            copy: true
                            paste: true
                            delete: true
                            rename: true
                            reload: true
                            publish: true
                            unpublish: true
                            searchAndMove: true
                            lock: true
                            unlock: true
                            lockAndPropagate: true
                            unlockAndPropagate: true
                            changeChildrenSortBy: true
                classes: ''
                joins: [
                    {
                        type: left,
                        name: { ev: object_query_EV },
                        condition: 'objects.id = ev.oo_id',
                        columns: { ev: tags }
                    }
                ]
                id: 87705013-edb9-c9ec-0f5e-c3ee45ca4459
                icon: /bundles/pimcoreadmin/img/flat-color-icons/vip.svg
                where: ''
                having: 'ev.tags LIKE "%%Salzburg%%"'
```

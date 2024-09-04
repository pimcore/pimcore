# Perspective Example Configuration

```yaml
#var/config/perspectives/Event.yaml

pimcore:
    perspectives:
        definitions:
            Event:
                elementTree:
                    -
                        type: customview
                        position: left
                        sort: 0
                        expanded: true
                        hidden: false
                        id: 87705013-edb9-c9ec-0f5e-c3ee45ca4459
                iconCls: null
                icon: /bundles/pimcoreadmin/img/flat-color-icons/vip.svg
                toolbar:
                    file:
                        hidden: true
                        items:
                            perspectives: true
                            dashboards: true
                            openDocument: true
                            openAsset: true
                            openObject: true
                            searchReplace: true
                            schedule: true
                            seemode: true
                            closeAll: true
                            help: true
                            about: true
                    marketing:
                        hidden: true
                        items:
                            reports: true
                            tagmanagement: true
                            targeting: true
                            seo:
                                hidden: false
                                items:
                                    documents: true
                                    robots: true
                                    httperrors: true
                    extras:
                        hidden: true
                        items:
                            glossary: true
                            redirects: true
                            translations: true
                            recyclebin: true
                            plugins: true
                            notesEvents: true
                            applicationlog: true
                            gdpr_data_extractor: true
                            emails: true
                            maintenance: true
                            systemtools:
                                hidden: false
                                items:
                                    phpinfo: true
                                    opcache: true
                                    requirements: true
                                    serverinfo: true
                                    database: true
                                    fileexplorer: true
                    settings:
                        hidden: true
                        items:
                            customReports: true
                            marketingReports: true
                            documentTypes: true
                            predefinedProperties: true
                            predefinedMetadata: true
                            system: true
                            website: true
                            web2print: true
                            users:
                                hidden: false
                                items:
                                    users: true
                                    roles: true
                            thumbnails: true
                            objects:
                                hidden: false
                                items:
                                    classes: true
                                    fieldcollections: true
                                    objectbricks: true
                                    quantityValue: true
                                    classificationstore: true
                                    bulkExport: true
                                    bulkImport: true
                            routes: true
                            cache:
                                hidden: false
                                items:
                                    clearAll: true
                                    clearData: true
                                    clearSymfony: true
                                    clearOutput: true
                                    clearTemp: true
                            adminTranslations: true
                            tagConfiguration: true
                            perspectiveEditor: true
                    search:
                        hidden: true
                        items:
                            quickSearch: true
                            documents: true
                            assets: true
                            objects: true
                    datahub:
                        hidden: true
````

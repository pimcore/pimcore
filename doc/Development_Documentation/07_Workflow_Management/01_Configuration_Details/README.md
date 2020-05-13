# Details


## Available options

```yaml
pimcore: 
    workflows:

        # Prototype
        name:
            # Placeholder values in this workflow configuration (locale: "%%locale%%") will be replaced by the given placeholder value (eg. "de_AT")
            placeholders:
                # Example:
                placeholders:
                    %%locale%%:          de_AT

            # Can be used to enable or disable the workflow.
            enabled:              true

            # When multiple custom view or permission settings from different places in different workflows are valid, the workflow with the highest priority will be used.
            priority:             0

            # Will be used in the backend interface as nice name for the workflow. If not set the technical workflow name will be used as label too.
            label:                ~

            # Enable default audit trail feature provided by Symfony. Take a look at the Symfony docs for more details.
            audit_trail:
                enabled:              false

            # A workflow with type "workflow" can handle multiple places at one time whereas a state_machine provides a finite state_machine (only one place at one time). Take a look at the Symfony docs for more details.
            type:                 ~ # One of "workflow"; "state_machine"

            # Handles the way how the state/place is stored. If not defined "state_table" will be used as default. Take a look at the marking store section of the Pimcore workflow docs for a description of the different types.
            marking_store:
                type:                 ~ # One of "multiple_state"; "single_state"; "state_table"; "data_object_multiple_state"; "data_object_splitted_state"
                arguments:            []
                service:              ~

            # List of supported entity classes. Take a look at the Symfony docs for more details.
            supports:

                # Example:
                - \Pimcore\Model\DataObject\Product

            # Can be used to implement a special logic which subjects are supported by the workflow. For example only products matching certain criteria. Take a look at the support strategies page of the Pimcore workflow docs for more details.
            support_strategy:

                # Examples:
                type:                expression
                arguments:
                    - \Pimcore\Model\DataObject\Product
                    - subject.getProductType() == "article" and is_fully_authenticated() and "ROLE_PIMCORE_ADMIN" in roles

                # Type "expression": a symfony expression to define a criteria.
                type:                 ~ # One of "expression"
                arguments:            []

                # Define a custom service to handle the logic. Take a look at the Symfony docs for more details.
                service:              ~

            # Will get way over initial_place and adds the possibility to add multiple initial places.
            initial_markings:     []

            # DEPRECATED: Will be applied when the current place is empty.
            initial_place:        null

            places:

                # Example:
                places:
                    closed:
                        label:               close product
                        permissions:
                            -
                                condition:           is_fully_authenticated() and 'ROLE_PIMCORE_ADMIN' in roles
                                modify:
                            -
                                modify:
                                objectLayout:        2

                # Prototype
                -

                    # Nice name which will be used in the Pimcore backend.
                    label:                ~

                    # Title/tooltip for this place when it is displayed in the header of the Pimcore element detail view in the backend.
                    title:                ''

                    # Color of the place which will be used in the Pimcore backend.
                    color:                '#bfdadc'

                    # If set to true the color will be used as border and font color otherwise as background color.
                    colorInverted:        false

                    # If set to false, the place will be hidden in the header of the Pimcore element detail view in the backend.
                    visibleInHeader:      true
                    permissions:

                        # Prototype
                        -

                            # A symfony expression can be configured here. The first set of permissions which are matching the condition will be used.
                            condition:            ~

                            # save permission as it can be configured in Pimcore workplaces
                            save:                 ~

                            # publish permission as it can be configured in Pimcore workplaces
                            publish:              ~

                            # unpublish permission as it can be configured in Pimcore workplaces
                            unpublish:            ~

                            # delete permission as it can be configured in Pimcore workplaces
                            delete:               ~

                            # rename permission as it can be configured in Pimcore workplaces
                            rename:               ~

                            # view permission as it can be configured in Pimcore workplaces
                            view:                 ~

                            # settings permission as it can be configured in Pimcore workplaces
                            settings:             ~

                            # versions permission as it can be configured in Pimcore workplaces
                            versions:             ~

                            # properties permission as it can be configured in Pimcore workplaces
                            properties:           ~

                            # a short hand for save, publish, unpublish, delete + rename
                            modify:               ~

                            # if set, the user will see the configured custom data object layout
                            objectLayout:         ~
            transitions:          # Required

                # Example:
                close_product:
                    from:                open
                    to:                  closed
                    options:
                        label:               close product
                        notes:
                            commentEnabled:      1
                            commentRequired:     1
                            additionalFields:
                                -
                                    name:                accept
                                    title:               accept terms
                                    required:            1
                                    fieldType:           checkbox
                                -
                                    name:                select
                                    title:               please select a type
                                    setterFn:            setSpecialWorkflowType
                                    fieldType:           select
                                    fieldTypeSettings:
                                        options:
                                            -
                                                key:                 Option A
                                                value:               a
                                            -
                                                key:                 Option B
                                                value:               b
                                            -
                                                key:                 Option C
                                                value:               c

                # Prototype
                -
                    name:                 ~ # Required

                    # An expression to block the transition
                    guard:                ~ # Example: is_fully_authenticated() and has_role('ROLE_JOURNALIST') and subject.getTitle() == 'My first article'
                    from:                 []
                    to:                   []
                    options:

                        # Nice name for the Pimcore backend.
                        label:                ~
                        notes:

                            # If enabled a detail window will open when the user executes the transition. In this detail view the user be asked to enter a "comment". This comment then will be used as comment for the notes/events feature.
                            commentEnabled:       false

                            # Set this to true if the comment should be a required field.
                            commentRequired:      false

                            # Can be used for data objects. The comment will be saved to the data object additionally to the notes/events through this setter function.
                            commentSetterFn:      ~

                            # Can be used for data objects to prefill the comment field with data from the data object.
                            commentGetterFn:      ~

                            # Set's the type string in the saved note.
                            type:                 'Status update'

                            # An optional alternative "title" for the note, if blank the actions transition result is used.
                            title:                ~

                            # Add additional field to the transition detail window.
                            additionalFields:

                                # Prototype
                                -

                                    # The technical name used in the input form.
                                    name:                 ~ # Required

                                    # The data component name/field type.
                                    fieldType:            ~ # One of "input"; "textarea"; "select"; "datetime"; "date"; "user"; "checkbox", Required

                                    # The label used by the field
                                    title:                ~

                                    # Whether or not the field is required.
                                    required:             false

                                    # Optional setter function (available in the element, for example in the updated object), if not specified, data will be added to notes. The Workflow manager will call the function with the whole field data.
                                    setterFn:             ~

                                    # Will be passed to the underlying Pimcore data object field type. Can be used to configure the options of a select box for example.
                                    fieldTypeSettings:    []

                        # Css class to define the icon which will be used in the actions button in the backend.
                        iconClass:            ~
                        # Forces an object layout after the transition was performed.
                        # This objectLayout setting overrules all objectLayout settings within the places configs.
                        objectLayout:            false
                        notificationSettings:

                            # Prototype
                            -

                                # A symfony expression can be configured here. All sets of notification which are matching the condition will be used.
                                condition:            ~

                                # Send a email notification to a list of users (user names) when the transition get's applied
                                notifyUsers:          []

                                # Send a email notification to a list of user roles (role names) when the transition get's applied
                                notifyRoles:          []

                                # Define which channel notification should be sent to, possible values "mail" and "pimcore_notification", default value is "mail".
                                channelType:

                                    # Default:
                                    - mail


                                # Type of mail source.
                                mailType:             template # One of "template"; "pimcore_document"

                                # Path to mail source - either Symfony path to template or fullpath to Pimcore document. Optional use %_locale% as placeholder for language.
                                mailPath:             '@PimcoreCore/Workflow/NotificationEmail/notificationEmail.html.twig'

                        # Change published state of element while transition (only available for documents and data objects).
                        changePublishedState: no_change # One of "no_change"; "force_unpublished"; "force_published", "save_version" (since Pimcore 6.6.0)

            # Actions which will be added to actions button independently of the current workflow place.
            globalActions:

                # Prototype
                -

                    # Nice name for the Pimcore backend.
                    label:                ~

                    # Css class to define the icon which will be used in the actions button in the backend.
                    iconClass:            ~
                    
                    # Forces an object layout after the global action was performed.
                    # This objectLayout setting overrules all objectLayout settings within the places configs.
                    objectLayout:         false

                    # An expression to block the action
                    guard:                ~ # Example: is_fully_authenticated() and has_role('ROLE_JOURNALIST') and subject.getTitle() == 'My first article'

                    # Optionally set the current place of the workflow. Can be used for example to reset the workflow to the initial place.
                    to:                   []

                    # See notes section of transitions. It works exactly the same way.
                    notes:
                        commentEnabled:       false
                        commentRequired:      false
                        commentSetterFn:      ~
                        commentGetterFn:      ~
                        type:                 'Status update'
                        title:                ~
                        additionalFields:

                            # Prototype
                            -
                                name:                 ~ # Required
                                fieldType:            ~ # One of "input"; "textarea"; "select"; "datetime"; "date"; "user"; "checkbox", Required
                                title:                ~
                                required:             false
                                setterFn:             ~
                                fieldTypeSettings:    []
```

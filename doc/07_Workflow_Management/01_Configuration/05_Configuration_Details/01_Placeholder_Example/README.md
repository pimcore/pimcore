---
title: Placeholder Example
description: Reuse workflow configurations across locales with YAML placeholders and anchors.
---

# Placeholder Example

Workflows support placeholders. Reuse an existing workflow definition and reapply places
and transitions onto a new workflow by substituting placeholder values.

## Available Options

Example:
```yaml
pimcore:
  workflows:
    product_workflow_local_de: &local_workflow_reference
        placeholders:
          '%%stateFieldName%%': 'translationStateDe'
          '%%locale%%': 'de'
        enabled: true
        label: "Local Release Workflow"
        type: "state_machine"
        supports: &local_workflow_supports_ref
          - \Pimcore\Model\DataObject\Product
        initial_markings: "local_300_basic_data_pending"
        marking_store: &local_workflow_marking_store_ref
          type: single_state
          arguments:
            -
              %%stateFieldName%%
        places: &local_workflow_places_ref

            100_new:
              label: "Basic Data Pending from Global Workflow (%%locale%%)"
              visibleInHeader: true
              color:  '#cddc39'
              colorInverted: false
              permissions:
                - publish: true

            local_300_basic_data_pending:
              label: "Pending"
              visibleInHeader: true
              color:  '#cddc39'
              colorInverted: false
              permissions:
                - publish: true

             ...

        transitions: &local_workflow_transitions_ref
            to_published:
                to: local_400_published
                from: [local_700_locked]
                guard: is_fully_authenticated() and ('ROLE_PIMCORE_ADMIN' in role_names or 'ROLE_RECIPE_WORKFLOW_FINISH_DEVELOPMENT' in role_names)
                options:
                  label: 'Set to local_400_published'

            ....
```

Example cloned workflows:
```yaml
    product_workflow_local_en:
        placeholders:
          '%%stateFieldName%%': 'translationStateEn'
          '%%locale%%': 'en'
          '%%flag%%': 'gb'
        enabled: true
        label: "Local Release Workflow"
        type: "state_machine"
        supports:
          <<: *local_workflow_supports_ref
        initial_markings: "local_300_basic_data_pending"
        marking_store:
          <<: *local_workflow_marking_store_ref
        places:
          <<: *local_workflow_places_ref
        transitions:
          <<: *local_workflow_transitions_ref

    product_workflow_local_hr:
      placeholders:
        '%%stateFieldName%%': "translationStateHr"
        '%%locale%%': "hr"
        '%%flag%%': 'hr'
      enabled: true
      label: "Local Release Workflow"
      type: "state_machine"
      supports:
        <<: *local_workflow_supports_ref
      initial_markings: "local_300_basic_data_pending"
      marking_store:
        <<: *local_workflow_marking_store_ref
      places:
        <<: *local_workflow_places_ref
      transitions:
        <<: *local_workflow_transitions_ref

    product_workflow_local_ro:
      placeholders:
        '%%stateFieldName%%': "translationStateRo"
        '%%locale%%': "ro"
        '%%flag%%': 'ro'
      enabled: true
      label: "Local Release Workflow"
      type: "state_machine"
      supports:
        <<: *local_workflow_supports_ref
      initial_markings: "local_300_basic_data_pending"
      marking_store:
        <<: *local_workflow_marking_store_ref
      places:
        <<: *local_workflow_places_ref
      transitions:
        <<: *local_workflow_transitions_ref
```

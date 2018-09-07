# Marking Stores

The Pimcore workflow engine provides several different ways how to store the acutal places of a subject. These are represented of the following marking store types.

## state_table (default)

This is the default marking store. The place information is stored in the element_workfow_state table. This would be the best option for Assets and Documents. For data objects the following 3 options might be the better choice as the data would be stored directly in the data object model as attributes.

## single_state

Stores the place in a attribute of the subject (calls the setter method). Can be used if a model cannot be in more then one state at the same time. This is the default single_state marking store provided by the Symfony framework. For data objects a select field (or maybe input field) would be the right Pimcore field to store the places when the single_state marking store is used.

## multiple_state

Same as single_state but can be used if the subject can be in more then one state at the same time. Therefore a multiselect field would be the best option if used for data objects.

## data_object_splitted_state
@TODO
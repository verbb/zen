<?php

use craft\fields\Entries;
use craft\fields\Matrix;
use craft\fields\Number;
use craft\fields;
use craft\fields\PlainText;
use craft\fields\Table;

return [
    [
        'uid' => 'field-layout-1000----------------uid',
        'type' => 'field_layout_with_matrix_and_normal_fields',
        'tabs' => [
            [
                'name' => 'Tab 1',
                'fields' => [
                    [
                        'name' => 'Test Assets Field 1',
                        'handle' => 'testAssetsField1',
                        'fieldType' => fields\Assets::class,
                        'uid' => 'assets-field---------------------uid',
                    ],
                    [
                        'name' => 'Test Categories Field 1',
                        'handle' => 'testCategoriesField1',
                        'fieldType' => fields\Categories::class,
                        'source' => 'group:category-group-1001--------------uid',
                        'uid' => 'categories-field-----------------uid',
                    ],
                    [
                        'name' => 'Test Checkboxes Field 1',
                        'handle' => 'testCheckboxesField1',
                        'fieldType' => fields\Checkboxes::class,
                        'options' => [
                            ['label' => 'Option 1', 'value' => 'option1'],
                            ['label' => 'Option 2', 'value' => 'option2'],
                            ['label' => 'Option 3', 'value' => 'option3'],
                            ['label' => 'Option 4', 'value' => 'option4'],
                        ],
                        'uid' => 'checkboxes-field-----------------uid',
                    ],
                    [
                        'name' => 'Test Color Field 1',
                        'handle' => 'testColorField1',
                        'fieldType' => fields\Color::class,
                        'uid' => 'color-field----------------------uid',
                    ],
                    [
                        'name' => 'Test Date Field 1',
                        'handle' => 'testDateField1',
                        'fieldType' => fields\Date::class,
                        'uid' => 'date-field-----------------------uid',
                    ],
                    [
                        'name' => 'Test Dropdown Field 1',
                        'handle' => 'testDropdownField1',
                        'fieldType' => fields\Dropdown::class,
                        'options' => [
                            ['label' => 'Option 1', 'value' => 'option1'],
                            ['label' => 'Option 2', 'value' => 'option2'],
                            ['label' => 'Option 3', 'value' => 'option3'],
                            ['label' => 'Option 4', 'value' => 'option4'],
                        ],
                        'uid' => 'dropdown-field-------------------uid',
                    ],
                    [
                        'name' => 'Test Email Field 1',
                        'handle' => 'testEmailField1',
                        'fieldType' => fields\Email::class,
                        'uid' => 'email-field----------------------uid',
                    ],
                    [
                        'name' => 'Test Entries Field 1',
                        'handle' => 'testEntriesField1',
                        'fieldType' => fields\Entries::class,
                        'uid' => 'entries-field--------------------uid',
                    ],
                    [
                        'name' => 'Test Lightswitch Field 1',
                        'handle' => 'testLightswitchField1',
                        'fieldType' => fields\Lightswitch::class,
                        'uid' => 'lightswitch-field----------------uid',
                    ],
                    [
                        'name' => 'Test Matrix Field 1',
                        'handle' => 'testMatrixField1',
                        'fieldType' => fields\Matrix::class,
                        'blockTypes' => [
                            'new1' => [
                                'name' => 'Test Block',
                                'handle' => 'testBlock',
                                'fields' => [
                                    'new1' => [
                                        'name' => 'Test Assets Field 1',
                                        'handle' => 'testAssetsField1',
                                        'type' => fields\Assets::class,
                                    ],
                                    'new2' => [
                                        'name' => 'Test Categories Field 1',
                                        'handle' => 'testCategoriesField1',
                                        'type' => fields\Categories::class,
                                        'typesettings' => [
                                            'source' => 'group:category-group-1001--------------uid',
                                        ],
                                    ],
                                    'new3' => [
                                        'name' => 'Test Checkboxes Field 1',
                                        'handle' => 'testCheckboxesField1',
                                        'type' => fields\Checkboxes::class,
                                        'typesettings' => [
                                            'options' => [
                                                ['label' => 'Option 1', 'value' => 'option1'],
                                                ['label' => 'Option 2', 'value' => 'option2'],
                                                ['label' => 'Option 3', 'value' => 'option3'],
                                                ['label' => 'Option 4', 'value' => 'option4'],
                                            ],
                                        ],
                                    ],
                                    'new4' => [
                                        'name' => 'Test Color Field 1',
                                        'handle' => 'testColorField1',
                                        'type' => fields\Color::class,
                                    ],
                                    'new5' => [
                                        'name' => 'Test Date Field 1',
                                        'handle' => 'testDateField1',
                                        'type' => fields\Date::class,
                                    ],
                                    'new6' => [
                                        'name' => 'Test Dropdown Field 1',
                                        'handle' => 'testDropdownField1',
                                        'type' => fields\Dropdown::class,
                                        'typesettings' => [
                                            'options' => [
                                                ['label' => 'Option 1', 'value' => 'option1'],
                                                ['label' => 'Option 2', 'value' => 'option2'],
                                                ['label' => 'Option 3', 'value' => 'option3'],
                                                ['label' => 'Option 4', 'value' => 'option4'],
                                            ],
                                        ],
                                    ],
                                    'new7' => [
                                        'name' => 'Test Email Field 1',
                                        'handle' => 'testEmailField1',
                                        'type' => fields\Email::class,
                                    ],
                                    'new8' => [
                                        'name' => 'Test Entries Field 1',
                                        'handle' => 'testEntriesField1',
                                        'type' => fields\Entries::class,
                                    ],
                                    'new9' => [
                                        'name' => 'Test Lightswitch Field 1',
                                        'handle' => 'testLightswitchField1',
                                        'type' => fields\Lightswitch::class,
                                    ],
                                    'new10' => [
                                        'name' => 'Test Money Field 1',
                                        'handle' => 'testMoneyField1',
                                        'type' => fields\Money::class,
                                    ],
                                    'new11' => [
                                        'name' => 'Test Multi Select Field 1',
                                        'handle' => 'testMultiSelectField1',
                                        'type' => fields\MultiSelect::class,
                                        'typesettings' => [
                                            'options' => [
                                                ['label' => 'Option 1', 'value' => 'option1'],
                                                ['label' => 'Option 2', 'value' => 'option2'],
                                                ['label' => 'Option 3', 'value' => 'option3'],
                                                ['label' => 'Option 4', 'value' => 'option4'],
                                            ],
                                        ],
                                    ],
                                    'new12' => [
                                        'name' => 'Test Number Field 1',
                                        'handle' => 'testNumberField1',
                                        'type' => fields\Number::class,
                                    ],
                                    'new13' => [
                                        'name' => 'Test Plain Text Field 1',
                                        'handle' => 'testPlainTextField1',
                                        'type' => fields\PlainText::class,
                                    ],
                                    'new14' => [
                                        'name' => 'Test Radio Buttons Field 1',
                                        'handle' => 'testRadioButtonsField1',
                                        'type' => fields\RadioButtons::class,
                                        'typesettings' => [
                                            'options' => [
                                                ['label' => 'Option 1', 'value' => 'option1'],
                                                ['label' => 'Option 2', 'value' => 'option2'],
                                                ['label' => 'Option 3', 'value' => 'option3'],
                                                ['label' => 'Option 4', 'value' => 'option4'],
                                            ],
                                        ],
                                    ],
                                    'new15' => [
                                        'name' => 'Test Table Field 1',
                                        'handle' => 'testTableField1',
                                        'type' => fields\Table::class,
                                        'typesettings' => [
                                            'columns' => [
                                                'col1' => ['heading' => 'Checkbox', 'handle' => 'checkbox', 'type' => 'checkbox'],
                                                'col2' => ['heading' => 'Color', 'handle' => 'color', 'type' => 'color'],
                                                'col3' => ['heading' => 'Date', 'handle' => 'date', 'type' => 'date'],
                                                'col4' => ['heading' => 'Select', 'handle' => 'select', 'type' => 'select', 'options' => [
                                                    ['label' => 'Option 1', 'value' => 'option1'],
                                                    ['label' => 'Option 2', 'value' => 'option2'],
                                                    ['label' => 'Option 3', 'value' => 'option3'],
                                                    ['label' => 'Option 4', 'value' => 'option4'],
                                                ]],
                                                'col5' => ['heading' => 'Email', 'handle' => 'email', 'type' => 'email'],
                                                'col6' => ['heading' => 'Lightswitch', 'handle' => 'lightswitch', 'type' => 'lightswitch'],
                                                'col7' => ['heading' => 'Multi-line Text', 'handle' => 'multiline', 'type' => 'multiline'],
                                                'col8' => ['heading' => 'Number', 'handle' => 'number', 'type' => 'number'],
                                                'col9' => ['heading' => 'Row Heading', 'handle' => 'heading', 'type' => 'heading'],
                                                'col10' => ['heading' => 'Single-line Text', 'handle' => 'singleline', 'type' => 'singleline'],
                                                'col11' => ['heading' => 'Time', 'handle' => 'time', 'type' => 'time'],
                                                'col12' => ['heading' => 'URL', 'handle' => 'url', 'type' => 'url'],
                                            ],
                                        ],
                                    ],
                                    'new16' => [
                                        'name' => 'Test Tags Field 1',
                                        'handle' => 'testTagsField1',
                                        'type' => fields\Tags::class,
                                    ],
                                    'new17' => [
                                        'name' => 'Test Time Field 1',
                                        'handle' => 'testTimeField1',
                                        'type' => fields\Time::class,
                                    ],
                                    'new18' => [
                                        'name' => 'Test Url Field 1',
                                        'handle' => 'testUrlField1',
                                        'type' => fields\Url::class,
                                    ],
                                    'new19' => [
                                        'name' => 'Test Users Field 1',
                                        'handle' => 'testUsersField1',
                                        'type' => fields\Users::class,
                                    ],
                                ],
                            ],
                        ],
                        'uid' => 'matrix-field---------------------uid',
                    ],
                    [
                        'name' => 'Test Money Field 1',
                        'handle' => 'testMoneyField1',
                        'fieldType' => fields\Money::class,
                        'uid' => 'money-field----------------------uid',
                    ],
                    [
                        'name' => 'Test Multi Select Field 1',
                        'handle' => 'testMultiSelectField1',
                        'fieldType' => fields\MultiSelect::class,
                        'options' => [
                            ['label' => 'Option 1', 'value' => 'option1'],
                            ['label' => 'Option 2', 'value' => 'option2'],
                            ['label' => 'Option 3', 'value' => 'option3'],
                            ['label' => 'Option 4', 'value' => 'option4'],
                        ],
                        'uid' => 'multi-select-field---------------uid',
                    ],
                    [
                        'name' => 'Test Number Field 1',
                        'handle' => 'testNumberField1',
                        'fieldType' => fields\Number::class,
                        'uid' => 'number-field---------------------uid',
                    ],
                    [
                        'name' => 'Test Plain Text Field 1',
                        'handle' => 'testPlainTextField1',
                        'fieldType' => fields\PlainText::class,
                        'uid' => 'plain-text-field-----------------uid',
                    ],
                    [
                        'name' => 'Test Radio Buttons Field 1',
                        'handle' => 'testRadioButtonsField1',
                        'fieldType' => fields\RadioButtons::class,
                        'options' => [
                            ['label' => 'Option 1', 'value' => 'option1'],
                            ['label' => 'Option 2', 'value' => 'option2'],
                            ['label' => 'Option 3', 'value' => 'option3'],
                            ['label' => 'Option 4', 'value' => 'option4'],
                        ],
                        'uid' => 'radio-field----------------------uid',
                    ],
                    [
                        'name' => 'Test Table Field 1',
                        'handle' => 'testTableField1',
                        'fieldType' => fields\Table::class,
                        'columns' => [
                            'col1' => ['heading' => 'Checkbox', 'handle' => 'checkbox', 'type' => 'checkbox'],
                            'col2' => ['heading' => 'Color', 'handle' => 'color', 'type' => 'color'],
                            'col3' => ['heading' => 'Date', 'handle' => 'date', 'type' => 'date'],
                            'col4' => ['heading' => 'Select', 'handle' => 'select', 'type' => 'select', 'options' => [
                                ['label' => 'Option 1', 'value' => 'option1'],
                                ['label' => 'Option 2', 'value' => 'option2'],
                                ['label' => 'Option 3', 'value' => 'option3'],
                                ['label' => 'Option 4', 'value' => 'option4'],
                            ]],
                            'col5' => ['heading' => 'Email', 'handle' => 'email', 'type' => 'email'],
                            'col6' => ['heading' => 'Lightswitch', 'handle' => 'lightswitch', 'type' => 'lightswitch'],
                            'col7' => ['heading' => 'Multi-line Text', 'handle' => 'multiline', 'type' => 'multiline'],
                            'col8' => ['heading' => 'Number', 'handle' => 'number', 'type' => 'number'],
                            'col9' => ['heading' => 'Row Heading', 'handle' => 'heading', 'type' => 'heading'],
                            'col10' => ['heading' => 'Single-line Text', 'handle' => 'singleline', 'type' => 'singleline'],
                            'col11' => ['heading' => 'Time', 'handle' => 'time', 'type' => 'time'],
                            'col12' => ['heading' => 'URL', 'handle' => 'url', 'type' => 'url'],
                        ],
                        'uid' => 'table-field----------------------uid',
                    ],
                    [
                        'name' => 'Test Tags Field 1',
                        'handle' => 'testTagsField1',
                        'fieldType' => fields\Tags::class,
                        'uid' => 'tags-field-----------------------uid',
                    ],
                    [
                        'name' => 'Test Time Field 1',
                        'handle' => 'testTimeField1',
                        'fieldType' => fields\Time::class,
                        'uid' => 'time-field-----------------------uid',
                    ],
                    [
                        'name' => 'Test Url Field 1',
                        'handle' => 'testUrlField1',
                        'fieldType' => fields\Url::class,
                        'uid' => 'url-field------------------------uid',
                    ],
                    [
                        'name' => 'Test Users Field 1',
                        'handle' => 'testUsersField1',
                        'fieldType' => fields\Users::class,
                        'uid' => 'users-field----------------------uid',
                    ],
                ],
            ],
        ],
    ],
];
<?php

use craft\fields;

return [
    [
        'groupId' => 1,
        'name' => 'Assets for Diff',
        'handle' => 'assetsForDiff',
        'fieldType' => fields\Assets::class,
        'uid' => 'assets-field---------------------uid',
    ],
    [
        'groupId' => 1,
        'name' => 'Matrix for Diff',
        'handle' => 'matrixForDiff',
        'fieldType' => fields\Matrix::class,
        'uid' => 'matrix-field---------------------uid',
        'blockTypes' => [
            'new1' => [
                'name' => 'A Block',
                'handle' => 'aBlock',
                'fields' => [
                    'new1' => [
                        'name' => 'First Subfield',
                        'handle' => 'firstSubfield',
                        'type' => fields\PlainText::class,
                    ],
                    'new2' => [
                        'name' => 'Assets Subfield',
                        'handle' => 'assetsSubfield',
                        'type' => fields\Assets::class,
                    ],
                ],
            ],
        ],
    ],
];
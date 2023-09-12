<?php

return [
    [
        'id' => '1000',
        'structureId' => null,
        'name' => 'Test Section',
        'handle' => 'testSection',
        'type' => 'channel',
        'enableVersioning' => true,
        'propagationMethod' => 'all',
        'defaultPlacement' => 'end',
        'previewTargets' => [
            ['label' => 'Primary entry page', 'urlFormat' => '{url}'],
        ],
        'uid' => 'section-1000---------------------uid',
    ],
    [
        'id' => '1001',
        'structureId' => null,
        'name' => 'Test Section with Fields',
        'handle' => 'testSectionWithFields',
        'type' => 'channel',
        'enableVersioning' => true,
        'propagationMethod' => 'all',
        'defaultPlacement' => 'end',
        'previewTargets' => [
            ['label' => 'Primary entry page', 'urlFormat' => '{url}'],
        ],
        'uid' => 'section-1001---------------------uid',
    ],
];

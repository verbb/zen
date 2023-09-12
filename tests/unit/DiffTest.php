<?php
namespace verbb\zentests\unit;

use verbb\zentests\fixtures\FieldsFixture;

use verbb\zen\helpers\DiffHelper;
use verbb\zen\models\DiffAdd;
use verbb\zen\models\DiffChange;
use verbb\zen\models\DiffRemove;
use verbb\zen\models\MapDiffer;
use verbb\zen\models\ElementDiffer;
use verbb\zen\services\Fields;

use Craft;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;

use yii\base\Event;

use Codeception\Test\Unit;

use UnitTester;

class DiffTest extends Unit
{
    protected UnitTester $tester;
    protected ElementDiffer $differ;

    public function _fixtures(): array
    {
        return [
            'fields' => [
                'class' => FieldsFixture::class,
            ],
        ];
    }

    protected function _before()
    {
        $this->differ = new ElementDiffer();
    }

    protected function diffTest(array $old, array $new, array $expected, string $message): void
    {
        $actual = $this->differ->doDiff($old, $new);

        // codecept_debug($actual);

        // Use `assertEquals` rather than `assetSame` to handle comparing objects
        self::assertEquals($expected, $actual, $message);
    }

    public function testBasicDiffs()
    {
        $old = [];
        $new = [];
        $expected = [];

        $this->diffTest($old, $new, $expected, 'There should be no difference between empty arrays.');

        $old = ['a'];
        $new = ['a'];
        $expected = [];

        $this->diffTest($old, $new, $expected, 'There should be no difference between two arrays with the same element.');

        $old = ['a', 'b'];
        $new = ['a', 'b'];
        $expected = [];

        $this->diffTest($old, $new, $expected, 'There should be no difference between two arrays with the same element.');

        $old = ['a', 10, false, null, [], ['b', 4.2]];
        $new = ['a', 10, false, null, [], ['b', 4.2]];
        $expected = [];

        $this->diffTest($old, $new, $expected, 'There should be no difference between two arrays with the same elements.');

        $old = [42, 42, 42];
        $new = [42, 42, 42];
        $expected = [];

        $this->diffTest($old, $new, $expected, 'There should be no difference between two arrays with the same elements.');

        $old = ['a', 'b'];
        $new = ['b', 'a'];
        $expected = [
            new DiffChange(['oldValue' => 'a', 'newValue' => 'b']),
            new DiffChange(['oldValue' => 'b', 'newValue' => 'a']),
        ];

        $this->diffTest($old, $new, $expected, 'Switching position should cause a diff.');

        $old = ['a', 'b', 'c', 'd'];
        $new = ['a', 'c', 'b', 'd'];
        $expected = [
            1 => new DiffChange(['oldValue' => 'b', 'newValue' => 'c']),
            2 => new DiffChange(['oldValue' => 'c', 'newValue' => 'b']),
        ];

        $this->diffTest($old, $new, $expected, 'Switching position should cause a diff.');

        $old = ['a' => 0, 'b' => 1, 'c' => 0];
        $new = ['a' => 42, 'b' => 1, 'c' => 42];
        $expected = [
            'a' => new DiffChange(['oldValue' => 0, 'newValue' => 42]),
            'c' => new DiffChange(['oldValue' => 0, 'newValue' => 42]),
        ];

        $this->diffTest($old, $new, $expected, 'Doing the same change to two different elements should result in two identical changes.');

        $old = ['a' => 0, 'b' => 1];
        $new = ['a' => 0, 'c' => 1];
        $expected = [
            'b' => new DiffRemove(['oldValue' => 1]),
            'c' => new DiffAdd(['newValue' => 1]),
        ];

        $this->diffTest($old, $new, $expected, 'Changing the key of an element should result in a remove and an add diff.');

        $old = ['a' => 0, 'b' => 1];
        $new = ['b' => 1, 'a' => 0];
        $expected = [];

        $this->diffTest($old, $new, $expected, 'Changing the order of associative elements should have no effect.');

        $old = ['a' => ['b']];
        $new = ['a' => ['b']];
        $expected = [];

        $this->diffTest($old, $new, $expected, 'Comparing equal nested elements should return nothing.');

        $old = [];
        $new = ['a' => ['b', 'c']];
        $expected = [
            'a' => new DiffAdd(['newValue' => ['b', 'c']]),
        ];

        $this->diffTest($old, $new, $expected, 'Adding a nested element should result in a single add diff.');

        $old = ['a' => ['b' => 1]];
        $new = ['a' => ['b' => 2]];
        $expected = [
            'a' => new DiffChange(['oldValue' => ['b' => 1], 'newValue' => ['b' => 2]]),
        ];

        $this->diffTest($old, $new, $expected, 'Comparing nested associative arrays.');

        $old = ['a' => ['b' => 0, 'c' => 1]];
        $new = ['a' => ['c' => 1, 'b' => 0]];
        $expected = [
            'a' => new DiffChange(['oldValue' => ['b' => 0, 'c' => 1], 'newValue' => ['c' => 1, 'b' => 0]]),
        ];

        $this->diffTest($old, $new, $expected, 'Changing the order of nested associative elements should produce a change diff.');
    }

    public function testFieldDiffs()
    {
        $old = ['fields' => []];
        $new = ['fields' => ['a' => 1]];
        $expected = [
            'fields' => ['a' => new DiffAdd(['newValue' => 1])],
        ];

        $this->diffTest($old, $new, $expected, 'Adding new fields when empty.');

        $old = ['fields' => ['a' => null]];
        $new = ['fields' => ['a' => 1]];
        $expected = [
            'fields' => ['a' => new DiffAdd(['newValue' => 1])],
        ];

        $this->diffTest($old, $new, $expected, 'Adding new fields when null.');

        $old = ['fields' => ['a' => 1]];
        $new = ['fields' => []];
        $expected = [
            'fields' => ['a' => new DiffRemove(['oldValue' => 1])],
        ];

        $this->diffTest($old, $new, $expected, 'Removing new fields when empty.');

        $old = ['fields' => ['a' => 1]];
        $new = ['fields' => ['a' => null]];
        $expected = [
            'fields' => ['a' => new DiffRemove(['oldValue' => 1])],
        ];

        $this->diffTest($old, $new, $expected, 'Removing new fields when null.');

        $old = ['fields' => ['a' => ['b' => 2, 'c' => 3]]];
        $new = ['fields' => ['a' => ['b' => 22, 'c' => 33]]];
        $expected = [
            'fields' => ['a' => new DiffChange(['oldValue' => ['b' => 2, 'c' => 3], 'newValue' => ['b' => 22, 'c' => 33]])],
        ];

        $this->diffTest($old, $new, $expected, 'Changing fields should not list each individual change. Just at the field lavel.');

        $old = ['fields' => ['a' => ['b' => null]]];
        $new = ['fields' => ['a' => ['b' => 1]]];
        $expected = [
            'fields' => ['a' => new DiffChange(['oldValue' => ['b' => null], 'newValue' => ['b' => 1]])],
        ];

        $this->diffTest($old, $new, $expected, 'Adding field settings results in a change for the field, not an add.');

        $old = [
            'fields' => [
                'assetsForDiff:assets-field---------------------uid' => [
                    ['id' => 1234, 'fields' => ['plainText' => 'b']],
                ],
            ],
        ];

        $new = [
            'fields' => [
                'assetsForDiff:assets-field---------------------uid' => [
                    ['id' => 4321, 'fields' => ['plainText' => 'c']],
                ],
            ],
        ];

        $expected = [
            'fields' => [
                'assetsForDiff:assets-field---------------------uid' => new DiffChange([
                    'oldValue' => [['id' => 1234]], 'newValue' => [['id' => 4321]],
                ]),
            ],
        ];

        $this->diffTest($old, $new, $expected, 'Relation fields should have their inner fields ignored, but still include changes.');

        $old = [
            'fields' => [
                'assetsForDiff:assets-field---------------------uid' => [
                    ['id' => 1234, 'fields' => ['plainText' => 'b']],
                ],
            ],
        ];

        $new = [
            'fields' => [
                'assetsForDiff:assets-field---------------------uid' => [
                    ['id' => 1234, 'fields' => ['plainText' => 'c']],
                ],
            ],
        ];

        $expected = [];

        $this->diffTest($old, $new, $expected, 'Relation fields should have their inner fields ignored.');

        $old = [
            'fields' => [
                'matrixForDiff:matrix-field---------------------uid' => [
                    [
                        'type' => 'aBlock',
                        'fields' => [
                            'firstSubfield:firstSubfield-field--------------uid' => 'a',
                        ],
                    ],
                ],
            ],
        ];

        $new = [
            'fields' => [
                'matrixForDiff:matrix-field---------------------uid' => [
                    [
                        'type' => 'aBlock',
                        'fields' => [
                            'firstSubfield:firstSubfield-field--------------uid' => 'b',
                        ],
                    ],
                ],
            ],
        ];

        $expected = [
            'fields' => [
                'matrixForDiff:matrix-field---------------------uid' => [
                    [
                        'fields' => [
                            'firstSubfield:firstSubfield-field--------------uid' => new DiffChange([
                                'oldValue' => 'a',
                                'newValue' => 'b',
                            ]),
                        ],
                    ],
                ],
            ],
        ];

        $this->diffTest($old, $new, $expected, 'Changes to Matrix should be reflected correctly.');

        // We can't set the UID of inner Matrix fields in tests
        $matrixField = Craft::$app->getFields()->getFieldByHandle('matrixForDiff');
        $subFields = $matrixField->getBlockTypeFields();
        $subField = ArrayHelper::firstWhere($subFields, 'handle', 'assetsSubfield');

        $old = [
            'fields' => [
                'matrixForDiff:matrix-field---------------------uid' => [
                    [
                        'type' => 'aBlock',
                        'fields' => [
                            ('assetsSubfield:' . $subField->uid) => [
                                ['id' => 1234, 'fields' => ['plainText' => 'b']],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $new = [
            'fields' => [
                'matrixForDiff:matrix-field---------------------uid' => [
                    [
                        'type' => 'aBlock',
                        'fields' => [
                            ('assetsSubfield:' . $subField->uid) => [
                                ['id' => 4321, 'fields' => ['plainText' => 'c']],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = [
            'fields' => [
                'matrixForDiff:matrix-field---------------------uid' => [
                    [
                        'fields' => [
                            ('assetsSubfield:' . $subField->uid) => new DiffChange([
                                'oldValue' => [['id' => 1234]],
                                'newValue' => [['id' => 4321]],
                            ]),
                        ],
                    ],
                ],
            ],
        ];

        $this->diffTest($old, $new, $expected, 'Relation fields in Matrix should be reflected correctly.');
    }
}
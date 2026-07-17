<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_search\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\oe_search\Traits\TestIndexFieldCreationTrait;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\Query\ConditionGroup;
use Drupal\search_api\Query\Query;

/**
 * Tests Europa Search query expression builder.
 *
 * @covers \Drupal\oe_search\QueryExpressionBuilder
 * @group oe_search
 */
class QueryBuilderTest extends KernelTestBase {

  use TestIndexFieldCreationTrait;

  /**
   * A Search API index ID.
   *
   * @var string
   */
  protected $indexId = 'europa_search_index';

  /**
   * A Search API index.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * The Search API Europa Search backend.
   *
   * @var \Drupal\search_api\Backend\BackendInterface
   */
  protected $backend;

  /**
   * The query expression builder service.
   *
   * @var \Drupal\oe_search\QueryExpressionBuilder
   */
  protected $queryBuilder;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'field',
    'oe_search',
    'oe_search_test',
    'oe_search_mock',
    'search_api',
    'system',
    'user',
    'media',
    'image',
    'file',
    'views',
    'text',
    'datetime',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->installSchema('search_api', ['search_api_item']);
    $this->installSchema('user', ['users_data']);
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('entity_test_mulrev_changed');
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installEntitySchema('user');
    $this->installEntitySchema('search_api_task');
    $this->installConfig([
      'search_api',
      'system',
      'oe_search',
      'views',
    ]);

    // Create the bundles and fields referenced by the test search index before
    // installing the index/view config. On Drupal 11.4+ installing that config
    // eagerly resolves the data definition of every indexed field, which would
    // otherwise fail because these fields do not exist yet.
    $this->createTestIndexFields();

    $this->installConfig(['oe_search_test']);

    $this->queryBuilder = $this->container->get('oe_search.query_expression_builder');
    $this->backend = Server::load('europa_search_server')->getBackend();
    $this->index = Index::load($this->indexId);
  }

  /**
   * Tests AND condition.
   */
  public function testAndCondition() {
    $query = new Query($this->index);
    $query->addCondition('id', 10);
    $query->addCondition('body', 'hello');
    $query_expression = $this->queryBuilder->prepareConditionGroup($query->getConditionGroup(), $query);
    $expected = [
      'bool' =>
        [
          'must' =>
            [
              [
                'term' => [
                  'ID' => 10,
                ],
              ]
              ,
              [
                'term' =>
                  [
                    'BODY' => 'hello',
                  ],
              ],
            ],
        ],
    ];

    $this->assertEquals($expected, $query_expression);
  }

  /**
   * Tests all comparison operators.
   */
  public function testComparisonOperators() {
    $query = new Query($this->index);
    $query->addCondition('ID', 10);
    $query->addCondition('ID', 1, '<>');
    $query->addCondition('ID', 10, '>');
    $query->addCondition('ID', 10, '<');
    $query->addCondition('ID', 10, '>=');
    $query->addCondition('ID', 10, '<=');
    $query->addCondition('ID', [10, 100], 'IN');
    $query->addCondition('ID', [20, 200], 'NOT IN');
    $query->addCondition('BODY', NULL, 'IS NULL');
    $query->addCondition('CREATED', NULL, 'IS NOT NULL');

    $query_expression = $this->queryBuilder->prepareConditionGroup($query->getConditionGroup(), $query);
    $expected = [
      'bool' =>
        [
          'must' =>
            [
              [
                'term' => [
                  'ID' => 10,
                ],
              ],
              [
                'range' => [
                  'ID' => [
                    'gt' => 10,
                  ],
                ],
              ],
              [
                'range' => [
                  'ID' => [
                    'lt' => 10,
                  ],
                ],
              ],
              [
                'range' => [
                  'ID' => [
                    'gte' => 10,
                  ],
                ],
              ],
              [
                'range' => [
                  'ID' => [
                    'lte' => 10,
                  ],
                ],
              ],
              [
                'terms' => [
                  'ID' => [
                    10,
                    100,
                  ],
                ],
              ],
              [
                'exists' => [
                  'field' => 'CREATED',
                ],
              ],
            ],
          'must_not' =>
            [
              [
                'term' => [
                  'ID' => 1,
                ],
              ],
              [
                'terms' => [
                  'ID' => [20, 200],
                ],
              ],
              [
                'exists' => [
                  'field' => 'BODY',
                ],
              ],
            ],
        ],
    ];

    $this->assertEquals($expected, $query_expression);
  }

  /**
   * Tests OR condition.
   */
  public function testOrCondition() {
    $query = new Query($this->index);
    $conditionGroup = new ConditionGroup('OR');
    $conditionGroup->addCondition('id', 10);
    $conditionGroup->addCondition('body', 'hello');
    $query->addConditionGroup($conditionGroup);

    $query_expression = $this->queryBuilder->prepareConditionGroup($query->getConditionGroup(), $query);
    $expected = [
      'bool' =>
        [
          'should' =>
            [
              [
                'term' => [
                  'ID' => 10,
                ],
              ]
              ,
              [
                'term' =>
                  [
                    'BODY' => 'hello',
                  ],
              ],
            ],
        ],
    ];

    $this->assertEquals($expected, $query_expression);
  }

  /**
   * Tests NOT condition.
   */
  public function testNotCondition() {
    $query = new Query($this->index);
    $conditionGroup = new ConditionGroup('AND');
    $conditionGroup->addCondition('id', 10, '<>');
    $query->addConditionGroup($conditionGroup);

    $query_expression = $this->queryBuilder->prepareConditionGroup($query->getConditionGroup(), $query);
    $expected = [
      'bool' =>
        [
          'must_not' =>
            [
              [
                'term' => [
                  'ID' => 10,
                ],
              ],
            ],
        ],
    ];
    $this->assertEquals($expected, $query_expression);
  }

  /**
   * Tests several combined conditions.
   */
  public function testAndOrCondition() {
    $query = new Query($this->index);
    $andConditionGroup = new ConditionGroup('AND');
    $andConditionGroup->addCondition('id', 10, '<>');

    $orConditionGroup = new ConditionGroup('OR');
    $orConditionGroup->addCondition('body', 'Node body', '=');
    $orConditionGroup->addCondition('created', '1664883852', '>');

    // First test AND, OR.
    $conditionGroup = new ConditionGroup('AND');
    $conditionGroup->addConditionGroup($andConditionGroup);
    $conditionGroup->addConditionGroup($orConditionGroup);
    $query->addConditionGroup($conditionGroup);

    $query_expression = $this->queryBuilder->prepareConditionGroup($query->getConditionGroup(), $query);
    $expected = [
      'bool' =>
        [
          'must' =>
            [
              [
                'bool' =>
                  [
                    'must_not' =>
                      [
                        [
                          'term' => [
                            'ID' => 10,
                          ],
                        ],
                      ],
                  ],
              ],
              [
                'bool' =>
                  [
                    'should' =>
                      [
                        [
                          'term' => [
                            'BODY' => 'Node body',
                          ],
                        ],
                        [
                          'range' => [
                            'CREATED' => [
                              'gt' => '1664883852',
                            ],
                          ],
                        ],
                      ],
                  ],
              ],
            ],
        ],
    ];
    $this->assertEquals($expected, $query_expression);

    // Now test OR, And.
    $conditionGroup = new ConditionGroup('OR');
    $conditionGroup->addConditionGroup($orConditionGroup);
    $conditionGroup->addConditionGroup($andConditionGroup);
    $query = new Query($this->index);
    $query->addConditionGroup($conditionGroup);
    $query_expression = $this->queryBuilder->prepareConditionGroup($query->getConditionGroup(), $query);

    $expected = [
      'bool' =>
        [
          'should' =>
            [
              [
                'bool' =>
                  [
                    'should' =>
                      [
                        [
                          'term' => [
                            'BODY' => 'Node body',
                          ],
                        ],
                        [
                          'range' => [
                            'CREATED' => [
                              'gt' => '1664883852',
                            ],
                          ],
                        ],
                      ],
                  ],
              ],
              [
                'bool' =>
                  [
                    'must_not' =>
                      [
                        [
                          'term' => [
                            'ID' => 10,
                          ],
                        ],
                      ],
                  ],
              ],
            ],
        ],
    ];
    $this->assertEquals($expected, $query_expression);
  }

  /**
   * Tests several combined conditions.
   */
  public function testLanguageCondition() {
    $query = new Query($this->index);

    $query->setLanguages(['fr']);
    $andConditionGroup = new ConditionGroup('AND');
    $andConditionGroup->addCondition('id', 10, '<>');

    $orConditionGroup = new ConditionGroup('OR');
    $orConditionGroup->addCondition('body', 'Node body', '=');
    $orConditionGroup->addCondition('created', '1664883852', '>');

    // First test AND, OR.
    $conditionGroup = new ConditionGroup('AND');
    $conditionGroup->addConditionGroup($andConditionGroup);
    $conditionGroup->addConditionGroup($orConditionGroup);
    $query->addConditionGroup($conditionGroup);

    $query_expression = $this->queryBuilder->prepareConditionGroup(
      $query->getConditionGroup(),
      $query
    );

    $expected = [
      'bool' =>
        [
          'must' =>
            [
              0 =>
                [
                  'bool' =>
                    [
                      'must' =>
                        [
                          0 =>
                            [
                              'bool' =>
                                [
                                  'must_not' =>
                                    [
                                      0 =>
                                        [
                                          'term' =>
                                            [
                                              'ID' => 10,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                          1 =>
                            [
                              'bool' =>
                                [
                                  'should' =>
                                    [
                                      0 =>
                                        [
                                          'term' =>
                                            [
                                              'BODY' => 'Node body',
                                            ],
                                        ],
                                      1 =>
                                        [
                                          'range' =>
                                            [
                                              'CREATED' =>
                                                [
                                                  'gt' => '1664883852',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
              1 =>
                [
                  'terms' =>
                    [
                      'SEARCH_API_LANGUAGE' =>
                        [
                          0 => 'fr',
                        ],
                    ],
                ],
            ],
        ],
    ];

    $this->assertEquals($expected, $query_expression);
  }

}

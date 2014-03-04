<?php

namespace Cybits;

use Cybits\ArrayBuilder\Generator;
use Cybits\ArrayBuilder\Runtime;
use Cybits\Test\ArrayBuilder;

/**
 * Class Test
 *
 * @package Cybits
 */
class ArrayBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test generator method
     */
    public function testGenerate()
    {
        $this->assertTrue(class_exists('\\Cybits\\Test\\ArrayBuilder\\Field'));
        $this->assertInstanceOf('\\Cybits\\Test\\ArrayBuilder\\Field', ArrayBuilder\Field::create());
        $this->assertTrue(class_exists('\\Cybits\\Test\\ArrayBuilder\\Match'));
        $this->assertInstanceOf('\\Cybits\\Test\\ArrayBuilder\\Match', ArrayBuilder\Match::create());
        $this->assertTrue(class_exists('\\Cybits\\Test\\ArrayBuilder\\ExampleType'));
        $this->assertInstanceOf('\\Cybits\\Test\\ArrayBuilder\\ExampleType', ArrayBuilder\ExampleType::create());
        $this->assertInstanceOf('\\Cybits\\Test\\ArrayBuilder\\Match', ArrayBuilder\ExampleType::create());
    }

    /**
     * Test for build
     */
    public function testNormalSet()
    {
        $base = Runtime::create()->set('alpha', 'test');
        $this->assertEquals('test', $base->get('alpha'));
        $this->assertEquals('test', $base->getAlpha());

        $field = ArrayBuilder\Field::create()
            ->setAnalyzer('test')
            ->setLenient(true)
            ->setOperator('and')
            ->setType('phrase')
            ->setQuery('query');
        $this->assertEquals(
            array(
                'analyzer' => 'test',
                'lenient' => true,
                'operator' => 'and',
                'type' => 'phrase',
                'query' => 'query',
            ),
            $field->toArray()
        );

        $match = ArrayBuilder\Match::create()->setVar($field);

        $this->assertEquals($field, $match->getVar());
        $this->assertEquals($field, $match->get('var'));

        $this->assertEquals(
            array('var' => $field->toArray()),
            $match->toArray()
        );

        $example = ArrayBuilder\ExampleType::create()->setText('ABC')->setVar($field);
        $this->assertEquals(
            array('text' => 'ABC', 'var' => $field->toArray()),
            $example->toArray()
        );

    }

    /**
     * Test append method
     */
    public function testAppend()
    {
        $field = ArrayBuilder\Field::create()->setOperator('and');
        $match = ArrayBuilder\Match::create()->appendField($field);

        $this->assertEquals(
            array(
                array(
                    'operator' => 'and',
                ),
            ),
            $match->toArray()
        );

        $another = ArrayBuilder\Field::create()->setOperator('or');
        $match->appendField($another);

        $this->assertEquals(
            array(
                array(
                    'operator' => 'and',
                ),
                array(
                    'operator' => 'or',
                ),

            ),
            $match->toArray()
        );

        $third = ArrayBuilder\Field::create()->setOperator('not');
        $match->appendField($third, 'hey');

        $this->assertEquals(
            array(
                array(
                    'operator' => 'and',
                ),
                array(
                    'operator' => 'or',
                ),
                'hey' => array(
                    'operator' => 'not',
                ),
            ),
            $match->toArray()
        );

    }

    /**
     * Test add method
     */
    public function testAdd()
    {
        $field = ArrayBuilder\Field::create()->setOperator('and');
        $exam = ArrayBuilder\Exam::create()->addValue('ok', $field);


        $this->assertEquals(
            array(
                'value' =>
                    array(
                        'ok' =>
                            array(
                                'operator' => 'and',
                            ),
                    ),
            ),
            $exam->toArray()
        );

        $another = ArrayBuilder\Field::create()->setOperator('or');

        $exam->addValue(null, $another);
        $this->assertEquals(
            array(
                'value' =>
                    array(
                        'ok' =>
                            array(
                                'operator' => 'and',
                            ),
                        array(
                            'operator' => 'or',
                        )
                    ),
            ),
            $exam->toArray()
        );
    }

    /**
     * Test json serialize
     */
    public function testJsonSerialize()
    {
        $field = ArrayBuilder\Field::create()->setOperator('not')->setAnalyzer('analyzer');

        $this->assertEquals(json_encode($field->toArray()), json_encode($field));
    }

    /**
     * Setup the tests
     */
    protected function setUp()
    {
        $data = <<<'EOT'
{
    "_meta" : {
        "namespace" : "Cybits\\Test\\ArrayBuilder"
    },
    "field" : {
        "query": "_string",
        "operator": "_string",
        "zero_terms_query": "_string",
        "cutoff_frequency": "_float",
        "analyzer": "_string",
        "max_expansions": "_int",
        "lenient": "_boolean",
        "type": "_string"
    },
    "match": {
        "var" : "field",
        "_field": "_array[field]"
    },
    "exam": {
        "value": "_array[field]"
    },
    "example_type": {
        "_parent" : "match",
        "text" : "_string",
        "example_field": "_array[_string]",
        "texts" : "_array[_string]"
    }
}
EOT;
        $pattern = json_decode($data, true);
        $generator = new Generator($pattern);
        $target = __DIR__ . '/../_test';
        @mkdir($target);

        //By saving them, they are available for autoload
        $generator->save($target);
        \Autoloader::getLoader()->addPsr4('Cybits\\Test\\ArrayBuilder\\', array(__DIR__ . '/../_test'));
    }
}

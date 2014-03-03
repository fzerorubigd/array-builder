<?php

namespace Cybits;
use Cybits\ArrayBuilder\Generator;
use Cybits\Test\ArrayBuilder;
use Cybits\Test\ArrayBuilder\Field;
use Cybits\Test\ArrayBuilder\Match;

/**
 * Class Test
 *
 * @package Cybits
 */
class ArrayBuilderTest extends \PHPUnit_Framework_TestCase
{
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
        "_any": "_array[field]"
    },
    "example_type": {
        "_parent" : "match",
        "text" : "_string",
        "example_field": "_array[_string]",
        "_any" : "_array[_string]"
    }
}
EOT;
        $pattern = json_decode($data, true);
        $generator = new Generator($pattern);
        $target = realpath(__DIR__ . '/../_test');
        @mkdir($target);

        $generator->save($target);
    }


    public function testBuild()
    {
        $field = Field::create()
            ->setAnalyzer('test')
            ->setLenient(true)
            ->setOperator('and')
            ->setType('phrase')
            ->setQuery('query');

        $match = ArrayBuilder\ExampleType::create()
            ->setText('text')
            ->addExampleField('aaa', 'bebebe')
            ->addField($field)
            ->addField($field);

        var_dump($match->toArray());
    }
}
 
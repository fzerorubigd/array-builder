<?php

namespace Cybits\ArrayBuilder;

use PHPParser_BuilderFactory;

/**
 * Class Generator
 *
 * @package Cybits\ArrayBuilder
 */
class Generator
{
    protected $pattern;

    protected $types;

    protected $classes = array();

    /**
     * Create new generator
     *
     * @param array $pattern
     */
    public function __construct(array $pattern)
    {
        $this->pattern = $pattern;

        $this->types = array_keys($this->pattern);

        $name = new \PHPParser_Node_Name(['cybits', 'boo', 'hoo']);
        $this->classes[] = new \PHPParser_Node_Stmt_Namespace($name);
        foreach ($this->pattern as $type => $data) {
            if ($type{0} != '_') {
                $this->classes[$type] = $this->buildClass($type, $data);
            }
        }


        $pettyPrinter = new \PHPParser_PrettyPrinter_Default();
        echo $pettyPrinter->prettyPrint($this->classes);
    }

    private function getSetterMethod($property, $type, $current)
    {
        if ($type{0} == '_') {
            $realType = substr($type, 1);
        } elseif (in_array($type, $this->types)) {
            $realType = $this->camelize($type, '\\');
        } else {
            throw new \Exception("Invalid type $type");
        }

        return "@method $current set" . $this->camelize($property, '') . "($realType \$v)";
    }

    private function getGetterMethod($property, $type)
    {
        if ($type{0} == '_') {
            $realType = substr($type, 1);
        } elseif (in_array($type, $this->types)) {
            $realType = $this->camelize($type, '\\');
        } else {
            throw new \Exception("Invalid type $type");
        }

        return "@method $realType get" . $this->camelize($property, '') . '()';
    }

    public function buildClass($type, $data)
    {
        $factory = new PHPParser_BuilderFactory();
        $className = $this->camelize($type, '\\');
        $class = $factory
            ->class($className)
            ->extend('\Cybits\ArrayLoader\Runtime');

        $validProperties = $factory->property('validProperties')
            ->makeProtected();
        if (isset($data['_any'])) {
            $validProperties->setDefault(true);
        } else {
            $validProperties->setDefault(array_keys($data));
        }

        $class->addStmt($validProperties);

        $realClass = $class->getNode();
        $comments = $realClass->getAttribute('comments', array());
        $index = count($comments) - 1;
        if (isset($comments[$index]) && $comments[$index] instanceof \PHPParser_Comment_Doc) {
            $doc = & $comments[$index];
        } else {
            $doc = new \PHPParser_Comment_Doc(
                '/**' . PHP_EOL .
                ' * @method static __Type__ create()' . PHP_EOL .
                ' */');
            $comments[] = $doc;
        }

        $text = trim($doc->getText());
        foreach ($data as $key => $value) {
            if ($key{0} != '_') {
                $text = str_replace(
                    '*/',
                    '* ' . $this->getSetterMethod($key, $value, $className) . PHP_EOL . ' */',
                    $text
                );
                $text = str_replace(
                    '*/',
                    '* ' . $this->getGetterMethod($key, $value) . PHP_EOL . ' */',
                    $text
                );
            }
        }

        $doc->setText($text);
        $realClass->setAttribute('comments', $comments);

        return $realClass;
    }

    /**
     * Transforms an under_scored_string to a camelCasedOne
     *
     * @param string $scored the_string
     * @param string $glue   the glue
     *
     * @return string TheString
     */
    protected function camelize($scored, $glue = '')
    {
        return ucfirst(
            implode(
                $glue,
                array_map(
                    'ucfirst',
                    array_map(
                        'strtolower',
                        explode(
                            '_', $scored
                        )
                    )
                )
            )
        );
    }
}

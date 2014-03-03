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

    protected $namespace;

    /**
     * Create new generator
     *
     * @param array $pattern the pattern to build data
     */
    public function __construct(array $pattern)
    {
        $this->pattern = $pattern;

        $this->types = array_keys($this->pattern);

        if (isset($pattern['_meta']) && isset($pattern['_meta']['namespace'])) {
            $this->namespace = $pattern['_meta']['namespace'];
        } else {
            $this->namespace = null;
        }
        foreach ($this->pattern as $type => $data) {
            if ($type{0} != '_') {
                array_merge($this->classes[$type] = $this->buildClass($type, $data));
            }
        }
    }

    /**
     * Build a class base on type
     *
     * @param string $type the type
     * @param array  $data the type members and data
     *
     * @return array
     */
    protected function buildClass($type, $data)
    {
        $namespace = $this->namespace;
        $factory = new PHPParser_BuilderFactory();
        $className = $this->camelize($type);

        $class = $factory
            ->class($className)
            ->extend('\Cybits\ArrayBuilder\Runtime');

        $validProperties = $factory->property('validProperties')
            ->makeProtected();
        $validProperties->setDefault($this->generateValidPropertyList($data));

        $class->addStmt($validProperties);

        $realClass = $class->getNode();
        $comments = $realClass->getAttribute('comments', array());
        $index = count($comments) - 1;
        if (isset($comments[$index]) && $comments[$index] instanceof \PHPParser_Comment_Doc) {
            $doc = & $comments[$index];
        } else {
            $doc = new \PHPParser_Comment_Doc(
                '/**' . PHP_EOL .
                ' * @method static ' . $className . ' create()' . PHP_EOL .
                ' */'
            );
            $comments[] = $doc;
        }

        $text = trim($doc->getText());
        foreach ($data as $key => $type) {
            if ($key{0} != '_') {
                $text = str_replace(
                    '*/',
                    '* ' .
                    $this->getSetterMethod(
                        $key,
                        $type,
                        $className
                    ) . PHP_EOL . ' */',
                    $text
                );
                $text = str_replace(
                    '*/',
                    '* ' .
                    $this->getGetterMethod(
                        $key,
                        $type
                    ) . PHP_EOL . ' */',
                    $text
                );
            }
            $matches = array();
            if (preg_match('/^_array\[([^\]]*)\]$/', $type, $matches)) {
                // We need add method to.
                $text = str_replace(
                    '*/',
                    '* ' .
                    $this->getAdderMethod(
                        $key,
                        $matches[1],
                        $className
                    ) . PHP_EOL . ' */',
                    $text
                );
            }
        }

        $doc->setText($text);
        $realClass->setAttribute('comments', $comments);

        if ($namespace) {
            $namespace = new \PHPParser_Node_Name($namespace);
        }

        return [new \PHPParser_Node_Stmt_Namespace($namespace), $realClass];
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
                            '_',
                            $scored
                        )
                    )
                )
            )
        );
    }

    /**
     * Create valid type mapping for setting default value
     *
     * @param array $data data to create mapping
     *
     * @return array
     */
    private function generateValidPropertyList(array $data)
    {
        foreach ($data as &$value) {
            $value = $this->translateTypeToPhpType($value);
        }

        return $data;
    }

    /**
     * get the php type
     *
     * @param string $type     the json type
     * @param bool   $document its for document? (so array are like string[] not array)
     *
     * @throws \Exception
     * @return string type
     */
    protected function translateTypeToPhpType($type, $document = false)
    {
        $type = trim($type);
        if ($type{0} == '_') {
            //Internal type
            $type = substr($type, 1);
            $matches = array();
            if (preg_match('/^array\[([^\]]*)\]$/', $type, $matches)) {
                if (!$document) {
                    $type = 'array';
                } else {
                    $type = $this->translateTypeToPhpType($matches[1]) . '[]';
                }
            }
        } else {
            if (in_array($type, $this->types)) {
                $namespace = $this->namespace;
                $className = $this->camelize($type);

                if ($namespace{0} != '\\') {
                    $namespace = '\\' . $namespace;
                }

                return $namespace . '\\' . $className;
            }
            throw new \Exception("Invalid type $type");
        }

        return $type;
    }

    /**
     * Get setter method document
     *
     * @param string $property the property
     * @param string $type     the type of property
     * @param string $current  the current class
     *
     * @return string
     * @throws \Exception
     */
    private function getSetterMethod($property, $type, $current)
    {
        // The array notation seems to be not valid in arguments?!
        $realType = $this->translateTypeToPhpType($type);

        return "@method $current set" . $this->camelize($property, '') . "($realType \$v)";
    }

    /**
     * Get getter method document
     *
     * @param string $property the property
     * @param string $type     the type of property
     *
     * @return string
     * @throws \Exception
     */
    private function getGetterMethod($property, $type)
    {
        $realType = $this->translateTypeToPhpType($type, true);

        return "@method $realType get" . $this->camelize($property, '') . '()';
    }

    /**
     * Get adder method document
     *
     * @param string $property the property
     * @param string $type     the type of property
     * @param string $current  the current class
     *
     * @return string
     * @throws \Exception
     */
    private function getAdderMethod($property, $type, $current)
    {
        $realType = $this->translateTypeToPhpType($type, true);
        if ($property == '_any') {
            // So this could add many property of this type
            $funcName = $this->camelize($type);

            return "@method $current add{$funcName}(string \$property, $realType \$v)";
        }

        return "@method $current add" . $this->camelize($property, '') . "(string \$property, $realType \$v)";
    }

    /**
     * Save all classes in psr0 classes
     *
     * @param string $folder the folder to save into
     */
    public function save($folder)
    {
        $prettyPrinter = new \PHPParser_PrettyPrinter_Default();
        foreach ($this->classes as $type => $data) {
            $fileName = $this->camelize($type);
            $result = $prettyPrinter->prettyPrint($data);
            file_put_contents($folder . DIRECTORY_SEPARATOR . $fileName . '.php', '<?php' . PHP_EOL . $result);
        }
    }
}

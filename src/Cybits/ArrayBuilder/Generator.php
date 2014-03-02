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
     * @param array $pattern
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
     * @param array $data the type members and data
     *
     * @return array
     */
    protected function buildClass($type, $data)
    {
        $namespace = $this->namespace;
        $factory = new PHPParser_BuilderFactory();
        $fullClassName = explode('\\', $this->camelize($type, '\\'));

        while (count($fullClassName) > 1) {
            $namespace .= '\\' . array_shift($fullClassName);
        }
        $className = $fullClassName[0];
        $class = $factory
            ->class($className)
            ->extend('\Cybits\ArrayBuilder\Runtime');

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
                ' * @method static ' . $className . ' create()' . PHP_EOL .
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
                            '_', $scored
                        )
                    )
                )
            )
        );
    }

    /**
     * Get setter method document
     *
     * @param string $property the property
     * @param string $type the type of property
     * @param string $current the current class
     *
     * @return string
     * @throws \Exception
     */
    private function getSetterMethod($property, $type, $current)
    {
        if ($type{0} == '_') {
            $realType = substr($type, 1);
        } elseif (in_array($type, $this->types)) {
            $realType = $this->getTypeFullName($type);
        } else {
            throw new \Exception("Invalid type $type");
        }

        return "@method $current set" . $this->camelize($property, '') . "($realType \$v)";
    }

    /**
     * get full name of a class base on type
     *
     * @param string $type the type string
     *
     * @return string
     */
    protected function getTypeFullName($type)
    {
        $namespace = $this->namespace;
        $fullClassName = explode('\\', $this->camelize($type, '\\'));

        while (count($fullClassName) > 1) {
            $namespace .= '\\' . array_shift($fullClassName);
        }
        $className = $fullClassName[0];
        if ($namespace{0} != '\\') {
            $namespace = '\\' . $namespace;
        }

        return $namespace . '\\' . $className;
    }

    /**
     * Get getter method document
     *
     * @param string $property the property
     * @param string $type the type of property
     *
     * @return string
     * @throws \Exception
     */
    private function getGetterMethod($property, $type)
    {
        if ($type{0} == '_') {
            $realType = substr($type, 1);
        } elseif (in_array($type, $this->types)) {
            $realType = $this->getTypeFullName($type);
        } else {
            throw new \Exception("Invalid type $type");
        }

        return "@method $realType get" . $this->camelize($property, '') . '()';
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
            $folders = explode('\\', $this->camelize($type, '\\'));
            while ($sub = array_shift($folders)) {
                if (count($folders)) {
                    $folder .= DIRECTORY_SEPARATOR . $sub;
                    if (!is_dir($folder)) {
                        mkdir($folder);
                    }
                } else {
                    $result = $prettyPrinter->prettyPrint($data);
                    file_put_contents($folder . DIRECTORY_SEPARATOR . $sub . '.php', '<?php' . PHP_EOL . $result);
                }
            }
        }
    }
}

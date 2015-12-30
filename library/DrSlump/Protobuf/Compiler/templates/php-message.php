<?php
/*
    Receives the following variables:
        $namespace - The current namespace (aka package) for the file
        $data      - A google.protobuf.DescriptorProto object
        $nbSpaces  - The number of spaces
*/

use DrSlump\Protobuf\Protobuf;
use RuntimeException;

$spaces = str_repeat(' ', $nbSpaces);
echo 'namespace '.$this->ns($namespace).';'.PHP_EOL;
echo '// @@protoc_insertion_point(scope_namespace)'.PHP_EOL;
echo '// @@protoc_insertion_point(namespace_'.$namespace.')'.PHP_EOL;
echo PHP_EOL;
echo 'use Closure;'.PHP_EOL;
echo 'use DrSlump\Protobuf\Descriptor;'.PHP_EOL;
echo 'use DrSlump\Protobuf\Field;'.PHP_EOL;
echo 'use DrSlump\Protobuf\Message;'.PHP_EOL;
echo 'use DrSlump\Protobuf\Protobuf;'.PHP_EOL;
echo PHP_EOL;
$ns = $namespace.'.'.$data->name;
if ($this->comment($ns)) {
    echo '/**'.PHP_EOL;
    echo ' * '.$this->comment($ns, $spaces.' *').PHP_EOL;
    echo ' */';
}
echo 'class '.$data->name.' extends Message'.PHP_EOL;
echo '{'.PHP_EOL;
if (!empty($data->field)) {
    foreach ($data->field as $field) {
        // Nothing to do.
    }
}
echo $spaces.'/** @var Descriptor */'.PHP_EOL;
echo $spaces.'protected static $__descriptor;'.PHP_EOL;
echo PHP_EOL;
echo $spaces.'/** @var Closure[] */'.PHP_EOL;
echo $spaces.'protected static $__extensions = array();'.PHP_EOL;
echo PHP_EOL;
echo $spaces.'public static function descriptor()'.PHP_EOL;
echo $spaces.'{'.PHP_EOL;
echo $spaces.$spaces.'$descriptor = new Descriptor(__CLASS__, \''.$ns.'\');'.PHP_EOL;
echo PHP_EOL;
if (!empty($data->field)) {
    foreach ($data->field as $f) {
        echo $spaces.$spaces.'// '.$this->rule($f).' '.$this->type($f).' '.$f->name.' = '.$f->number.PHP_EOL;
        echo $spaces.$spaces.'$f = new Field();'.PHP_EOL;
        echo $spaces.$spaces.'$f->number = '.$f->number.';'.PHP_EOL;
        echo $spaces.$spaces.'$f->name = \''.$this->fieldname($f).'\';'.PHP_EOL;
        echo $spaces.$spaces.'$f->rule = Protobuf::RULE_'.strtoupper($this->rule($f)).';'.PHP_EOL;
        echo $spaces.$spaces.'$f->type = Protobuf::TYPE_'.strtoupper($this->type($f)).';'.PHP_EOL;
        if (!empty($f->type_name)) {
            $ref = $f->type_name;
            if (substr($ref, 0, 1) !== '.') {
                throw new RuntimeException("Only fully qualified names are supported but found '$ref' at $ns");
            }
            echo $spaces.$spaces.'$f->reference = \''.$this->ns($ref).'\';'.PHP_EOL;
        }

        if (isset($f->default_value)) {
            switch ($f->type) {
                case Protobuf::TYPE_BOOL:
                    $bool = filter_var($f->default_value, FILTER_VALIDATE_BOOLEAN);
                    echo $spaces.$spaces.'$f->default = '.($bool ? 'true' : 'false').';'.PHP_EOL;
                    break;
                case Protobuf::TYPE_STRING:
                    echo $spaces.$spaces.'$f->default = \''.addcslashes($f->default_value, '\'\\').'\';'.PHP_EOL;
                    break;
                case Protobuf::TYPE_ENUM:
                    echo $spaces.$spaces.'$f->default = \\'.$this->ns($f->type_name).'::'.$f->default_value.';'.PHP_EOL;
                    break;
                default: // Numbers
                    echo $spaces.$spaces.'$f->default = '.$f->default_value.';'.PHP_EOL;
            } // switch
        }
        echo $spaces.$spaces.'// @@protoc_insertion_point(scope_field)'.PHP_EOL;
        echo $spaces.$spaces.'// @@protoc_insertion_point(field_'.$ns.':'.$f->name.')'.PHP_EOL;
        echo $spaces.$spaces.'$descriptor->addField($f);'.PHP_EOL;
        echo PHP_EOL;
    }
}

echo $spaces.$spaces.'foreach (self::$__extensions as $cb) {'.PHP_EOL;
echo $spaces.$spaces.$spaces.'$descriptor->addField($cb(), true);'.PHP_EOL;
echo $spaces.$spaces.'}'.PHP_EOL;
echo $spaces.$spaces.'// @@protoc_insertion_point(scope_descriptor)'.PHP_EOL;
echo $spaces.$spaces.'// @@protoc_insertion_point(descriptor_'.$ns.')'.PHP_EOL;
echo PHP_EOL;
echo $spaces.$spaces.'return $descriptor;'.PHP_EOL;
echo $spaces.'}'.PHP_EOL;

if (!empty($data->field)) {
    echo PHP_EOL;
    foreach ($data->getField() as $f) {
        $name = $this->fieldname($f);
        $Name = $this->camelize(ucfirst($name));

        echo $spaces.'/**'.PHP_EOL;
        echo $spaces.' * Check if "'.$name.'" has a value'.PHP_EOL;
        echo $spaces.' *'.PHP_EOL;
        echo $spaces.' * @return bool'.PHP_EOL;
        echo $spaces.' */'.PHP_EOL;
        echo $spaces.'public function has'.$Name.'()'.PHP_EOL;
        echo $spaces.'{'.PHP_EOL;
        echo $spaces.$spaces.'return isset($this->'.$name.');'.PHP_EOL;
        echo $spaces.'}'.PHP_EOL;
        echo PHP_EOL;

        echo $spaces.'/**'.PHP_EOL;
        echo $spaces.' * Clear "'.$name.'" value'.PHP_EOL;
        echo $spaces.' */'.PHP_EOL;
        echo $spaces.'public function clear'.$Name.'()'.PHP_EOL;
        echo $spaces.'{'.PHP_EOL;
        echo $spaces.$spaces.'unset($this->'.$name.');'.PHP_EOL;
        echo $spaces.'}'.PHP_EOL;
        echo PHP_EOL;

        if ($f->label === Protobuf::RULE_REPEATED) {
            echo $spaces.'/**'.PHP_EOL;
            echo $spaces.' * Get "'.$name.'" value'.PHP_EOL;
            echo $spaces.' *'.PHP_EOL;
            echo $spaces.' * @param int|null $idx'.PHP_EOL;
            echo $spaces.' *'.PHP_EOL;
            echo $spaces.' * @return \PhpOption\Option of type \\'.$this->doctype($f).'[]'.PHP_EOL;
            echo $spaces.' */'.PHP_EOL;
            echo $spaces.'public function get'.$Name.'($idx = null)'.PHP_EOL;
            echo $spaces.'{'.PHP_EOL;
            echo $spaces.$spaces.'if (null === $idx || !array_key_exists($idx, $this->'.$name.')) {'.PHP_EOL;
            echo $spaces.$spaces.$spaces.'return \PhpOption\None::create();'.PHP_EOL;
            echo $spaces.$spaces.'}'.PHP_EOL;
            echo PHP_EOL;
            echo $spaces.$spaces.'return new \PhpOption\Some($this->'.$name.'[$idx]);'.PHP_EOL;
            echo $spaces.'}'.PHP_EOL;
            echo PHP_EOL;

            echo $spaces.'/**'.PHP_EOL;
            echo $spaces.' * Get "'.$name.'" list of values'.PHP_EOL;
            echo $spaces.' *'.PHP_EOL;
            echo $spaces.' * @return \\'.$this->doctype($f).'[]'.PHP_EOL;
            echo $spaces.' */'.PHP_EOL;
            echo $spaces.'public function get'.$Name.'List()'.PHP_EOL;
            echo $spaces.'{'.PHP_EOL;
            echo $spaces.$spaces.'return $this->'.$name.';'.PHP_EOL;
            echo $spaces.'}'.PHP_EOL;
            echo PHP_EOL;

            echo $spaces.'/**'.PHP_EOL;
            echo $spaces.' * Set "'.$name.'" list of values'.PHP_EOL;
            echo $spaces.' *'.PHP_EOL;
            echo $spaces.' * @param \\'.$this->doctype($f).'[] $value'.PHP_EOL;
            echo $spaces.' *'.PHP_EOL;
            echo $spaces.' * @return \\'.$this->doctype($f).'[]'.PHP_EOL;
            echo $spaces.' */'.PHP_EOL;
            echo $spaces.'public function set'.$Name.'List($value)'.PHP_EOL;
            echo $spaces.'{'.PHP_EOL;
            echo $spaces.$spaces.'return $this->'.$name.' = $value;'.PHP_EOL;
            echo $spaces.'}'.PHP_EOL;
            echo PHP_EOL;

            echo $spaces.'/**'.PHP_EOL;
            echo $spaces.' * Add a new element to "'.$name.'"'.PHP_EOL;
            echo $spaces.' *'.PHP_EOL;
            echo $spaces.' * @param \\'.$this->doctype($f).' $value'.PHP_EOL;
            echo $spaces.' */'.PHP_EOL;
            echo $spaces.'public function add'.$Name.'($value)'.PHP_EOL;
            echo $spaces.'{'.PHP_EOL;
            echo $spaces.$spaces.'$this->'.$name.'[] = $value;'.PHP_EOL;
            echo $spaces.'}'.PHP_EOL;
            echo PHP_EOL;
        } else {
            echo $spaces.'/**'.PHP_EOL;
            echo $spaces.' * Get "'.$name.'" value'.PHP_EOL;
            echo $spaces.' *'.PHP_EOL;
            if ($f->label == Protobuf::RULE_OPTIONAL) {
                echo $spaces.' * @return \PhpOption\Option of type (\\'.$this->doctype($f).')'.PHP_EOL;
            } else {
                echo $spaces.' * @return \\'.$this->doctype($f).PHP_EOL;
            }
            echo $spaces.' */'.PHP_EOL;
            echo $spaces.'public function get'.$Name.'()'.PHP_EOL;
            echo $spaces.'{'.PHP_EOL;
            if ($f->label == Protobuf::RULE_OPTIONAL) {
                echo $spaces.$spaces.'return \PhpOption\Option::fromValue($this->'.$name.');'.PHP_EOL;
            } else {
                echo $spaces.$spaces.'return $this->'.$name.';'.PHP_EOL;
            }
            echo $spaces.'}'.PHP_EOL;
            echo PHP_EOL;

            echo $spaces.'/**'.PHP_EOL;
            echo $spaces.' * Set "'.$name.'" value'.PHP_EOL;
            echo $spaces.' *'.PHP_EOL;
            echo $spaces.' * @param \\'.$this->doctype($f).' $value'.PHP_EOL;
            echo $spaces.' *'.PHP_EOL;
            echo $spaces.' * @return \\'.$this->doctype($f).PHP_EOL;
            echo $spaces.' */'.PHP_EOL;
            echo $spaces.'public function set'.$Name.'($value)'.PHP_EOL;
            echo $spaces.'{'.PHP_EOL;
            echo $spaces.$spaces.'return $this->'.$name.' = $value;'.PHP_EOL;
            echo $spaces.'}'.PHP_EOL;
            echo PHP_EOL;
        }
    }
}
echo $spaces.'// @@protoc_insertion_point(scope_class)'.PHP_EOL;
echo $spaces.'// @@protoc_insertion_point(class_'.$ns.')'.PHP_EOL;
echo '}'.PHP_EOL;

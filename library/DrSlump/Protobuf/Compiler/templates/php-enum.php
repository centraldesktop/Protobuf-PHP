<?php
/*
    Receives the following variables:
        $namespace - The current namespace (aka package)
        $data      - A google.protobuf.EnumDescriptorProto object
        $nbSpaces  - The number of spaces
*/
$spaces = str_repeat(' ', $nbSpaces);
echo 'namespace '.$this->ns($namespace).';'.PHP_EOL;
echo '// @@protoc_insertion_point(scope_namespace)'.PHP_EOL;
echo '// @@protoc_insertion_point(namespace_'.$namespace.')'.PHP_EOL;
echo PHP_EOL;
echo 'use DrSlump\Protobuf\Enum;'.PHP_EOL;
echo PHP_EOL;
$ns = $namespace.'.'.$data->name;
if ($this->comment($ns)) {
    echo '/**'.PHP_EOL;
    echo ' * '.$this->comment($ns, $spaces.' *').PHP_EOL;
    echo ' */';
}
echo 'class '.$data->name.' extends Enum'.PHP_EOL;
echo '{'.PHP_EOL;
foreach ($data->value as $value) {
    echo $spaces.'const '.$value->name.' = '.$value->number.';'.PHP_EOL;
}
echo $spaces.'// @@protoc_insertion_point(scope_class)'.PHP_EOL;
echo $spaces.'// @@protoc_insertion_point(class_'.$ns.')'.PHP_EOL;
echo '}'.PHP_EOL;

<?php

namespace DrSlump\Protobuf\Test\Codec;

use DrSlump\Protobuf;
use DrSlump\Protobuf\Codec;
use DrSlump\Protobuf\Test\protos;
use DrSlump\Protobuf\Test\protos\AddressBook;
use DrSlump\Protobuf\Test\protos\ExtA;
use DrSlump\Protobuf\Test\protos\ExtB;
use DrSlump\Protobuf\Test\protos\Person;
use DrSlump\Protobuf\Test\protos\Person\PhoneNumber;
use DrSlump\Protobuf\Test\protos\Person\PhoneType;
use DrSlump\Protobuf\Test\protos\Repeated;
use DrSlump\Protobuf\Test\protos\Simple;

class BinaryTest extends \PHPUnit_Framework_TestCase
{
    function setUp()
    {
        $codec = new Codec\Binary();
        Protobuf\Protobuf::setDefaultCodec($codec);

        $this->bin_simple = file_get_contents(dirname(__DIR__) . '/protos/simple.bin');
        $this->bin_book = file_get_contents(dirname(__DIR__) . '/protos/addressbook.bin');
        $this->bin_repeated_string = file_get_contents(dirname(__DIR__) . '/protos/repeated-string.bin');
        $this->bin_repeated_int32 = file_get_contents(dirname(__DIR__) . '/protos/repeated-int32.bin');
        $this->bin_repeated_nested = file_get_contents(dirname(__DIR__) . '/protos/repeated-nested.bin');
        $this->bin_ext = file_get_contents(dirname(__DIR__) . '/protos/extension.bin');
    }

    function testSerializeSimpleMessageComparingTypesWithProtoc()
    {
        $max = pow(2, 54)-1;
        $min = -$max;

        $fields = array(
            'double' => [1, 0.1, 1.0, -1, -0.1, -100000, 123456789.12345, -123456789.12345],
            'float'  => [1, 0.1, 1.0, -1, -0.1, -100000, 12345.123, -12345.123],
            'int64'  => [0, 1, -1, 123456789123456789, -123456789123456789, $min],
            'uint64' => [0, 1, 1000, 123456789123456789, PHP_INT_MAX, $max],
            'int32'  => [0, 1, -1, 123456789, -123456789],
            'fixed64'  => [0, 1, 1000, 123456789123456789],
            'fixed32'  => [0, 1, 1000, 123456789],
            'bool'  => [0, 1],
            'string'  => ["", "foo"],
            'bytes'  => ["", "foo"],
            'uint32'  => [0, 1, 1000, 123456789],
            'sfixed32'  => [0, 1, -1, 123456789, -123456789],
            'sfixed64'  => [0, 1, -1, 123456789123456789, -123456789123456789],
            'sint32'  => [0, 1, -1, 123456789, -123456789],
            'sint64' => [0, 1, -1, 123456789123456789, -123456789123456789, $min, $max],
        );

        foreach ($fields as $field=>$values) {
            foreach ($values as $value) {
                $simple = new Simple();
                $simple->$field = $value;
                $bin = Protobuf\Protobuf::encode($simple);

                if (is_string($value)) $value = '"' . $value . '"';

                exec("echo '$field: $value' | protoc --encode=DrSlump.Protobuf.Test.protos.Simple -Itest test/library/DrSlump/Protobuf/Test/protos/simple.proto", $out);

                $out = implode("\n", $out);

                $printValue = var_export($value, true);
                $this->assertEquals(bin2hex($bin), bin2hex($out), "Encoding $field with value $printValue");
            }
        }

        foreach ($fields as $field=>$values) {
            foreach ($values as $value) {
                $cmdValue = is_string($value)
                          ? '"' . $value . '"'
                          : $value;

                exec("echo '$field: $cmdValue' | protoc --encode=DrSlump.Protobuf.Test.protos.Simple -Itest test/library/DrSlump/Protobuf/Test/protos/simple.proto", $out);
                $out = implode("\n", $out);

                $simple = Protobuf\Protobuf::decode('\DrSlump\Protobuf\Test\protos\simple', $out);

                // Hack the comparison for float precision
                if (is_float($simple->$field)) {
                    $precision = strlen($value) - strpos($value, '.');
                    $simple->$field = round($simple->$field, $precision);
                }

                $printValue = var_export($value, true);
                $this->assertEquals($simple->$field, $value, "Decoding $field with value $printValue");
            }
        }
    }

    function testSerializeEnumComparingWithProtoc()
    {
        $complex = new protos\Complex();

        exec("echo 'enum: FOO' | protoc --encode=DrSlump.Protobuf.Test.protos.Complex -Itest test/library/DrSlump/Protobuf/Test/protos/complex.proto", $protocbin);
        $protocbin = implode("\n", $protocbin);

        // Check encoding an enum
        $complex->enum = protos\Complex\Enum::FOO;
        $encbin = Protobuf\Protobuf::encode($complex);

        $this->assertEquals(bin2hex($encbin), bin2hex($protocbin), "Encoding Enum field");

        // Check decoding an enum
        $complex = Protobuf\Protobuf::decode('\DrSlump\Protobuf\Test\protos\Complex', $protocbin);
        $this->assertEquals($complex->enum, protos\Complex\Enum::FOO, "Decoding Enum field");
    }

    function testSerializeNestedMessageComparingWithProtoc()
    {
        exec("echo 'nested { foo: \"FOO\" }' | protoc --encode=DrSlump.Protobuf.Test.protos.Complex -Itest test/library/DrSlump/Protobuf/Test/protos/complex.proto", $protocbin);
        $protocbin = implode("\n", $protocbin);

        // Encoding
        $complex = new protos\Complex();
        $complex->nested = new protos\Complex\Nested();
        $complex->nested->foo = 'FOO';
        $encbin = Protobuf\Protobuf::encode($complex);

        $this->assertEquals(bin2hex($encbin), bin2hex($protocbin), "Encoding nested message");

        // Decoding
        $complex = Protobuf\Protobuf::decode('\DrSlump\Protobuf\Test\protos\Complex', $protocbin);
        $this->assertEquals($complex->nested->foo, 'FOO', "Decoding nested message");
    }

    function testSerializeMessageWithRepeatedFields()
    {
        $repeated = new Repeated();
        $repeated->addString('one');
        $repeated->addString('two');
        $repeated->addString('three');
        $bin = Protobuf\Protobuf::encode($repeated);
        $this->assertEquals($bin, $this->bin_repeated_string);

        $repeated = new Repeated();
        $repeated->addInt(1);
        $repeated->addInt(2);
        $repeated->addInt(3);
        $bin = Protobuf\Protobuf::encode($repeated);
        $this->assertEquals($bin, $this->bin_repeated_int32);


        $repeated = new Repeated();
        $nested = new protos\Repeated\Nested();
        $nested->setId(1);
        $repeated->addNested($nested);
        $nested = new protos\Repeated\Nested();
        $nested->setId(2);
        $repeated->addNested($nested);
        $nested = new protos\Repeated\Nested();
        $nested->setId(3);
        $repeated->addNested($nested);
        $bin = Protobuf\Protobuf::encode($repeated);
        $this->assertEquals($bin, $this->bin_repeated_nested);
    }

    function testSerializeComplexMessage()
    {
        $book = new AddressBook();
        $person = new Person();
        $person->name = 'John Doe';
        $person->id = 2051;
        $person->email = 'john.doe@gmail.com';
        $phone = new PhoneNumber();
        $phone->number = '1231231212';
        $phone->type = PhoneType::HOME;
        $person->addPhone($phone);
        $phone = new PhoneNumber();
        $phone->number = '55512321312';
        $phone->type = PhoneType::MOBILE;
        $person->addPhone($phone);
        $book->addPerson($person);

        $person = new Person();
        $person->name = 'Iván Montes';
        $person->id = 23;
        $person->email = 'drslump@pollinimini.net';
        $phone = new PhoneNumber();
        $phone->number = '3493123123';
        $phone->type = PhoneType::WORK;
        $person->addPhone($phone);
        $book->addPerson($person);

        $bin = Protobuf\Protobuf::encode($book);
        $this->assertEquals($bin, $this->bin_book);
    }

    function testSerializeMessageWithExtendedFields()
    {
        $this->markTestSkipped('Extensions are not currently supported');
        $ext = new ExtA();
        $ext->first = 'FIRST';
        $ext['test\ExtB\second'] = 'SECOND';
        $bin = Protobuf\Protobuf::encode($ext);
        $this->assertEquals($this->bin_ext, $bin);
    }

    function testUnserializeSimpleMessage()
    {
        $simple = Protobuf\Protobuf::decode(Simple::class, $this->bin_simple);
        $this->assertInstanceOf(Simple::class, $simple);
        $this->assertEquals('foo', $simple->string);
        $this->assertEquals(-123456789, $simple->int32);
    }

    function testUnserializeMessageWithRepeatedFields()
    {
        /** @var Repeated $repeated */
        $repeated = Protobuf\Protobuf::decode(Repeated::class, $this->bin_repeated_string);
        $this->assertInstanceOf(Repeated::class, $repeated);
        $this->assertEquals(['one', 'two', 'three'], $repeated->getStringList());

        $repeated = Protobuf\Protobuf::decode(Repeated::class, $this->bin_repeated_int32);
        $this->assertInstanceOf(Repeated::class, $repeated);
        $this->assertEquals([1, 2, 3], $repeated->getIntList() );

        $repeated = Protobuf\Protobuf::decode(Repeated::class, $this->bin_repeated_nested);
        $this->assertInstanceOf(Repeated::class, $repeated);
        foreach ($repeated->getNested() as $i => $nested) {
            $this->assertEquals($i + 1, $nested->getId());
        }
    }

    function testUnserializeComplexMessage()
    {
        $complex = Protobuf\Protobuf::decode(AddressBook::class, $this->bin_book);
        $this->assertEquals(count($complex->person), 2);
        $this->assertEquals($complex->getPerson(0)->get()->name, 'John Doe');
        $this->assertEquals($complex->getPerson(1)->get()->name, 'Iván Montes');
        $this->assertEquals($complex->getPerson(0)->get()->getPhone(1)->get()->number, '55512321312');
    }

    function testUnserializeMessageWithExtendedFields()
    {
        $this->markTestSkipped("Extension aren't currently supported");
        /** @var ExtA $ext */
        $ext = Protobuf\Protobuf::decode(ExtB::class, $this->bin_ext);
        $this->assertEquals('FIRST', $ext->getFirst()->get() );
        $this->assertEquals('SECOND', $ext['test\ExtB\second']);
    }

    function testMultiCodecSimpleMessage()
    {
        $jsonCodec = new Codec\Json();
        $simple = Protobuf\Protobuf::decode(Simple::class, $this->bin_simple);
        $json = $jsonCodec->encode($simple);
        $simple = $jsonCodec->decode(new Simple(), $json);
        $bin = Protobuf\Protobuf::encode($simple);
        $this->assertEquals($bin, $this->bin_simple);
    }

    function testMultiCodecMessageWithRepeatedFields()
    {
        $jsonCodec = new Codec\Json();
        $repeated = Protobuf\Protobuf::decode(Repeated::class, $this->bin_repeated_nested);
        $json = $jsonCodec->encode($repeated);
        $repeated = $jsonCodec->decode(new Repeated(), $json);
        $bin = Protobuf\Protobuf::encode($repeated);
        $this->assertEquals($bin, $this->bin_repeated_nested);
    }
    
}

<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Tests\DocBlock;

use PhpCsFixer\DocBlock\Annotation;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\DocBlock\Line;
use PhpCsFixer\DocBlock\TypeExpression;
use PhpCsFixer\Tests\TestCase;
use PhpCsFixer\Tokenizer\Analyzer\Analysis\NamespaceAnalysis;
use PhpCsFixer\Tokenizer\Analyzer\Analysis\NamespaceUseAnalysis;

/**
 * @author Graham Campbell <graham@alt-three.com>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 *
 * @covers \PhpCsFixer\DocBlock\Annotation
 */
final class AnnotationTest extends TestCase
{
    /**
     * This represents the content an entire docblock.
     *
     * @var string
     */
    private static $sample = '/**
     * Test docblock.
     *
     * @param string $hello
     * @param bool $test Description
     *        extends over many lines
     *
     * @param adkjbadjasbdand $asdnjkasd
     *
     * @throws \Exception asdnjkasd
     *
     * asdasdasdasdasdasdasdasd
     * kasdkasdkbasdasdasdjhbasdhbasjdbjasbdjhb
     *
     * @return void
     */';

    /**
     * This represents the content of each annotation.
     *
     * @var string[]
     */
    private static $content = [
        "     * @param string \$hello\n",
        "     * @param bool \$test Description\n     *        extends over many lines\n",
        "     * @param adkjbadjasbdand \$asdnjkasd\n",
        "     * @throws \\Exception asdnjkasd\n     *\n     * asdasdasdasdasdasdasdasd\n     * kasdkasdkbasdasdasdjhbasdhbasjdbjasbdjhb\n",
        "     * @return void\n",
    ];

    /**
     * This represents the start indexes of each annotation.
     *
     * @var int[]
     */
    private static $start = [3, 4, 7, 9, 14];

    /**
     * This represents the start indexes of each annotation.
     *
     * @var int[]
     */
    private static $end = [3, 5, 7, 12, 14];

    /**
     * This represents the tag type of each annotation.
     *
     * @var string[]
     */
    private static $tags = ['param', 'param', 'param', 'throws', 'return'];

    /**
     * @param int    $index
     * @param string $content
     *
     * @dataProvider provideGetContentCases
     */
    public function testGetContent($index, $content)
    {
        $doc = new DocBlock(self::$sample);
        $annotation = $doc->getAnnotation($index);

        static::assertSame($content, $annotation->getContent());
        static::assertSame($content, (string) $annotation);
    }

    public function provideGetContentCases()
    {
        $cases = [];

        foreach (self::$content as $index => $content) {
            $cases[] = [$index, $content];
        }

        return $cases;
    }

    /**
     * @param int $index
     * @param int $start
     *
     * @dataProvider provideStartCases
     */
    public function testStart($index, $start)
    {
        $doc = new DocBlock(self::$sample);
        $annotation = $doc->getAnnotation($index);

        static::assertSame($start, $annotation->getStart());
    }

    public function provideStartCases()
    {
        $cases = [];

        foreach (self::$start as $index => $start) {
            $cases[] = [$index, $start];
        }

        return $cases;
    }

    /**
     * @param int $index
     * @param int $end
     *
     * @dataProvider provideEndCases
     */
    public function testEnd($index, $end)
    {
        $doc = new DocBlock(self::$sample);
        $annotation = $doc->getAnnotation($index);

        static::assertSame($end, $annotation->getEnd());
    }

    public function provideEndCases()
    {
        $cases = [];

        foreach (self::$end as $index => $end) {
            $cases[] = [$index, $end];
        }

        return $cases;
    }

    /**
     * @param int    $index
     * @param string $tag
     *
     * @dataProvider provideGetTagCases
     */
    public function testGetTag($index, $tag)
    {
        $doc = new DocBlock(self::$sample);
        $annotation = $doc->getAnnotation($index);

        static::assertSame($tag, $annotation->getTag()->getName());
    }

    public function provideGetTagCases()
    {
        $cases = [];

        foreach (self::$tags as $index => $tag) {
            $cases[] = [$index, $tag];
        }

        return $cases;
    }

    /**
     * @param int $index
     * @param int $start
     * @param int $end
     *
     * @dataProvider provideRemoveCases
     */
    public function testRemove($index, $start, $end)
    {
        $doc = new DocBlock(self::$sample);
        $annotation = $doc->getAnnotation($index);

        $annotation->remove();
        static::assertSame('', $annotation->getContent());
        static::assertSame('', $doc->getLine($start)->getContent());
        static::assertSame('', $doc->getLine($end)->getContent());
    }

    public function provideRemoveCases()
    {
        $cases = [];

        foreach (self::$start as $index => $start) {
            $cases[] = [$index, $start, self::$end[$index]];
        }

        return $cases;
    }

    /**
     * @param string $expected
     * @param string $input
     *
     * @dataProvider provideRemoveEdgeCasesCases
     */
    public function testRemoveEdgeCases($expected, $input)
    {
        $doc = new DocBlock($input);
        $annotation = $doc->getAnnotation(0);

        $annotation->remove();
        static::assertSame($expected, $doc->getContent());
    }

    public function provideRemoveEdgeCasesCases()
    {
        return [
            // Single line
            ['', '/** @return null*/'],
            ['', '/** @return null */'],
            ['', '/** @return null  */'],

            // Multi line, annotation on start line
            [
                '/**
                 */',
                '/** @return null
                 */',
            ],
            [
                '/**
                 */',
                '/** @return null '.'
                 */',
            ],
            // Multi line, annotation on end line
            [
                '/**
                 */',
                '/**
                 * @return null*/',
            ],
            [
                '/**
                 */',
                '/**
                 * @return null */',
            ],
        ];
    }

    /**
     * @param string   $input
     * @param string[] $expected
     *
     * @dataProvider provideTypeParsingCases
     */
    public function testTypeParsing($input, array $expected)
    {
        $tag = new Annotation([new Line($input)]);

        static::assertSame($expected, $tag->getTypes());
    }

    public function provideTypeParsingCases()
    {
        return [
            [
                ' * @method int method()',
                ['int'],
            ],
            [
                " * @return int[]\r",
                ['int[]'],
            ],
            [
                " * @return int[]\r\n",
                ['int[]'],
            ],
            [
                ' * @method Foo[][] method()',
                ['Foo[][]'],
            ],
            [
                ' * @method int[] method()',
                ['int[]'],
            ],
            [
                ' * @method int[]|null method()',
                ['int[]', 'null'],
            ],
            [
                ' * @method int[]|null|?int|array method()',
                ['int[]', 'null', '?int', 'array'],
            ],
            [
                ' * @method null|Foo\Bar|\Baz\Bax|int[] method()',
                ['null', 'Foo\Bar', '\Baz\Bax', 'int[]'],
            ],
            [
                ' * @method gen<int> method()',
                ['gen<int>'],
            ],
            [
                ' * @method int|gen<int> method()',
                ['int', 'gen<int>'],
            ],
            [
                ' * @method \int|\gen<\int, \bool> method()',
                ['\int', '\gen<\int, \bool>'],
            ],
            [
                ' * @method gen<int,  int> method()',
                ['gen<int,  int>'],
            ],
            [
                ' * @method gen<int,  bool|string> method()',
                ['gen<int,  bool|string>'],
            ],
            [
                ' * @method gen<int,  string[]> method() <> a',
                ['gen<int,  string[]>'],
            ],
            [
                ' * @method gen<int,  gener<string, bool>> method() foo <a >',
                ['gen<int,  gener<string, bool>>'],
            ],
            [
                ' * @method gen<int,  gener<string, null|bool>> method()',
                ['gen<int,  gener<string, null|bool>>'],
            ],
            [
                ' * @method null|gen<int,  gener<string, bool>>|int|string[] method() foo <a >',
                ['null', 'gen<int,  gener<string, bool>>', 'int', 'string[]'],
            ],
            [
                ' * @method null|gen<int,  gener<string, bool>>|int|array<int, string>|string[] method() foo <a >',
                ['null', 'gen<int,  gener<string, bool>>', 'int', 'array<int, string>', 'string[]'],
            ],
            [
                '/** @return    this */',
                ['this'],
            ],
            [
                '/** @return    @this */',
                ['@this'],
            ],
            [
                '/** @return $SELF|int */',
                ['$SELF', 'int'],
            ],
            [
                '/** @var array<string|int, string>',
                ['array<string|int, string>'],
            ],
            [
                " * @return int\n",
                ['int'],
            ],
            [
                " * @return int\r\n",
                ['int'],
            ],
            [
                '/** @var Collection<Foo<Bar>, Foo<Baz>>',
                ['Collection<Foo<Bar>, Foo<Baz>>'],
            ],
            [
                '/** @var int | string',
                ['int', 'string'],
            ],
            [
                '/** @var Foo::*',
                ['Foo::*'],
            ],
            [
                '/** @var Foo::A',
                ['Foo::A'],
            ],
            [
                '/** @var Foo::A|Foo::B',
                ['Foo::A', 'Foo::B'],
            ],
            [
                '/** @var Foo::A*',
                ['Foo::A*'],
            ],
            [
                '/** @var array<Foo::A*>|null',
                ['array<Foo::A*>', 'null'],
            ],
            [
                '/** @var null|true|false|1|1.5|\'a\'|"b"',
                ['null', 'true', 'false', '1', '1.5', "'a'", '"b"'],
            ],
            [
                '/** @param int | "a" | A<B<C, D>, E<F::*|G[]>> $foo */',
                ['int', '"a"', 'A<B<C, D>, E<F::*|G[]>>'],
            ],
            [
                '/** @var class-string<Foo> */',
                ['class-string<Foo>'],
            ],
            [
                '/** @var A&B */',
                ['A&B'],
            ],
            [
                '/** @var A & B */',
                ['A & B'],
            ],
            [
                '/** @var array{1: bool, 2: bool} */',
                ['array{1: bool, 2: bool}'],
            ],
            [
                '/** @var array{a: int|string, b?: bool} */',
                ['array{a: int|string, b?: bool}'],
            ],
            [
                '/** @var array{\'a\': "a", "b"?: \'b\'} */',
                ['array{\'a\': "a", "b"?: \'b\'}'],
            ],
            [
                '/** @var array { a : int | string , b ? : A<B, C> } */',
                ['array { a : int | string , b ? : A<B, C> }'],
            ],
            [
                '/** @param callable(string) $function',
                ['callable(string)'],
            ],
            [
                '/** @param callable(string): bool $function',
                ['callable(string): bool'],
            ],
            [
                '/** @param callable(array<int, string>, array<int, Foo>): bool $function',
                ['callable(array<int, string>, array<int, Foo>): bool'],
            ],
            [
                '/** @param array<int, callable(string): bool> $function',
                ['array<int, callable(string): bool>'],
            ],
            [
                '/** @param callable(string): callable(int) $function',
                ['callable(string): callable(int)'],
            ],
            [
                '/** @param callable(string) : callable(int) : bool $function',
                ['callable(string) : callable(int) : bool'],
            ],
            [
                '* @param TheCollection<callable(Foo, Bar,Baz): Foo[]>|string[]|null $x',
                ['TheCollection<callable(Foo, Bar,Baz): Foo[]>', 'string[]', 'null'],
            ],
            [
                '/** @param Closure(string) $function',
                ['Closure(string)'],
            ],
            [
                '/** @param   array  <  int   , callable  (  string  )  :   bool  > $function',
                ['array  <  int   , callable  (  string  )  :   bool  >'],
            ],
        ];
    }

    /**
     * @param string[] $expected
     * @param string[] $new
     * @param string   $input
     * @param string   $output
     *
     * @dataProvider provideTypesCases
     */
    public function testTypes($expected, $new, $input, $output)
    {
        $line = new Line($input);
        $tag = new Annotation([$line]);

        static::assertSame($expected, $tag->getTypes());

        $tag->setTypes($new);

        static::assertSame($new, $tag->getTypes());

        static::assertSame($output, $line->getContent());
    }

    public function provideTypesCases()
    {
        return [
            [['Foo', 'null'], ['Bar[]'], '     * @param Foo|null $foo', '     * @param Bar[] $foo'],
            [['false'], ['bool'], '*   @return            false', '*   @return            bool'],
            [['RUNTIMEEEEeXCEPTION'], [\Throwable::class], "* \t@throws\t  \t RUNTIMEEEEeXCEPTION\t\t\t\t\t\t\t\n\n\n", "* \t@throws\t  \t Throwable\t\t\t\t\t\t\t\n\n\n"],
            [['RUNTIMEEEEeXCEPTION'], [\Throwable::class], "*\t@throws\t  \t RUNTIMEEEEeXCEPTION\t\t\t\t\t\t\t\n\n\n", "*\t@throws\t  \t Throwable\t\t\t\t\t\t\t\n\n\n"],
            [['RUNTIMEEEEeXCEPTION'], [\Throwable::class], "*@throws\t  \t RUNTIMEEEEeXCEPTION\t\t\t\t\t\t\t\n\n\n", "*@throws\t  \t Throwable\t\t\t\t\t\t\t\n\n\n"],
            [['string'], ['string', 'null'], ' * @method string getString()', ' * @method string|null getString()'],
        ];
    }

    /**
     * @param string[] $expected
     * @param string   $input
     *
     * @dataProvider provideNormalizedTypesCases
     */
    public function testNormalizedTypes($expected, $input)
    {
        $line = new Line($input);
        $tag = new Annotation([$line]);

        static::assertSame($expected, $tag->getNormalizedTypes());
    }

    public function provideNormalizedTypesCases()
    {
        return [
            [['null', 'string'], '* @param StRiNg|NuLl $foo'],
            [['void'], '* @return Void'],
            [['bar', 'baz', 'foo', 'null', 'qux'], '* @return Foo|Bar|Baz|Qux|null'],
        ];
    }

    public function testGetTypesOnBadTag()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('This tag does not support types');

        $tag = new Annotation([new Line(' * @deprecated since 1.2')]);

        $tag->getTypes();
    }

    public function testSetTypesOnBadTag()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('This tag does not support types');

        $tag = new Annotation([new Line(' * @author Chuck Norris')]);

        $tag->setTypes(['string']);
    }

    public function testGetTagsWithTypes()
    {
        $tags = Annotation::getTagsWithTypes();
        static::assertIsArray($tags);
        foreach ($tags as $tag) {
            static::assertIsString($tag);
            static::assertNotEmpty($tag);
        }
    }

    /**
     * @param Line[]                 $lines
     * @param null|NamespaceAnalysis $namespace
     * @param NamespaceUseAnalysis[] $namespaceUses
     * @param null|string            $expectedCommonType
     *
     * @dataProvider provideTypeExpressionCases
     */
    public function testGetTypeExpression(array $lines, $namespace, array $namespaceUses, $expectedCommonType)
    {
        $annotation = new Annotation($lines, $namespace, $namespaceUses);
        $result = $annotation->getTypeExpression();

        static::assertInstanceOf(TypeExpression::class, $result);
        static::assertSame($expectedCommonType, $result->getCommonType());
    }

    public function provideTypeExpressionCases()
    {
        $appNamespace = new NamespaceAnalysis('App', 'App', 0, 999, 0, 999);
        $useTraversable = new NamespaceUseAnalysis('Traversable', 'Traversable', false, 0, 999, NamespaceUseAnalysis::TYPE_CLASS);

        yield [[new Line('* @param array|Traversable $foo')], null, [], 'iterable'];
        yield [[new Line('* @param array|Traversable $foo')], $appNamespace, [], null];
        yield [[new Line('* @param array|Traversable $foo')], $appNamespace, [$useTraversable], 'iterable'];
    }

    /**
     * @param Line[]      $lines
     * @param null|string $expectedVariableName
     *
     * @dataProvider provideGetVariableCases
     */
    public function testGetVariableName(array $lines, $expectedVariableName)
    {
        $annotation = new Annotation($lines);
        static::assertSame($expectedVariableName, $annotation->getVariableName());
    }

    public function provideGetVariableCases()
    {
        yield [[new Line('* @param int $foo')], '$foo'];
        yield [[new Line('* @param int $foo some description')], '$foo'];
        yield [[new Line('/** @param int $foo*/')], '$foo'];
        yield [[new Line('* @param int')], null];
        yield [[new Line('* @var int $foo')], '$foo'];
        yield [[new Line('* @var int $foo some description')], '$foo'];
        yield [[new Line('/** @var int $foo*/')], '$foo'];
        yield [[new Line('* @var int')], null];
    }
}

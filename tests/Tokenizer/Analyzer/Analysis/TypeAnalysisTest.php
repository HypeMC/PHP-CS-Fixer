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

namespace PhpCsFixer\Tests\Tokenizer\Analyzer\Analysis;

use PhpCsFixer\Tests\TestCase;
use PhpCsFixer\Tokenizer\Analyzer\Analysis\StartEndTokenAwareAnalysis;
use PhpCsFixer\Tokenizer\Analyzer\Analysis\TypeAnalysis;

/**
 * @author VeeWee <toonverwerft@gmail.com>
 *
 * @internal
 *
 * @covers \PhpCsFixer\Tokenizer\Analyzer\Analysis\TypeAnalysis
 */
final class TypeAnalysisTest extends TestCase
{
    public function testStartEndTokenAwareAnalysis()
    {
        $analysis = new TypeAnalysis('string', 1, 2);
        static::assertInstanceOf(StartEndTokenAwareAnalysis::class, $analysis);
    }

    public function testName()
    {
        $analysis = new TypeAnalysis('string', 1, 2);
        static::assertSame('string', $analysis->getName());

        $analysis = new TypeAnalysis('?\foo\bar', 1, 2);
        static::assertSame('\foo\bar', $analysis->getName());
    }

    public function testStartIndex()
    {
        $analysis = new TypeAnalysis('string', 1, 2);
        static::assertSame(1, $analysis->getStartIndex());
    }

    public function testEndIndex()
    {
        $analysis = new TypeAnalysis('string', 1, 2);
        static::assertSame(2, $analysis->getEndIndex());
    }

    /**
     * @dataProvider provideReservedCases
     *
     * @param mixed $type
     * @param mixed $expected
     */
    public function testReserved($type, $expected)
    {
        $analysis = new TypeAnalysis($type, 1, 2);
        static::assertSame($expected, $analysis->isReservedType());
    }

    public function provideReservedCases()
    {
        return [
            ['array', true],
            ['bool', true],
            ['callable', true],
            ['int', true],
            ['iterable', true],
            ['float', true],
            ['mixed', true],
            ['numeric', true],
            ['object', true],
            ['resource', true],
            ['self', true],
            ['string', true],
            ['void', true],
            ['other', false],
        ];
    }

    /**
     * @dataProvider provideNullableCases
     *
     * @param string $input
     * @param bool   $expected
     */
    public function testIsNullable($input, $expected)
    {
        $analysis = new TypeAnalysis($input, 1, 2);
        static::assertSame($expected, $analysis->isNullable());
    }

    public function provideNullableCases()
    {
        yield ['string', false];
        yield ['?string', true];
        yield ['\foo\bar', false];
        yield ['?\foo\bar', true];

        if (\PHP_VERSION_ID >= 80000) {
            yield ['string|int', false];
            yield ['string|null', true];
            yield ['null|string', true];
            yield ['string|NULL', true];
            yield ['NULL|string', true];
            yield ['string|int|null', true];
            yield ['null|string|int', true];
            yield ['string|null|int', true];
            yield ['string|int|NULL', true];
            yield ['NULL|string|int', true];
            yield ['string|NULL|int', true];
            yield ['string|\foo\bar', false];
            yield ['string|\foo\bar|null', true];
            yield ['null|string|\foo\bar', true];
            yield ['string|null|\foo\bar', true];
        }
    }
}

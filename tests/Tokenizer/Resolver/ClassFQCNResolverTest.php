<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Tests\Tokenizer\Resolver;

use PhpCsFixer\Tests\TestCase;
use PhpCsFixer\Tokenizer\Resolver\ClassFQCNResolver;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @internal
 *
 * @covers \PhpCsFixer\Tokenizer\Resolver\ClassFQCNResolver
 */
final class ClassFQCNResolverTest extends TestCase
{
    /**
     * @dataProvider provideResolveForCases
     *
     * @param array<int, string> $expectedNames
     */
    public function testResolveFor(string $code, array $expectedNames): void
    {
        $tokens = Tokens::fromCode($code);
        $classFQCNResolver = new ClassFQCNResolver($tokens, $tokens->getNextTokenOfKind(0, [[T_FUNCTION]]));

        foreach ($expectedNames as $index => $expectedName) {
            self::assertSame($expectedName, $classFQCNResolver->resolveFor($index));
        }
    }

    /**
     * @return iterable<array{0: string, 1: array<int, string>}>
     */
    public static function provideResolveForCases(): iterable
    {
        yield 'In global namespace' => [
            '<?php
            use A\B\Foo;
            use A\B\Bar as BarAlias;
            use A\B as AB;

            function X(
                AB\Baz $foo,
                A\B\Quux $bar,
                \A\B\Qux $baz,
                BarAlias $quux,
                Corge $qux,
                Foo $corge
            ) {}
            ',
            [
                40 => 'A\B\Baz',
                49 => 'A\B\Quux',
                58 => 'A\B\Qux',
                66 => 'A\B\Bar',
                71 => 'Corge',
                76 => 'A\B\Foo',
            ],
        ];

        yield 'In namespace' => [
            '<?php
            namespace Test;

            use A\B\Foo;
            use A\B\Bar as BarAlias;
            use A\B as AB;

            function X(
                AB\Baz $foo,
                A\B\Quux $bar,
                \A\B\Qux $baz,
                BarAlias $quux,
                Corge $qux,
                Foo $corge
            ) {}
            ',
            [
                45 => 'A\B\Baz',
                54 => 'Test\A\B\Quux',
                63 => 'A\B\Qux',
                71 => 'A\B\Bar',
                76 => 'Test\Corge',
                81 => 'A\B\Foo',
            ],
        ];
    }
}

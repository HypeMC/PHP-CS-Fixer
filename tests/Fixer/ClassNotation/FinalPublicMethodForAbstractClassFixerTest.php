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

namespace PhpCsFixer\Tests\Fixer\ClassNotation;

use PhpCsFixer\Tests\Test\AbstractFixerTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @author Filippo Tessarotto <zoeslam@gmail.com>
 *
 * @internal
 *
 * @covers \PhpCsFixer\Fixer\ClassNotation\FinalPublicMethodForAbstractClassFixer
 */
final class FinalPublicMethodForAbstractClassFixerTest extends AbstractFixerTestCase
{
    /**
     * @param string      $expected PHP source code
     * @param null|string $input    PHP source code
     *
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    /**
     * @return iterable<array{0: string, 1?: string}>
     */
    public static function provideFixCases(): iterable
    {
        yield 'interface' => [
            '<?php

namespace Tests\Debug {
    use Stringable as Marko;

    interface Foo extends Marko
    {
        public function __toString();
    }
}

namespace Tests\Debug2 {
    use Bal\Bal\Stringable;

    interface Foo extends Stringable
    {
        public function __toString();
    }
}

namespace Tests\Debug3 {
    use Stringable;

    interface Foo extends Stringable
    {
        public function __toString();
    }
}

namespace Tests\Debug4 {
    interface Foo extends \Stringable
    {
        public function __toString();
    }
}

namespace {
    interface Foo extends Stringable
    {
        public function __toString();
    }
}

// nested class
// abstract method
// method s &

',
        ];
    }
}

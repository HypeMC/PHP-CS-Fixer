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

namespace PhpCsFixer\Fixer\ClassNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Filippo Tessarotto <zoeslam@gmail.com>
 */
final class FinalPublicMethodForAbstractClassFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'All `public` methods of `abstract` classes should be `final`.',
            [
                new CodeSample(
                    '<?php

abstract class AbstractMachine
{
    public function start()
    {}
}
'
                ),
            ],
            'Enforce API encapsulation in an inheritance architecture. '
            .'If you want to override a method, use the Template method pattern.',
            'Risky when overriding `public` methods of `abstract` classes.'
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([T_CLASS, T_INTERFACE]);
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens): void
    {
        // možda ignore child classa/interfacea?
        $classes = [
            ...array_keys($tokens->findGivenKind(T_CLASS)),
            ...array_keys($tokens->findGivenKind(T_INTERFACE)), // optional?
        ];

        while ($classIndex = array_pop($classes)) {
            $classOpenIndex = $tokens->getNextTokenOfKind($classIndex, ['{']);

            if ($this->hasInterface($tokens, $classIndex, $classOpenIndex)) {
                continue;
            }

            $classCloseIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $classOpenIndex);

            if ($this->hasToStringMethod($tokens, $classOpenIndex, $classCloseIndex)) {
                $this->fixClass($tokens, $classIndex, $classOpenIndex);
            }
        }
    }

    private function hasInterface(Tokens $tokens, int $classIndex, int $classOpenIndex): bool
    {
        $implementsIndex = $tokens->getNextTokenOfKind($classIndex, [[T_IMPLEMENTS]]);

        if (null === $implementsIndex || $implementsIndex > $classOpenIndex) {
            return false;
        }

        for ($i = $implementsIndex; $i < $classOpenIndex; ++$i) {
            $token = $tokens[$i];

            if ($token->isGivenKind(T_STRING) && \Stringable::class === $token->getContent()) {
                return true;
            }
        }

        return false;
    }

    private function hasToStringMethod(Tokens $tokens, int $classOpenIndex, int $classCloseIndex): bool
    {
        for ($index = $classOpenIndex + 1; $index < $classCloseIndex; ++$index) {
            if (!$tokens[$index]->isGivenKind(T_FUNCTION)) {
                continue;
            }

            $nextIndex = $tokens->getNextMeaningfulToken($index);
            $nextToken = $tokens[$nextIndex];

            if ($nextToken->isGivenKind(CT::T_RETURN_REF)) {
                $nextIndex = $tokens->getNextMeaningfulToken($nextIndex);
                $nextToken = $tokens[$nextIndex];
            }

            if ('__tostring' === strtolower($nextToken->getContent())) {
                return true;
            }

            // skip method content
            $bracesIndex = $tokens->getNextTokenOfKind($index, ['{', ';']);
            if ($tokens[$bracesIndex]->equals('{')) {
                $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $bracesIndex);
            }
        }

        return false;
    }

    private function fixClass(Tokens $tokens, int $classIndex, int $classOpenIndex): void
    {
        $isInterface = $tokens[$classIndex]->isGivenKind(T_INTERFACE);

        $insertTokens = [
            new Token([T_WHITESPACE, ' ']),
            new Token([T_STRING, '\\'.\Stringable::class]),
        ];

        $implementsIndex = $tokens->getPrevTokenOfKind($classOpenIndex, [[$isInterface ? T_EXTENDS : T_IMPLEMENTS]]);

        if (null === $implementsIndex || $implementsIndex < $classIndex) {
            $implementsIndex = $tokens->getPrevNonWhitespace($classOpenIndex);
            $insertTokens = [
                new Token([T_WHITESPACE, ' ']),
                $isInterface ? new Token([T_EXTENDS, 'extends']) : new Token([T_IMPLEMENTS, 'implements']),
                ...$insertTokens,
            ];
        } else {
            $insertTokens[] = new Token(',');
        }

        $tokens->insertAt($implementsIndex + 1, $insertTokens);
    }
}

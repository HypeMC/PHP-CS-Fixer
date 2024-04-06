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

namespace PhpCsFixer\Tokenizer\Resolver;

use PhpCsFixer\Tokenizer\Analyzer\Analysis\NamespaceAnalysis;
use PhpCsFixer\Tokenizer\Analyzer\Analysis\NamespaceUseAnalysis;
use PhpCsFixer\Tokenizer\Analyzer\NamespacesAnalyzer;
use PhpCsFixer\Tokenizer\Analyzer\NamespaceUsesAnalyzer;
use PhpCsFixer\Tokenizer\Tokens;

final class ClassFQCNResolver
{
    private Tokens $tokens;
    private int $index;
    private NamespaceAnalysis $namespaceAnalysis;

    /**
     * @var array<string, NamespaceUseAnalysis>
     */
    private array $namespaceUseAnalyses;

    public function __construct(Tokens $tokens, int $index)
    {
        $this->tokens = $tokens;
        $this->index = $index;
    }

    public function resolveFor(int $index): string
    {
        if (!$this->tokens[$index]->isGivenKind([T_STRING, T_NS_SEPARATOR])) {
            throw new \InvalidArgumentException('Given index must point to a T_STRING or T_NS_SEPARATOR.');
        }

        $index = $this->tokens->getTokenNotOfKindSibling($index, -1, [[T_STRING], [T_NS_SEPARATOR]]) + 1;
        $endIndex = $this->tokens->getTokenNotOfKindSibling($index, 1, [[T_STRING], [T_NS_SEPARATOR]]) - 1;

        $name = $this->tokens->generatePartialCode($index, $endIndex);

        if ('\\' === $name[0]) {
            return ltrim($name, '\\');
        }

        $namespace = $this->getNamespaceAnalysis()->getFullName();

        $firstTokenOfName = $this->tokens[$index]->getContent();
        $namespaceUseAnalysis = $this->getNamespaceUseAnalyses()[$firstTokenOfName] ?? false;
        if ($namespaceUseAnalysis) {
            $namespace = $namespaceUseAnalysis->getFullName();

            if ($name === $firstTokenOfName) {
                return $namespace;
            }

            $name = substr(strstr($name, '\\'), 1);
        }

        return '' !== $namespace ? $namespace.'\\'.$name : $name;
    }

    private function getNamespaceAnalysis(): NamespaceAnalysis
    {
        return $this->namespaceAnalysis ??= (new NamespacesAnalyzer())->getNamespaceAt($this->tokens, $this->index);
    }

    /**
     * @return array<string, NamespaceUseAnalysis>
     */
    private function getNamespaceUseAnalyses(): array
    {
        if (isset($this->namespaceUseAnalyses)) {
            return $this->namespaceUseAnalyses;
        }

        foreach ((new NamespaceUsesAnalyzer())->getDeclarationsInNamespace($this->tokens, $this->getNamespaceAnalysis()) as $namespaceUseAnalysis) {
            if (!$namespaceUseAnalysis->isClass()) {
                continue;
            }
            $this->namespaceUseAnalyses[$namespaceUseAnalysis->getShortName()] = $namespaceUseAnalysis;
        }

        return $this->namespaceUseAnalyses;
    }
}

<?php

use PhpCsFixerCustomFixers\Fixer\ConstructorEmptyBracesFixer;
use PhpCsFixerCustomFixers\Fixer\MultilinePromotedPropertiesFixer;

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
;

return (new PhpCsFixer\Config())
    ->registerCustomFixers(new PhpCsFixerCustomFixers\Fixers())
    ->setRules([
        '@Symfony' => true,
        'phpdoc_align' => false,
        'no_superfluous_phpdoc_tags' => false,
        'array_syntax' => ['syntax' => 'short'],
        ConstructorEmptyBracesFixer::name() => true,
        MultilinePromotedPropertiesFixer::name() => true,
    ])
    ->setFinder($finder)
;

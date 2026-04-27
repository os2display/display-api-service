<?php
// This file is copied from config/symfony/php/.php-cs-fixer.dist.php in https://github.com/itk-dev/devops_itkdev-docker.
// Feel free to edit the file, but consider making a pull request if you find a general issue with the file.

// https://github.com/PHP-CS-Fixer/PHP-CS-Fixer/blob/master/doc/config.rst

use PhpCsFixerCustomFixers\Fixer\ConstructorEmptyBracesFixer;
use PhpCsFixerCustomFixers\Fixer\MultilinePromotedPropertiesFixer;

$finder = new PhpCsFixer\Finder();
// Check all files …
$finder->in(__DIR__);
// … that are not ignored by VCS
$finder->ignoreVCSIgnored(true);

$config = new PhpCsFixer\Config();
$config->setFinder($finder);

$config->registerCustomFixers(new PhpCsFixerCustomFixers\Fixers());
$config->setRiskyAllowed(true);

$config->setRules([
    '@Symfony' => true,
    'phpdoc_align' => false,
    'no_superfluous_phpdoc_tags' => false,
    'array_syntax' => ['syntax' => 'short'],
    'phpdoc_to_comment' => false,
    'declare_strict_types' => true,
    ConstructorEmptyBracesFixer::name() => true,
    MultilinePromotedPropertiesFixer::name() => true,
]);

return $config;

<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Validator\ThemeCss;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class ThemeCssValidatorTest extends TestCase
{
    /**
     * @dataProvider validCss
     */
    public function testAcceptsValidCss(string $css): void
    {
        $violations = Validation::createValidator()->validate($css, new ThemeCss());

        $this->assertCount(0, $violations, (string) $violations);
    }

    /**
     * @dataProvider invalidCss
     */
    public function testRejectsInvalidCss(string $css, string $message): void
    {
        $violations = Validation::createValidator()->validate($css, new ThemeCss());

        $this->assertCount(1, $violations);
        $this->assertStringContainsString($message, (string) $violations->get(0)->getMessage());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validCss(): iterable
    {
        yield 'empty' => [''];
        yield 'rule' => ['#SLIDE_ID .text { color: red; }'];
        yield 'native nesting' => ['#SLIDE_ID { .text { color: red; } & .box { padding: 1rem; } }'];
        yield 'nested @media' => ['#SLIDE_ID { @media (min-width: 10px) { .x { color: red; } } }'];
        yield '@import for fonts' => ['@import url("https://p.typekit.net/p.css?s=1&k=x"); #SLIDE_ID { color: red; }'];
        yield 'url with //' => ['#SLIDE_ID { background: url(https://example.com/a.png); }'];
        yield 'braces and comment markers in strings' => ['#SLIDE_ID .a::after { content: "} /* {"; } #SLIDE_ID .b::after { content: \'it\\\'s\'; }'];
        yield 'closed comments' => ["/* note\n spanning lines */ #SLIDE_ID { color: /* inline */ red; }"];
        yield 'forbidden constructs named in comments' => ['/* never use expression( or -moz-binding */ #SLIDE_ID { color: red; /* no javascript: urls */ }'];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidCss(): iterable
    {
        yield 'unterminated comment (#572)' => ["/* note\n#SLIDE_ID { color: red; }", 'comment that is not closed'];
        yield 'unterminated string' => ['#SLIDE_ID .a::after { content: "abc; }', 'string that is not closed'];
        yield 'missing closing brace' => ['#SLIDE_ID { .text { color: red; }', 'braces'];
        yield 'extra closing brace' => ['#SLIDE_ID { color: red; } }', 'braces'];
        yield 'expression()' => ['#SLIDE_ID { width: expression(alert(1)); }', 'expression('];
        yield '-moz-binding' => ['#SLIDE_ID { -moz-binding: url(x.xml#a); }', '-moz-binding'];
        yield 'javascript: url' => ['#SLIDE_ID { background: url("javascript:alert(1)"); }', 'javascript:'];
    }
}

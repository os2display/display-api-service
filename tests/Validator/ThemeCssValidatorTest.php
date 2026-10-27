<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Validator\ThemeCss;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class ThemeCssValidatorTest extends TestCase
{
    /**
     * The cases the admin's theme css lint runs too, so the editor and the API agree.
     *
     * @dataProvider sharedCases
     */
    public function testAgreesWithTheAdminLint(string $css, ?string $error): void
    {
        $violations = Validation::createValidator()->validate($css, new ThemeCss());

        $this->assertSame($error, $violations->count() > 0 ? $violations->get(0)->getCode() : null, (string) $violations);
    }

    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function sharedCases(): iterable
    {
        $cases = json_decode((string) file_get_contents(__DIR__.'/../fixtures/theme-css-cases.json'), true, 512, \JSON_THROW_ON_ERROR);
        foreach ($cases as $case) {
            yield $case['name'] => [$case['css'], $case['error']];
        }
    }

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
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidCss(): iterable
    {
        yield 'unterminated comment (#572)' => ["/* note\n#SLIDE_ID { color: red; }", 'comment that is not closed'];
        yield 'unterminated string' => ['#SLIDE_ID .a::after { content: "abc; }', 'string that is not closed'];
        yield 'missing closing brace' => ['#SLIDE_ID { .text { color: red; }', '"{" on line 1 that is never closed'];
        yield 'extra closing brace' => ['#SLIDE_ID { color: red; } }', '"}" on line 1 with no matching'];
        yield 'stray closing brace with matching totals' => ['#SLIDE_ID { } } #SLIDE_ID .x { }', '"}" on line 1 with no matching'];
        yield 'extra closing brace on a later line' => ["#SLIDE_ID {\n  color: red;\n}\n}", '"}" on line 4 with no matching'];
    }
}

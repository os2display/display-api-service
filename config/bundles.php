<?php

declare(strict_types=1);

use ApiPlatform\Symfony\Bundle\ApiPlatformBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Fidry\AliceDataFixtures\Bridge\Symfony\FidryAliceDataFixturesBundle;
use Gesdinet\JWTRefreshTokenBundle\GesdinetJWTRefreshTokenBundle;
use Hautelook\AliceBundle\HautelookAliceBundle;
use ItkDev\OpenIdConnectBundle\ItkDevOpenIdConnectBundle;
use Lexik\Bundle\JWTAuthenticationBundle\LexikJWTAuthenticationBundle;
use Liip\ImagineBundle\LiipImagineBundle;
use Nelmio\Alice\Bridge\Symfony\NelmioAliceBundle;
use Nelmio\CorsBundle\NelmioCorsBundle;
use Pentatrion\ViteBundle\PentatrionViteBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Vich\UploaderBundle\VichUploaderBundle;

return [
    FrameworkBundle::class => ['all' => true],
    TwigBundle::class => ['all' => true],
    SecurityBundle::class => ['all' => true],
    NelmioCorsBundle::class => ['all' => true],
    ApiPlatformBundle::class => ['all' => true],
    WebProfilerBundle::class => ['dev' => true, 'test' => true],
    MonologBundle::class => ['all' => true],
    DebugBundle::class => ['dev' => true],
    MakerBundle::class => ['dev' => true],
    DoctrineBundle::class => ['all' => true],
    DoctrineMigrationsBundle::class => ['all' => true],
    NelmioAliceBundle::class => ['dev' => true, 'test' => true],
    FidryAliceDataFixturesBundle::class => ['dev' => true, 'test' => true],
    HautelookAliceBundle::class => ['dev' => true, 'test' => true],
    VichUploaderBundle::class => ['all' => true],
    ItkDevOpenIdConnectBundle::class => ['all' => true],
    LexikJWTAuthenticationBundle::class => ['all' => true],
    GesdinetJWTRefreshTokenBundle::class => ['all' => true],
    LiipImagineBundle::class => ['all' => true],
    PentatrionViteBundle::class => ['all' => true],
];

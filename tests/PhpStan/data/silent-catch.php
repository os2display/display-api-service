<?php

declare(strict_types=1);

namespace SilentCatchFixture;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

function work(): void
{
}

function emptyCatchIsFlagged(): void
{
    try {
        work();
    } catch (\RuntimeException $e) {
    }
}

function rethrowIsAllowed(): void
{
    try {
        work();
    } catch (\RuntimeException $e) {
        throw $e;
    }
}

function wrappedRethrowIsAllowed(): void
{
    try {
        work();
    } catch (\RuntimeException $e) {
        throw new \LogicException('wrapped', 0, $e);
    }
}

function loggerCallIsAllowed(LoggerInterface $logger): void
{
    try {
        work();
    } catch (\RuntimeException $e) {
        $logger->error('failed', ['exception' => $e]);
    }
}

function errorLogIsAllowed(): void
{
    try {
        work();
    } catch (\RuntimeException $e) {
        error_log('failed');
    }
}

function consoleOutputIsAllowed(SymfonyStyle $io): void
{
    try {
        work();
    } catch (\RuntimeException $e) {
        $io->error('failed');
    }
}

function consoleQueryMethodIsNotEnough(SymfonyStyle $io): void
{
    try {
        work();
    } catch (\RuntimeException $e) {
        $io->isVerbose();
    }
}

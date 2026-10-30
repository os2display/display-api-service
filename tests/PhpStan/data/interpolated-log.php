<?php

declare(strict_types=1);

namespace InterpolatedLogFixture;

use Psr\Log\LoggerInterface;

const PREFIX = 'prefix: ';

function interpolatedStringIsFlagged(LoggerInterface $logger, string $detail): void
{
    $logger->error("failed: $detail");
}

function concatWithVariableIsFlagged(LoggerInterface $logger, string $detail): void
{
    $logger->error('failed: '.$detail);
}

function logLevelMessageSecondArgIsFlagged(LoggerInterface $logger, string $detail): void
{
    $logger->log('error', "dynamic $detail");
}

function staticMessageIsAllowed(LoggerInterface $logger): void
{
    $logger->info('static message');
}

function placeholderMessageIsAllowed(LoggerInterface $logger, string $detail): void
{
    $logger->info('static {detail}', ['detail' => $detail]);
}

function constConcatIsAllowed(LoggerInterface $logger): void
{
    $logger->error(PREFIX.'literal');
}

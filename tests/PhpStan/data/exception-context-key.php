<?php

declare(strict_types=1);

namespace ExceptionContextFixture;

use Psr\Log\LoggerInterface;

function exceptionKeyIsAllowed(LoggerInterface $logger, \Throwable $e): void
{
    $logger->error('failed', ['exception' => $e]);
}

function otherKeyIsFlagged(LoggerInterface $logger, \Throwable $e): void
{
    $logger->error('failed', ['error' => $e]);
}

function anotherOtherKeyIsFlagged(LoggerInterface $logger, \Throwable $e): void
{
    $logger->warning('failed', ['cause' => $e]);
}

function nonThrowableValueIsAllowed(LoggerInterface $logger): void
{
    $logger->info('ok', ['foo' => 'bar']);
}

function logSignatureExceptionKeyIsAllowed(LoggerInterface $logger, \Throwable $e): void
{
    $logger->log('error', 'failed', ['exception' => $e]);
}

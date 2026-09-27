<?php

declare(strict_types=1);

namespace App\Tests\Api\FlushCount;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * POST /v2/media.
 */
class MediaPostFlushCountTest extends AbstractFlushCountTestCase
{
    public function testPostMedia(): void
    {
        // The upload moves the source file, so upload a temporary copy.
        $tmpFile = stream_get_meta_data(tmpfile())['uri'];
        file_put_contents($tmpFile, file_get_contents('fixtures/files/test.jpg'));
        $file = new UploadedFile($tmpFile, 'test.jpg');

        $client = $this->getAuthenticatedClient();

        $this->resetProbes();
        $client->request('POST', '/v2/media', [
            'extra' => [
                'parameters' => [
                    'title' => 'Flush count media',
                    'description' => 'Flush count media',
                    'license' => 'Free CC',
                ],
                'files' => [
                    'file' => $file,
                ],
            ],
            'headers' => [
                'Content-Type' => 'multipart/form-data',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);

        // MediaDoctrineEventListener::postPersist() flushes inside the outer flush's transaction.
        $this->assertProbes(2, [1, 0]);
    }
}

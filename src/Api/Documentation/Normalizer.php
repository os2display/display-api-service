<?php

namespace App\Api\Documentation;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class Normalizer implements NormalizerInterface
{
    private NormalizerInterface $decorated;

    public function __construct(NormalizerInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        return $this->decorated->supportsNormalization($data, $format);
    }

    public function normalize($object, string $format = null, array $context = []): array
    {
        /** @var array $docs */
        $docs = $this->decorated->normalize($object, $format, $context);

        // Make some changes to $docs here.

        return $docs;
    }
}

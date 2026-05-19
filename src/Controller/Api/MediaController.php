<?php

declare(strict_types=1);

namespace App\Controller\Api;

use ApiPlatform\Validator\Exception\ValidationException;
use App\Entity\Tenant\Media;
use App\Exceptions\MediaException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsController]
class MediaController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly int $mediaMaxUploadSizeMb,
    ) {}

    /**
     * @throws MediaException
     */
    public function __invoke(Request $request): Media
    {
        $uploadedFile = $request->files->get('file');
        if (!$uploadedFile) {
            throw new BadRequestHttpException('"file" is required');
        }

        // API Platform skips its ValidateListener when `deserialize: false` is set on the
        // operation, so we run the Assert\File constraint here. `Mi` (binary MiB) matches
        // the admin dropzone's `mb * 1024 * 1024` threshold.
        $violations = $this->validator->validate(
            $uploadedFile,
            new Assert\File(
                maxSize: $this->mediaMaxUploadSizeMb.'Mi',
                mimeTypes: ['image/jpeg', 'image/png', 'image/svg+xml', 'video/webm', 'video/mp4', 'image/gif'],
                mimeTypesMessage: 'Please upload a valid image format: jpeg, svg, gif or png, or video format: webm or mp4',
            ),
        );

        if (count($violations) > 0) {
            throw new ValidationException($violations);
        }

        $title = $this->getRequestParameter($request, 'title');
        $description = $this->getRequestParameter($request, 'description');
        $license = $this->getRequestParameter($request, 'license');

        $media = new Media();
        $media
            ->setFile($uploadedFile)
            ->setTitle($title)
            ->setDescription($description)
            ->setLicense($license)
        ;

        // Note that the extra information about the uploaded file is added in the MediaDoctrineEventListener because
        // the file does not exist on disk before this point.
        return $media;
    }

    /**
     * @throws MediaException
     */
    private function getRequestParameter(Request $request, string $key): string
    {
        if (!$request->request->has($key)) {
            throw new MediaException(sprintf('Missing request parameter: %s', $key));
        }

        return strval($request->request->get($key));
    }
}

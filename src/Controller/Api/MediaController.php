<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Tenant\Media;
use App\Exceptions\MediaException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[AsController]
class MediaController extends AbstractController
{
    /**
     * @throws MediaException
     */
    public function __invoke(Request $request): Media
    {
        $uploadedFile = $request->files->get('file');
        if (!$uploadedFile) {
            throw new BadRequestHttpException('"file" is required');
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

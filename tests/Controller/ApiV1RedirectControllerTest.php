<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tests\AbstractBaseApiTestCase;
use Symfony\Component\HttpFoundation\Request;

class ApiV1RedirectControllerTest extends AbstractBaseApiTestCase
{
    public function testIndex()
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');
        $client->request(Request::METHOD_GET, '/v1/screens/01GN9PW2Z03V8VQG7SN6Q9R17H');

        $this->assertResponseRedirects('/v2/screens/01GN9PW2Z03V8VQG7SN6Q9R17H', 308);

        $client->request(Request::METHOD_POST, '/v1/authentication/screen');

        $this->assertResponseRedirects('/v2/authentication/screen', 308);
    }
}

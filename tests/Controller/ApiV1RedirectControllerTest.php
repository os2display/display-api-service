<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tests\AbstractBaseApiTestCase;

class ApiV1RedirectControllerTest extends AbstractBaseApiTestCase
{
    public function testIndex()
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');
        $crawler = $client->request('GET', '/v1/screens/01GN9PW2Z03V8VQG7SN6Q9R17H');

        $this->assertResponseRedirects('/v2/screens/01GN9PW2Z03V8VQG7SN6Q9R17H', 301);
    }
}

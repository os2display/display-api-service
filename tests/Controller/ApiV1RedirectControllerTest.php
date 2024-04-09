<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiV1RedirectControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/v1/screens/01GN9PW2Z03V8VQG7SN6Q9R17H');

        $this->assertResponseRedirects('/v2/screens/01GN9PW2Z03V8VQG7SN6Q9R17H', 301);

        $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }
}

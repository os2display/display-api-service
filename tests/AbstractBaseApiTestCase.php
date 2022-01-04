<?php

namespace App\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client;
use App\Entity\User;
use App\Utils\IriHelperUtils;
use Hautelook\AliceBundle\PhpUnit\BaseDatabaseTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

abstract class AbstractBaseApiTestCase extends ApiTestCase
{
    use BaseDatabaseTrait;

    protected iriHelperUtils $iriHelperUtils;
    protected JWTTokenManagerInterface $JWTTokenManager;

    public static function setUpBeforeClass(): void
    {
        static::bootKernel();
        static::ensureKernelTestCase();
        static::populateDatabase();
    }

    protected function setUp(): void
    {
        $this->iriHelperUtils = static::getContainer()->get(IriHelperUtils::class);
        $this->JWTTokenManager = static::getContainer()->get(JWTTokenManagerInterface::class);
    }

    protected function getJwtToken(User $user): string
    {
        return $this->JWTTokenManager->create($user);
    }

    protected function getAuthenticatedClient(User $user = null): Client
    {
        if (null === $user) {
            $manager = self::$container->get('doctrine')->getManager();

            $user = $manager->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);

            if (null === $user) {
                $user = new User();
                $user->setFullName('Test Test');
                $user->setEmail('test@example.com');
                $user->setPassword(
                    self::$container->get('security.user_password_hasher')->hashPassword($user, '$3CR3T')
                );
                $manager->persist($user);
                $manager->flush();
            }
        }

        $token = $this->getJwtToken($user);

        return static::createClient([], ['auth_bearer' => $token]);
    }
}
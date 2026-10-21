<?php

declare(strict_types=1);

namespace App\Tests\Command\Theme;

use App\Entity\Tenant;
use App\Entity\Tenant\Theme;
use App\Tests\AbstractBaseApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ValidateThemeCssCommandTest extends AbstractBaseApiTestCase
{
    public function testListsThemesWhoseCssFailsTheChecks(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $theme = new Theme();
        $theme->setTitle('broken theme css');
        $theme->setTenant($em->getRepository(Tenant::class)->findOneBy(['tenantKey' => 'ABC']));
        $theme->setCssStyles("/* note\n#SLIDE_ID { color: red; }");
        $em->persist($theme);
        $em->flush();

        $command = (new Application(self::$kernel))->find('app:theme:validate-css');
        $tester = new CommandTester($command);
        $tester->execute([]);

        $output = $tester->getDisplay();
        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('broken theme css', $output);
        $this->assertStringContainsString('comment that is not closed', $output);
        $this->assertStringNotContainsString('theme_abc_1', $output);
    }
}

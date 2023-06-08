<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegisterTest extends WebTestCase
{
    /**
     * @dataProvider provideUsers
     */
    public function testRegistration(string $username, string $password): void
    {
        $client = static::createClient();
        $user = $this->createUser($client, $username, $password);

        $entityManager = $client->getContainer()->get('doctrine')->getManager();
        $savedUser = $entityManager->getRepository(User::class)->findOneBy(['name' => $username]);

        $this->assertInstanceOf(User::class, $savedUser);
        $this->assertEquals($username, $savedUser->getName());

        $passwordHasher = $client->getContainer()->get(UserPasswordHasherInterface::class);
        $this->assertTrue($passwordHasher->isPasswordValid($savedUser, $password));

        $this->removeUser($client, $user);
    }

    /**
     * @dataProvider provideUsersForDuplicate
     */
    public function testDuplicateUsernameRegistration(string $username, string $password): void
    {
        $client = static::createClient();
        $user = $this->createUser($client, $username, $password);

        $crawler = $client->request('GET', '/register');
        $form = $crawler->selectButton('Register')->form([
            'registration_form[name]' => $username,
            'registration_form[plainPassword]' => $password,
        ]);

        $client->submit($form);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $crawler = $client->getCrawler();
        $this->assertStringContainsString('There is already an account with this name', $crawler->filter('div.alert.alert-danger')->text());

        $this->removeUser($client, $user);
    }

    /**
     * @dataProvider provideUsersForShortPassword
     */
    public function testShortPasswordRegistration(string $username, string $password): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/register');
        $form = $crawler->selectButton('Register')->form([
            'registration_form[name]' => $username,
            'registration_form[plainPassword]' => $password,
        ]);

        $client->submit($form);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $crawler = $client->getCrawler();
        $this->assertStringContainsString('Your password should be at least 6 characters', $crawler->filter('div.alert.alert-danger')->text());
    }

    public static function provideUsers()
    {
        return [
            ['john_doe', 'password123'],
        ];
    }

    public static function provideUsersForDuplicate()
    {
        return [
            ['john_doe', 'password123'],
        ];
    }

    public static function provideUsersForShortPassword()
    {
        return [
            ['john_doe', 'short'],
        ];
    }

    private function createUser($client, $name, $password): User
    {
        $entityManager = $client->getContainer()->get('doctrine')->getManager();
        $passwordHasher = $client->getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setName($name);
        $user->setPassword($passwordHasher->hashPassword($user, $password));

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    private function removeUser($client, $user): void
    {
        $entityManager = $client->getContainer()->get('doctrine')->getManager();
        $userRepository = $entityManager->getRepository(User::class);
        $managedUser = $userRepository->find($user->getId());

        $entityManager->remove($managedUser);
        $entityManager->flush();
    }
}
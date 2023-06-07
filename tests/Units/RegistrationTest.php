<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationTest extends WebTestCase
{
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

    public function testRegistration(): void
    {
        $client = static::createClient();
        $user = $this->createUser($client, 'john_doe', 'password123');

        $entityManager = $client->getContainer()->get('doctrine')->getManager();
        $savedUser = $entityManager->getRepository(User::class)->findOneBy(['name' => 'john_doe']);

        $this->assertInstanceOf(User::class, $savedUser);
        $this->assertEquals('john_doe', $savedUser->getName());

        $passwordHasher = $client->getContainer()->get(UserPasswordHasherInterface::class);
        $this->assertTrue($passwordHasher->isPasswordValid($savedUser, 'password123'));

        $this->removeUser($client, $user);
    }

    public function testDuplicateUsernameRegistration(): void
    {
        $client = static::createClient();
        $user = $this->createUser($client, 'john_doe', 'password123');

        $crawler = $client->request('GET', '/register');
        $form = $crawler->selectButton('Register')->form([
            'registration_form[name]' => 'john_doe',
            'registration_form[plainPassword]' => 'password123',
        ]);

        $client->submit($form);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $crawler = $client->getCrawler();
        $this->assertStringContainsString('There is already an account with this name', $crawler->filter('div.alert.alert-danger')->text());

        $this->removeUser($client, $user);
    }
}
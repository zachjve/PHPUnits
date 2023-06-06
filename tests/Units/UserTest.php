<?php

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;

class UserTest extends KernelTestCase
{
    private $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
    }

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    public function testConnection()
    {
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        // Créer un nouvel utilisateur
        $user = new User();
        $user->setName('john_doe');
        $user->setPassword($passwordHasher->hashPassword($user, 'password123'));

        // Sauvegarder l'utilisateur en base de données
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Récupérer l'utilisateur enregistré en base de données
        $savedUser = $this->entityManager->getRepository(User::class)->findOneBy(['name' => 'john_doe']);

        // Vérifier si l'utilisateur est enregistré correctement
        $this->assertInstanceOf(User::class, $savedUser);
        $this->assertEquals('john_doe', $savedUser->getName());
        $this->assertTrue(password_verify('password123', $savedUser->getPassword()));

        $this->entityManager->remove($savedUser);
        $this->entityManager->flush();
    }
}
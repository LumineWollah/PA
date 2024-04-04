<?php

namespace App\DataFixtures;

use App\Entity\CSDocument;
use App\Entity\CSLessor;
use App\Entity\CSUser;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{

    private $userPasswordHasher;
    
    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Création d'un user "normal"
        $user = new CSUser();
        $user->setEmail("test@gmail.com");
        $user->setFirstname("Test");
        $user->setLastname("Test");
        $user->setTelNumber("0606060606");
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "Test1234!"));
        $user->setRoles(['ROLE_LESSOR']);
        $manager->persist($user);
        
        // Création d'un user admin
        $userAdmin = new CSUser();
        $userAdmin->setEmail("leopold.goudier@gmail.com");
        $userAdmin->setFirstname("Léopold");
        $userAdmin->setLastname("Goudier");
        $userAdmin->setTelNumber("0637774127");
        $userAdmin->setPassword($this->userPasswordHasher->hashPassword($userAdmin, "Test1234!"));
        $userAdmin->setAdmin(true);
        $manager->persist($userAdmin);

        $document = new CSDocument();
        $document->setName("Facture Francis");
        $document->setType("FACTURE");
        $document->setUrl("test.s3");
        $document->setOwner($userAdmin);
        $manager->persist($document);

        $manager->flush();
    }
}

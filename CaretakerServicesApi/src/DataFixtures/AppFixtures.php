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
        $user->setProfessional(false);
        $user->setisVerified(false);
        $user->setRoles(['ROLE_LESSOR']);
        $manager->persist($user);

        $user = new CSUser();
        $user->setEmail("mathis.vareilles@yahoo.com");
        $user->setFirstname("Mathis");
        $user->setLastname("Vareilles");
        $user->setTelNumber("0606060606");
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "Test1234!"));
        $user->setProfessional(false);
        $user->setisVerified(false);
        $user->setRoles(['ROLE_PROVIDER']);
        $manager->persist($user);

        $user = new CSUser();
        $user->setEmail("test@yahoo.com");
        $user->setFirstname("test");
        $user->setLastname("test");
        $user->setTelNumber("0606060606");
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "Test1234!"));
        $user->setProfessional(false);
        $user->setisVerified(true);
        $user->setRoles(['ROLE_PROVIDER', 'ROLE_TRAVELER', 'ROLE_LESSOR']);
        $manager->persist($user);
        
        // Création d'un user admin
        $userAdmin = new CSUser();
        $userAdmin->setEmail("leopold.goudier@gmail.com");
        $userAdmin->setFirstname("Léopold");
        $userAdmin->setLastname("Goudier");
        $userAdmin->setTelNumber("0637774127");
        $userAdmin->setPassword($this->userPasswordHasher->hashPassword($userAdmin, "Test1234!"));
        $userAdmin->setProfessional(false);
        $userAdmin->setisVerified(true);
        $userAdmin->setAdmin(true);
        $manager->persist($userAdmin);

        $document = new CSDocument();
        $document->setName("Facture Francis");
        $document->setType("FACTURE");
        $document->setUrl("test.s3");
        $document->setOwner($userAdmin);
        $manager->persist($document);

        $document = new CSDocument();
        $document->setName("Devis jean-pierre");
        $document->setType("DEVIS");
        $document->setUrl("test2.s3");
        $document->setOwner($user);
        $manager->persist($document);

        $manager->flush();
    }
}

<?php

namespace App\DataFixtures;

use App\Entity\CsApartment;
use App\Entity\CsCompany;
use App\Entity\CsDocument;
use App\Entity\CsUser;
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
        $user = new CsUser();
        $user->setEmail("test@gmail.com");
        $user->setFirstname("Test");
        $user->setLastname("Test");
        $user->setTelNumber("0606060606");
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "Test1234!"));
        $user->setProfessional(false);
        $user->setisVerified(false);
        $user->setRoles(['ROLE_LESSOR']);
        $manager->persist($user);

        $apartment = new CsApartment();
        $apartment->setName("Mon super appart");
        $apartment->setDescription("Test de description");
        $apartment->setBedrooms(4);
        $apartment->setTravelersMax(8);
        $apartment->setArea(825.25);
        $apartment->setIsFullhouse(true);
        $apartment->setIsHouse(true);
        $apartment->setPrice(421);
        $apartment->setOwner($user);
        $apartment->setAdress("71, avenue d'Italie");
        $apartment->setPostalCode("75013");
        $apartment->setCity("Paris");
        $apartment->setCountry("France");
        $manager->persist($apartment);

        $company = new CsCompany();
        $company->setCompanyName("Boite de glands");
        $company->setCompanyEmail("lesglands@gmail.com");
        $company->setCompanyPhone("0606060606");
        $company->setSiretNumber("01022033304444");
        $company->setAdress("71, avenue d'Italie");
        $company->setPostalCode("75013");
        $company->setCity("Paris");
        $company->setCountry("France");
        $manager->persist($company);

        $user = new CsUser();
        $user->setEmail("mathis.vareilles@yahoo.com");
        $user->setFirstname("Mathis");
        $user->setLastname("Vareilles");
        $user->setTelNumber("0606060606");
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "Test1234!"));
        $user->setProfessional(true);
        $user->setisVerified(false);
        $user->setRoles(['ROLE_PROVIDER']);
        $user->setCompany($company);
        $manager->persist($user);
        
        $user = new CsUser();
        $user->setEmail("test@yahoo.com");
        $user->setFirstname("test");
        $user->setLastname("test");
        $user->setTelNumber("0606060606");
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "Test1234!"));
        $user->setProfessional(false);
        $user->setisVerified(true);
        $user->setRoles(['ROLE_PROVIDER', 'ROLE_TRAVELER', 'ROLE_LESSOR']);
        $manager->persist($user);            
        
        $userAdmin = new CsUser();
        $userAdmin->setEmail("leopold.goudier@gmail.com");
        $userAdmin->setFirstname("Léopold");
        $userAdmin->setLastname("Goudier");
        $userAdmin->setTelNumber("0637774127");
        $userAdmin->setPassword($this->userPasswordHasher->hashPassword($userAdmin, "Test1234!"));
        $userAdmin->setProfessional(false);
        $userAdmin->setisVerified(true);
        $userAdmin->setAdmin(true);
        $manager->persist($userAdmin);

        $document = new CsDocument();
        $document->setName("Facture Francis");
        $document->setType("FACTURE");
        $document->setUrl("test.s3");
        $document->setOwner($userAdmin);
        $manager->persist($document);

        $document = new CsDocument();
        $document->setName("Devis jean-pierre");
        $document->setType("DEVIS");
        $document->setUrl("test2.s3");
        $document->setOwner($user);
        $manager->persist($document);

        $manager->flush();
    }
}

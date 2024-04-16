<?php

namespace App\DataFixtures;

use App\Entity\CsApartment;
use App\Entity\CsApartmentPicture;
use App\Entity\CsCompany;
use App\Entity\CsDocument;
use App\Entity\CsUser;
use App\Entity\CsService;
use App\Entity\CsReservation;
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

        $images = ["https://tinyurl.com/ycyr8zdf", "https://tinyurl.com/9mmcfchx", "https://tinyurl.com/2ss5cam3", "https://tinyurl.com/6s9cpzhz", "https://tinyurl.com/bdeaxv7d", "https://tinyurl.com/mrxwuyy3", "https://tinyurl.com/mrxcrxfr", "https://tinyurl.com/5t4vwx47", "https://tinyurl.com/5bhk7a8f"];

        for ($i=0; $i < 32; $i++) { 
            $apartment = new CsApartment();
            $apartment->setName("Mon super appart ".$i);
            $apartment->setDescription("Contemplez le coucher de soleil sur les flots depuis la terrasse de cet appartement récemment rénové. Confortable et parfaitement aménagé, il possède une décoration soignée et des téléviseurs à écran plat dans chacune des deux chambres.\n\nDans le cadre de la pandémie de coronavirus (COVID-19), nous appliquons actuellement des mesures sanitaires supplémentaires.\n\nD'une superficie de 45 m2, l'appartement a été refait à neuf en 2018. J'ai choisi des matériaux et du mobilier \"comme si c'était pour moi\" : 2 chambres avec chacune sa petite salle d'eau, T.V écran plat au mur dans chaque chambre , séjour avec petite terrasse et vue mer magnifique, cuisine équipée d'un lave vaisselle, réfrigérateur/compartiment congélateur, four micro-onde et traditionnel, machine à laver le linge séchante, machine Nespresso et tout ce qu'il faut pour cuisiner :-)");
            $apartment->setBedrooms(4);
            $apartment->setTravelersMax(8);
            $apartment->setArea(825.25);
            $apartment->setIsFullhouse(true);
            $apartment->setIsHouse(true);
            $apartment->setPrice(421);
            $apartment->setOwner($user);
            $apartment->setAddress("71, avenue d'Italie");
            $apartment->setCenterGps([2.357781, 48.825486]);
            // $apartment->setPostalCode("75013");
            // $apartment->setCity("Paris");
            // $apartment->setCountry("France");
            $apartment->setMainPict($images[rand(0, 8)]);
            $apartment->setPictures(["https://tinyurl.com/2w8fnhrs", "https://tinyurl.com/4bck47tz"]);
            $manager->persist($apartment);
        }

        $company = new CsCompany();
        $company->setCompanyName("Boite de glands");
        $company->setCompanyEmail("lesglands@gmail.com");
        $company->setCompanyPhone("0606060606");
        $company->setSiretNumber("01022033304444");
        $company->setAddress("71, avenue d'Italie");
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
        $userAdmin->setLastname("Legland");
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

        $service = new CsService();
        $service->setName("Ménage");
        $service->setDescription("Ménage de l'appartement");
        $service->setPrice(50);
        $service->setProvider($user);
        $manager->persist($service);

        $reservation = new CsReservation();
        $reservation->setStartingDate(new \DateTime ("now"));
        $reservation->setEndingDate(new \DateTime ("now + 1 month"));
        $reservation->setUser($user);
        $reservation->setApartment($apartment);
        $reservation->setPrice(50);
        $manager->persist($reservation);
        
        $reservation = new CsReservation();
        $reservation->setStartingDate(new \DateTime ("now"));
        $reservation->setEndingDate(new \DateTime ("now"));
        $reservation->setUser($user);
        $reservation->setService($service);
        $reservation->setPrice(50);
        $manager->persist($reservation);

        $manager->flush();
    }
}

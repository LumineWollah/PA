<?php

namespace App\DataFixtures;

use App\Entity\CsAddons;
use App\Entity\CsApartment;
use App\Entity\CsApartmentPicture;
use App\Entity\CsCategory;
use App\Entity\CsCompany;
use App\Entity\CsDocument;
use App\Entity\CsUser;
use App\Entity\CsService;
use App\Entity\CsReservation;
use App\Entity\CsReviews;
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
        $company = new CsCompany();
        $company->setCompanyName("Philippe Électricité");
        $company->setCompanyEmail("philippe.semloh@gmail.com");
        $company->setCompanyPhone("0606060606");
        $company->setSiretNumber("01022033304444");
        $company->setAddress("71, avenue d'Italie");
        $company->setPostalCode("75013");
        $company->setCenterGps([2.357781, 48.825486]);
        $company->setCity("Paris");
        $company->setCountry("France");
        $manager->persist($company);

        $user = new CsUser();
        $user->setEmail("philippe.semloh@gmail.com");
        $user->setFirstname("Philippe");
        $user->setLastname("Semloh");
        $user->setTelNumber("0606060606");
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "Test1234!"));
        $user->setProfessional(true);
        $user->setisVerified(true);
        $user->setRoles(['ROLE_PROVIDER']);
        $user->setEmailIsVerify(true);
        $user->setCompany($company);
        $manager->persist($user);

        $category = new CsCategory();
        $category->setName("Autres");
        $category->setColor("6CC34D");
        $manager->persist($category);

        $category = new CsCategory();
        $category->setName("Bricolage");
        $category->setColor("2A04C5");
        $manager->persist($category);

        $category = new CsCategory();
        $category->setName("Électricité");
        $category->setColor("C2C538");
        $manager->persist($category);

        $service = new CsService();
        $service->setName("Installation électrique");
        $service->setCompany($company);
        $service->setDescription("Nous installons vos installations électriques à domicile. Nous intervenons rapidement et efficacement pour vous dépanner. Nous sommes disponibles 5j/7 et 12h/24.");
        $service->setCategory($category);
        $service->setAddressInputs(1);
        $service->setDaysOfWeek([1, 2, 3, 4, 5]);
        $service->setStartTime("08:00:00");
        $service->setEndTime("20:00:00");
        $manager->persist($service);

        $service = new CsService();
        $service->setName("Dépannage électrique");
        $service->setCompany($company);
        $service->setDescription("Nous venons réparer vos installations électriques à domicile en cas de panne. Nous intervenons rapidement et efficacement pour vous dépanner. Nous sommes disponibles 5j/7 et 12h/24.");
        $service->setPrice(85.5);
        $service->setCategory($category);
        $service->setAddressInputs(2);
        $service->setDaysOfWeek([1, 2, 3, 4, 5]);
        $service->setStartTime("08:00:00");
        $service->setEndTime("20:00:00");
        $manager->persist($service);

        $addon = new CsAddons();
        $addon->setName("Piscine");
        $manager->persist($addon);

        $images = ["https://tinyurl.com/ycyr8zdf", "https://tinyurl.com/9mmcfchx", "https://tinyurl.com/2ss5cam3", "https://tinyurl.com/6s9cpzhz", "https://tinyurl.com/bdeaxv7d", "https://tinyurl.com/mrxwuyy3", "https://tinyurl.com/mrxcrxfr", "https://tinyurl.com/5t4vwx47", "https://tinyurl.com/5bhk7a8f"];

        $user = new CsUser();
        $user->setEmail("leopoldg.discord@gmail.com");
        $user->setFirstname("Léopold");
        $user->setLastname("Goudier");
        $user->setTelNumber("0606060606");
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "Test1234!"));
        $user->setProfessional(false);
        $user->setisVerified(true);
        $user->setRoles(['ROLE_LESSOR']);
        $user->setEmailIsVerify(true);
        $manager->persist($user);

        for ($i=0; $i < 20; $i++) { 
            $apartment = new CsApartment();
            $apartment->setName("Bel appartement n°".$i);
            $apartment->setDescription("Contemplez le coucher de soleil sur les flots depuis la terrasse de cet appartement récemment rénové. Confortable et parfaitement aménagé, il possède une décoration soignée et des téléviseurs à écran plat dans chacune des deux chambres.\n\nDans le cadre de la pandémie de coronavirus (COVID-19), nous appliquons actuellement des mesures sanitaires supplémentaires.\n\nD'une superficie de 45 m2, l'appartement a été refait à neuf en 2018. J'ai choisi des matériaux et du mobilier \"comme si c'était pour moi\" : 2 chambres avec chacune sa petite salle d'eau, T.V écran plat au mur dans chaque chambre , séjour avec petite terrasse et vue mer magnifique, cuisine équipée d'un lave vaisselle, réfrigérateur/compartiment congélateur, four micro-onde et traditionnel, machine à laver le linge séchante, machine Nespresso et tout ce qu'il faut pour cuisiner :-)");
            $apartment->setBedrooms(4);
            $apartment->setBathrooms(4);
            $apartment->setTravelersMax(8);
            $apartment->setArea(825.25);
            $apartment->setPrice(421);
            $apartment->setOwner($user);
            $apartment->setAddress("71, avenue d'Italie");
            $apartment->setCenterGps([2.357781, 48.825486]);
            $apartment->setPostalCode("75013");
            $apartment->setCity("Paris");
            $apartment->setCountry("France");
            $apartment->addMandatoryService($service);
            $apartment->addAddon($addon);
            if ($i % 2 == 0) {
                $apartment->setIsHouse(false);
                $apartment->setIsFullhouse(false);
            }else{
                $apartment->setIsFullhouse(true);
                $apartment->setIsHouse(true);
            }
            if ($i % 5 == 0) {
                $apartment->setActive(false);
                $apartment->setIsVerified(false);
            }else{
                $apartment->setActive(true);
                $apartment->setIsVerified(true);
            }
            $apartment->setMainPict($images[rand(0, 8)]);
            $apartment->setPictures(["https://tinyurl.com/2w8fnhrs", "https://tinyurl.com/4bck47tz"]);
            $manager->persist($apartment);
        }

        $user = new CsUser();
        $user->setEmail("leopold.urahara@gmail.com");
        $user->setFirstname("Leopold");
        $user->setLastname("Urahara");
        $user->setTelNumber("0606060606");
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "Test1234!"));
        $user->setProfessional(false);
        $user->setisVerified(true);
        $user->setRoles(['ROLE_TRAVELER']);
        $user->setEmailIsVerify(true);
        $manager->persist($user);

        $reservation = new CsReservation();
        $reservation->setStartingDate(new \DateTime ("now"));
        $reservation->setEndingDate(new \DateTime ("now + 5 days"));
        $reservation->setUser($user);
        $reservation->setAdultTravelers(2);
        $reservation->setChildTravelers(1);
        $reservation->addService($service);
        $reservation->setApartment($apartment);
        $reservation->setPrice(564);
        $manager->persist($reservation);

        $document = new CsDocument();
        $document->setName("doc-667ac21af1ec5.pdf");
        $document->setType("FACTURE");
        $document->setUrl("https://caretakerservices.s3.eu-west-2.amazonaws.com/doc-667ac21af1ec5.pdf");
        $document->setOwner($user);
        $document->setattachedReserv($reservation);
        $manager->persist($document);

        $document = new CsDocument();
        $document->setName("doc-6fze51af1ec5.pdf");
        $document->setType("FACTURE");
        $document->setUrl("https://caretakerservices.s3.eu-west-2.amazonaws.com/doc-667ac21af1ec5.pdf");
        $document->setOwner($user);
        $document->setattachedReserv($reservation);
        $manager->persist($document);

        $reservation = new CsReservation();
        $reservation->setStartingDate(new \DateTime ("now"));
        $reservation->setEndingDate(new \DateTime ("now"));
        $reservation->setDateCreation(new \DateTime);
        $reservation->setUser($user);
        $reservation->setService($service);
        $reservation->setPrice(50);
        $manager->persist($reservation);

        $reservation2 = new CsReservation();
        $reservation2->setStartingDate(new \DateTime ("now"));
        $reservation2->setEndingDate(new \DateTime ("now"));
        $reservation2->setDateCreation(new \DateTime);
        $reservation2->setUser($user);
        $reservation2->setService($service);
        $reservation2->setPrice(50);
        $manager->persist($reservation2);
        
        for ($i=0; $i < 32; $i++) { 
            $user = new CsUser();
            $user->setEmail("test".$i."@yahoo.com");
            $user->setFirstname("test".$i);
            $user->setLastname("test");
            $user->setTelNumber("0606060606");
            $user->setPassword($this->userPasswordHasher->hashPassword($user, "Test1234!"));
            $user->setProfessional(false);
            $user->setisVerified(true);
            $user->setRoles(['ROLE_TRAVELER']);
            $manager->persist($user);     
        }
        
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

        $user = new CsUser();
        $user->setEmail("mathis.vareilles@yahoo.com");
        $user->setFirstname("Mathis");
        $user->setLastname("Vareilles");
        $user->setTelNumber("0606060606");
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "Test1234!"));
        $user->setProfessional(true);
        $user->setisVerified(false);
        $user->setAdmin($company);
        $user->setEmailIsVerify(true);
        $manager->persist($user);

        $review = new CsReviews();
        $review->setRate(4);
        $review->setContent("Super séjour");
        $review->setPostDate(new \DateTime);
        $review->setApartment($apartment);
        $review->setAuthor($user);
        $review->setReservation($reservation);
        $manager->persist($review);

        $review = new CsReviews();
        $review->setRate(5);
        $review->setContent("Super intervention");
        $review->setPostDate(new \DateTime);
        $review->setService($service);
        $review->setAuthor($user);
        $review->setReservation($reservation2);
        $manager->persist($review);

        $manager->flush();
    }
}

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
use App\Entity\CsTicket;
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
        $category->setName("Électricité");
        $category->setColor("C2C538");
        $manager->persist($category);

        $service = new CsService();
        $service->setName("Installation électrique");
        $service->setCompany($company);
        $service->setDescription("Nous installons vos installations électriques à domicile. Nous intervenons rapidement et efficacement pour vous dépanner. Nous sommes disponibles 5j/7 et 12h/24.");
        $service->setCategory($category);
        $service->setAddressInputs(1);
        $service->setDaysOfWeek([1, 2, 4, 5]);
        $service->setStartTime("08:00:00");
        $service->setEndTime("20:00:00");
        $manager->persist($service);

        $service2 = new CsService();
        $service2->setName("Dépannage électrique");
        $service2->setCompany($company);
        $service2->setDescription("Nous venons réparer vos installations électriques à domicile en cas de panne. Nous intervenons rapidement et efficacement pour vous dépanner. Nous sommes disponibles 5j/7 et 12h/24.");
        $service2->setPrice(85.5);
        $service2->setCategory($category);
        $service2->setAddressInputs(1);
        $service2->setDaysOfWeek([2, 3, 4, 5]);
        $service2->setStartTime("08:00:00");
        $service2->setEndTime("20:00:00");
        $manager->persist($service2);

        $category = new CsCategory();
        $category->setName("Bricolage");
        $category->setColor("2A04C5");
        $manager->persist($category);

        $service = new CsService();
        $service->setName("Réparation plomberie");
        $service->setCompany($company);
        $service->setDescription("Nous venons réparer vos installations de plomberie à domicile en cas de panne. Nous intervenons rapidement et efficacement pour vous dépanner. Nous sommes disponibles 5j/7 et 12h/24.");
        $service->setPrice(85.5);
        $service->setCategory($category);
        $service->setAddressInputs(1);
        $service->setDaysOfWeek([2, 3, 4, 5]);
        $service->setStartTime("08:00:00");
        $service->setEndTime("20:00:00");
        $manager->persist($service);

        $service = new CsService();
        $service->setName("Montage de meubles");
        $service->setCompany($company);
        $service->setDescription("Nous montons vos meubles à domicile. Nous intervenons rapidement et efficacement pour vous dépanner. Nous sommes disponibles 5j/7 et 12h/24.");
        $service->setCategory($category);
        $service->setAddressInputs(1);
        $service->setDaysOfWeek([2, 3, 4, 5]);
        $service->setStartTime("08:00:00");
        $service->setEndTime("20:00:00");
        $manager->persist($service);

        $category = new CsCategory();
        $category->setName("Autres");
        $category->setColor("6CC34D");
        $manager->persist($category);

        $service = new CsService();
        $service->setName("Déménagement");
        $service->setCompany($company);
        $service->setDescription("Déplacez-vous en toute sérénité avec notre service de déménagement. Nous intervenons rapidement et efficacement pour vous dépanner. Nous sommes disponibles 5j/7 et 12h/24.");
        $service->setCategory($category);
        $service->setAddressInputs(1);
        $service->setDaysOfWeek([2, 3, 4, 5]);
        $service->setStartTime("08:00:00");
        $service->setEndTime("20:00:00");
        $manager->persist($service);

        $service = new CsService();
        $service->setName("Taxi");
        $service->setCompany($company);
        $service->setDescription("Déplacez-vous en toute sérénité avec notre service de taxi. Nous intervenons rapidement et efficacement pour vous dépanner. Nous sommes disponibles 6j/7 et 24h/24.");
        $service->setCategory($category);
        $service->setAddressInputs(2);
        $service->setDaysOfWeek([1, 2, 3, 4, 5, 6]);
        $service->setStartTime("00:00:00");
        $service->setEndTime("23:59:00");
        $manager->persist($service);

        $addons = ['Piscine', 'Terrasse', 'Jardin', 'Véranda', 'Baignoire', 'Jacuzzi', 'Sauna', 'Hammam', 'Climatisation', 'Cheminée', 'Barbecue', 'Billard', 'Baby-foot', 'Ping-pong', 'Piano', 'Salle de sport'];
        $addonsObjects = [];

        foreach ($addons as $addonName) {
            $addon = new CsAddons();
            $addon->setName($addonName);
            $manager->persist($addon);
            $addonsObjects[] = $addon;
        }
        
        $images = ["https://tinyurl.com/ycyr8zdf", "https://tinyurl.com/9mmcfchx", "https://tinyurl.com/2ss5cam3", "https://tinyurl.com/6s9cpzhz", "https://tinyurl.com/bdeaxv7d", "https://tinyurl.com/mrxwuyy3", "https://tinyurl.com/mrxcrxfr", "https://tinyurl.com/5t4vwx47", "https://tinyurl.com/5bhk7a8f", "https://tinyurl.com/yh9byzj6", "https://tinyurl.com/8rvfd7r6", "https://tinyurl.com/5n8pxaa9", "https://tinyurl.com/37988bfp", "https://tinyurl.com/2n5ukh8j", "https://tinyurl.com/cj7efwck", "https://tinyurl.com/52cntjub", "https://tinyurl.com/3ywfvjv2", "https://tinyurl.com/2cwp3r4p", "https://tinyurl.com/2df9aefk", "https://tinyurl.com/yv59jwvk"];
        shuffle($images);

        $user = new CsUser();
        $user->setEmail("leopoldg.discord@gmail.com");
        $user->setFirstname("Léopold");
        $user->setLastname("Goudier");
        $user->setTelNumber("0606060606");
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "Test1234!"));
        $user->setProfessional(false);
        $user->setisVerified(true);
        $user->setEmailIsVerify(true);
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
            if ($i % 3 == 0) {
                $apartment->addMandatoryService($service);
                $apartment->setisHouse(True);
            }else{
                $apartment->setisHouse(False);
            }
            if ($i % 2 == 0) {
                $apartment->setIsFullhouse(false);
            }else{
                $apartment->setIsFullhouse(true);
            } 
            if ($i % 5 == 0) {
                $apartment->setActive(false);
                $apartment->setIsVerified(false);
            }else{
                $apartment->setActive(true);
                $apartment->setIsVerified(true);
            }
            foreach ($addonsObjects as $addon) {
                if (rand(0, 8) == 1) {
                    $apartment->addAddon($addon);
                }
            }
            $apartment->setMainPict($images[$i]);
            $apartment->setPictures([$images[$i], $images[rand(0, 19)], $images[rand(0, 19)], $images[rand(0, 19)]]);
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
        $user->setEmailIsVerify(true);
        $user->setRoles(['ROLE_TRAVELER']);
        $user->setEmailIsVerify(true);
        $manager->persist($user);

        $reservation = new CsReservation();
        $reservation->setStartingDate(new \DateTime ("now - 10 days"));
        $reservation->setEndingDate(new \DateTime ("now - 2 days"));
        $reservation->setUser($user);
        $reservation->setAdultTravelers(2);
        $reservation->setChildTravelers(1);
        $reservation->setApartment($apartment);
        $reservation->setPrice(564);
        $manager->persist($reservation);

        $document = new CsDocument();
        $document->setName("doc-667ac21af1ec5.pdf");
        $document->setType("Facture");
        $document->setUrl("https://caretakerservices.s3.eu-west-2.amazonaws.com/doc-667ac21af1ec5.pdf");
        $document->setOwner($user);
        $document->setattachedReserv($reservation);
        $manager->persist($document);

        $document = new CsDocument();
        $document->setName("doc-66fed8s541ec5.pdf");
        $document->setType("Etat des lieux entree");
        $document->setUrl("https://caretakerservices.s3.eu-west-2.amazonaws.com/doc-668edcf40c715.pdf");
        $document->setOwner($user);
        $document->setattachedReserv($reservation);
        $manager->persist($document);

        $document = new CsDocument();
        $document->setName("doc-698e6qfs54dec5.pdf");
        $document->setType("Etat des lieux sortie");
        $document->setUrl("https://caretakerservices.s3.eu-west-2.amazonaws.com/doc-668ede14e3ebd.pdf");
        $document->setOwner($user);
        $document->setattachedReserv($reservation);
        $manager->persist($document);

        $reservation = new CsReservation();
        $reservation->setStartingDate(new \DateTime ("now + 6 days"));
        $reservation->setEndingDate(new \DateTime ("now + 15 days"));
        $reservation->setUser($user);
        $reservation->setAdultTravelers(2);
        $reservation->setChildTravelers(1);
        $reservation->setApartment($apartment);
        $reservation->setPrice(564);
        $manager->persist($reservation);

        $document = new CsDocument();
        $document->setName("doc-6fze51af1ec5.pdf");
        $document->setType("Facture");
        $document->setUrl("https://caretakerservices.s3.eu-west-2.amazonaws.com/doc-667ac21af1ec5.pdf");
        $document->setOwner($user);
        $document->setattachedReserv($reservation);
        $manager->persist($document);

        $reservation2 = new CsReservation();
        $reservation2->setStartingDate(new \DateTime ("now"));
        $reservation2->setEndingDate(new \DateTime ("now"));
        $reservation2->setDateCreation(new \DateTime);
        $reservation2->setUser($user);
        $reservation2->setService($service2);
        $reservation2->setOtherData(["address0" => ["address" => "71, avenue d'Italie", "postalCode" => "75013", "city" => "Paris", "country" => "France", "centerGps" => [2.357781, 48.825486]]]);
        $reservation2->setPrice(50);
        $manager->persist($reservation2);

        $document = new CsDocument();
        $document->setName("doc-6fze51af1ec5.pdf");
        $document->setType("Facture");
        $document->setUrl("https://caretakerservices.s3.eu-west-2.amazonaws.com/doc-667ac21af1ec5.pdf");
        $document->setOwner($user);
        $document->setattachedReserv($reservation);
        $manager->persist($document);

        $review = new CsReviews();
        $review->setRate(4);
        $review->setContent("Une propriété incroyable, avec des caractéristiques époustouflantes. Le manoir offre luxe et confort pour tous les voyageurs. Le salon et la salle à manger sont des chambres parfaites pour se divertir. La cuisine est grande, mais apportez des casseroles et des poêles si vous cuisinez pour de grands groupes ! L'équipe a été incroyable et ...");
        $review->setPostDate(new \DateTime);
        $review->setApartment($apartment);
        $review->setAuthor($user);
        $review->setReservation($reservation);
        $manager->persist($review);

        $review = new CsReviews();
        $review->setRate(3);
        $review->setContent("Haylie a été une hôte formidable du début à la fin, des instructions très claires sur la façon de localiser et d'entrer dans la villa. La seule surprise pour nous, c'est que nous n'avons...");
        $review->setPostDate(new \DateTime);
        $review->setApartment($apartment);
        $review->setAuthor($user);
        $review->setReservation($reservation2);
        $manager->persist($review);

        $reservation2 = new CsReservation();
        $reservation2->setStartingDate(new \DateTime ("now"));
        $reservation2->setEndingDate(new \DateTime ("now"));
        $reservation2->setDateCreation(new \DateTime);
        $reservation2->setUser($user);
        $reservation2->setService($service);
        $reservation2->setOtherData(["address0" => ["address" => "71, avenue d'Italie", "postalCode" => "75013", "city" => "Paris", "country" => "France", "centerGps" => [2.357781, 48.825486]], 'address1' => ["address" => "71, avenue d'Italie", "postalCode" => "75013", "city" => "Paris", "country" => "France", "centerGps" => [2.357781, 48.825486]]]);
        $reservation2->setPrice(50);
        $manager->persist($reservation2);

        $document = new CsDocument();
        $document->setName("doc-6fze51af1ec5.pdf");
        $document->setType("Facture");
        $document->setUrl("https://caretakerservices.s3.eu-west-2.amazonaws.com/doc-667ac21af1ec5.pdf");
        $document->setOwner($user);
        $document->setattachedReserv($reservation2);
        $manager->persist($document);

        $review = new CsReviews();
        $review->setRate(2);
        $review->setContent("Mauvaise intervention");
        $review->setPostDate(new \DateTime);
        $review->setService($service);
        $review->setAuthor($user);
        $review->setReservation($reservation2);
        $manager->persist($review);
        
        for ($i=0; $i < 3; $i++) { 
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

        $ticket = new CsTicket();
        $ticket->setAuthor($user);
        $ticket->setDateCreation(new \DateTime);
        $ticket->setPriority("BASSE");
        $ticket->setSubject("Site Web");
        $ticket->setName("Problème de connexion");
        $ticket->setDescription("Je n'arrive pas à me connecter à mon compte");
        $ticket->setClientEmail($user->getEmail());
        $manager->persist($ticket);

        $ticket = new CsTicket();
        $ticket->setAuthor($user);
        $ticket->setDateCreation(new \DateTime ("now - 2 days"));
        $ticket->setDateClosing(new \DateTime);
        $ticket->setPriority("HAUTE");
        $ticket->setStatus("FINISHED");
        $ticket->setSubject("Site Web");
        $ticket->setName("Problème de création d'appartement");
        $ticket->setDescription("Je n'arrive pas à créer un appartement sur la plateforme");
        $ticket->setResponse("Vous avez un compte de voyageur, et non de bailleur. Veuillez créer un compte de bailleur via le formulaire d'inscription");
        $ticket->setClientEmail($user->getEmail());
        $manager->persist($ticket);
        
        $userAdmin = new CsUser();
        $userAdmin->setEmail("leopold.goudier@gmail.com");
        $userAdmin->setFirstname("Léopold");
        $userAdmin->setLastname("Goudier");
        $userAdmin->setTelNumber("0637774127");
        $userAdmin->setPassword($this->userPasswordHasher->hashPassword($userAdmin, "Test1234!"));
        $userAdmin->setProfessional(false);
        $userAdmin->setisVerified(true);
        $userAdmin->setEmailIsVerify(true);
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
        $user->setEmailIsVerify(true);
        $user->setAdmin($company);
        $user->setEmailIsVerify(true);
        $manager->persist($user);

        $manager->flush();
    }
}
<?php

namespace App\Controller\Frontend;

use App\Service\AmazonS3Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use DateTime;
use Stripe\Stripe;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\Country;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email as EmailMime;

class providersController extends AbstractController
{
    private $apiHttpClient;
    private $amazonS3Client;
    private $stripeKeyPrivate;

    public function __construct(ApiHttpClient $apiHttpClient, AmazonS3Client $amazonS3Client, string $stripeKeyPrivate)
    {
        $this->apiHttpClient = $apiHttpClient;
        $this->amazonS3Client = $amazonS3Client;
        Stripe::setApiKey($stripeKeyPrivate);
    }

    #[Route('/providers', name: 'providersList')]
    public function providersList(Request $request)
    {
        $client = $this->apiHttpClient->getClientWithoutBearer();

        $responseCompanies = $client->request('GET', 'cs_companies');

        $companies = $responseCompanies->toArray()['hydra:member'];

        return $this->render('frontend/companies/companiesList.html.twig', [
            'companies'=>$companies
        ]); 
    }

    #[Route('/providers/{id}', name: 'providersDetail')]
    public function providersDetail(Request $request, int $id, MailerInterface $mailer)
    {
        $client = $this->apiHttpClient->getClientWithoutBearer();

        $responseCompany = $client->request('GET', 'cs_companies/'.$id);

        $company = $responseCompany->toArray();
        
        $id = $request->cookies->get('id');
        $default = [];

        if ($id != null) {
            $default['name'] = $request->cookies->get('lastname').' '.$request->cookies->get('firstname');
            $default['email'] = $request->cookies->get('email');
        }

        $form = $this->createFormBuilder($default)
        ->add("name", TextType::class, [
            'constraints'=>[
                new NotBlank(),
                new Length([
                    'min' => 2,
                    'max' => 50,
                    'minMessage' => 'Votre nom doit contenir au moins 2 caractères',
                    'maxMessage' => 'Votre nom doit contenir au maximum 50 caractères'
                ])
            ],
            'attr' => ['class' => 'form-control'],
        ])
        ->add("email", EmailType::class, [
            'constraints'=>[
                new NotBlank(),
                new Email(),
            ],
            'attr' => ['class' => 'form-control'],
        ])
        ->add("message", TextareaType::class, [
            'constraints'=>[
                new NotBlank(),
                new Length([
                    'min' => 10,
                    'max' => 500,
                    'minMessage' => 'Votre message doit contenir au moins 10 caractères',
                    'maxMessage' => 'Votre message doit contenir au maximum 500 caractères'
                ])
            ],
            'attr' => ['class' => 'form-control'],
        ])
        ->getForm()->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();

            $email = (new EmailMime())
                ->from('ne-pas-repondre@caretakerservices.fr')
                ->to($company['companyEmail'])
                ->subject('Demande d\'aide')
                ->html('
                    <html>
                        <body>
                            <p>Nom: '.$data['name'].'</p>
                            <p>Email: '.$data['email'].'</p>
                            <p>Message: '.$data['message'].'</p>
                        </body>
                    </html>
                ');

            $mailer->send($email);

            $this->addFlash('success', 'Your message has been sent successfully.');
        }

        return $this->render('frontend/companies/companiesDetail.html.twig', [
            'company'=>$company,
            'form'=>$form
        ]);
    }

    #[Route('/quote-requests', name: 'quoteRequests')]
    public function quoteRequests(Request $request)
    {
        $id = $request->cookies->get('id');
            
        if ($id == null) {
            return $this->redirectToRoute('login', ['redirect'=>'myRequests']);
        }

        $client = $this->apiHttpClient->getClientWithoutBearer();

        $response = $client->request('GET', 'cs_companies?users=['.$id.']');
        $serv = $response->toArray()['hydra:member'][0]['services'];

        $ids = [];

        foreach ($serv as $service) {
            $ids[] = $service['id'];
        }

        $response = $client->request('GET', 'cs_reservations?service[]='.implode('&service[]=', $ids).'&active=false');
        $requests = $response->toArray();

        foreach ($requests['hydra:member'] as $key => $value) {
            if ($value['isRequest'] == false) {
                unset($requests['hydra:member'][$key]);
            }
        }

        return $this->render('frontend/services/quoteRequestsList.html.twig', [
            'requests'=>$requests['hydra:member']
        ]);
    }

    #[Route('/quote-requests/delete', name: 'quoteRequestDelete')]
    public function quoteRequestDelete(Request $request, MailerInterface $mailer)
    {
        $userId = $request->cookies->get('id');
        
        if ($userId == null) {
            return $this->redirectToRoute('login', ['redirect'=>'quoteRequestDelete']);
        }

        $client = $this->apiHttpClient->getClientWithoutBearer();

        $reservation = $request->request->get('reservationContent');
        $reservation = json_decode($reservation, true);

        $emailCustomer = $reservation['user']['email'];
        $lastnameCustomer = $reservation['user']['lastname'];
        $firstnameCustomer = $reservation['user']['firstname'];
        $dateCreation = (new DateTime($reservation['dateCreation']))->format('d/m/Y');
        $serviceName = $reservation['service']['name'];

        $email = (new EmailMime())
            ->from('ne-pas-repondre@caretakerservices.fr')
            ->to($emailCustomer)
            ->subject('Votre demande de devis a été annulée')
            ->html('<p>Bonjour '.$lastnameCustomer.' '.$firstnameCustomer.',</p><p>Votre demande de devis du '.$dateCreation.' pour le service '.$serviceName.' a été annulée.</p><p>Si vous avez des questions, n\'hésitez pas à nous contacter.</p><p>Cordialement,</p><p>L\'équipe Caretaker Services</p>');

        $mailer->send($email);

        $response = $client->request('DELETE', 'cs_reservations/'.$reservation['id']);

        return $this->redirectToRoute('quoteRequests');
    }

    #[Route('/quote-requests/show', name: 'quoteRequestDetail')]
    public function quoteRequestDetail(Request $request)
    {
        $id = $request->cookies->get('id');
            
        if ($id == null) {
            return $this->redirectToRoute('login', ['redirect'=>'quoteRequestDetail']);
        }

        $reservation = $request->request->get('reservation');
        $reservation = json_decode($reservation, true);

        return $this->render('frontend/services/quoteRequestDetail.html.twig', [
            'request'=>$request
        ]);
    }
}

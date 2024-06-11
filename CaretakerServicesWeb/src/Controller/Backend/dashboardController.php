<?php

namespace App\Controller\Backend;

use App\Security\CustomAccessManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class dashboardController extends AbstractController
{
    private $apiHttpClient;

    public function __construct(ApiHttpClient $apiHttpClient)
    {
        $this->apiHttpClient = $apiHttpClient;
    }

    private function checkUserRole(Request $request): bool
    {
        $role = $request->cookies->get('roles');
        return $role !== null && $role == 'ROLE_ADMIN';
    }
#[Route('/admin-panel/dashboard', name: 'dashboard')]
    public function dashboard(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $usersList = $this->fetchData($request, 'cs_users');
        $reservationsList = $this->fetchData($request, 'cs_reservations', ['unavailability' => 'false']);
        $apartments = count($this->fetchData($request, 'cs_apartments'));
        $companies = count($this->fetchData($request, 'cs_companies'));
        $services = count($this->fetchData($request, 'cs_services'));

        $dateLabels = $this->generateDateLabels(7);

        $chartUsers = $this->createChart($dateLabels, 'Users');
        $chartReservations = $this->createChart($dateLabels, 'Reservations');

        return $this->render('backend/dashboard/dashboard.html.twig', [
            'users' => $usersList['hydra:member'],
            'reservations' => $reservationsList['hydra:member'],
            'apartments' => $apartments,
            'companies' => $companies,
            'services' => $services,
            'chartUsers' => $chartUsers,
            'chartReservations' => $chartReservations,
        ]);
    }

    private function fetchData(Request $request, string $endpoint, array $query = ['page' => 1]): array
    {
        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $response = $client->request('GET', $endpoint, [
            'query' => $query,
        ]);

        return $response->toArray();
    }

    private function generateDateLabels(int $days): array
    {
        $labels = [];
        $now = new \DateTime();

        for ($i = 1; $i <= $days; $i++) {
            $labels[] = $now->modify('-1 day')->format('d/m/Y');
        }

        return array_reverse($labels);
    }

    private function createChart(array $labels, string $label): array
    {
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $label,
                    'backgroundColor' => 'rgb(128, 128, 128)',
                    'borderColor' => 'rgb(128, 128, 128)',
                    'data' => [0, 10, 5, 2, 20, 30, 45], // Example data, replace with real data as needed
                ],
            ],
            'options' => [
                'scales' => [
                    'y' => [
                        'suggestedMin' => 0,
                        'suggestedMax' => 100,
                    ],
                ],
            ],
        ];
    }
}

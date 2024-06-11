<?php

namespace App\Controller\Backend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Symfony\Component\HttpFoundation\Request;

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

        $userData = [];
        $reservationData = [];
        for ($j = 0; $j < count($dateLabels); $j++) {
            $userData[$j] = 0;
            $reservationData[$j] = 0;
            $formattedDate = date('Y-d-m', strtotime($dateLabels[$j]));
            for ($i = 0; $i < $usersList['hydra:totalItems']; $i++) {
                if (substr($usersList['hydra:member'][$i]['dateInscription'], 0, 10) == $formattedDate) {
                    $userData[$j] += 1;
                }
            }
            for ($i = 0; $i < $reservationsList['hydra:totalItems']; $i++) {
                if (substr($reservationsList['hydra:member'][$i]['dateCreation'], 0, 10) == $formattedDate) {
                    $reservationData[$j] += 1;
                }
            }
        }

        $chartUsers = $this->createChart($dateLabels, $userData);
        $chartReservations = $this->createChart($dateLabels, $reservationData);

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
        $now = $now->modify('+1 day');

        for ($i = 1; $i <= $days; $i++) {
            $labels[] = $now->modify('-1 day')->format('d/m/Y');
        }

        return array_reverse($labels);
    }

    private function createChart(array $labels, array $data): array
    {
        return [
            'labels' => $labels,
            'data' => $data
        ];
    }
}

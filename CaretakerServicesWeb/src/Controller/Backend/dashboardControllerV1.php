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
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class dashboardControllerV1 extends AbstractController
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

    #[Route('/admin-panel/dashboard/old', name: 'dashboardOld')]
    public function dashboardOld(Request $request, ChartBuilderInterface $chartBuilder)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $response = $client->request('GET', 'cs_users', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $usersList = $response->toArray();

        $response = $client->request('GET', 'cs_reservations', [
            'query' => [
                'page' => 1,
                'unavailability' => 'false',
            ]
        ]);

        $reservationsList = $response->toArray();
        
        $response = $client->request('GET', 'cs_apartments', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $apartments = count($response->toArray());

        $response = $client->request('GET', 'cs_companies', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $companies = count($response->toArray());

        $response = $client->request('GET', 'cs_services', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $services = count($response->toArray());

        $t = time();

        $chartUsers = [
            'labels' => [date("%d/%m%Y", $t), date("%d/%m%Y", $t-86400), $day-3 . "/" . $month . "/" . $year, $day-4 . "/" . $month . "/" . $year, $day-5 . "/" . $month . "/" . $year, $day-6 . "/" . $month . "/" . $year, $day-7 . "/" . $month . "/" . $year],
            'datasets' => [
                    [
                        'label' => 'Users',
                        'backgroundColor' => 'rgb(128, 128, 128)',
                        'borderColor' => 'rgb(128, 128, 128)',
                        'data' => [0, 10, 5, 2, 20, 30, 45],
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

        $chartReservations = [
            'labels' => [$day-1 . "/" . $month . "/" . $year, $day-2 . "/" . $month . "/" . $year, $day-3 . "/" . $month . "/" . $year, $day-4 . "/" . $month . "/" . $year, $day-5 . "/" . $month . "/" . $year, $day-6 . "/" . $month . "/" . $year, $day-7 . "/" . $month . "/" . $year],
            'datasets' => [
                [
                    'label' => 'Reservations',
                    'backgroundColor' => 'rgb(128, 128, 128)',
                    'borderColor' => 'rgb(128, 128, 128)',
                    'data' => [0, 10, 5, 2, 20, 30, 45],
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

        // dd($chartUsers, $chartReservations);
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
}
      

//   new Chart(ctr, {
//       type: 'line',
//       data: {
//         labels: {{ chartReservations.labels|json_encode }},
//         datasets: {{ chartReservations.datasets|json_encode }}
//       },
//       options: {{ chartReservations.options|json_encode }},
//     });
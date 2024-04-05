<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;

class providerController extends AbstractController
{
    private $apiHttpClient;

    public function __construct(ApiHttpClient $apiHttpClient)
    {
        $this->apiHttpClient = $apiHttpClient;
    }

    #[Route('/admin-panel/provider/list', name: 'providerList')]
    public function providerList()
    {
        $_SESSION["token"] = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE3MTIzMDM5MTgsImV4cCI6MTcxMjMwNzUxOCwicm9sZXMiOlsiUk9MRV9BRE1JTiIsIlJPTEVfVVNFUiJdLCJ1c2VybmFtZSI6Imxlb3BvbGQuZ291ZGllckBnbWFpbC5jb20ifQ.s-oXSTZphNSoiYPpfbh_msFDAtKC_QI9QbC7WoG2ae2VqQ1_BQK16rkQDbbu7yjywVY9K0xeh8Vpf4s57BhKwHPJOXD1wqKS8qF-OpKzJZPOKclGVlWgHKUvx2oGfeIRj7Ew8TBaGS4ayeBQBEi02o-2ZDjNzx4f6XuV_3geqYViO10CqBxl_1crsN0x51xiAYzPNHItb4abrB7uh0HRYxHwVOqdYxgzi02pIipfF4J8s0ZG01xr3i-d5VA1aOXrVdxLJA4hGs2-P8TAdf0Q-ZcpVO8ZYWSrqIZT51Y2E01ZJ_n0dqMnmtm77TaLyN57feWTSUhwYgCqMrXD3H7EaQEXSBt8HE3-04ESePXFOzjn0-dY5I1BPK2xSIbLABRy2gl4pvCqnTbzLmgiaGa_yG9e9Kv78J4luFSG2ISExPFsjgIAhZSwZhKg12uXZSeEaNtb51Y7S1J6KWi3ahBjbcGgeDeQwTX7ruLR35AgftYYFuNw0Nnw-Z-lfJ8tbDzm44myopUCmtY7hCAnf9MNE1j4Zs9Z3UoNda51h9O44udNvSKfHIQJ8RD_nZwMuozMvz9D8LUBT4-N0_phDjXAmF162bEByJkCMXunpZ-EZ3Barp2YwlKbrDwaWlxOxvHRuuWMNSqU6_R7IgU_hS8pDhbvh6VwBb6fQDrTF4BtNF0";

        $client = $this->apiHttpClient->getClient($_SESSION["token"]);

        $response = $client->request('GET', 'cs_users', [
            'query' => [
                'page' => 1,
                'roles' => 'ROLE_provider'
            ]
        ]);
        
        $providersList = $response->toArray();

        // echo "<pre>";
        // print_r($providersList);
        // echo "</pre>";

        return $this->render('backend/providers.html.twig', [
            'providers' => $providersList['hydra:member']
        ]);
    }
}

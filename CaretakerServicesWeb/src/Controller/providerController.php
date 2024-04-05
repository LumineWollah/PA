<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Symfony\Component\HttpFoundation\Request;

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
        $_SESSION["token"] = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE3MTIzMzUwNjksImV4cCI6MTcxMjMzODY2OSwicm9sZXMiOlsiUk9MRV9BRE1JTiIsIlJPTEVfVVNFUiJdLCJ1c2VybmFtZSI6Imxlb3BvbGQuZ291ZGllckBnbWFpbC5jb20ifQ.oFeIu1A61C_KUyWMzMFbhZpQXZDJ9Rqcjj0C6w0QqQW2MqbgZs7gJZeChxlU6dccv0f_G3eM7IKEw7wZpd3k7O-vtZ6nh-2G5o0OpincuDipWPqIWCUdnjRZW-c93UhRUXvvG7zKhtkiQLlh3CjqkeWP8FwSoVruKE_F_gzjumRJXEIcrVigKgpySPz89QljEWsXeBBlHOI-tuFpf2pnQO7UPR7wEmGwSdLfYtIN3Tnmev29n8w7O6_uybND8M1PdQEW-8munXVdoBPuF7vb96Wo2aBqZXOA7rajYluJG9e8eFEOB9wswf8niFtm43rolXbPdakkKv4vO2gIG5zOZpJXJ93Z044dL-gsgjdu4d90kGpgq9O5L6uyS2kmdjfK7VG9CgZWNJWEGKK7ePqrWjKY5QzV3Oi8R2oLoyFtNBwNMPipJpACltHu9lIRwO2PnWkKxQazcdFWfo4vfCQECF9QeLHr0xcUfM01_1R1lGdOTjvWmVo6Eorr2NLLK12l-9Y09Yloyy05MiwvIVBEsanhkgdb1-l6Dga1-gbo9IWxn85vv3QHO7ezClth-bhf99qIM164Q-37_iU4wDtZk-z3WZRZZZyaJHVGHBbWsK0CEMMNxS9f1YbFq7iUerKfkVvaRUkwbRxiGTFg2mHOqRPS60FCca0Sxp4xxROmaCs";

        $client = $this->apiHttpClient->getClient($_SESSION["token"]);

        $response = $client->request('GET', 'cs_users', [
            'query' => [
                'page' => 1,
                'roles' => 'ROLE_PROVIDER'
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

#[Route('/admin-panel/provider/delete', name: 'providerDelete')]
public function providerDelete(Request $request)
{
    $_SESSION["token"] = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE3MTIzMjY0ODIsImV4cCI6MTcxMjMzMDA4Miwicm9sZXMiOlsiUk9MRV9BRE1JTiIsIlJPTEVfVVNFUiJdLCJ1c2VybmFtZSI6Imxlb3BvbGQuZ291ZGllckBnbWFpbC5jb20ifQ.cz6j4aGdXDnC9d1nG_XIZ8LVUpxvrS_p2RKB6oXyB3dyuKy8mvyeusUTB58ye1wEqS_A6ddDdlzKMsLZ4RhyzsMJW5sokylwE_vj-KHDzbY79qXs_0LjQuGZl-6-kAaKBMXLwKSSlH0yug73OOdzTYV1N9vreokJ9zDncnF3HmsL6j0scbTSQx8lFkiwEM13jDb-lwbSSlQM7UUotg7SAKX2CuJJz4EYdZqOEjSqYdIDBeUp9mlZeHFaKc3cy7AnPzmEZGdRxTZI9A3bGirJ9gg7M9VZrR9F8iBo4SSwzfm59tg7kXB_9nIVofgcrm2U2KfPVXOKOESjinBnzBeQShrLsct1KTE_h9yyCCrZoFVjeijk-GkCDSI_xNNTSQ3tCZ1p5GRXJz2wxBo6GuGcb2IgqSZ_GSE84bplGeH7vdRgbbFxndtEJ-jjftzg5O6P2R5GtIbTebzs3VKPh-4pDyqtybG8ra_B187wzN0t-pxqpcASf31ZXquduaPeMicH3t1FBc5KcnkCHAd7XgCVwxKtfSm2Y5qN2J-D14H48ZbW8evTqjdSl8Ym_uj9jWygvK_XWTJII9SJtoRgv_a43-gLDJSLgdidSmMqEmPaT1m8I7MJFMjg5zVqtMh6Zo7BKifn-1mG5tob87uAXx_3gko6YjGQ9NM5u-dlCJixmxQ";

    $client = $this->apiHttpClient->getClient($_SESSION["token"]);

    $id = $request->query->get('id');

    $response = $client->request('DELETE', 'cs_users', [
        'query' => [
            'id' => $id
        ]
    ]);
}
}

<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Odm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\CsObligatoryServiceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(normalizationContext: ['groups' => ['getObligatoryServices']])]
#[ORM\Entity(repositoryClass: CsObligatoryServiceRepository::class)]
#[Get]
#[GetCollection]
#[Delete(security: "is_granted('ROLE_ADMIN') or object.getUser() == user or object.getApartmentOwner() == user or object.getProviderOwner() == user")]
#[Post(security: "is_granted('ROLE_LESSOR')")]
#[ApiFilter(SearchFilter::class, properties: ['apartment' => 'exact', 'service' => 'exact'])]
class CsObligatoryService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getObligatoryServices", "getApartments"])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'obligatoryServices')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["getObligatoryServices"])]
    private ?CsApartment $apartment = null;

    #[ORM\ManyToOne(inversedBy: 'obligatoryServices')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["getObligatoryServices", "getApartments"])]
    private ?CsService $service = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApartment(): ?CsApartment
    {
        return $this->apartment;
    }

    public function setApartment(?CsApartment $apartment): static
    {
        $this->apartment = $apartment;

        return $this;
    }

    public function getService(): ?CsService
    {
        return $this->service;
    }

    public function setService(?CsService $service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getApartmentOwner(): ?CsUser
    {
        return $this->apartment->getOwner();
    }

    public function getServiceOwner(): ?CsUser
    {
        return $this->service->getProvider();
    }
}

<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\CsReservationRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(normalizationContext: ['groups' => ['getReservations']])]
#[ORM\Entity(repositoryClass: CsReservationRepository::class)]
#[Patch(security: "is_granted('ROLE_ADMIN') or 
    (object.getUser() and object.getUser() == user) or 
    (object.getApartmentOwner() != null and object.getApartmentOwner() == user) or
    (object.getServiceOwner() != null and user in object.getServiceOwner().toArray())")]
#[Get]
#[GetCollection]
#[Delete(security: "is_granted('ROLE_ADMIN') or 
    (object.getUser() and object.getUser() == user) or 
    (object.getApartmentOwner() != null and object.getApartmentOwner() == user) or
    (object.getServiceOwner() != null and user in object.getServiceOwner().toArray())")]
#[Post]
#[ApiFilter(SearchFilter::class, properties: ['apartment' => 'exact', 'service' => 'exact', 'user' => 'exact', 'unavailability' => 'exact', 'isRequest' => 'exact', 'active' => 'exact', 'status' => 'exact'])]
class CsReservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getReservations", "getUsers", "getApartments", "getDocuments"])]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(["getReservations", "getUsers", "getApartments", "getDocuments"])]
    private ?\DateTimeInterface $startingDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(["getReservations", "getUsers", "getApartments", "getDocuments"])]
    private ?\DateTimeInterface $endingDate = null;

    #[ORM\Column()]
    #[Groups(["getReservations", "getUsers", "getApartments", "getDocuments"])]
    private ?float $price = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["getReservations"])]
    private ?string $payementId = null;

    #[ORM\Column()]
    #[Groups(["getReservations", "getUsers", "getApartments", "getDocuments"])]
    private ?bool $active = true;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[Groups(["getReservations", "getDocuments"])]
    public ?CsApartment $apartment = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[Groups(["getReservations", "getDocuments"])]
    public ?CsService $service = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[Groups(["getReservations"])]
    public ?CsUser $user = null;

    #[ORM\Column()]
    #[Groups(["getReservations", "getUsers", "getApartments", "getDocuments"])]
    private ?int $adultTravelers = 0;

    #[ORM\Column()]
    #[Groups(["getReservations", "getUsers", "getApartments", "getDocuments"])]
    private ?int $childTravelers = 0;

    #[ORM\Column()]
    #[Groups(["getReservations", "getUsers", "getApartments", "getDocuments"])]
    private ?int $babyTravelers = 0;

    #[ORM\Column]
    #[Groups(["getReservations", "getUsers", "getApartments", "getDocuments"])]
    private bool $unavailability = false;

    /**
     * @var Collection<int, CsService>
     */
    #[ORM\ManyToMany(targetEntity: CsService::class, inversedBy: 'reservationsForApart')]
    #[Groups(["getReservations", "getDocuments"])]
    private Collection $services;

    /**
     * @var Collection<int, CsDocument>
     */
    #[ORM\OneToMany(targetEntity: CsDocument::class, mappedBy: 'attachedReserv')]
    #[Groups(["getReservations"])]
    private Collection $documents;

    #[ORM\Column]
    #[Groups(["getReservations", "getUsers", "getApartments", "getDocuments"])]
    private ?DateTime $dateCreation = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["getReservations", "getUsers", "getApartments", "getDocuments"])]
    private ?array $otherData = null;

    #[ORM\Column]
    #[Groups(["getReservations", "getUsers", "getApartments", "getDocuments"])]
    private ?bool $isRequest = false;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Groups(["getReservations", "getUsers", "getApartments", "getDocuments"])]
    private ?int $status = null;

    public function __construct()
    {
        $this->services = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->dateCreation = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartingDate(): ?\DateTimeInterface
    {
        return $this->startingDate;
    }

    public function setStartingDate(\DateTimeInterface $startingDate): static
    {
        $this->startingDate = $startingDate;

        return $this;
    }

    public function getEndingDate(): ?\DateTimeInterface
    {
        return $this->endingDate;
    }

    public function setEndingDate(\DateTimeInterface $endingDate): static
    {
        $this->endingDate = $endingDate;

        return $this;
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

    public function getUser(): ?CsUser
    {
        return $this->user;
    }

    public function setUser(?CsUser $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    public function getActive()
    {
        return $this->active;
    }

    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    public function getApartmentOwner(): ?CsUser
    {
        if ($this->apartment !== null) {
            return $this->apartment->getOwner();
        }
        
        return null;
    }

    public function getServiceOwner()
    {
        if ($this->service !== null) {
            return $this->service->getCompany()->getUsers();
        }
        
        return null;
    }

    public function getAdultTravelers(): ?int
    {
        return $this->adultTravelers;
    }

    public function setAdultTravelers(?int $adultTravelers): static
    {
        $this->adultTravelers = $adultTravelers;

        return $this;
    }

    public function getChildTravelers(): ?int
    {
        return $this->childTravelers;
    }

    public function setChildTravelers(?int $childTravelers): static
    {
        $this->childTravelers = $childTravelers;

        return $this;
    }

    public function getBabyTravelers(): ?int
    {
        return $this->babyTravelers;
    }

    public function setBabyTravelers(?int $babyTravelers): static
    {
        $this->babyTravelers = $babyTravelers;

        return $this;
    }

    /**
     * @return Collection<int, CsService>
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(CsService $service): static
    {
        if (!$this->services->contains($service)) {
            $this->services->add($service);
        }

        return $this;
    }

    public function removeService(CsService $service): static
    {
        $this->services->removeElement($service);

        return $this;
    }

    public function getUnavailability()
    {
        return $this->unavailability;
    }

    public function setUnavailability($unavailability)
    {
        $this->unavailability = $unavailability;

        return $this;
    }

    public function getPayementId()
    {
        return $this->payementId;
    }

    public function setPayementId($payementId)
    {
        $this->payementId = $payementId;

        return $this;
    }

    /**
     * @return Collection<int, CsDocument>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(CsDocument $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setattachedReserv($this);
        }

        return $this;
    }

    public function removeDocument(CsDocument $document): static
    {
        if ($this->documents->removeElement($document)) {
            if ($document->getattachedReserv() === $this) {
                $document->setattachedReserv(null);
            }
        }

        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getOtherData(): ?array
    {
        return $this->otherData;
    }

    public function setOtherData(?array $otherData): static
    {
        $this->otherData = $otherData;

        return $this;
    }

    public function getisRequest(): ?bool
    {
        return $this->isRequest;
    }

    public function setisRequest($isRequest) 
    {
        $this->isRequest = $isRequest;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): static
    {
        $this->status = $status;

        return $this;
    }
}

<?php

namespace App\Entity;

use DateTime;

use App\Repository\CsApartmentRepository;
use App\Controller\CsApartmentController;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Serializer\Annotation\Groups;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;

#[ApiResource(operations: [
    new Post(
        name: 'checkAvailability', 
        uriTemplate: '/cs_apartments/availables', 
        controller: CsApartmentController::class,
        deserialize: false,
            openapiContext: [
                'summary' => 'Check availability of apartments for a given date range',
                'requestBody' => [
                    'description' => 'Check availability of apartments for a given date range',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'starting_date' => [
                                        'type' => 'string',
                                        'format' => 'date',
                                        'example' => 'YYYY-MM-DD',
                                    ],
                                    'ending_date' => [
                                        'type' => 'string',
                                        'format' => 'date',
                                        'example' => 'YYYY-MM-DD',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
    )], normalizationContext: ['groups' => ['getApartments']])]
#[Get()]
#[Patch(security: "is_granted('ROLE_ADMIN') or object.getOwner() == user")]
#[GetCollection()]
#[Delete(security: "is_granted('ROLE_ADMIN') or object.getOwner() == user")]
#[Post(security: "is_granted('ROLE_LESSOR') or is_granted('ROLE_ADMIN')")]
// #[ApiFilter(SearchFilter::class, properties: ['type' => 'exact'])]
#[ORM\Entity(repositoryClass: CsApartmentRepository::class)]
class CsApartment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getUsers", "getApartments"])]
    public ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(["getUsers", "getApartments"])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["getUsers", "getApartments"])]
    private ?string $description = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Groups(["getUsers", "getApartments"])]
    private ?int $bedrooms = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Groups(["getUsers", "getApartments"])]
    private ?int $travelersMax = null;

    #[ORM\Column]
    #[Groups(["getUsers", "getApartments"])]
    private ?float $area = null;

    #[ORM\Column]
    #[Groups(["getUsers", "getApartments"])]
    private ?bool $isFullhouse = null;

    #[ORM\Column]
    #[Groups(["getUsers", "getApartments"])]
    private ?DateTime $dateCreation = null;

    #[ORM\Column]
    #[Groups(["getUsers", "getApartments"])]
    private ?bool $isHouse = null;

    #[ORM\Column]
    #[Groups(["getUsers", "getApartments"])]
    private ?float $price = null;

    #[ORM\Column(length: 5, nullable: true)]
    #[Groups(["getUsers", "getApartments"])]
    private ?string $apartNumber = null;

    #[ORM\Column]
    #[Groups(["getUsers", "getApartments"])]
    private ?bool $isVerified = false;

    #[ORM\Column]
    #[Groups(["getUsers", "getApartments"])]
    private ?bool $active = true;

    #[ORM\Column(length: 255)]
    #[Groups(["getUsers", "getApartments"])]
    private ?string $address = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getUsers", "getApartments"])]
    private ?string $city = null;

    #[ORM\Column(length: 5)]
    #[Groups(["getUsers", "getApartments"])]
    private ?string $postalCode = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getUsers", "getApartments"])]
    private ?string $country = null;

    #[ORM\ManyToOne(inversedBy: 'apartments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["getApartments"])]
    private ?CsUser $owner = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getUsers", "getApartments"])]
    private ?String $mainPict;

    #[ORM\Column(nullable: true)]
    #[Groups(["getUsers", "getApartments"])]
    private ?array $pictures = null;

    #[ORM\OneToMany(targetEntity: CsReservation::class, mappedBy: 'apartment')]
    #[Groups(["getApartments"])]
    private Collection $reservations;

    #[ORM\OneToMany(targetEntity: CsReviews::class, mappedBy: 'apartment')]
    #[Groups(["getApartments"])]
    private Collection $reviews;

    #[ORM\OneToMany(targetEntity: CsObligatoryService::class, mappedBy: 'apartment', orphanRemoval: true)]
    #[Groups(["getApartments"])]
    private Collection $obligatoryServices;

    public function __construct()
    {
        $this->dateCreation = new DateTime();
        $this->reservations = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->obligatoryServices = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getBedrooms(): ?int
    {
        return $this->bedrooms;
    }

    public function setBedrooms(int $bedrooms): static
    {
        $this->bedrooms = $bedrooms;

        return $this;
    }

    public function getTravelersMax(): ?int
    {
        return $this->travelersMax;
    }

    public function setTravelersMax(int $travelersMax): static
    {
        $this->travelersMax = $travelersMax;

        return $this;
    }

    public function getArea(): ?float
    {
        return $this->area;
    }

    public function setArea(float $area): static
    {
        $this->area = $area;

        return $this;
    }

    public function isIsFullhouse(): ?bool
    {
        return $this->isFullhouse;
    }

    public function setIsFullhouse(bool $isFullhouse): static
    {
        $this->isFullhouse = $isFullhouse;

        return $this;
    }

    public function isIsHouse(): ?bool
    {
        return $this->isHouse;
    }

    public function setIsHouse(bool $isHouse): static
    {
        $this->isHouse = $isHouse;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getApartNumber(): ?string
    {
        return $this->apartNumber;
    }

    public function setApartNumber(?string $apartNumber): static
    {
        $this->apartNumber = $apartNumber;

        return $this;
    }

    public function isIsVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getOwner(): ?CsUser
    {
        return $this->owner;
    }

    public function setOwner(?CsUser $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getDateCreation()
    {
        return $this->dateCreation;
    }

    public function setDateCreation($dateCreation)
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    public function getPostalCode()
    {
        return $this->postalCode;
    }

    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    public function getMainPict(): ?string
    {
        return $this->mainPict;
    }

    public function setMainPict(string $mainPict): static
    {
        $this->mainPict = $mainPict;

        return $this;
    }

    public function getPictures(): ?array
    {
        return $this->pictures;
    }

    public function setPictures(?array $pictures): static
    {
        $this->pictures = $pictures;

        return $this;
    }

    /**
     * @return Collection<int, CsReservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(CsReservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setApartment($this);
        }

        return $this;
    }

    public function removeReservation(CsReservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getApartment() === $this) {
                $reservation->setApartment(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CsReviews>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(CsReviews $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setApartment($this);
        }

        return $this;
    }

    public function removeReview(CsReviews $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getApartment() === $this) {
                $review->setApartment(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CsObligatoryService>
     */
    public function getObligatoryServices(): Collection
    {
        return $this->obligatoryServices;
    }

    public function addObligatoryService(CsObligatoryService $obligatoryService): static
    {
        if (!$this->obligatoryServices->contains($obligatoryService)) {
            $this->obligatoryServices->add($obligatoryService);
            $obligatoryService->setApartment($this);
        }

        return $this;
    }

    public function removeObligatoryService(CsObligatoryService $obligatoryService): static
    {
        if ($this->obligatoryServices->removeElement($obligatoryService)) {
            // set the owning side to null (unless already changed)
            if ($obligatoryService->getApartment() === $this) {
                $obligatoryService->setApartment(null);
            }
        }

        return $this;
    }
}

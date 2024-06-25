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
        uriTemplate: 'api/cs_apartments/availables', 
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
    ),
    new Post(
        name: 'checkAvailabilityApartment', 
        uriTemplate: 'api/cs_apartments/availables/{id}', 
        controller: CsApartmentController::class,
        deserialize: false,
        openapiContext: [
            'summary' => 'Check availability of an apartment for a given date range',
            'parameters' => [
                [
                    'name' => 'id',
                    'in' => 'path',
                    'required' => true,
                    'description' => 'ID of the apartment',
                    'schema' => [
                        'type' => 'integer',
                    ],
                ],
            ],
            'requestBody' => [
                'description' => 'Check availability of an apartment for a given date range',
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
#[ApiFilter(SearchFilter::class, properties: ['owner' => 'exact'])]
#[ORM\Entity(repositoryClass: CsApartmentRepository::class)]
class CsApartment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getUsers", "getApartments", "getReservations", "getAddons", "getReviews", "getDocuments"])]
    public ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(["getUsers", "getApartments", "getReservations", "getAddons", "getReviews", "getDocuments"])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["getUsers", "getApartments", "getReservations", "getAddons", "getReviews", "getDocuments"])]
    private ?string $description = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Groups(["getUsers", "getApartments", "getReservations", "getAddons", "getReviews", "getDocuments"])]
    private ?int $bedrooms = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Groups(["getUsers", "getApartments", "getReservations", "getAddons", "getReviews", "getDocuments"])]
    private ?int $bathrooms = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Groups(["getUsers", "getApartments", "getReservations", "getAddons", "getReviews", "getDocuments"])]
    private ?int $travelersMax = null;

    #[ORM\Column]
    #[Groups(["getUsers", "getApartments", "getReservations", "getAddons", "getReviews", "getDocuments"])]
    private ?float $area = null;

    #[ORM\Column]
    #[Groups(["getUsers", "getApartments", "getReservations", "getAddons", "getReviews", "getDocuments"])]
    private ?bool $isFullhouse = null;

    #[ORM\Column]
    #[Groups(["getUsers", "getApartments", "getReservations", "getAddons", "getReviews", "getDocuments"])]
    private ?DateTime $dateCreation = null;

    #[ORM\Column]
    #[Groups(["getUsers", "getApartments", "getReservations", "getAddons", "getReviews", "getDocuments"])]
    private ?bool $isHouse = null;

    #[ORM\Column]
    #[Groups(["getUsers", "getApartments", "getReservations", "getAddons", "getReviews", "getDocuments"])]
    private ?float $price = null;

    #[ORM\Column]
    #[Groups(["getUsers", "getApartments", "getReservations", "getAddons", "getReviews", "getDocuments"])]
    private ?bool $isVerified = false;

    #[ORM\Column]
    #[Groups(["getUsers", "getApartments", "getReservations", "getAddons", "getReviews", "getDocuments"])]
    private ?bool $active = true;

    #[ORM\Column(length: 500)]
    #[Groups(["getUsers", "getApartments", "getReservations", "getAddons", "getReviews", "getDocuments"])]
    private ?string $address = null;

    #[ORM\Column]
    #[Groups(["getUsers", "getApartments", "getReservations", "getAddons", "getReviews", "getDocuments"])]
    private ?array $centerGps = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getUsers", "getApartments", "getReservations", "getAddons", "getReviews", "getDocuments"])]
    private ?string $city = null;

    #[ORM\Column(length: 5)]
    #[Groups(["getUsers", "getApartments", "getReservations", "getAddons", "getReviews", "getDocuments"])]
    private ?string $postalCode = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getUsers", "getApartments", "getReservations", "getAddons", "getReviews", "getDocuments"])]
    private ?string $country = null;

    #[ORM\ManyToOne(inversedBy: 'apartments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["getApartments"])]
    private ?CsUser $owner = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getUsers", "getApartments", "getReservations", "getAddons", "getReviews", "getDocuments"])]
    private ?String $mainPict;

    #[ORM\Column(nullable: true)]
    #[Groups(["getUsers", "getApartments", "getReservations", "getAddons", "getReviews", "getDocuments"])]
    private ?array $pictures = null;

    #[ORM\OneToMany(targetEntity: CsReservation::class, mappedBy: 'apartment', orphanRemoval:true)]
    #[Groups(["getApartments"])]
    private Collection $reservations;

    #[ORM\OneToMany(targetEntity: CsReviews::class, mappedBy: 'apartment')]
    #[Groups(["getApartments"])]
    private Collection $reviews;

    /**
     * @var Collection<int, CsService>
     */
    #[ORM\ManyToMany(targetEntity: CsService::class, inversedBy: 'apartments')]
    #[Groups(["getApartments"])]
    private Collection $mandatoryServices;

    /**
     * @var Collection<int, CsAddons>
     */
    #[ORM\ManyToMany(targetEntity: CsAddons::class, inversedBy: 'apartments')]
    #[Groups(["getApartments"])]
    private Collection $addons;

    public function __construct()
    {
        $this->dateCreation = new DateTime();
        $this->reservations = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->mandatoryServices = new ArrayCollection();
        $this->addons = new ArrayCollection();
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

    public function getCenterGps()
    {
        return $this->centerGps;
    }
 
    public function setCenterGps($centerGps)
    {
        $this->centerGps = $centerGps;

        return $this;
    }

    /**
     * @return Collection<int, CsService>
     */
    public function getMandatoryServices(): Collection
    {
        return $this->mandatoryServices;
    }

    public function addMandatoryService(CsService $mandatoryService): static
    {
        if (!$this->mandatoryServices->contains($mandatoryService)) {
            $this->mandatoryServices->add($mandatoryService);
        }

        return $this;
    }

    public function removeMandatoryService(CsService $mandatoryService): static
    {
        $this->mandatoryServices->removeElement($mandatoryService);

        return $this;
    }

    public function getBathrooms()
    {
        return $this->bathrooms;
    }

    public function setBathrooms($bathrooms)
    {
        $this->bathrooms = $bathrooms;

        return $this;
    }

    /**
     * @return Collection<int, CsAddons>
     */
    public function getAddons(): Collection
    {
        return $this->addons;
    }

    public function addAddon(CsAddons $addon): static
    {
        if (!$this->addons->contains($addon)) {
            $this->addons->add($addon);
        }

        return $this;
    }

    public function removeAddon(CsAddons $addon): static
    {
        $this->addons->removeElement($addon);

        return $this;
    }
}

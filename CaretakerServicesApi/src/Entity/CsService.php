<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use App\Controller\CsServiceController;
use App\Repository\CsServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(normalizationContext: ['groups' => ['getServices']],
operations: [
    new Get(),
    new GetCollection(),
    new Post(
        name: 'createService', 
        uriTemplate: '/cs_services/',
        controller: CsServiceController::class,
        security: "is_granted('ROLE_PROVIDER') or is_granted('ROLE_ADMIN')"
    ),
    new Post(
        name: 'checkAvailabilityService', 
        uriTemplate: 'api/cs_services/availables/{id}', 
        controller: CsServiceController::class,
        deserialize: false,
        openapiContext: [
            'summary' => 'Check availability of a service for a given date range',
            'parameters' => [
                [
                    'name' => 'id',
                    'in' => 'path',
                    'required' => true,
                    'description' => 'ID of the service',
                    'schema' => [
                        'type' => 'integer',
                    ],
                ],
            ],
            'requestBody' => [
                'description' => 'Check availability of a service for a given date range',
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
    new Patch(security: "is_granted('ROLE_ADMIN') or user in object.getProviders().toArray()"),
    new Delete(security: "is_granted('ROLE_ADMIN') or user in object.getProviders().toArray()"),
])]
#[ORM\Entity(repositoryClass: CsServiceRepository::class)]
#[ApiFilter(SearchFilter::class, properties: ['company' => 'exact'])]
// #[Patch(security: "is_granted('ROLE_ADMIN') or object.getProvider() == user")]
// #[Get]
// #[GetCollection]
// #[Delete(security: "is_granted('ROLE_ADMIN') or object.getProvider() == user")]
// #[Post(security: "is_granted('ROLE_PROVIDER') or is_granted('ROLE_ADMIN')")]
class CsService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getServices", "getUsers", "getReservations", "getApartments", "getCategories", "getCompanies", "getReviews", "getDocuments"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getServices", "getUsers", "getReservations", "getApartments", "getCategories", "getCompanies", "getReviews", "getDocuments"])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["getServices", "getUsers", "getReservations", "getApartments", "getCategories", "getCompanies", "getReviews"])]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'services')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["getServices", "getReservations", "getCategories"])]
    private ?CsCompany $company = null;

    #[ORM\ManyToOne(inversedBy: 'services')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["getServices", "getReservations", "getCompanies"])]
    private ?CsCategory $category = null;

    #[ORM\OneToMany(targetEntity: CsReservation::class, mappedBy: 'service', cascade: ['remove'])]
    #[Groups(["getServices", "getCompanies"])]
    private Collection $reservations;

    #[ORM\OneToMany(targetEntity: CsReviews::class, mappedBy: 'service', cascade: ['remove'])]
    #[Groups(["getServices"])]
    private Collection $reviews;

    /**
     * @var Collection<int, CsApartment>
     */
    #[ORM\ManyToMany(targetEntity: CsApartment::class, mappedBy: 'mandatoryServices')]
    private Collection $apartments;

    /**
     * @var Collection<int, CsReservation>
     */
    #[ORM\ManyToMany(targetEntity: CsReservation::class, mappedBy: 'services', cascade: ['remove'])]
    private Collection $reservationsForApart;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["getServices", "getUsers", "getReservations", "getApartments", "getCategories", "getCompanies", "getReviews"])]
    private ?string $coverImage = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Groups(["getServices", "getUsers", "getReservations", "getApartments", "getCategories", "getCompanies", "getReviews"])]
    private ?int $addressInputs = null;

    #[ORM\Column]
    #[Groups(["getServices", "getUsers", "getReservations", "getApartments", "getCategories", "getCompanies", "getReviews"])]
    private array $daysOfWeek = [];

    #[ORM\Column]
    #[Groups(["getServices", "getUsers", "getReservations", "getApartments", "getCategories", "getCompanies", "getReviews"])]
    private ?string $startTime = null;

    #[ORM\Column]
    #[Groups(["getServices", "getUsers", "getReservations", "getApartments", "getCategories", "getCompanies", "getReviews"])]
    private ?string $endTime = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["getServices", "getUsers", "getReservations", "getApartments", "getCategories", "getCompanies", "getReviews", "getDocuments"])]
    private ?float $price = null;

    public function __construct()
    {
        $this->reviews = new ArrayCollection();
        $this->apartments = new ArrayCollection();
        $this->reservationsForApart = new ArrayCollection();
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

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCompany(): ?CsCompany
    {
        return $this->company;
    }

    public function setCompany(?CsCompany $company): static
    {
        $this->company = $company;

        return $this;
    }
    
    public function getReservations()
    {
        return $this->reservations;
    }

    public function setReservations($reservations)
    {
        $this->reservations = $reservations;

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
            $review->setService($this);
        }

        return $this;
    }

    public function removeReview(CsReviews $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getService() === $this) {
                $review->setService(null);
            }
        }

        return $this;
    }

    public function getCategory(): ?CsCategory
    {
        return $this->category;
    }

    public function setCategory(?CsCategory $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, CsApartment>
     */
    public function getApartments(): Collection
    {
        return $this->apartments;
    }

    public function addApartment(CsApartment $apartment): static
    {
        if (!$this->apartments->contains($apartment)) {
            $this->apartments->add($apartment);
            $apartment->addMandatoryService($this);
        }

        return $this;
    }

    public function removeApartment(CsApartment $apartment): static
    {
        if ($this->apartments->removeElement($apartment)) {
            $apartment->removeMandatoryService($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, CsReservation>
     */
    public function getReservationsForApart(): Collection
    {
        return $this->reservationsForApart;
    }

    public function addReservationsForApart(CsReservation $reservationsForApart): static
    {
        if (!$this->reservationsForApart->contains($reservationsForApart)) {
            $this->reservationsForApart->add($reservationsForApart);
            $reservationsForApart->addService($this);
        }

        return $this;
    }

    public function removeReservationsForApart(CsReservation $reservationsForApart): static
    {
        if ($this->reservationsForApart->removeElement($reservationsForApart)) {
            $reservationsForApart->removeService($this);
        }

        return $this;
    }

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(string $coverImage): static
    {
        $this->coverImage = $coverImage;

        return $this;
    }

    public function getAddressInputs(): ?int
    {
        return $this->addressInputs;
    }

    public function setAddressInputs(int $addressInputs): static
    {
        $this->addressInputs = $addressInputs;

        return $this;
    }

    public function getDaysOfWeek(): array
    {
        return $this->daysOfWeek;
    }

    public function setDaysOfWeek(array $daysOfWeek): static
    {
        $this->daysOfWeek = $daysOfWeek;

        return $this;
    }

    public function getStartTime(): ?string
    {
        return $this->startTime;
    }

    public function setStartTime(string $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?string
    {
        return $this->endTime;
    }

    public function setEndTime(string $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getProviders(): Collection
    {
        return $this->company->getUsers();
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): static
    {
        $this->price = $price;

        return $this;
    }
}

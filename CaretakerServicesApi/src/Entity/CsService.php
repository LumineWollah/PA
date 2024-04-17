<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use App\Repository\CsServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(normalizationContext: ['groups' => ['getServices']])]
#[ORM\Entity(repositoryClass: CsServiceRepository::class)]
#[Patch(security: "is_granted('ROLE_ADMIN') or object.getProvider() == user")]
#[Get]
#[GetCollection]
#[Delete(security: "is_granted('ROLE_ADMIN') or object.getProvider() == user")]
#[Post(security: "is_granted('ROLE_PROVIDER')")]
class CsService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getServices", "getUsers", "getReservations", "getApartments"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getServices", "getUsers", "getReservations", "getApartments"])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["getServices", "getUsers", "getReservations", "getApartments"])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(["getServices", "getUsers", "getReservations", "getApartments"])]
    private ?float $price = null;

    #[ORM\ManyToOne(inversedBy: 'services')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["getServices", "getReservations"])]
    private ?CsCompany $company = null;

    #[ORM\ManyToOne(inversedBy: 'services')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["getServices", "getReservations"])]
    private ?CsCategory $category = null;

    #[ORM\OneToMany(targetEntity: CsReservation::class, mappedBy: 'service')]
    #[Groups(["getServices"])]
    private Collection $reservations;

    #[ORM\OneToMany(targetEntity: CsReviews::class, mappedBy: 'service')]
    #[Groups(["getServices"])]
    private Collection $reviews;

    /**
     * @var Collection<int, CsApartment>
     */
    #[ORM\ManyToMany(targetEntity: CsApartment::class, mappedBy: 'mandatoryServices')]
    private Collection $apartments;

    public function __construct()
    {
        $this->reviews = new ArrayCollection();
        $this->apartments = new ArrayCollection();
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

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

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
}

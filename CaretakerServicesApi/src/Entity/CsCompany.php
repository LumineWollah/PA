<?php

namespace App\Entity;

use App\Repository\CsCompanyRepository;

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
use App\Controller\CsCompanyController;

#[ApiResource(operations: [
    new Delete(
        name: 'delete', 
        uriTemplate: '/cs_companies/{id}', 
        controller: CsCompanyController::class
    )], normalizationContext: ['groups' => ['getCompanies']])]
#[Get()]
#[Patch(security: "is_granted('ROLE_ADMIN') or user in object.getUsers().toArray()")]
#[GetCollection()]
// #[Delete(security: "is_granted('ROLE_ADMIN') or user in object.getUsers().toArray()")]
#[Post()]
#[ApiFilter(SearchFilter::class, properties: ['siretNumber' => 'exact'])]
#[ORM\Entity(repositoryClass: CsCompanyRepository::class)]
class CsCompany
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getUsers", "getCompanies", "getServices", "getReservations"])]
    private ?int $id = null;

    #[ORM\Column(length: 14)]
    #[Groups(["getUsers", "getCompanies", "getServices", "getReservations"])]
    private ?string $siretNumber = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getUsers", "getCompanies", "getServices", "getReservations"])]
    private ?string $companyName = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getUsers", "getCompanies", "getServices", "getReservations"])]
    private ?string $companyEmail = null;

    #[ORM\Column(length: 10)]
    #[Groups(["getUsers", "getCompanies", "getServices", "getReservations"])]
    private ?string $companyPhone = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getUsers", "getCompanies", "getServices", "getReservations"])]
    private ?string $address = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getUsers", "getCompanies", "getServices", "getReservations"])]
    private ?string $city = null;

    #[ORM\Column(length: 5)]
    #[Groups(["getUsers", "getCompanies", "getServices", "getReservations"])]
    private ?string $postalCode = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getUsers", "getCompanies", "getServices", "getReservations"])]
    private ?string $country = null;

    #[ORM\Column]
    #[Groups(["getUsers", "getCompanies", "getServices", "getReservations"])]
    private ?array $centerGps = null;

    #[ORM\OneToMany(targetEntity: CsService::class, mappedBy: 'company', orphanRemoval: true)]
    #[Groups(["getCompanies"])]
    private Collection $services;

    #[ORM\OneToMany(targetEntity: CsUser::class, mappedBy: 'company')]
    #[Groups(["getCompanies"])]
    private Collection $users;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["getUsers", "getCompanies", "getServices", "getReservations"])]
    private ?string $coverImage = null;

    /**
     * @var Collection<int, CsCategory>
     */
    #[ORM\ManyToMany(targetEntity: CsCategory::class, inversedBy: 'csCompanies')]
    #[Groups(["getCompanies"])]
    private Collection $categories;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->categories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSiretNumber(): ?string
    {
        return $this->siretNumber;
    }

    public function setSiretNumber(string $siretNumber): static
    {
        $this->siretNumber = $siretNumber;

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getCompanyEmail(): ?string
    {
        return $this->companyEmail;
    }

    public function setCompanyEmail(string $companyEmail): static
    {
        $this->companyEmail = $companyEmail;

        return $this;
    }

    public function getCompanyPhone(): ?string
    {
        return $this->companyPhone;
    }

    public function setCompanyPhone(string $companyPhone): static
    {
        $this->companyPhone = $companyPhone;

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

    /**
     * @return Collection<int, CsUser>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(CsUser $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setCompany($this);
        }

        return $this;
    }

    public function removeUser(CsUser $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getCompany() === $this) {
                $user->setCompany(null);
            }
        }

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
            $service->setCompany($this);
        }

        return $this;
    }

    public function removeService(CsService $service): static
    {
        if ($this->services->removeElement($service)) {
            // set the owning side to null (unless already changed)
            if ($service->getCompany() === $this) {
                $service->setCompany(null);
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

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(?string $coverImage): static
    {
        $this->coverImage = $coverImage;

        return $this;
    }

    /**
     * @return Collection<int, CsCategory>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(CsCategory $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function removeCategory(CsCategory $category): static
    {
        $this->categories->removeElement($category);

        return $this;
    }
}

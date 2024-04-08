<?php

namespace App\Entity;

use App\Repository\CsCompanyRepository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
// #[ApiFilter(SearchFilter::class, properties: ['type' => 'exact'])]
#[ORM\Entity(repositoryClass: CsCompanyRepository::class)]
class CsCompany
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getUsers", "getCompanies"])]
    private ?int $id = null;

    #[ORM\Column(length: 14)]
    #[Groups(["getUsers", "getCompanies"])]
    private ?string $siretNumber = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getUsers", "getCompanies"])]
    private ?string $companyName = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getUsers", "getCompanies"])]
    private ?string $companyEmail = null;

    #[ORM\Column(length: 10)]
    #[Groups(["getUsers", "getCompanies"])]
    private ?string $companyPhone = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getUsers", "getCompanies"])]
    private ?string $adress = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getUsers", "getCompanies"])]
    private ?string $city = null;

    #[ORM\Column(length: 5)]
    #[Groups(["getUsers", "getCompanies"])]
    private ?string $postalCode = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getUsers", "getCompanies"])]
    private ?string $country = null;

    #[ORM\OneToMany(targetEntity: CsUser::class, mappedBy: 'company')]
    #[Groups(["getCompanies"])]
    private Collection $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
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

    public function getAdress()
    {
        return $this->adress;
    }

    public function setAdress($adress)
    {
        $this->adress = $adress;

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
}

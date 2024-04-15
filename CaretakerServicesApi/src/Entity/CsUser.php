<?php

namespace App\Entity;

use DateTime;

use App\Controller\CsUserController;
use App\Repository\CsUserRepository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;

#[ApiResource(normalizationContext: ['groups' => ['getUsers']])]
#[Get(security: "is_granted('ROLE_ADMIN') or object.getOwner() == user")]
#[Patch(security: "is_granted('ROLE_ADMIN') or object.getOwner() == user")]
#[GetCollection()]
#[Delete(security: "is_granted('ROLE_ADMIN')")]
#[Post()]
#[ORM\Entity(repositoryClass: CsUserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ApiFilter(SearchFilter::class, properties: ['roles' => 'partial', 'email' => 'exact'])]
class CsUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getUsers", "getDocuments", "getApartments", "getCompanies"])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(["getUsers", "getDocuments", "getApartments", "getCompanies"])]
    private ?string $email = null;

    #[ORM\Column(length: 150)]
    #[Groups(["getUsers", "getDocuments", "getApartments", "getCompanies"])]
    #[Assert\NotBlank(message: "Le prénom est obligaoire")]
    #[Assert\Length(min: 3, max: 150, minMessage: "Le prénom doit faire au moins {{ limit }} caractères", maxMessage: "Le prénom ne peut pas faire plus de {{ limit }} caractères")]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getUsers", "getDocuments", "getApartments", "getCompanies"])]
    private ?string $lastname = null;

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    #[Groups(["getUsers", "getDocuments", "getApartments", "getCompanies"])]
    private ?DateTime $lastConnection = null;

    #[ORM\Column]
    #[Groups(["getUsers", "getDocuments", "getApartments", "getCompanies"])]
    private ?DateTime $dateInscription = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(["getUsers", "getDocuments", "getApartments", "getCompanies"])]
    private ?string $profilePict = null;

    #[ORM\Column(type: 'json')]
    #[Groups(["getUsers", "getDocuments", "getApartments", "getCompanies"])]
    private $roles = [];

    #[ORM\Column(length: 10)]
    #[Groups(["getUsers", "getDocuments", "getApartments", "getCompanies"])]
    private ?string $telNumber = null;

    #[ORM\Column]
    #[Groups(["getUsers", "getDocuments", "getApartments", "getCompanies"])]
    private ?bool $isVerified = false;

    #[ORM\Column]
    #[Groups(["getUsers", "getDocuments", "getApartments", "getCompanies"])]
    private ?bool $professional = false;

    #[ORM\Column(nullable: true)]
    #[Groups(["getUsers", "getDocuments", "getApartments", "getCompanies"])]
    private ?int $subscription = null;

    #[ORM\OneToMany(targetEntity: CsDocument::class, mappedBy: 'owner', orphanRemoval: true)]
    #[Groups(["getUsers"])]
    private Collection $documents;

    #[ORM\OneToMany(targetEntity: CsApartment::class, mappedBy: 'owner', orphanRemoval: true)]
    #[Groups(["getUsers"])]
    private Collection $apartments;

    #[ORM\ManyToOne(targetEntity: CsCompany::class, inversedBy: 'users')]
    #[Groups(["getUsers"])]
    private ?CsCompany $company = null;

    #[ORM\OneToMany(targetEntity: CsReservation::class, mappedBy: 'user')]
    #[Groups(["getUsers"])]
    private Collection $reservations;

    #[ORM\OneToMany(targetEntity: CsService::class, mappedBy: 'provider', orphanRemoval: true)]
    #[Groups(["getUsers"])]
    private Collection $services;

    #[ORM\OneToMany(targetEntity: CsReviews::class, mappedBy: 'author')]
    #[Groups(["getUsers"])]
    private Collection $reviews;

    public function __construct()
    {
        $this->dateInscription = new DateTime();
        $this->lastConnection = new DateTime();
        $this->documents = new ArrayCollection();
        $this->apartments = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->services = new ArrayCollection();
        $this->reviews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * Get the value of firstname
     */ 
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set the value of firstname
     *
     * @return  self
     */ 
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
        return $this;
    }

    /**
     * Get the value of lastname
     */ 
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set the value of lastname
     *
     * @return  self
     */ 
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get the value of lastConnection
     */ 
    public function getLastConnection()
    {
        return $this->lastConnection;
    }

    /**
     * Set the value of lastConnection
     *
     * @return  self
     */ 
    public function setLastConnection($lastConnection)
    {
        $this->lastConnection = $lastConnection;

        return $this;
    }

    /**
     * Get the value of dateInscription
     */ 
    public function getDateInscription()
    {
        return $this->dateInscription;
    }

    /**
     * Set the value of dateInscription
     *
     * @return  self
     */ 
    public function setDateInscription($dateInscription)
    {
        $this->dateInscription = $dateInscription;

        return $this;
    }

    /**
     * Get the value of profilePict
     */ 
    public function getProfilePict()
    {
        return $this->profilePict;
    }

    /**
     * Set the value of profilePict
     *
     * @return  self
     */ 
    public function setProfilePict($profilePict)
    {
        $this->profilePict = $profilePict;

        return $this;
    }

    /**
     * Get the value of telNumber
     */ 
    public function getTelNumber()
    {
        return $this->telNumber;
    }

    /**
     * Set the value of telNumber
     *
     * @return  self
     */ 
    public function setTelNumber($telNumber)
    {
        $this->telNumber = $telNumber;

        return $this;
    }

    /**
     * Get the value of admin
     */ 
    public function getAdmin()
    {
        return in_array("ROLE_ADMIN", $this->roles);
    }

    /**
     * Set the value of admin
     *
     * @return  self
     */ 
    public function setAdmin($admin)
    {
        if ($admin){
            $this->roles[] = "ROLE_ADMIN";
        }
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
            $document->setOwner($this);
        }

        return $this;
    }

    public function removeDocument(CsDocument $document): static
    {
        if ($this->documents->removeElement($document)) {
            // set the owning side to null (unless already changed)
            if ($document->getOwner() === $this) {
                $document->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * Get the value of isVerified
     */ 
    public function getisVerified()
    {
        return $this->isVerified;
    }

    /**
     * Set the value of isVerified
     *
     * @return  self
     */ 
    public function setisVerified($isVerified)
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    /**
     * Get the value of professional
     */ 
    public function getProfessional()
    {
        return $this->professional;
    }

    /**
     * Set the value of professional
     *
     * @return  self
     */ 
    public function setProfessional($professional)
    {
        $this->professional = $professional;

        return $this;
    }

    /**
     * Get the value of subscription
     */ 
    public function getSubscription()
    {
        return $this->subscription;
    }

    /**
     * Set the value of subscription
     *
     * @return  self
     */ 
    public function setSubscription($subscription)
    {
        $this->subscription = $subscription;

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
            $apartment->setOwner($this);
        }

        return $this;
    }

    public function removeApartment(CsApartment $apartment): static
    {
        if ($this->apartments->removeElement($apartment)) {
            // set the owning side to null (unless already changed)
            if ($apartment->getOwner() === $this) {
                $apartment->setOwner(null);
            }
        }

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

    /**
     * @return Collection<int, CsReservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(CsReservation $reservations): static
    {
        if (!$this->reservations->contains($reservations)) {
            $this->reservations->add($reservations);
            $reservations->setUser($this);
        }

        return $this;
    }

    public function removeReservation(CsReservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getUser() === $this) {
                $reservation->setUser(null);
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
            $service->setProvider($this);
        }

        return $this;
    }

    public function removeService(CsService $service): static
    {
        if ($this->services->removeElement($service)) {
            // set the owning side to null (unless already changed)
            if ($service->getProvider() === $this) {
                $service->setProvider(null);
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
            $review->setAuthor($this);
        }

        return $this;
    }

    public function removeReview(CsReviews $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getAuthor() === $this) {
                $review->setAuthor(null);
            }
        }

        return $this;
    }
}

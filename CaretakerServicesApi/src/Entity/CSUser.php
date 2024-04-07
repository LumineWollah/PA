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

#[ApiResource(security: "is_granted('ROLE_USER')", operations: [
    new Get(
        name: 'me', 
        uriTemplate: '/cs_users/me', 
        controller: CsUserController::class
    )
])]
#[ApiResource(security: "is_granted('ROLE_USER')", normalizationContext: ['groups' => ['getUsers']])]
#[Get(security: "is_granted('ROLE_ADMIN')")]
#[Patch(security: "is_granted('ROLE_ADMIN') or object.owner == user")]
#[GetCollection(security: "is_granted('ROLE_ADMIN')")]
#[Delete(security: "is_granted('ROLE_ADMIN')")]
#[Post(security: "is_granted('ROLE_ADMIN')")]
#[ORM\Entity(repositoryClass: CsUserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ApiFilter(SearchFilter::class, properties: ['roles' => 'partial'])]
class CsUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getUsers", "getDocuments", "getApartments"])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(["getUsers", "getDocuments", "getApartments"])]
    private ?string $email = null;

    #[ORM\Column(length: 150)]
    #[Groups(["getUsers", "getDocuments", "getApartments"])]
    #[Assert\NotBlank(message: "Le prénom est obligaoire")]
    #[Assert\Length(min: 3, max: 150, minMessage: "Le prénom doit faire au moins {{ limit }} caractères", maxMessage: "Le prénom ne peut pas faire plus de {{ limit }} caractères")]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getUsers", "getDocuments", "getApartments"])]
    private ?string $lastname = null;

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    #[Groups(["getUsers", "getDocuments", "getApartments"])]
    private ?DateTime $lastConnection = null;

    #[ORM\Column]
    #[Groups(["getUsers", "getDocuments", "getApartments"])]
    private ?DateTime $dateInscription = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(["getUsers", "getDocuments", "getApartments"])]
    private ?string $profilePict = null;

    #[ORM\Column(type: 'json')]
    #[Groups(["getUsers", "getDocuments", "getApartments"])]
    private $roles = [];

    #[ORM\Column(length: 10)]
    #[Groups(["getUsers", "getDocuments", "getApartments"])]
    private ?string $telNumber = null;

    #[ORM\Column]
    #[Groups(["getUsers", "getDocuments", "getApartments"])]
    private ?bool $isVerified = false;

    #[ORM\Column]
    #[Groups(["getUsers", "getDocuments", "getApartments"])]
    private ?bool $professional = false;

    #[ORM\Column(nullable: true)]
    #[Groups(["getUsers", "getDocuments", "getApartments"])]
    private ?int $subscription = null;

    #[ORM\OneToMany(targetEntity: CsDocument::class, mappedBy: 'owner', orphanRemoval: true)]
    #[Groups(["getUsers"])]
    private Collection $documents;

    #[ORM\OneToMany(targetEntity: CsApartment::class, mappedBy: 'owner', orphanRemoval: true)]
    #[Groups(["getUsers"])]
    private Collection $apartments;

    public function __construct()
    {
        $this->dateInscription = new DateTime();
        $this->lastConnection = new DateTime();
        $this->documents = new ArrayCollection();
        $this->apartments = new ArrayCollection();
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
}

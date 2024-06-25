<?php

namespace App\Entity;

use DateTime;

use App\Repository\CsDocumentRepository;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Serializer\Annotation\Groups;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;

#[ApiResource(normalizationContext: ['groups' => ['getDocuments']])]
#[Get(security: "is_granted('ROLE_ADMIN') or object.getOwner() == user")]
#[Patch(security: "is_granted('ROLE_ADMIN') or object.getOwner() == user")]
#[GetCollection(security: "is_granted('ROLE_ADMIN')")]
#[Delete(security: "is_granted('ROLE_ADMIN')")]
#[Post()]
#[ApiFilter(SearchFilter::class, properties: ['type' => 'exact'])]
#[ORM\Entity(repositoryClass: CsDocumentRepository::class)]
class CsDocument
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getUsers", "getDocuments", "getReservations"])]
    private ?int $id = null;

    #[ORM\Column(length: 300)]
    #[Groups(["getUsers", "getDocuments", "getReservations"])]
    private ?string $name = null;

    #[ORM\Column(length: 50)]
    #[Groups(["getUsers", "getDocuments", "getReservations"])]
    private ?string $type = null;

    #[ORM\Column(length: 300)]
    #[Groups(["getUsers", "getDocuments", "getReservations"])]
    private ?string $url = null;

    #[ORM\Column]
    #[Groups(["getUsers", "getDocuments", "getReservations"])]
    private ?DateTime $dateCreation = null;

    #[ORM\Column]
    #[Groups(["getUsers", "getDocuments", "getReservations"])]
    private ?bool $visibility = true;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["getDocuments"])]
    private ?CsUser $owner = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["getDocuments"])]
    private ?CsReservation $attachedReserv = null;

    public function __construct()
    {
        $this->dateCreation = new DateTime();
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

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

    #[Groups(["getDocuments"])]
    public function getOwner(): ?CsUser
    {
        return $this->owner;
    }

    public function setOwner(?CsUser $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get the value of visibility
     */ 
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set the value of visibility
     *
     * @return  self
     */ 
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getReservation(): ?CsReservation
    {
        return $this->attachedReserv;
    }

    public function setReservation(?CsReservation $reservation): static
    {
        $this->attachedReserv = $reservation;

        return $this;
    }
}

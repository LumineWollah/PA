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
use App\Controller\CsDocumentController;

#[ApiResource(operations: [new Post(
    name: 'inventoryFormCreate', 
    uriTemplate: 'api/inventory-form/create', 
    controller: CsDocumentController::class,
    deserialize: false,
    openapiContext: [
        'summary' => 'Create a document in PDF for an inventory form',
        'requestBody' => [
            'description' => 'Create a document in PDF for an inventory form',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [],
                    ],
                ],
            ],
        ],
    ]
)], normalizationContext: ['groups' => ['getDocuments']])]
#[Get(security: "is_granted('ROLE_ADMIN') or object.getOwner() == user")]
#[Patch(security: "is_granted('ROLE_ADMIN') or object.getOwner() == user")]
#[GetCollection]
#[Delete(security: "is_granted('ROLE_ADMIN') or object.getOwner() == user")]
#[Post()]
#[ApiFilter(SearchFilter::class, properties: ['type' => 'exact', 'owner' => 'exact'])]
#[ORM\Entity(repositoryClass: CsDocumentRepository::class)]
class CsDocument
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getUsers", "getDocuments", "getReservations", "getReviews"])]
    private ?int $id = null;

    #[ORM\Column(length: 300)]
    #[Groups(["getUsers", "getDocuments", "getReservations", "getReviews"])]
    private ?string $name = null;

    #[ORM\Column(length: 50)]
    #[Groups(["getUsers", "getDocuments", "getReservations", "getReviews"])]
    private ?string $type = null;

    #[ORM\Column(length: 300)]
    #[Groups(["getUsers", "getDocuments", "getReservations", "getReviews"])]
    private ?string $url = null;

    #[ORM\Column]
    #[Groups(["getUsers", "getDocuments", "getReservations", "getReviews"])]
    private ?DateTime $dateCreation = null;

    #[ORM\Column]
    #[Groups(["getUsers", "getDocuments", "getReservations", "getReviews"])]
    private ?bool $visibility = true;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["getDocuments"])]
    private ?CsUser $owner = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: true)]
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

    public function getattachedReserv(): ?CsReservation
    {
        return $this->attachedReserv;
    }

    public function setattachedReserv(?CsReservation $reservation): static
    {
        $this->attachedReserv = $reservation;

        return $this;
    }
}

<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\CsTicketRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(normalizationContext: ['groups' => ['getTickets']])]
#[ORM\Entity(repositoryClass: CsTicketRepository::class)]
#[Patch(security: "is_granted('ROLE_ADMIN')")]
#[Get(security: "is_granted('ROLE_ADMIN')")]
#[GetCollection(security: "is_granted('ROLE_ADMIN')")]
#[Delete(security: "is_granted('ROLE_ADMIN')")]
#[Post(security: "is_granted('ROLE_ADMIN')")]
class CsTicket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['getTickets'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['getTickets'])]
    private ?CsUser $author = null;

    #[ORM\Column(length: 20)]
    #[Groups(['getTickets'])]
    private ?string $status = "NEW";

    #[ORM\Column]
    #[Groups(['getTickets'])]
    private ?\DateTime $dateCreation = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['getTickets'])]
    private ?\DateTime $dateClosing = null;

    #[ORM\Column(length: 10)]
    #[Groups(['getTickets'])]
    private ?string $priority = null;

    #[ORM\Column(length: 20)]
    #[Groups(['getTickets'])]
    private ?string $subject = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getTickets'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getTickets'])]
    private ?string $description = null;

    #[ORM\Column(length: 300, nullable: true)]
    #[Groups(['getTickets'])]
    private ?string $clientEmail = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['getTickets'])]
    private ?string $response = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthor(): ?CsUser
    {
        return $this->author;
    }

    public function setAuthor(?CsUser $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getDateClosing(): ?\DateTimeInterface
    {
        return $this->dateClosing;
    }

    public function setDateClosing(?\DateTimeInterface $dateClosing): static
    {
        $this->dateClosing = $dateClosing;

        return $this;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
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

    public function getClientEmail(): ?string
    {
        return $this->clientEmail;
    }

    public function setClientEmail(?string $clientEmail): static
    {
        $this->clientEmail = $clientEmail;

        return $this;
    }

    public function getResponse(): ?string
    {
        return $this->response;
    }

    public function setResponse(?string $response): static
    {
        $this->response = $response;

        return $this;
    }
}

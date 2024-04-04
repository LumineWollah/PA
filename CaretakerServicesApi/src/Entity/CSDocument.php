<?php

namespace App\Entity;

use App\Repository\CSDocumentRepository;
use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CSDocumentRepository::class)]
class CSDocument
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getUsers", "getDocuments"])]
    private ?int $id = null;

    #[ORM\Column(length: 300)]
    #[Groups(["getUsers", "getDocuments"])]
    private ?string $name = null;

    #[ORM\Column(length: 50)]
    #[Groups(["getUsers", "getDocuments"])]
    private ?string $type = null;

    #[ORM\Column(length: 300)]
    #[Groups(["getUsers", "getDocuments"])]
    private ?string $url = null;

    #[ORM\Column]
    #[Groups(["getUsers", "getDocuments"])]
    private ?DateTime $date_creation = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["getDocuments"])]
    private ?CSUser $owner = null;

    public function __construct()
    {
        $this->date_creation = new DateTime();
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
        return $this->date_creation;
    }

    public function setDateCreation($date_creation)
    {
        $this->date_creation = $date_creation;
        return $this;
    }

    public function getOwner(): ?CSUser
    {
        return $this->owner;
    }

    public function setOwner(?CSUser $owner): static
    {
        $this->owner = $owner;

        return $this;
    }
}

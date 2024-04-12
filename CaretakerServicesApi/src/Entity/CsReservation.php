<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Repository\CsReservationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(normalizationContext: ['groups' => ['getReservations']])]
#[ORM\Entity(repositoryClass: CsReservationRepository::class)]
#[Get]
#[GetCollection]
#[Delete]
#[Post]
class CsReservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getReservations", "getUsers", "getApartments"])]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(["getReservations", "getUsers", "getApartments"])]
    private ?\DateTimeInterface $startingDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(["getReservations", "getUsers", "getApartments"])]

    private ?\DateTimeInterface $endingDate = null;

    #[ORM\Column()]
    #[Groups(["getReservations", "getUsers", "getApartments"])]

    private ?float $price = null;

    #[ORM\Column()]
    #[Groups(["getReservations", "getUsers", "getApartments"])]

    private ?bool $active = true;

    #[ORM\ManyToOne(inversedBy: 'occupancyDates')]
    #[Groups(["getReservations"])]
    public ?CsApartment $apartment = null;

    #[ORM\ManyToOne(inversedBy: 'occupancyDates')]
    #[Groups(["getReservations"])]
    public ?CsUser $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartingDate(): ?\DateTimeInterface
    {
        return $this->startingDate;
    }

    public function setStartingDate(\DateTimeInterface $startingDate): static
    {
        $this->startingDate = $startingDate;

        return $this;
    }

    public function getEndingDate(): ?\DateTimeInterface
    {
        return $this->endingDate;
    }

    public function setEndingDate(\DateTimeInterface $endingDate): static
    {
        $this->endingDate = $endingDate;

        return $this;
    }

    public function getApartment(): ?CsApartment
    {
        return $this->apartment;
    }

    public function setApartment(?CsApartment $apartment): static
    {
        $this->apartment = $apartment;

        return $this;
    }

    public function getUser(): ?CsUser
    {
        return $this->user;
    }

    public function setUser(?CsUser $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    public function getActive()
    {
        return $this->active;
    }

    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }
}
<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Repository\CsReviewsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(normalizationContext: ['groups' => ['getReviews']])]
#[ORM\Entity(repositoryClass: CsReviewsRepository::class)]
#[Get]
#[GetCollection]
#[Delete(security: "is_granted('ROLE_ADMIN') or object.getAuthor() == user")]
#[Post]
class CsReviews
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getReviews", "getApartments", "getServices"])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["getReviews", "getApartments", "getServices"])]
    private ?string $content = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Groups(["getReviews", "getApartments", "getServices"])]
    private ?int $rate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(["getReviews", "getApartments", "getServices"])]
    private ?\DateTimeInterface $postDate = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[Groups(["getReviews"])]
    private ?CsService $service = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[Groups(["getReviews"])]
    private ?CsApartment $apartment = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["getReviews", "getApartments", "getServices"])]
    private ?CsUser $author = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getRate(): ?int
    {
        return $this->rate;
    }

    public function setRate(int $rate): static
    {
        $this->rate = $rate;

        return $this;
    }

    public function getPostDate(): ?\DateTimeInterface
    {
        return $this->postDate;
    }

    public function setPostDate(\DateTimeInterface $postDate): static
    {
        $this->postDate = $postDate;

        return $this;
    }

    public function getService(): ?CsService
    {
        return $this->service;
    }

    public function setService(?CsService $service): static
    {
        $this->service = $service;

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

    public function getAuthor(): ?CsUser
    {
        return $this->author;
    }

    public function setAuthor(?CsUser $author): static
    {
        $this->author = $author;

        return $this;
    }
}

<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\CsCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(normalizationContext: ['groups' => ['getCategories']])]
#[ORM\Entity(repositoryClass: CsCategoryRepository::class)]
#[Patch(security: "is_granted('ROLE_ADMIN')")]
#[Get]
#[GetCollection]
#[Delete(security: "is_granted('ROLE_ADMIN')")]
#[Post(security: "is_granted('ROLE_ADMIN')")]
class CsCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['getCategories', 'getServices', 'getReservations'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getCategories', 'getServices', 'getReservations'])]
    private ?string $name = null;

    #[ORM\Column(length: 6)]
    #[Groups(['getCategories', 'getServices', 'getReservations'])]
    private ?string $color = null;

    /**
     * @var Collection<int, CsService>
     */
    #[ORM\OneToMany(targetEntity: CsService::class, mappedBy: 'category')]
    #[Groups(['getCategories'])]
    private Collection $services;

    public function __construct()
    {
        $this->services = new ArrayCollection();
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

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

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
            $service->setCategory($this);
        }

        return $this;
    }

    public function removeService(CsService $service): static
    {
        if ($this->services->removeElement($service)) {
            // set the owning side to null (unless already changed)
            if ($service->getCategory() === $this) {
                $service->setCategory(null);
            }
        }

        return $this;
    }
}
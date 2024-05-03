<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Repository\CsAddonsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(normalizationContext: ['groups' => ['getAddons']])]
#[ORM\Entity(repositoryClass: CsAddonsRepository::class)]
#[Get]
#[GetCollection]
#[Delete(security: "is_granted('ROLE_ADMIN')")]
#[Post(security: "is_granted('ROLE_ADMIN')")]
class CsAddons
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['getAddons', 'getApartments'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getAddons', 'getApartments'])]
    private ?string $name = null;

    /**
     * @var Collection<int, CsApartment>
     */
    #[ORM\ManyToMany(targetEntity: CsApartment::class, mappedBy: 'addons')]
    #[Groups(['getAddons'])]
    private Collection $apartments;

    public function __construct()
    {
        $this->apartments = new ArrayCollection();
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
            $apartment->addAddon($this);
        }

        return $this;
    }

    public function removeApartment(CsApartment $apartment): static
    {
        if ($this->apartments->removeElement($apartment)) {
            $apartment->removeAddon($this);
        }

        return $this;
    }
}

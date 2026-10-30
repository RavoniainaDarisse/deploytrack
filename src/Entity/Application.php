<?php

namespace App\Entity;

use App\Entity\Environment;
use App\Repository\ApplicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ApplicationRepository::class)]
class Application
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de l’application est obligatoire.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: 'Veuillez saisir une URL valide.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'L’URL ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $repositoryUrl = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, Environment>
     */
    #[ORM\OneToMany(
        targetEntity: Environment::class,
        mappedBy: 'application',
        orphanRemoval: true
    )]
    private Collection $environments;

    /**
     * @var Collection<int, Release>
     */
    #[ORM\OneToMany(targetEntity: Release::class, mappedBy: 'application', orphanRemoval: true)]
    private Collection $releases;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->environments = new ArrayCollection();
        $this->releases = new ArrayCollection();
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
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

    public function getRepositoryUrl(): ?string
    {
        return $this->repositoryUrl;
    }

    public function setRepositoryUrl(?string $repositoryUrl): static
    {
        $this->repositoryUrl = $repositoryUrl;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, Environment>
     */
    public function getEnvironments(): Collection
    {
        return $this->environments;
    }

    public function addEnvironment(Environment $environment): static
    {
        if (!$this->environments->contains($environment)) {
            $this->environments->add($environment);
            $environment->setApplication($this);
        }

        return $this;
    }

    public function removeEnvironment(Environment $environment): static
    {
        if ($this->environments->removeElement($environment)) {
            // set the owning side to null (unless already changed)
            if ($environment->getApplication() === $this) {
                $environment->setApplication(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Release>
     */
    public function getReleases(): Collection
    {
        return $this->releases;
    }

    public function addRelease(Release $release): static
    {
        if (!$this->releases->contains($release)) {
            $this->releases->add($release);
            $release->setApplication($this);
        }

        return $this;
    }

    public function removeRelease(Release $release): static
    {
        if ($this->releases->removeElement($release)) {
            // set the owning side to null (unless already changed)
            if ($release->getApplication() === $this) {
                $release->setApplication(null);
            }
        }

        return $this;
    }
}

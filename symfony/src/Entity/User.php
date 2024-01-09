<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity; //under the assumption that the user must be unique
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Table(name: "user", uniqueConstraints: [
    new ORM\UniqueConstraint(name: "UNIQ_8D93D649E7927C74", columns: ["email"]),
    new ORM\UniqueConstraint(name: "IDX_8D93D649F92F3E70", columns: ["country_id"]),
])]
#[ORM\Entity]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Column(name: "id", type: "integer", nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    private $id;

    #[ORM\Column(name: "name", type: "string", length: 128, nullable: false)]
    private $name;

    #[ORM\Column(name: "email", type: "string", length: 128, nullable: false)]
    private $email;

    #[ORM\Column(name: "password", type: "string", length: 128, nullable: false)]
    private $password;

    #[ORM\Column(name: "register_date", type: "datetime", nullable: true)]
    private $registerDate;

    #[ORM\Column(name: "admin", type: "boolean", nullable: false)]
    private $admin = '0';

    #[ORM\ManyToOne(targetEntity: "Country")]
    #[ORM\JoinColumn(name: "country_id", referencedColumnName: "id")]
    private $country;

    #[ORM\ManyToMany(targetEntity: "Series", inversedBy: "genre")]
    #[ORM\JoinTable(
        name: "user_series",
        joinColumns: [new ORM\JoinColumn(name: "user_id", referencedColumnName: "id")],
        inverseJoinColumns: [new ORM\JoinColumn(name: "series_id", referencedColumnName: "id")]
    )]
    private $series = array();

    #[ORM\ManyToMany(targetEntity: "Episode", inversedBy: "genre")]
    #[ORM\JoinTable(
        name: "user_episode",
        joinColumns: [new ORM\JoinColumn(name: "user_id", referencedColumnName: "id")],
        inverseJoinColumns: [new ORM\JoinColumn(name: "episode_id", referencedColumnName: "id")]
    )]
    private $episode = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->series = new \Doctrine\Common\Collections\ArrayCollection();
        $this->episode = new \Doctrine\Common\Collections\ArrayCollection();
    }

    //added (see TD4 Authentification)
    public function getUserIdentifier(): string { return $this->getEmail(); }
    public function getRoles(): array { return ['ROLE_USER']; }
    public function eraseCredentials() { }

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getRegisterDate(): ?\DateTimeInterface
    {
        return $this->registerDate;
    }

    public function setRegisterDate(?\DateTimeInterface $registerDate): static
    {
        $this->registerDate = $registerDate;

        return $this;
    }

    public function isAdmin(): ?bool
    {
        return $this->admin;
    }

    public function setAdmin(bool $admin): static
    {
        $this->admin = $admin;

        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): static
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return Collection<int, Series>
     */
    public function getSeries(): Collection
    {
        return $this->series;
    }

    public function addSeries(Series $series): static
    {
        if (!$this->series->contains($series)) {
            $this->series->add($series);
        }

        return $this;
    }

    public function removeSeries(Series $series): static
    {
        $this->series->removeElement($series);

        return $this;
    }

    /**
     * @return Collection<int, Episode>
     */
    public function getEpisode(): Collection
    {
        return $this->episode;
    }

    public function addEpisode(Episode $episode): static
    {
        if (!$this->episode->contains($episode)) {
            $this->episode->add($episode);
        }

        return $this;
    }

    public function removeEpisode(Episode $episode): static
    {
        $this->episode->removeElement($episode);

        return $this;
    }
}

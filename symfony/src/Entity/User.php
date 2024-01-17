<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use App\Validator\ContainsAlphanumeric;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
// under the assumption that the user must be unique
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

#[ORM\Table(
    name: 'user',
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'UNIQ_8D93D649E7927C74', columns: ['email']),
        new ORM\UniqueConstraint(name: 'IDX_8D93D649F92F3E70', columns: ['country_id']),
    ]
)]
#[ORM\Entity]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    #[ORM\Column(name: "name", type: "string", length: 128, nullable: false)]
    //#[Assert\NotBlank(message:'Empty name')]
    private $name;

    #[ORM\Column(name: 'email', type: 'string', length: 128, nullable: false)]

    /*
        #[Assert\NotBlank(message:'Empty email address')]
        #[Assert\Email(message: 'Invalid email address')]
    #[Assert\Length(min:5, minMessage: 'email address must be valid')]*/
    private $email;

    #[ORM\Column(name: 'password', type: 'string', length: 128, nullable: false)]

    // #[Assert\NotBlank(message:'Empty paaaassword')]
    #[Assert\Length(min: 6, minMessage: 'Password must be at least 6 characters long')]
    private $password;

    #[ORM\Column(name: 'register_date', type: 'datetime', nullable: true)]
    private $registerDate;

    #[ORM\Column(name: 'admin', type: 'boolean', nullable: false)]
    private $admin = '0';

    #[ORM\Column(name: 'fake', type: 'boolean', nullable: false)]
    private $fake = '0';

    #[ORM\ManyToOne(targetEntity: 'Country')]
    #[ORM\JoinColumn(name: 'country_id', referencedColumnName: 'id')]
    private $country;

    #[ORM\ManyToMany(targetEntity: 'Series', inversedBy: 'genre')]
    #[ORM\JoinTable(
        name: 'user_series',
        joinColumns: [new ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'series_id', referencedColumnName: 'id')]
    )]
    private $series = [];

    #[ORM\ManyToMany(targetEntity: 'Episode')]
    #[ORM\JoinTable(
        name: 'user_episode',
        joinColumns: [new ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'episode_id', referencedColumnName: 'id')]
    )]
    private $episode = [];


    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->series  = new ArrayCollection();
        $this->episode = new ArrayCollection();
    }//end __construct()


    // added (see TD4 Authentification)
    public function getUserIdentifier(): string
    {
        return $this->getEmail();
    }//end getUserIdentifier()


    public function getRoles(): array
    {
        if ($this->isAdmin()) {
            return ['ROLE_ADMIN'];
        }

        return ['ROLE_USER'];
    }//end getRoles()


    public function eraseCredentials()
    {
    }//end eraseCredentials()


    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addConstraint(new UniqueEntity([
            'fields' => 'email',
            'message' => 'This email was already used to register an account.',
        ]));
        $metadata->addConstraint(new UniqueEntity([
            'fields' => 'name',
            'message' => 'This name already exists.',
        ]));
        $metadata->addPropertyConstraint('name', new ContainsAlphanumeric(['mode' => 'loose']));

        $metadata->addPropertyConstraint('email', new Assert\Email(['message' => 'The email address is invalid']));
    }//end loadValidatorMetadata()


    public function getId(): ?int
    {
        return $this->id;
    }//end getId()


    public function getName(): ?string
    {
        return $this->name;
    }//end getName()


    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }//end setName()


    public function getEmail(): ?string
    {
        return $this->email;
    }//end getEmail()


    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }//end setEmail()


    public function getPassword(): ?string
    {
        return $this->password;
    }//end getPassword()


    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }//end setPassword()


    public function getRegisterDate()
    {
        return $this->registerDate;
    }//end getRegisterDate()


    public function setRegisterDate($registerDate): self
    {
        $this->registerDate = $registerDate;

        return $this;
    }//end setRegisterDate()


    public function isAdmin(): ?bool
    {
        return $this->admin;
    }//end isAdmin()


    public function setAdmin(bool $admin): self
    {
        $this->admin = $admin;

        return $this;
    }//end setAdmin()

    public function isFake(): ?bool
    {
        return $this->fake;
    }//end isFake()


    public function setFake(bool $fake): self
    {
        $this->fake = $fake;

        return $this;
    }//end setFake()


    public function getCountry(): ?Country
    {
        return $this->country;
    }//end getCountry()


    public function setCountry(?Country $country): self
    {
        $this->country = $country;

        return $this;
    }//end setCountry()


    /**
     * @return Collection<int, Series>
     */
    public function getSeries(): Collection
    {
        return $this->series;
    }//end getSeries()


    public function addSeries(Series $series): self
    {
        if (!$this->series->contains($series)) {
            $this->series->add($series);
        }

        return $this;
    }//end addSeries()


    public function removeSeries(Series $series): self
    {
        $this->series->removeElement($series);

        return $this;
    }//end removeSeries()


    /**
     * @return Collection<int, Episode>
     */
    public function getEpisode(): Collection
    {
        return $this->episode;
    }//end getEpisode()


    public function addEpisode(Episode $episode): self
    {
        if (!$this->episode->contains($episode)) {
            $this->episode->add($episode);
        }

        return $this;
    }//end addEpisode()


    public function removeEpisode(Episode $episode): self
    {
        $this->episode->removeElement($episode);

        return $this;
    }//end removeEpisode()
}//end class

<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(
    name: 'episode',
    indexes: [
        new ORM\Index(name: 'IDX_DDAA1CDA4EC001D1', columns: ['season_id']),
    ]
)]
#[ORM\Entity]
class Episode
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    #[ORM\Column(name: 'title', type: 'string', length: 128, nullable: false)]
    private $title;

    #[ORM\Column(name: 'date', type: 'date', nullable: true)]
    private $date;

    #[ORM\Column(name: 'imdb', type: 'string', length: 128, nullable: false)]
    private $imdb;

    #[ORM\Column(name: 'imdbrating', type: 'float', precision: 10, scale: 0, nullable: true)]
    private $imdbrating;

    #[ORM\Column(name: 'number', type: 'integer', nullable: false)]
    private $number;

    #[ORM\ManyToOne(targetEntity: 'Season')]
    #[ORM\JoinColumn(name: 'season_id', referencedColumnName: 'id')]
    private $season;

    #[ORM\ManyToMany(targetEntity: 'User', mappedBy: 'episode')]
    private $user = [];


    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->user = new ArrayCollection();
    }//end __construct()


    public function getId(): ?int
    {
        return $this->id;
    }//end getId()


    public function getTitle(): ?string
    {
        return $this->title;
    }//end getTitle()


    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }//end setTitle()


    public function getImdb(): ?string
    {
        return $this->imdb;
    }//end getImdb()


    public function setImdb(string $imdb): self
    {
        $this->imdb = $imdb;

        return $this;
    }//end setImdb()


    public function getImdbrating(): ?float
    {
        return $this->imdbrating;
    }//end getImdbrating()


    public function setImdbrating(?float $imdbrating): self
    {
        $this->imdbrating = $imdbrating;

        return $this;
    }//end setImdbrating()


    public function getNumber(): ?int
    {
        return $this->number;
    }//end getNumber()


    public function setNumber(int $number): self
    {
        $this->number = $number;

        return $this;
    }//end setNumber()


    public function getSeason(): ?Season
    {
        return $this->season;
    }//end getSeason()


    public function setSeason(?Season $season): self
    {
        $this->season = $season;

        return $this;
    }//end setSeason()


    /**
     * @return Collection<int, User>
     */
    public function getUser(): Collection
    {
        return $this->user;
    }//end getUser()


    public function addUser(User $user): self
    {
        if (!$this->user->contains($user)) {
            $this->user->add($user);
            $user->addEpisode($this);
        }

        return $this;
    }//end addUser()


    public function removeUser(User $user): self
    {
        if ($this->user->removeElement($user)) {
            $user->removeEpisode($this);
        }

        return $this;
    }//end removeUser()


    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }//end getDate()


    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }//end setDate()
}//end class

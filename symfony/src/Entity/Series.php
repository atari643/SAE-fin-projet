<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(
    name: 'series',
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'UNIQ_3A10012D85489131', columns: ['imdb']),
    ]
)]
#[ORM\Entity(repositoryClass: 'App\Repository\SeriesRepository')]
class Series
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    #[ORM\Column(name: 'title', type: 'string', length: 128, nullable: false)]
    private $title;

    #[ORM\Column(name: 'plot', type: 'text', length: 0, nullable: true)]
    private $plot;

    #[ORM\Column(name: 'imdb', type: 'string', length: 128, nullable: false)]
    private $imdb;

    #[ORM\Column(name: 'poster', type: 'blob', nullable: true)]
    private $poster;

    #[ORM\Column(name: 'director', type: 'string', length: 128, nullable: true)]
    private $director;

    #[ORM\Column(name: 'youtube_trailer', type: 'string', length: 128, nullable: true)]
    private $youtubeTrailer;

    #[ORM\Column(name: 'awards', type: 'text', length: 0, nullable: true)]
    private $awards;

    #[ORM\Column(name: 'year_start', type: 'integer', nullable: true)]
    private $yearStart;

    #[ORM\Column(name: 'year_end', type: 'integer', nullable: true)]
    private $yearEnd;

    #[ORM\ManyToMany(targetEntity: 'User', mappedBy: 'series')]
    private $user = [];

    #[ORM\ManyToMany(targetEntity: 'Genre', mappedBy: 'series')]
    private $genre = [];

    #[ORM\ManyToMany(targetEntity: 'Actor', mappedBy: 'series')]
    private $actor = [];

    #[ORM\ManyToMany(targetEntity: 'Country', mappedBy: 'series')]
    private $country = [];

    #[ORM\OneToMany(mappedBy: 'series', targetEntity: 'Season')]
    #[ORM\OrderBy(['number' => 'ASC'])]
    private Collection $seasons;


    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->user    = new ArrayCollection();
        $this->genre   = new ArrayCollection();
        $this->actor   = new ArrayCollection();
        $this->country = new ArrayCollection();
        $this->seasons = new ArrayCollection();
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


    public function getPlot(): ?string
    {
        return $this->plot;
    }//end getPlot()


    public function setPlot(?string $plot): self
    {
        $this->plot = $plot;

        return $this;
    }//end setPlot()


    public function getImdb(): ?string
    {
        return $this->imdb;
    }//end getImdb()


    public function setImdb(string $imdb): self
    {
        $this->imdb = $imdb;

        return $this;
    }//end setImdb()


    public function getDirector(): ?string
    {
        return $this->director;
    }//end getDirector()


    public function setDirector(?string $director): self
    {
        $this->director = $director;

        return $this;
    }//end setDirector()


    public function getYoutubeTrailer(): ?string
    {
        return str_replace('www.youtube.com/watch?v=', 'www.youtube.com/embed/', $this->youtubeTrailer);
    }//end getYoutubeTrailer()


    public function setYoutubeTrailer(?string $youtubeTrailer): self
    {
        $this->youtubeTrailer = $youtubeTrailer;

        return $this;
    }//end setYoutubeTrailer()


    public function getAwards(): ?string
    {
        return $this->awards;
    }//end getAwards()


    public function setAwards(?string $awards): self
    {
        $this->awards = $awards;

        return $this;
    }//end setAwards()


    public function getYearStart(): ?int
    {
        return $this->yearStart;
    }//end getYearStart()


    public function setYearStart(?int $yearStart): self
    {
        $this->yearStart = $yearStart;

        return $this;
    }//end setYearStart()


    public function getYearEnd(): ?int
    {
        return $this->yearEnd;
    }//end getYearEnd()


    public function setYearEnd(?int $yearEnd): self
    {
        $this->yearEnd = $yearEnd;

        return $this;
    }//end setYearEnd()


    public function setPosterFromUrl(string $url): static
    {
        $this->poster = file_get_contents($url);

        return $this;
    }//end setPosterFromUrl()


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
            $user->addSeries($this);
        }

        return $this;
    }//end addUser()


    public function removeUser(User $user): self
    {
        if ($this->user->removeElement($user)) {
            $user->removeSeries($this);
        }

        return $this;
    }//end removeUser()


    /**
     * @return Collection<int, Genre>
     */
    public function getGenre(): Collection
    {
        return $this->genre;
    }//end getGenre()


    public function addGenre(Genre $genre): self
    {
        if (!$this->genre->contains($genre)) {
            $this->genre->add($genre);
            $genre->addSeries($this);
        }

        return $this;
    }//end addGenre()


    public function removeGenre(Genre $genre): self
    {
        if ($this->genre->removeElement($genre)) {
            $genre->removeSeries($this);
        }

        return $this;
    }//end removeGenre()


    /**
     * @return Collection<int, Actor>
     */
    public function getActor(): Collection
    {
        return $this->actor;
    }//end getActor()


    public function addActor(Actor $actor): self
    {
        if (!$this->actor->contains($actor)) {
            $this->actor->add($actor);
            $actor->addSeries($this);
        }

        return $this;
    }//end addActor()


    public function removeActor(Actor $actor): self
    {
        if ($this->actor->removeElement($actor)) {
            $actor->removeSeries($this);
        }

        return $this;
    }//end removeActor()


    /**
     * @return Collection<int, Country>
     */
    public function getCountry(): Collection
    {
        return $this->country;
    }//end getCountry()


    public function addCountry(Country $country): self
    {
        if (!$this->country->contains($country)) {
            $this->country->add($country);
            $country->addSeries($this);
        }

        return $this;
    }//end addCountry()


    public function removeCountry(Country $country): self
    {
        if ($this->country->removeElement($country)) {
            $country->removeSeries($this);
        }

        return $this;
    }//end removeCountry()


    public function getPoster()
    {
        return $this->poster;
    }//end getPoster()


    public function setPoster($poster): static
    {
        $this->poster = $poster;

        return $this;
    }//end setPoster()


    /**
     * @return Collection<int, Season>
     */
    public function getSeasons(): Collection
    {
        return $this->seasons;
    }//end getSeasons()


    public function addSeason(Season $season): static
    {
        if (!$this->seasons->contains($season)) {
            $this->seasons->add($season);
            $season->setSeries($this);
        }

        return $this;
    }//end addSeason()


    public function removeSeason(Season $season): static
    {
        if ($this->seasons->removeElement($season)) {
            // set the owning side to null (unless already changed)
            if ($season->getSeries() === $this) {
                $season->setSeries(null);
            }
        }

        return $this;
    }//end removeSeason()
}//end class

<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(
    name: 'season',
    indexes: [
        new ORM\Index(name: 'IDX_F0E45BA95278319C', columns: ['series_id']),
    ]
)]
#[ORM\Entity]
class Season
{

    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    #[ORM\Column(name: 'number', type: 'integer', nullable: false)]
    private $number;

    #[ORM\ManyToOne(targetEntity: 'Series')]
    #[ORM\JoinColumn(name: 'series_id', referencedColumnName: 'id')]
    private $series;

    #[ORM\OneToMany(mappedBy: 'season', targetEntity: 'Episode')]
    #[ORM\OrderBy(value: ['number' => 'ASC'])]
    private Collection $episodes;


    public function __construct()
    {
        $this->episodes = new ArrayCollection();

    }//end __construct()


    public function getEpisodes(): Collection
    {
        return $this->episodes;

    }//end getEpisodes()


    public function getId(): ?int
    {
        return $this->id;

    }//end getId()


    public function getNumber(): ?int
    {
        return $this->number;

    }//end getNumber()


    public function setNumber(int $number): self
    {
        $this->number = $number;

        return $this;

    }//end setNumber()


    public function getSeries(): ?Series
    {
        return $this->series;

    }//end getSeries()


    public function setSeries(?Series $series): self
    {
        $this->series = $series;

        return $this;

    }//end setSeries()


    public function addEpisode(Episode $episode): static
    {
        if (!$this->episodes->contains($episode)) {
            $this->episodes->add($episode);
            $episode->setSeason($this);
        }

        return $this;

    }//end addEpisode()


    public function removeEpisode(Episode $episode): static
    {
        if ($this->episodes->removeElement($episode)) {
            // set the owning side to null (unless already changed)
            if ($episode->getSeason() === $this) {
                $episode->setSeason(null);
            }
        }

        return $this;

    }//end removeEpisode()


}//end class

<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'country')]
#[ORM\Entity]
class Country
{

    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    #[ORM\Column(name: 'name', type: 'string', length: 128, nullable: false)]
    private $name;

    #[ORM\ManyToMany(targetEntity: 'Series', inversedBy: 'country')]
    #[ORM\JoinTable(
        name: 'country_series',
        joinColumns: [new ORM\JoinColumn(name: 'country_id', referencedColumnName: 'id')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'series_id', referencedColumnName: 'id')]
    )]
    private $series = [];


    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->series = new ArrayCollection();

    }//end __construct()


    public function getId(): ?int
    {
        return $this->id;

    }//end getId()


    public function __toString()
    {
        return $this->name;

    }//end __toString()


    public function getName(): ?string
    {
        return $this->name;

    }//end getName()


    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;

    }//end setName()


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


}//end class

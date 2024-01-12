<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(
    name: 'external_rating',
    indexes: [
        new ORM\Index(name: 'IDX_AC0AB9CB953C1C61', columns: ['source_id']),
        new ORM\Index(name: 'IDX_AC0AB9CB5278319C', columns: ['series_id']),
    ]
)]
#[ORM\Entity]
class ExternalRating
{

    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    #[ORM\Column(name: 'value', type: 'string', length: 255, nullable: false)]
    private $value;

    #[ORM\Column(name: 'votes', type: 'integer', nullable: true)]
    private $votes;

    #[ORM\ManyToOne(targetEntity: 'ExternalRatingSource')]
    #[ORM\JoinColumn(name: 'source_id', referencedColumnName: 'id')]
    private $source;

    #[ORM\ManyToOne(targetEntity: 'Series')]
    #[ORM\JoinColumn(name: 'series_id', referencedColumnName: 'id')]
    private $series;


    public function getId(): ?int
    {
        return $this->id;

    }//end getId()


    public function getValue(): ?string
    {
        return $this->value;

    }//end getValue()


    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;

    }//end setValue()


    public function getVotes(): ?int
    {
        return $this->votes;

    }//end getVotes()


    public function setVotes(?int $votes): self
    {
        $this->votes = $votes;

        return $this;

    }//end setVotes()


    public function getSource(): ?ExternalRatingSource
    {
        return $this->source;

    }//end getSource()


    public function setSource(?ExternalRatingSource $source): self
    {
        $this->source = $source;

        return $this;

    }//end setSource()


    public function getSeries(): ?Series
    {
        return $this->series;

    }//end getSeries()


    public function setSeries(?Series $series): self
    {
        $this->series = $series;

        return $this;

    }//end setSeries()


}//end class

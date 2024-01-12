<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'external_rating_source')]
#[ORM\Entity]
class ExternalRatingSource
{

    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: false)]
    private $name;


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


}//end class

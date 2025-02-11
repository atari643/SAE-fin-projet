<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(
    name: 'rating',
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'unique_rating', columns: ['series_id', 'users_id']),
    ],
    indexes: [
        new ORM\Index(name: 'IDX_D88926225278319C', columns: ['series_id']),
        new ORM\Index(name: 'IDX_D8892622A76ED395', columns: ['user_id']),
    ]
)]
#[ORM\Entity(repositoryClass: 'App\Repository\RatingRepository')]

class Rating
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    #[ORM\Column(name: 'value', type: 'integer', nullable: false)]
    private $value;

    #[ORM\Column(name: 'comment', type: 'text', length: 0, nullable: false)]
    private $comment;

    #[ORM\Column(name: 'date', type: 'datetime', nullable: false)]
    private $date;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private $user;

    #[ORM\ManyToOne(targetEntity: 'Series')]
    #[ORM\JoinColumn(name: 'series_id', referencedColumnName: 'id')]
    private $series;


    public function getId(): ?int
    {
        return $this->id;
    }//end getId()


    public function getValue(): ?int
    {
        return $this->value;
    }//end getValue()


    public function setValue(int $value): self
    {
        $this->value = $value;

        return $this;
    }//end setValue()


    public function getComment(): ?string
    {
        return $this->comment;
    }//end getComment()


    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }//end setComment()


    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }//end getDate()


    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }//end setDate()


    public function getUser(): ?User
    {
        return $this->user;
    }//end getUser()


    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }//end setUser()


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

<?php

namespace App\Entity;

use App\Repository\BaseMinionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * This class represents base Card entity in game. Table holds data about all available Hero Powers in current build
 * 
 * @ORM\Entity(repositoryClass=BaseMinionRepository::class)
 */
class BaseMinion 
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    public function getId(): ?int
    {
        return $this->id;
    }

        /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $cardId; 

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name; 


    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $type; 

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $image = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $dbfId; 

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tribe = 'NEUTRAL';

    /**
     * @ORM\OneToMany(targetEntity=Card::class, mappedBy="baseCard")
     */
    private $cards;

    public function __construct()
    {
        $this->cards = new ArrayCollection();
    } // 

    
    public function getCardId(): ?string
    {
        return $this->cardId;
    }

    public function setCardId(?string $cardId): self
    {
        $this->cardId = $cardId;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }



    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getImage(): ?bool
    {
        return $this->image;
    }

    public function setImage(?bool $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getDbfId(): ?int
    {
        return $this->dbfId;
    }

    public function setDbfId(?int $dbfId): self
    {
        $this->dbfId = $dbfId;

        return $this;
    }

    public function getTribe(): ?string
    {
        return $this->tribe;
    }

    public function setTribe(?string $tribe): self
    {
        $this->tribe = $tribe;

        return $this;
    }

    /**
     * @return Collection|Card[]
     */
    public function getCards(): Collection
    {
        return $this->cards;
    }

    public function addCard(Card $card): self
    {
        if (!$this->cards->contains($card)) {
            $this->cards[] = $card;
            $card->setBaseCard($this);
        }

        return $this;
    }

    public function removeCard(Card $card): self
    {
        if ($this->cards->removeElement($card)) {
            // set the owning side to null (unless already changed)
            if ($card->getBaseCard() === $this) {
                $card->setBaseCard(null);
            }
        }

        return $this;
    }

}

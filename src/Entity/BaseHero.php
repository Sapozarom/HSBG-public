<?php

namespace App\Entity;

use App\Repository\BaseHeroRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * This class represents base Hero entity in game. Table holds data about all available heroes in current build
 * 
 * @ORM\Entity(repositoryClass=BaseHeroRepository::class)
 */
class BaseHero
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $cardId;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $dbfId;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $image = null;

    /**
     * @ORM\OneToMany(targetEntity=Player::class, mappedBy="hero")
     */
    private $players;

    /**
     * @ORM\OneToMany(targetEntity=BaseHeroPower::class, mappedBy="baseHero")
     */
    private $baseHeroPower;

    public function __construct()
    {
        $this->players = new ArrayCollection();
        $this->baseHeroPower = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCardId(): ?string
    {
        return $this->cardId;
    }

    public function setCardId(string $cardId): self
    {
        $this->cardId = $cardId;

        return $this;
    }

    public function getDbfId(): ?int
    {
        return $this->dbfId;
    }

    public function setDbfId(int $dbfId): self
    {
        $this->dbfId = $dbfId;

        return $this;
    }


    public function getImage(): ?bool
    {
        return $this->image;
    }

    public function setImage(bool $image): self
    {
        $this->image = $image;

        return $this;
    }


    public function __toString()
    {
        return $this->cardId;
    }

    /**
     * @return Collection|Player[]
     */
    public function getPlayers(): Collection
    {
        return $this->players;
    }

    public function addPlayer(Player $player): self
    {
        if (!$this->players->contains($player)) {
            $this->players[] = $player;
            $player->setHero($this);
        }

        return $this;
    }

    public function removePlayer(Player $player): self
    {
        if ($this->players->removeElement($player)) {
            // set the owning side to null (unless already changed)
            if ($player->getHero() === $this) {
                $player->setHero(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|BaseHeroPower[]
     */
    public function getBaseHeroPower(): Collection
    {
        return $this->baseHeroPower;
    }

    public function addBaseHeroPower(BaseHeroPower $baseHeroPower): self
    {
        if (!$this->baseHeroPower->contains($baseHeroPower)) {
            $this->baseHeroPower[] = $baseHeroPower;
            $baseHeroPower->setBaseHero($this);
        }

        return $this;
    }

    public function removeBaseHeroPower(BaseHeroPower $baseHeroPower): self
    {
        if ($this->baseHeroPower->removeElement($baseHeroPower)) {
            // set the owning side to null (unless already changed)
            if ($baseHeroPower->getBaseHero() === $this) {
                $baseHeroPower->setBaseHero(null);
            }
        }

        return $this;
    }
}

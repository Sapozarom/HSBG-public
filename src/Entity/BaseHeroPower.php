<?php

namespace App\Entity;

use App\Repository\BaseHeroPowerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * This class represents base Hero Power entity in game. Table holds data about all available Hero Powers in current build
 * 
 * @ORM\Entity(repositoryClass=BaseHeroPowerRepository::class)
 */
class BaseHeroPower
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
     * @ORM\OneToMany(targetEntity=Player::class, mappedBy="heroPower")
     */
    private $players;

    /**
     * @ORM\ManyToOne(targetEntity=BaseHero::class, inversedBy="baseHeroPower")
     */
    private $baseHero;


    public function __construct()
    {
        $this->players = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCardId(): ?string
    {
        return $this->cardId;
    }

    public function setCardId(?string $cardId): self
    {
        $this->cardId = $cardId;

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
            $player->setHeroPower($this);
        }

        return $this;
    }

    public function removePlayer(Player $player): self
    {
        if ($this->players->removeElement($player)) {
            // set the owning side to null (unless already changed)
            if ($player->getHeroPower() === $this) {
                $player->setHeroPower(null);
            }
        }

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

    public function getBaseHero(): ?BaseHero
    {
        return $this->baseHero;
    }

    public function setBaseHero(?BaseHero $baseHero): self
    {
        $this->baseHero = $baseHero;

        return $this;
    }
}

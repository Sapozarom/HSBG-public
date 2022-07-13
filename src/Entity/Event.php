<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Default class that is used to represent every event in game apart from COMBAT events that holds different set of data
 * As event we can understand player moves as well as game events and some opponents moves that are displayed by client
 * 
 * @ORM\Entity(repositoryClass=EventRepository::class)
 */
class Event
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $timestamp;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $text;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $playerHealth;

    /**
     * @ORM\OneToOne(targetEntity=Board::class, cascade={"persist", "remove"})
     */
    private $playerBoard;

    /**
     * @ORM\OneToOne(targetEntity=Board::class, cascade={"persist", "remove"})
     */
    private $innkeeperBoard;

    /**
     * @ORM\OneToOne(targetEntity=Board::class, cascade={"persist", "remove"})
     */
    private $hand;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $gold;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $tavernTier;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $upgradeCost;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $rerollCost;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $powerUsed;

    /**
     * @ORM\ManyToOne(targetEntity=Round::class, inversedBy="events")
     */
    private $round;

    /**
     * @ORM\OneToOne(targetEntity=Card::class, cascade={"persist", "remove"})
     */
    private $target;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTimestamp(): ?String
    {
        return $this->timestamp;
    }

    public function setTimestamp(?String $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getPlayerHealth(): ?int
    {
        return $this->playerHealth;
    }

    public function setPlayerHealth(?int $playerHealth): self
    {
        $this->playerHealth = $playerHealth;

        return $this;
    }

    public function getPlayerBoard(): ?Board
    {
        return $this->playerBoard;
    }

    public function setPlayerBoard(?Board $playerBoard): self
    {
        $this->playerBoard = $playerBoard;

        return $this;
    }

    public function getInnkeeperBoard(): ?Board
    {
        return $this->innkeeperBoard;
    }

    public function setInnkeeperBoard(?Board $innkeeperBoard): self
    {
        $this->innkeeperBoard = $innkeeperBoard;

        return $this;
    }

    public function getHand(): ?Board
    {
        return $this->hand;
    }

    public function setHand(?Board $hand): self
    {
        $this->hand = $hand;

        return $this;
    }

    public function getGold(): ?int
    {
        return $this->gold;
    }

    public function setGold(?int $gold): self
    {
        $this->gold = $gold;

        return $this;
    }

    public function getTavernTier(): ?int
    {
        return $this->tavernTier;
    }

    public function setTavernTier(?int $tavernTier): self
    {
        $this->tavernTier = $tavernTier;

        return $this;
    }

    public function getUpgradeCost(): ?int
    {
        return $this->upgradeCost;
    }

    public function setUpgradeCost(?int $upgradeCost): self
    {
        $this->upgradeCost = $upgradeCost;

        return $this;
    }

    public function getRerollCost(): ?int
    {
        return $this->rerollCost;
    }

    public function setRerollCost(?int $rerollCost): self
    {
        $this->rerollCost = $rerollCost;

        return $this;
    }

    public function getPowerUsed(): ?bool
    {
        return $this->powerUsed;
    }

    public function setPowerUsed(?bool $powerUsed): self
    {
        $this->powerUsed = $powerUsed;

        return $this;
    }

    public function getRound(): ?Round
    {
        return $this->round;
    }

    public function setRound(?Round $round): self
    {
        $this->round = $round;

        return $this;
    }

    public function getTarget(): ?Card
    {
        return $this->target;
    }

    public function setTarget(?Card $target): self
    {
        $this->target = $target;

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Repository\CardRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * This class is implemented to represent every card in game.
 * 
 * @ORM\Entity(repositoryClass=CardRepository::class)
 */
class Card
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
    private $cardId; //

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name; //

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $position;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $type; //

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $health;//

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $attack;//

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $dbfId; // 

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tribe = 'NEUTRAL'; // 

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $trippleCheck; // 

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $techLvl; //

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $golden; //

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $reborn; //

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $showTrigger; //

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $poisonous; // 

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $legendary; //

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $taunt; //

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $frozen; //

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $divShield; //

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $frenzy;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $deathrattle;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $parseKey;

    /**
     * @ORM\ManyToOne(targetEntity=BaseMinion::class, inversedBy="cards")
     */
    private $baseCard;

    /**
     * @ORM\ManyToOne(targetEntity=Board::class, inversedBy="cards")
     */
    private $board;


    public function getId(): ?int
    {
        return $this->id;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position;

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

    public function getHealth(): ?int
    {
        return $this->health;
    }

    public function setHealth(?int $health): self
    {
        $this->health = $health;

        return $this;
    }

    public function getAttack(): ?int
    {
        return $this->attack;
    }

    public function setAttack(?int $attack): self
    {
        $this->attack = $attack;

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

    public function getTrippleCheck(): ?bool
    {
        return $this->trippleCheck;
    }

    public function setTrippleCheck(?bool $trippleCheck): self
    {
        $this->trippleCheck = $trippleCheck;

        return $this;
    }

    public function getTechLvl(): ?int
    {
        return $this->techLvl;
    }

    public function setTechLvl(?int $techLvl): self
    {
        $this->techLvl = $techLvl;

        return $this;
    }

    public function getGolden(): ?bool
    {
        return $this->golden;
    }

    public function setGolden(?bool $golden): self
    {
        $this->golden = $golden;

        return $this;
    }

    public function getReborn(): ?bool
    {
        return $this->reborn;
    }

    public function setReborn(?bool $reborn): self
    {
        $this->reborn = $reborn;

        return $this;
    }

    public function getShowTrigger(): ?bool
    {
        return $this->showTrigger;
    }

    public function setShowTrigger(?bool $showTrigger): self
    {
        $this->showTrigger = $showTrigger;

        return $this;
    }

    public function getPoisonous(): ?bool
    {
        return $this->poisonous;
    }

    public function setPoisonous(?bool $poisonous): self
    {
        $this->poisonous = $poisonous;

        return $this;
    }

    public function getLegendary(): ?bool
    {
        return $this->legendary;
    }

    public function setLegendary(?bool $legendary): self
    {
        $this->legendary = $legendary;

        return $this;
    }

    public function getTaunt(): ?bool
    {
        return $this->taunt;
    }

    public function setTaunt(?bool $taunt): self
    {
        $this->taunt = $taunt;

        return $this;
    }

    public function getFrozen(): ?bool
    {
        return $this->frozen;
    }

    public function setFrozen(?bool $frozen): self
    {
        $this->frozen = $frozen;

        return $this;
    }

    public function getDivShield(): ?bool
    {
        return $this->divShield;
    }

    public function setDivShield(?bool $divShield): self
    {
        $this->divShield = $divShield;

        return $this;
    }

    public function getFrenzy(): ?bool
    {
        return $this->frenzy;
    }

    public function setFrenzy(?bool $frenzy): self
    {
        $this->frenzy = $frenzy;

        return $this;
    }

    public function getDeathrattle(): ?bool
    {
        return $this->deathrattle;
    }

    public function setDeathrattle(?bool $deathrattle): self
    {
        $this->deathrattle = $deathrattle;

        return $this;
    }

    public function getParseKey(): ?int
    {
        return $this->parseKey;
    }

    public function setParseKey(?int $parseKey): self
    {
        $this->parseKey = $parseKey;

        return $this;
    }

    public function getBaseCard(): ?BaseMinion
    {
        return $this->baseCard;
    }

    public function setBaseCard(?BaseMinion $baseCard): self
    {
        $this->baseCard = $baseCard;

        return $this;
    }

    public function getBoard(): ?Board
    {
        return $this->board;
    }

    public function setBoard(?Board $board): self
    {
        $this->board = $board;

        return $this;
    }
}

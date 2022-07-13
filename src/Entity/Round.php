<?php

namespace App\Entity;

use App\Repository\RoundRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Default class that holds data about every played round
 * 
 * @ORM\Entity(repositoryClass=RoundRepository::class)
 */
class Round
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $roundNumber;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $nextOppId;

    /**
     * @ORM\ManyToOne(targetEntity=Game::class, inversedBy="rounds")
     */
    private $game;

    /**
     * @ORM\OneToMany(targetEntity=Event::class, mappedBy="round", fetch="EAGER")
     */
    private $events;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $leaderboard = [];

    /**
     * @ORM\OneToOne(targetEntity=Combat::class, mappedBy="round", cascade={"persist", "remove"}, fetch="EAGER")
     */
    private $combat;

    private $lastRound = false;

    public function __construct()
    {
        $this->events = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoundNumber(): ?int
    {
        return $this->roundNumber;
    }

    public function setRoundNumber(int $roundNumber): self
    {
        $this->roundNumber = $roundNumber;

        return $this;
    }

    public function getNextOppId(): ?int
    {
        return $this->nextOppId;
    }

    public function setNextOppId(?int $nextOppId): self
    {
        $this->nextOppId = $nextOppId;

        return $this;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): self
    {
        $this->game = $game;

        return $this;
    }

    /**
     * @return Collection|event[]
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(event $event): self
    {
        if (!$this->events->contains($event)) {
            $this->events[] = $event;
            $event->setRound($this);
        }

        return $this;
    }

    public function removeEvent(event $event): self
    {
        if ($this->events->removeElement($event)) {
            // set the owning side to null (unless already changed)
            if ($event->getRound() === $this) {
                $event->setRound(null);
            }
        }

        return $this;
    }

    public function getLeaderboard(): ?array
    {
        return $this->leaderboard;
    }

    public function setLeaderboard(?array $leaderboard): self
    {
        $this->leaderboard = $leaderboard;

        return $this;
    }

    public function getCombat(): ?Combat
    {
        return $this->combat;
    }

    public function setCombat(?Combat $combat): self
    {
        // unset the owning side of the relation if necessary
        if ($combat === null && $this->combat !== null) {
            $this->combat->setRound(null);
        }

        // set the owning side of the relation if necessary
        if ($combat !== null && $combat->getRound() !== $this) {
            $combat->setRound($this);
        }

        $this->combat = $combat;

        return $this;
    }

    public function setLastRound($bool)
    {
        $this->lastRound = $bool;
    }

    public function getLastRound()
    {
        return $this->lastRound;
    }
}
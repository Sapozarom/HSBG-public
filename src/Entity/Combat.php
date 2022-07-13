<?php

namespace App\Entity;

use App\Repository\CombatRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * This class handles all data about combat event
 * 
 * @ORM\Entity(repositoryClass=CombatRepository::class)
 */
class Combat
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=Round::class, inversedBy="combat", cascade={"persist", "remove"})
     */
    private $round;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $playerHealth;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $oppHealth;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $oppPlayer;

    /**
     * @ORM\OneToOne(targetEntity=Board::class, cascade={"persist", "remove"})
     */
    private $playerBoard;

    /**
     * @ORM\OneToOne(targetEntity=Board::class, cascade={"persist", "remove"})
     */
    private $oppBoard;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $winner;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $damage;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $timestamp;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPlayerHealth(): ?int
    {
        return $this->playerHealth;
    }

    public function setPlayerHealth(?int $playerHealth): self
    {
        $this->playerHealth = $playerHealth;

        return $this;
    }

    public function getOppHealth(): ?int
    {
        return $this->oppHealth;
    }

    public function setOppHealth(?int $oppHealth): self
    {
        $this->oppHealth = $oppHealth;

        return $this;
    }

    public function getOppPlayer(): ?int
    {
        return $this->oppPlayer;
    }

    public function setOppPlayer(?int $oppPlayer): self
    {
        $this->oppPlayer = $oppPlayer;

        return $this;
    }

    public function getPlayerBoard(): ?board
    {
        return $this->playerBoard;
    }

    public function setPlayerBoard(?board $playerBoard): self
    {
        $this->playerBoard = $playerBoard;

        return $this;
    }

    public function getOppBoard(): ?board
    {
        return $this->oppBoard;
    }

    public function setOppBoard(?board $oppBoard): self
    {
        $this->oppBoard = $oppBoard;

        return $this;
    }

    public function getWinner(): ?int
    {
        return $this->winner;
    }

    public function setWinner(?int $winner): self
    {
        $this->winner = $winner;

        return $this;
    }

    public function getDamage(): ?int
    {
        return $this->damage;
    }

    public function setDamage(?int $damage): self
    {
        $this->damage = $damage;

        return $this;
    }

    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(?\DateTimeInterface $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }
}

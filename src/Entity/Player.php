<?php

namespace App\Entity;

use App\Repository\PlayerRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class used to represent players in game
 * 
 * @ORM\Entity(repositoryClass=PlayerRepository::class)
 */
class Player
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
     * @ORM\Column(type="integer")
     */
    private $PlayerId;

    /**
     * @ORM\ManyToOne(targetEntity=Game::class, inversedBy="players")
     */
    private $game;

    /**
     * @ORM\ManyToOne(targetEntity=BaseHero::class, inversedBy="players")
     */
    private $hero;

    /**
     * @ORM\ManyToOne(targetEntity=BaseHeroPower::class, inversedBy="players")
     */
    private $heroPower;


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

    public function getPlayerId(): ?int
    {
        return $this->PlayerId;
    }

    public function setPlayerId(int $PlayerId): self
    {
        $this->PlayerId = $PlayerId;

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


    public function getHero(): ?BaseHero
    {
        return $this->hero;
    }

    public function setHero(?BaseHero $hero): self
    {
        $this->hero = $hero;

        return $this;
    }

    public function getHeroPower(): ?BaseHeroPower
    {
        return $this->heroPower;
    }

    public function setHeroPower(?BaseHeroPower $heroPower): self
    {
        $this->heroPower = $heroPower;

        return $this;
    }
}

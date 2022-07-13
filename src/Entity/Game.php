<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Player;

/**
 * Default class that holds all information about single game
 * 
 * @ORM\Entity(repositoryClass=GameRepository::class)
 */
class Game
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;


    /**
     * @ORM\OneToMany(targetEntity=Player::class, mappedBy="game", fetch="EAGER")
     */
    private $players;

    /**
     * @ORM\OneToOne(targetEntity=Player::class, cascade={"persist", "remove"})
     */
    private $owner;

    /**
     * @ORM\OneToMany(targetEntity=Round::class, mappedBy="game", fetch="EAGER")
     */
    private $rounds;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $tribes = [];

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="games")
     */
    private $user;

    /**
     * @ORM\OneToOne(targetEntity=LogFile::class, mappedBy="game", cascade={"persist", "remove"})
     */
    private $logFile;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $placement;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $composition;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $public;

    public function __construct()
    {
        $this->players = new ArrayCollection();
        $this->rounds = new ArrayCollection();
        $this->public = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
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
            $player->setGame($this);
        }

        return $this;
    }

    public function removePlayer(Player $player): self
    {
        if ($this->Players->removeElement($player)) {
            // set the owning side to null (unless already changed)
            if ($player->getGame() === $this) {
                $player->setGame(null);
            }
        }

        return $this;
    }

    public function getOwner(): ?Player
    {
        return $this->owner;
    }

    public function setOwner(?Player $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection|round[]
     */
    public function getRounds(): Collection
    {
        return $this->rounds;
    }

    public function addRound(round $round): self
    {
        if (!$this->rounds->contains($round)) {
            $this->rounds[] = $round;
            $round->setGame($this);
        }

        return $this;
    }

    public function removeRound(round $round): self
    {
        if ($this->rounds->removeElement($round)) {
            // set the owning side to null (unless already changed)
            if ($round->getGame() === $this) {
                $round->setGame(null);
            }
        }

        return $this;
    }

    public function getTribes(): ?array
    {
        return $this->tribes;
    }

    public function setTribes(?array $tribes): self
    {
        $this->tribes = $tribes;

        return $this;
    }

    public function countRounds()
    {
        $this->countedRounds = count($this->rounds);
    }

    public function getLogFile(): ?LogFile
    {
        return $this->logFile;
    }

    public function setLogFile(?LogFile $logFile): self
    {
        // unset the owning side of the relation if necessary
        if ($logFile === null && $this->logFile !== null) {
            $this->logFile->setGame(null);
        }

        // set the owning side of the relation if necessary
        if ($logFile !== null && $logFile->getGame() !== $this) {
            $logFile->setGame($this);
        }

        $this->logFile = $logFile;

        return $this;
    }

    public function getPlayerById($playerId)
    {
        foreach ($this->players as $player) {
            
            if ($player->getPlayerId() == $playerId) {
                return $player;
            }
        }
    }

    public function getPlacement(): ?int
    {
        return $this->placement;
    }

    public function setPlacement(?int $placement): self
    {
        $this->placement = $placement;

        return $this;
    }

    public function getComposition(): ?string
    {
        return $this->composition;
    }

    public function setComposition(?string $composition): self
    {
        $this->composition = $composition;

        return $this;
    }

    public function getPublic(): ?bool
    {
        return $this->public;
    }

    public function setPublic(?bool $public): self
    {
        $this->public = $public;

        return $this;
    }
}

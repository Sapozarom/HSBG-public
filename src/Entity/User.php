<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * This class is used to hold data about users of web page
 * 
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @UniqueEntity(fields={"email"}, message="There is already an account with this email")
 */
class User implements UserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\OneToMany(targetEntity=Game::class, mappedBy="user")
     */
    private $games;

    /**
     * @ORM\OneToMany(targetEntity=LogFile::class, mappedBy="user")
     */
    private $logFiles;

    /**
     * @ORM\OneToMany(targetEntity=SingleGameFile::class, mappedBy="user")
     */
    private $singleGameFiles;

    public function __construct()
    {
        $this->games = new ArrayCollection();
        $this->logFiles = new ArrayCollection();
        $this->singleGameFiles = new ArrayCollection();
        $this->roles[] = 'ROLE_USER';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection|Game[]
     */
    public function getGames(): Collection
    {
        return $this->games;
    }

    public function addGame(Game $game): self
    {
        if (!$this->games->contains($game)) {
            $this->games[] = $game;
            $game->setUserr($this);
        }

        return $this;
    }

    public function removeGame(Game $game): self
    {
        if ($this->games->removeElement($game)) {
            // set the owning side to null (unless already changed)
            if ($game->getUserr() === $this) {
                $game->setUserr(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|LogFile[]
     */
    public function getLogFiles(): Collection
    {
        return $this->logFiles;
    }

    public function addLogFile(LogFile $logFile): self
    {
        if (!$this->logFiles->contains($logFile)) {
            $this->logFiles[] = $logFile;
            $logFile->setUser($this);
        }

        return $this;
    }

    public function removeLogFile(LogFile $logFile): self
    {
        if ($this->logFiles->removeElement($logFile)) {
            // set the owning side to null (unless already changed)
            if ($logFile->getUser() === $this) {
                $logFile->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|SingleGameFile[]
     */
    public function getSingleGameFiles(): Collection
    {
        return $this->singleGameFiles;
    }

    public function addSingleGameFile(SingleGameFile $singleGameFile): self
    {
        if (!$this->singleGameFiles->contains($singleGameFile)) {
            $this->singleGameFiles[] = $singleGameFile;
            $singleGameFile->setUser($this);
        }

        return $this;
    }

    public function removeSingleGameFile(SingleGameFile $singleGameFile): self
    {
        if ($this->singleGameFiles->removeElement($singleGameFile)) {
            // set the owning side to null (unless already changed)
            if ($singleGameFile->getUser() === $this) {
                $singleGameFile->setUser(null);
            }
        }

        return $this;
    }
}

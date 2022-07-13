<?php

namespace App\Entity;

use App\Repository\LogFileRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Class that represents uploaded log files in DB
 * 
 * @ORM\Entity(repositoryClass=LogFileRepository::class)
 * @Vich\Uploadable
 */
class LogFile
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="logFiles")
     */
    private $user;

    /**
     * @ORM\Column(type="boolean")
     */
    private $divided = false;

    /**
     * @ORM\OneToOne(targetEntity=Game::class, inversedBy="logFile", cascade={"persist", "remove"})
     */
    private $game;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     * 
     * @Vich\UploadableField(mapping="game_log_file", fileNameProperty="name")
     * 
     * @var File|null
     */
    private $logFile;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $updatedAt;

        /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile|null $logFile
     */
    public function setLogFile(?File $logFile = null): void
    {
        $this->logFile = $logFile;

        if (null !== $logFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getLogFile(): ?File
    {
        return $this->logFile;
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

    public function getUser(): ?user
    {
        return $this->user;
    }

    public function setUser(?user $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getDivided(): ?bool
    {
        return $this->divided;
    }

    public function setDivided(bool $divided): self
    {
        $this->divided = $divided;

        return $this;
    }

    public function getGame(): ?game
    {
        return $this->game;
    }

    public function setGame(?game $game): self
    {
        $this->game = $game;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}

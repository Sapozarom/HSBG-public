<?php

namespace App\Entity;

use App\Repository\SingleGameFileRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SingleGameFileRepository::class)
 */
class SingleGameFile
{
    /**
     * Class that represent single game files obtained after splitting uploaded log file
     * 
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $filename;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="singleGameFiles")
     */
    private $user;

    /**
     * @ORM\Column(type="boolean")
     */
    private $type = 0;

    /**
     * @ORM\Column(type="boolean")
     */
    private $parsed = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

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

    public function getType(): ?bool
    {
        return $this->type;
    }

    public function setType(bool $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getParsed(): ?bool
    {
        return $this->parsed;
    }

    public function setParsed(bool $parsed): self
    {
        $this->parsed = $parsed;

        return $this;
    }
}

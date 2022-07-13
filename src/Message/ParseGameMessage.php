<?php

namespace App\Message;

/**
 * Message send after log file is divided into single game files
 */
class ParseGameMessage
{
    private $filename;
    private $userId;

    public function __construct(string $filename, int $userId)
    {
        $this->filename = $filename;
        $this->userId = $userId;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
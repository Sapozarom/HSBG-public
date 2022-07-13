<?php

namespace App\Message;

/**
 * Message send after uploading log file
 */
class DivideFileMessage
{
    private $fileId;

    public function __construct(int $fileId)
    {
        $this->fileId = $fileId;
    }

    public function getFileId(): int
    {
        return $this->fileId;
    }


}
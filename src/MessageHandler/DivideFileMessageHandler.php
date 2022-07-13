<?php

namespace App\MessageHandler;

use App\Service\LogFileDivider\LogFileDivider;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use App\Message\DivideFileMessage;

/**
 * Handles DivideFileMessage, It is sent after uploading log file. Handler triggers Service that split log file into single games.
 */
class DivideFileMessageHandler implements MessageHandlerInterface

{
    private $logDFileividerService;

    public function __construct(LogFileDivider $lfd)
    {
        $this->logDFileividerService = $lfd;
    }

    public function __invoke(DivideFileMessage $divideFileMessage)
    {
        $fileId = $divideFileMessage->getFileId();
        
        $this->logDFileividerService->divideLogFile($fileId);
    }
}
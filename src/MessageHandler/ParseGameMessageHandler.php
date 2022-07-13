<?php

namespace App\MessageHandler;

use App\Service\SaveGame\SaveGame;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use App\Message\ParseGameMessage;

/**
 * Handle ParseGameMessage. It is sent after log file is divided into single game files. It triggers service that saves game into DB
 */
class ParseGameMessageHandler implements MessageHandlerInterface
{
    private $saveGameService;

    public function __construct(SaveGame $sg)
    {
        $this->saveGameService = $sg;
    }

    public function __invoke(ParseGameMessage $parseGameMessage)
    {
        $filename = $parseGameMessage->getFilename();
        $userId = $parseGameMessage->getUserId();

        $this->saveGameService->saveGameToDb($filename, $userId);
    }
}
<?php

namespace App\Service\LogFileDivider;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use App\Entity\LogFile;
use App\Entity\SingleGameFile;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Message\ParseGameMessage;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

/**
 * This service takes uploaded files with multiple games and splits it into single game files.
 * 
 * It is triggered by symfony/messenger script that is executed by Supervisor after file is correctly uploaded 
 * 
 * After dividing file, service sends messeges that triggers parsing data form single game files.
 * 
 */
class LogFileDivider
{
    private $dividedFiles = array();

    private $newFile = false;

    private $fileNr = 0;

    private $gameFinish;

    private $gameType = null;

    private $currentFile = null;

    private $messageBus;

    private $params;


    public function __construct(EntityManagerInterface $em, MessageBusInterface $bus, ContainerBagInterface $params)
    {   
        $this->entityManager = $em;
        $this->messageBus = $bus;
        $this->params = $params;
    }

    /**
     * Main processing function it runs through .log file and 
     * 
     * @param  $fileId | database ID of procesed file
     */
    public function divideLogFile($fileId)
    {   
        //Value incremented with every game. It will be added to name of specific game file.
        $this->fileNr = 0;

        //Finds .log file by $fileId
        $fileRepo = $this->entityManager->getRepository("App:LogFile");
        $logFile = $fileRepo->findOneBy(['id' => $fileId]);

        //Checks if file was previously divided
        if ($logFile->getDivided() == false) {
            $this->dividedFiles = array();
            
            //Gets gilename of .log from DB
            $filename =  $logFile->getName();

            //Gets user that uploaded .log file
            $user = $logFile->getUser();

            $nameOnly = \rtrim($filename, '.log');
            
            $filesystem = new Filesystem();

            //File localisation
            $publicPath = $this->params->get('app.public_path');
            $path = $publicPath.'/games/logs/'.$filename;
           
            //File as object
            $file = new \SplFileObject($path);

            //Runs thorugh file line by line until end of file
            while (!$file->eof()) {
                
                $line =  $file->fgets();

                //Start new game flag
                if (strpos($line, 'GameState.DebugPrintPower')
                    && strpos($line, 'CREATE_GAME')
                ) {
                    
                    $newFilename =''. $nameOnly .''. $this->fileNr.'.log';
                    
                    //Creates new SingleGameFile object
                    $this->currentFile = new SingleGameFile();
                    $this->currentFile->setFilename($newFilename);
                    $this->entityManager->persist($this->currentFile);
                    
                    //Creates new SingleGameFile object
                    $this->newFile =$publicPath.'/games/singleGameFiles/'. $nameOnly .''. $this->fileNr.'.log';
                    $filesystem->appendToFile($this->newFile, 'New Game '. "\r\n");
                    
                    $this->fileNr++;

                    array_push($this->dividedFiles, $newFilename);

                }

                //Checks the game mode, only Battlegrounds are saved into DB
                if ((strpos($line, 'GameType=GT_BATTLEGROUNDS'))) {
                    $this->currentFile->setType(true) ;
                    $this->entityManager->persist($this->currentFile);
                }
                
                if($this->newFile) {
                    $filesystem->appendToFile($this->newFile, $line);
                }
            }

            //Marks file as divided
            $logFile->setDivided(true);
            
            //Data about new files are updated ind DB
            $this->entityManager->persist($logFile);
            $this->entityManager->flush();

            //Sends messeges for every file to execute next step wchich is parasing every game file. 
            foreach($this->dividedFiles as $createdFile) {
                $this->messageBus->dispatch(new ParseGameMessage( $createdFile, $user->getId()));
            }
        }
    }
}
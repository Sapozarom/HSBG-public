<?php

namespace App\Service\EventInterpreter\Entity;

use App\Service\LogCrawler\Entities\Entity;


class Mulligan
{
    private $id;

    private $options = array();

    private $choice;

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function addOption($entity)
    {
        array_push($this->options, $entity);
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setChoice($entity)
    {
        $this->choice = $entity;
    }

    public function getChoice()
    {
        return $this->choice;
    }

    public function summarizeMulligan()
    {
        switch ($this->choice['CARDTYPE']) {
            case 'HERO':
                # code...
                break;
            
            default:
                echo 'error - no cardtype in mulligan';
                break;
        }
    }

    public function establishMulliganType()
    {
        if ($this->choice->getCardId() === "SCH_149") {
            return "SPELL";
        } else {
            return ($this->choice->getTagValueFromPairArray('CARDTYPE'));
        }
    }


}
<?php

namespace App\Service\EventInterpreter\Entity;

use App\Service\LogCrawler\Entities\Entity;
use App\Service\EventInterpreter\Entity\Option;



class SelectedOption
{
    private $timestamp;

    private $optionBlock;

    private $selectedOption;

    private $selectedTarget;

    private $selectedPosition;

    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    public function setOptionBlock($optionBlock)
    {
        $this->optionBlock = $optionBlock;
    }

    public function setSelectedOption($selectedOption)
    {
        $this->selectedOption = $selectedOption;
    }

    public function setSelectedTarget($selectedTarget)
    {
        $this->selectedTarget = $selectedTarget;
    }

    public function setSelectedPosition($selectedPosition)
    {
        $this->selectedPosition = $selectedPosition;
    }
}
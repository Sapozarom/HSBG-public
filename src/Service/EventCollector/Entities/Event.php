<?php

namespace App\Service\EventCollector\Entities;

class Event 
{
    private $timestamp;

    private $content;

    private $type;

    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function __toString()
    {
        return $this->timestamp . ' '. $this->type .' '. $this->content;
    }


}
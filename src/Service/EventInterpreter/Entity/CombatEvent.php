<?php

namespace App\Service\EventInterpreter\Entity;

class CombatEvent extends Event
{
    private $winner;

    private $error = false;

    private $opponent;

    private $oppHealth;

    private $damageArray;

    private $secretArray;

    protected $type = 'CBT';

    public function getWinner()
    {
        return $this->winner;
    }

    public function setWinner($playerId)
    {
        $this->winner = $playerId;
    }

    public function getError()
    {
        return $this->error;
    }

    public function setError($error)
    {
        $this->error = $error;
    }

    public function getOpponent()
    {
        return $this->opponent;
    }

    public function setOpponent($playerId)
    {
        $this->opponent = $playerId;
    }

    public function getOpponentHealth()
    {
        return $this->oppHealth;
    }

    public function setOpponentHealth($health)
    {
        $this->oppHealth = $health;
    }

    public function getDamageArray()
    {
        return $this->damageArray;
    }

    public function setDamageArray($dmgArray)
    {
        $this->damageArray = $dmgArray;
    }
}
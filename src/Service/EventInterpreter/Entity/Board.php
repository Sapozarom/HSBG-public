<?php

namespace  App\Service\EventInterpreter\Entity;

use App\Service\EventInterpreter\Entity\CardCollection;


class Board extends CardCollection
{

    public function __construct()
    {
        $this->limit = 7;
    }

}
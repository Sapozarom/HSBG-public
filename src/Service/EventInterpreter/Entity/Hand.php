<?php

namespace  App\Service\EventInterpreter\Entity;

use App\Service\EventInterpreter\Entity\CardCollection;


class Hand  extends CardCollection
{

    public function __construct()
    {
        $this->limit = 10;
    }

}
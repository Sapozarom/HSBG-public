<?php

namespace App\Service\EventInterpreter\Entity;

use App\Service\LogCrawler\Entities\Entity;
use App\Service\EventInterpreter\Entity\Option;



class OptionBlock
{
    private $blockId;

    private $options = array();

    private $select;


    public function setBlockId($id)
    {
        $this->blockId = $id;
    }

    public function getBlockId()
    {
        return $this->blockId;
    }

    public function addOption(Option $option)
    {
        $index = $option->getOptionNumber();

        $this->options[$index] = $option;
    }

    public function getOption($optId)
    {
        if (isset($this->options[$optId])) {
            return $this->options[$optId];
        } else {
            return false;
        }
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setSelect($select)
    {
        $this->select = $select;
    }

    public function getSelect()
    {
        return $this->select;
    }

    public function findShopPosition()
    {
        $buyPos = null;
        $sellPos = null;

        foreach ($this->options as $option) {
            if($option->getMainEntity()->getCardId() === 'TB_BaconShop_DragSell' ) {
                $sellPos= $option->getOptionNumber();
            }
            
            if($option->getMainEntity()->getCardId() === 'TB_BaconShop_DragBuy' ) {
                $buyPos= $option->getOptionNumber();
            }
        }

        if($buyPos != null && $sellPos != null) {
            
            return min($buyPos, $sellPos);
        } elseif ($buyPos != null) {
            
            return $buyPos; 
        } elseif ($sellPos != null) {
            
            return $sellPos;
        }
    }
}
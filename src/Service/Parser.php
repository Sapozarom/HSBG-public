<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;



class Parser
{
    public function parseFile($fileName, $publicPath)
    {
        $i = 0;
        
        $path = $publicPath .'/games/singleGameFiles/'.$fileName;
        
        $file = new \SplFileObject($path);
        
        while (!$file->eof()) {
            
            $string =  $file->fgets();    

            $timestamp = substr($string,2,16);
            $functionEndPosition = strpos($string, ')' );
            $functionLength = $functionEndPosition - 18;
            $function = substr($string, 19 ,$functionLength);
    
            $contentStartPosition = strpos($string, '-' ) + 1;
            $content = substr($string, $contentStartPosition);
            $content = ltrim($content, ' ');
            $content = rtrim($content, "\n");
            $content = rtrim($content, "\r");

            $row[$i]['timestamp'] = $timestamp;
            $row[$i]['function'] = $function;
            $row[$i]['content'] = $content;

            $i++;
        }

        $i=0;

        return $row;
    }
}
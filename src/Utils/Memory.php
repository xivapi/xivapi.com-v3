<?php

namespace App\Utils;

class Memory
{
    /**
     * Save a file from a source to a destination, this will also create the destination directory
     */
    public static function read()
    {
        $size    = memory_get_peak_usage(true);
        $unit    = ['b','kb','mb','gb','tb','pb'];
        $current =  @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
        $max     = ini_get('memory_limit');

        return [$current, $max];
    }
}

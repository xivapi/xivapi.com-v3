<?php

namespace App\Utils;

class Downloader
{
    /**
     * Save a file from a source to a destination, this will also create the destination directory
     */
    public static function save(string $source, string $destination)
    {
        $pi = pathinfo($destination);

        // create directory if it does not exist
        if (!is_dir($pi['dirname'])) {
            mkdir($pi['dirname'], 0777, true);
        }
        
        file_put_contents($destination, file_get_contents($source));
    }
}

<?php

namespace App\Service\GameData;

use App\Service\SaintCoinach\SaintCoinach;
use App\Service\SaintCoinach\SchemaBuilder;
use League\Csv\Reader;
use League\Csv\Statement;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Converts all the fancy CSV to JSON
 */
class CSVManager
{
    const FOREIGN_REMOVALS = [
        '<Emphasis>',   '</Emphasis>',   '<Emphasis/>',
        '<Indent>',     '</Indent>',     '<Indent/>',
        '<SoftHyphen>', '</SoftHyphen>', '<SoftHyphen/>',
    ];
    
    /**
     * Extracts the CSV files and saves them to JSON
     */
    public function extractCsvFilesToJson()
    {
        $console = new ConsoleOutput();
        
        if (!is_dir(SaintCoinach::GAME_DATA_JSON)) {
            mkdir(SaintCoinach::GAME_DATA_JSON);
        }
        
        // grab schema
        $schema = file_get_contents(SchemaBuilder::SCHEMA_FILENAME);
        $schema = json_decode($schema, true);
        $total  = count(array_keys($schema));
        $count  = 1;
        
        $console->writeln([
            "",
            "Processing: {$total} CSVs",
            "<fg=green>-----------------------------------------------------</>",
            ""
        ]);

        foreach ($schema as $contentName => $contentSchema) {
            $console->writeln("<fg=cyan>[{$count} / {$total}]</> <comment>{$contentName}</comment>");
            
            $filenameRaw      = SaintCoinach::GAME_DATA . "/{$contentName}.csv";
            $filenameLanguage = SaintCoinach::GAME_DATA . "/{$contentName}.[lang].csv";
            
            // check for raw
            if (file_exists($filenameRaw)) {
                [$json, $types] = $this->handleCsvData([], $filenameRaw, null, true);
            } else {
                $json = [];
                foreach (GameLanguages::LANGUAGES as $language) {
                    $filename = str_ireplace("[lang]", $language, $filenameLanguage);
                    
                    // some languages (eg: chs/ko) may not have the file in their current patches
                    // this will be handled during game document build
                    if (file_exists($filename)) {
                        [$json, $types] = $this->handleCsvData($json, $filename, $language);
                    }
                }
            }
            
            /**
             * Save the data, we don't care about column headings or
             * types as we'll use the custom schema
             */
            $filename = SaintCoinach::GAME_DATA_JSON . "/{$contentName}.json";
            file_put_contents($filename, json_encode($json));
    
            /**
             * Save the data types
             */
            $filename = SaintCoinach::GAME_DATA_JSON . "/{$contentName}.types.json";
            file_put_contents($filename, json_encode($types));
            
            $count++;
        }
        
        $console->writeln("Finished");
    }
    
    /**
     * Process CSV data and returns it as a big array.
     */
    private function handleCsvData(array $data, string $filename, ?string $language = null, ?bool $isRaw = false)
    {
        // we only process "everything" on English, otherwise it just grabs str entries
        $isEnglish = ($language !== null && $language == 'en');
        
        // load the CSV document from a file path
        $csv = Reader::createFromPath($filename, 'r');

        // get column headers
        $stmt       = (new Statement())->offset(1)->limit(1);
        $columns    = $stmt->process($csv)->fetchOne();
        $columns[0] = 'ID';
    
        // get types
        $stmt  = (new Statement())->offset(2)->limit(1);
        $types = $stmt->process($csv)->fetchOne();
        
        // column to type
        $columnToType = [];
        foreach ($columns as $i => $col) {
            $columnToType[$col] = $types[$i];
        }
    
        // get data
        $stmt    = (new Statement())->offset(3);
        $records = $stmt->process($csv)->getRecords();
        
        foreach($records as $record) {
            $id = $record[0];
    
            // loop through records
            foreach($record as $offset => $value) {
                $columnName = $columns[$offset];
                $columnType = $types[$offset];
                
                // skip if this is not English and column type is not str
                if ($isRaw == false && !$isEnglish && $columnType != 'str') {
                    continue;
                }
    
                // handle value situations
                if (empty($columnName)) {
                    // unset and ignore
                    unset($record[$offset]);
                    continue;
                } elseif ($value > 2147483647) {
                    // not dealing with this shit!
                    // this is likely a wrong mapper, eg uint instead of a int64
                    $value = null;
                } elseif (strtoupper($value) === 'TRUE') {
                    $value = 1;
                } elseif (strtoupper($value) === 'FALSE') {
                    $value = 0;
                } elseif ($columnType == 'Image') {
                    // Keep the existing ID value.
                    $data[$id]["{$columnName}ID"]  = $value;
                    $columnToType["{$columnName}ID"] = $columnType;
                    
                    // convert icon to a url
                    $value = $this->handleImage($value);
                } elseif ($columnType == 'str') {
                    $columnName = "{$columnName}_{$language}";
                    $value = str_ireplace("\r", "\n", $value);
                }
    
                // remove some junk
                $value = str_ireplace(self::FOREIGN_REMOVALS, null, $value);

                // set value
                $data[$id][$columnName] = $value;
            }
        }
    
        // clean up and return
        unset($csv);
        return [$data , $columnToType];
    }
    
    /**
     * Handle image paths by returning the correct PNG filepath.
     */
    private function handleImage($number)
    {
        $number = intval($number);
        $extended = (strlen($number) >= 6);
    
        if ($number == 0) {
            return null;
        }
    
        // create icon filename
        $icon = $extended ? str_pad($number, 5, "0", STR_PAD_LEFT) : '0' . str_pad($number, 5, "0", STR_PAD_LEFT);
    
        // create icon path
        $path = [];
        $path[] = $extended ? $icon[0] . $icon[1] . $icon[2] .'000' : '0'. $icon[1] . $icon[2] .'000';
        $path[] = $icon;
    
        // combine
        return '/i/'. implode('/', $path) .'.png';
    }
}

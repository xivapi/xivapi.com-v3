<?php

namespace App\Service\GameData;

use App\Service\SaintCoinach\SchemaBuilder;
use Symfony\Component\Console\Output\ConsoleOutput;

class DocumentBuilder
{
    const SCHEMA_FILENAME     = SchemaBuilder::SCHEMA_FILENAME;
    const DIRECTORY_JSON_DATA = ROOT . '/data/gamejson';
    const DIRECTORY_DOCUMENTS = ROOT . '/data/game_documents';
    
    private $cache = [];
    
    public function buildGameDocuments()
    {
        $console = new ConsoleOutput();
    
        if (!is_dir(self::DIRECTORY_DOCUMENTS)) {
            mkdir(self::DIRECTORY_DOCUMENTS);
        }
        
        // grab schema
        $schema = file_get_contents(SchemaBuilder::SCHEMA_FILENAME);
        $schema = json_decode($schema, true);
        $total  = count(array_keys($schema));
        
        $console->writeln([
            "",
            "Processing: {$total} CSVs",
            "<fg=green>-----------------------------------------------------</>",
            ""
        ]);
        
        $this->build(1, $schema);
    }
    
    /**
     * Build documents
     */
    private function build(int $pass, array $schema)
    {
        $console = new ConsoleOutput();
        $console = $console->section();
        
        foreach ($schema as $contentName => $contentSchema) {
            $console->overwrite("- {$pass} - {$contentName}");
            $document = [];
            
            // grab json
            [ $json, $types ] = $this->getSheet($contentName);
            
            // go through each row
            foreach ($json as $row) {
                $console->overwrite("- {$pass} - {$contentName} - {$row['ID']}");
                $entry = [
                    'ID' => $row['ID']
                ];
                
                // go through schema
                foreach ($contentSchema as $struct) {
                    $column = $struct['name'];
                    $value  = $row[$column] ?? null;
                    $type   = $types[$column] ?? null;
    
                    $console->writeln("- {$pass} - {$contentName} - {$row['ID']} - {$column}");
    
                    // handle the type
                    switch ($struct['type']) {
                        default:
                            $console->writeln("Unknown schema structure type: {$struct['type']}");
                            die;
                            break;
                            
                        // basic is just assign value, nothing to do
                        case 'basic':
                            // if type is str, its multi-language
                            if ($type == 'str' && !isset($row[$column])) {
                                foreach (GameLanguages::LANGUAGES as $language) {
                                    $langColumn = "{$column}_{$language}";
                                    $entry[$langColumn] = $row[$langColumn] ?? null;
                                }
                            } else {
                                $entry[$column] = $value;
                            }
                            break;
                            
                        // icon we don't need to do much, append the "id" version
                        case 'icon':
                            $entry[$column . "ID"] = $row[$column . "ID"];
                            break;
                            
                        // link another sheet
                        case 'link_sheet':
                            // only link those above 0
                            // todo - some of these can be 0
                            if ($value > 0) {
                                [ $value, $a ] = $this->getSheet($struct['target'], $value);
                            }
                            $entry[$column] = $value;
                            break;
                            
                        // this will be multiple links based on another column
                        case 'link_complex':
                            $linkConditionField = $struct['field'];
                            $linkConditionValue = $row[$linkConditionField];
                            
                            // ignore 0 values
                            // todo - some of these can be 0
                            if ($linkConditionValue == 0) {
                                break;
                            }
                            
                            // set default
                            $entry[$column] = $value;
                            
                            foreach ($struct['sheets'] as $linkSheet) {
                                if ($linkSheet['value'] == $linkConditionValue) {
                                    [ $value, $b ] = $this->getSheet($linkSheet['sheet'], $value);
                                    $entry[$column] = $value;
                                    $entry[$column . "Sheet"] = $linkSheet['sheet'];
                                    break;
                                }
                            }
        
                            break;
                            
                        // a basic array
                        case 'array_basic':
                            // create empty array
                            $arr = [];
                            
                            // populate it
                            foreach(range(0, $struct['count'] -1) as $i) {
                                $arrValue = $row["{$column}[{$i}]"] ?? null;
                                
                                if ($arrValue) {
                                    $arr[] = $arrValue;
                                }
                            }
    
                            $entry[$column] = $arr;
                            break;
                    }
                }
    
                $document[$row['ID']] = $entry;
            }
    
            $console->overwrite("- {$pass} - {$contentName} - Complete.");
            
            // save
            file_put_contents(
                self::DIRECTORY_DOCUMENTS . "/{$contentName}.json",
                json_encode($document, JSON_PRETTY_PRINT)
            );
            
            // release memory
            unset($this->cache);
            
            die;
        }
    }
    
    /**
     * Get a document sheet
     */
    private function getSheet(string $contentName, int $index = null)
    {
        // if no in-memory cache, start one
        if (!isset($this->cache[$contentName])) {
            // grab json
            $json = file_get_contents(self::DIRECTORY_JSON_DATA . "/{$contentName}.json");
            $json = json_decode($json, true);
    
            // grab types
            $types = file_get_contents(self::DIRECTORY_JSON_DATA . "/{$contentName}.types.json");
            $types = json_decode($types, true);
    
            $this->cache[$contentName] = [ $json, $types ];
        }
    
        [ $json, $types ] = $this->cache[$contentName];
        
        if ($index !== null) {
            return [ $json[$index] ?? null, $types ];
        }
        
        return [ $json, $types ];
    }
}

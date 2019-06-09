<?php

namespace App\Service\SaintCoinach;

use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Converts the SaintCoinach ex.json into a reliable format for XIVAPI, this
 * includes appending on custom data, performing custom links as well as
 * changing names to be in a consistent format. It also simplifies all data.
 *
 * todo - rename all "index" to "index_start" and all "start" to "index_start"
 * todo - rename all "finish" to "index_finish"
 *
 * Class SaintCoinachSchemaGenerator
 *
 * @package App\Service\SaintCoinach
 */
class SchemaBuilder
{
    const SCHEMA_SAVE_PATH = __DIR__.'/schema';

    /** @var SaintCoinach */
    private $saintCoinach;
    /** @var ConsoleOutput */
    private $console;
    /** @var array */
    private $schema = [];
    /** @var array */
    private $bigschema = [];

    public function __construct(SaintCoinach $saintCoinach)
    {
        $this->console = new ConsoleOutput();

        $this->saintCoinach = $saintCoinach;
    }

    public function addDefinition($definition)
    {
        $this->schema[] = $definition;
    }

    /**
     * Build the saint schema
     */
    public function build()
    {
        $schema = $this->saintCoinach->schema();

        // reindex sheets
        foreach ($schema->sheets as $i => $sheet) {
            $schema->sheets[$sheet->sheet] = $sheet;
            unset($schema->sheets[$i]);
        }

        // build-it
        foreach ($schema->sheets as $sheetName => $sheet) {
            // todo - testing purposes
            if ($sheetName != 'TopicSelect') {
                //continue;
            }

            $this->console->writeln("Sheet: {$sheetName}");

            // build schema for this sheet
            $this->handleSheet($sheet);

            // save the schema for this sheet
            $this->saveSheetSchema($sheetName);

            // reset
            $this->schema = [];
        }
    }

    /**
     * Basic save method for saving sheet schemas
     * uses $this->schema
     */
    private function saveSheetSchema($sheetName)
    {
        if (!is_dir(self::SCHEMA_SAVE_PATH)) {
            mkdir(self::SCHEMA_SAVE_PATH);
        }
        
        file_put_contents(
            self::SCHEMA_SAVE_PATH . "/{$sheetName}.json",
            json_encode($this->schema, JSON_PRETTY_PRINT)
        );

        $this->bigschema[$sheetName] = $this->schema;

        file_put_contents(
            self::SCHEMA_SAVE_PATH . "/../schema.json",
            json_encode($this->bigschema, JSON_PRETTY_PRINT)
        );
    }

    /**
     * A sheet should have a list of definitions
     */
    private function handleSheet($sheet)
    {
        foreach ($sheet->definitions as $definition) {
            // set index, this will always be plus 1, since the CSV 0 is "ID"
            $definition->index = ($definition->index ?? 0) + 1;

            // try to detect definition type
            $isConverter = (isset($definition->converter));
            $isRepeater  = (isset($definition->type) && $definition->type == 'repeat');
            $isBasic     = ($isConverter == false && $isRepeater == false);

            // handle basic column name definition
            if ($isBasic) {
                $this->addDefinition(
                    $this->handleBasicDefinition($definition)
                );
                continue;
            }

            // handle direct converters
            if ($isConverter) {
                switch($definition->converter->type) {
                    default:
                        $this->console->writeln("[D] Unknown converter: {$definition->converter->type}");
                        break;

                    case 'link':
                        $this->addDefinition(
                            $this->handleLinkDefinition($definition)
                        );
                        break;

                    case 'complexlink':
                        $this->addDefinition(
                            $this->handleComplexLinkDefinition($definition)
                        );
                        break;

                    case 'multiref':
                        $this->addDefinition(
                            $this->handleMultiRefDefinition($definition)
                        );
                        break;

                    case 'generic':
                        $this->addDefinition(
                            $this->handleGenericDefinition($definition)
                        );
                        break;

                    case 'color':
                        $this->addDefinition(
                            $this->handleColorDefinition($definition)
                        );
                        break;

                    case 'tomestone':
                        $this->addDefinition(
                            $this->handleTomestoneDefinition($definition)
                        );
                        break;

                    case 'icon':
                        $this->addDefinition(
                            $this->handleIconDefinition($definition)
                        );
                        break;
                }
            }

            // handle repeaters
            if ($isRepeater) {
                $this->addDefinition(
                    $this->handleRepeaterDefinition($definition)
                );
            }
        }
    }

    /**
     * Handle all basic definitions (no link, converters or repeaters)
     */
    private function handleBasicDefinition($definition)
    {
        return [
            'type'   => 'basic',
            'index'  => $definition->index,
            'name'   => $definition->name,
        ];
    }

    /**
     * Handle basic icon definitions
     */
    private function handleIconDefinition($definition)
    {
        return [
            'type'   => 'icon',
            'index'  => $definition->index,
            'name'   => $definition->name,
        ];
    }

    /**
     * Handle basic color definition
     */
    private function handleColorDefinition($definition)
    {
        return [
            'type'   => 'color',
            'index'  => $definition->index,
            'name'   => $definition->name,
        ];
    }

    /**
     * Handle basic tomestone definition
     */
    private function handleTomestoneDefinition($definition)
    {
        return [
            'type'   => 'tomestone',
            'index'  => $definition->index,
            'name'   => $definition->name,
        ];
    }

    /**
     * Handle all link definitions
     */
    private function handleLinkDefinition($definition)
    {
        return [
            'type'   => 'link_sheet',
            'index'  => $definition->index ?? null,
            'name'   => $definition->name,
            'target' => $definition->converter->target
        ];
    }

    /**
     * Handle "Generic" definitions, I'm not sure these even have any data...
     */
    private function handleGenericDefinition($definition, $isRepeater = false)
    {
        if ($isRepeater === true) {
            return [
                'type'   => 'array_generic',
                'start'  => $definition->index,
                'finish' => ($definition->index + $definition->count) - 1,
                'count'  => $definition->count,
                'name'   => $definition->definition->name,
            ];
        }

        return [
            'type'   => 'array_generic',
            'start'  => $definition->index,
            'name'   => $definition->name,
        ];
    }

    /**
     * Handle all complex link definitions
     */
    private function handleComplexLinkDefinition($definition)
    {
        $linkField  = null;
        $linkSheets = [];

        foreach ($definition->converter->links as $condition) {
            $linkField = $condition->when->key ?? null;

            // if there are multiple sheets then the ID of the value will not overlap
            // so all "sheet_array" can be scanned to find the exact match
            // - this works exactly like "multiref" converters
            $linkSheets[] = [
                'sheet'         => $condition->sheet ?? null,
                'sheet_array'   => $condition->sheets ?? null,
                'project'       => $condition->project ?? null,
                'value'         => $condition->when->value ?? null,
            ];
        }

        return [
            'type'   => 'link_complex',
            'index'  => $definition->index ?? null,
            'name'   => $definition->name,
            'field'  => $linkField,
            'sheets' => $linkSheets
        ];
    }

    /**
     * Handle complex link arrays
     */
    private function handleComplexLinkArrayDefinition($definition)
    {
        if (isset($definition->converter)) {
            switch($definition->converter->type) {
                default:
                    $this->console->writeln("[J] Unknown converter type: {$definition->converter->type}");
                    print_r($definition);
                    die;

                case 'complexlink':
                    return $this->handleComplexLinkDefinition($definition);
            }
        }

        return [
            'type'   => 'array_complex',
            'start'  => $definition->index,
            'finish' => ($definition->index + $definition->count) - 1,
            'count'  => $definition->count,
            'name'   => $definition->definition->name,
            'repeat' => $this->handleComplexLinkDefinition($definition->definition)
        ];
    }

    /**
     * Handle all different types of repeater
     */
    private function handleRepeaterDefinition($definition)
    {
        $definition->index = $definition->index ?? 0;

        // if it's a group
        if (isset($definition->definition->type) && $definition->definition->type === 'group') {
            return $this->handleGroupRepeaterDefinition($definition);
        }

        // check if it's a repeat repeat
        $isRepeatOne = $definition->type ?? null;
        $isRepeatTwo = $definition->definition->type ?? null;
        if ($isRepeatOne === 'repeat' && $isRepeatTwo === 'repeat') {
            return [
                'type'    => 'array_repeat_repeat',
                'start'   => $definition->index,
                'finish'  => ($definition->index + $definition->count) - 1,
                'count'  => $definition->count,
                'repeats' => $this->handleRepeaterDefinition($definition->definition)
            ];
        }

        // if it's still a repeater
        if ($isRepeatOne === 'repeat' && $isRepeatTwo === null) {
            if ($definition->type == 'repeat') {
                return [
                    'type'    => 'array_basic',
                    'start'   => $definition->index,
                    'finish'  => ($definition->index + $definition->count) - 1,
                    'count'   => $definition->count,
                    'name'    => $definition->definition->name,
                ];
            }

            return [
                'type'    => 'array_repeat',
                'start'   => $definition->index,
                'finish'  => ($definition->index + $definition->count) - 1,
                'count'  => $definition->count,
                'repeats' => $this->handleRepeaterDefinition($definition->definition)
            ];
        }

        // if it's a converter
        if (isset($definition->definition->converter->type)) {
            switch($definition->definition->converter->type) {
                default:
                    $this->console->writeln("[A] Unknown converter type: {$definition->definition->converter->type}");
                    print_r($definition);
                    die;

                case 'complexlink':
                    return $this->handleComplexLinkArrayDefinition($definition);

                case 'multiref':
                    return $this->handleMultiRefDefinition($definition);

                case 'generic':
                    return $this->handleGenericDefinition($definition, true);
            }
        }

        if (isset($definition->converter)) {
            switch ($definition->converter->type) {
                default:
                    $this->console->writeln("[B] Unknown converter type: {$definition->converter->type}");
                    print_r($definition);
                    die;

                case 'complexlink':
                    return $this->handleComplexLinkArrayDefinition($definition);

                case 'multiref':
                    return $this->handleMultiRefDefinition($definition);

                case 'generic':
                    return $this->handleGenericDefinition($definition, true);

                case 'link':
                    return $this->handleLinkDefinition($definition);
            }
        }

        // otherwise it's just a basic field
        return [
            'type'    => 'array_basic',
            'start'   => $definition->index,
            'finish'  => ($definition->index + $definition->count) - 1,
            'count'   => $definition->count,
            'name'    => $definition->definition->name,
        ];
    }

    /**
     * Handle multi-ref definitions, this one is easy as it usually just
     * links to multiple sheets, the sheets will all have unique ids
     * and not overlap, so the ID in the field can be checked against
     * each sheet to find the correct link
     */
    private function handleMultiRefDefinition($definition)
    {
        if (isset($definition->type) && $definition->type === 'repeat') {
            return [
                'type'    => 'array_multiref',
                'start'   => $definition->index,
                'finish'  => ($definition->index + $definition->count) - 1,
                'count'   => $definition->count,
                'name'    => $definition->definition->name,
                'targets' => $definition->definition->converter->targets,
            ];
        }

        return [
            'type'    => 'multiref',
            'index'   => $definition->index ?? 0,
            'name'    => $definition->name,
            'targets' => $definition->converter->targets,
        ];
    }

    /**
     * Handle "Group" repeater definitions, these are quite complicated, almost a mini schema
     * inside a repeater group
     */
    private function handleGroupRepeaterDefinition($definition)
    {
        // sometimes don't have an index, eg: GCSupplyDuty
        $definition->index = $definition->index ?? 1;

        return [
            'type'   => 'array_group',
            'start'  => $definition->index,
            'finish' => ($definition->index + $definition->count) - 1,
            'count'  => $definition->count,
            'repeat' => $this->handleGroupRepeaterMemberDefinition($definition->definition->members)
        ];
    }

    /**
     * Handles the members for group repeater definitions, will merge complex link
     */
    private function handleGroupRepeaterMemberDefinition($members)
    {
        $arr = [];
        foreach ($members as $i => $member) {
            $temp = [
                'offset' => $i,
                'name'   => $member->name ?? null,
                'type'   => 'basic',
            ];

            // special case for SatisfactionNpc
            // repeat a group of repeaters....
            if (isset($member->type) && $member->type == 'repeat') {
                $temp2 = [
                    'type'   => 'array',
                    'name'   => $member->definition->name,
                    'start'  => ($i * $member->count),
                    'finish' => ($i * $member->count) + $member->count - 1,
                    'count'  => $member->count,
                ];

                if (isset($member->definition->converter->type)) {
                    switch($member->definition->converter->type) {
                        case 'link':
                            $temp2['link_sheet'] = $member->definition->converter->target;
                            break;
                    }
                }

                $temp = array_merge($temp, $temp2);
            }

            // if it has a converter
            if (isset($member->converter)) {
                // handle based on type
                switch($member->converter->type) {
                    default:
                        $this->console->writeln("[C] Unknown converter type: {$member->converter->type}");
                        print_r($member);
                        die;

                    case 'complexlink':
                        $temp = array_merge($temp, ($this->handleComplexLinkDefinition($member)));
                        break;

                    case 'link':
                        $temp = array_merge($temp, ($this->handleLinkDefinition($member)));
                        break;

                    case 'multiref':
                        $temp = array_merge($temp, ($this->handleMultiRefDefinition($member)));
                        break;
                }
            }

            $arr[] = $temp;
        }

        return $arr;
    }
}

<?php

namespace App\Service\SaintCoinach;

use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Converts the SaintCoinach ex.json into a reliable format for XIVAPI, this
 * includes appending on custom data, performing custom links as well as
 * changing names to be in a consistent format. It also simplifies all data.
 *
 * Class SaintCoinachSchemaGenerator
 *
 * @package App\Service\SaintCoinach
 */
class SaintCoinachSchemaGenerator
{
    /** @var SaintCoinach */
    private $saintCoinach;
    /** @var ConsoleOutput */
    private $console;

    public function __construct(SaintCoinach $saintCoinach)
    {
        $this->console = new ConsoleOutput();

        $this->saintCoinach = $saintCoinach;
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
            $obj = new \stdClass();

            foreach ($sheet->definitions as $def) {
                $this->handleDefinition($obj, $def);
            }



            print_r($obj);
            die;
        }
    }

    private function handleDefinition($obj, $def)
    {
        // loop through definitions
        $def->index = $def->index ?? 0;
        $index = $def->index;

        //
        // if it is an icon
        //
        if (isset($def->converter) && $def->converter->type == 'icon') {
            $obj->{$index} = 'Icon';
            return;
        }

        //
        // if it's a complex link
        //
        if (isset($def->converter) && $def->converter->type == 'complexlink') {
            $obj->{$index} = [
                'name'    => $def->name,
                'complex' => $this->handleComplexLink($def),
            ];
            return;
        }

        //
        // if it is a converter
        //
        if (isset($def->converter)) {
            $obj->{$index} = [
                'link' => $def->converter->target
            ];

            return;
        }

        //
        // if it is a repeater
        //
        if (isset($def->type) && $def->type == 'repeat') {
            $name = "{$def->definition->name}_%s";

            foreach (range($def->index, $def->index + $def->count) as $index) {
                //
                // if it is a repeater with a multiref
                //
                if (isset($def->definition->converter->type) && $def->definition->converter->type == "multiref") {
                    $obj->{$index} = $def->definition->converter->targets;
                    return;
                }

                //
                // if it is a repeater with a group
                //
                if (isset($def->definition->type) && $def->definition->type == "group") {
                    $obj->{$index} = $this->handleGroupLink($def->definition->members);
                }


                if (isset($def->definition->type) && $def->definition->type == "complexlink") {
                    $obj->{$index} = [
                        'name' => sprintf($name, $index),
                        'complex' => $this->handleComplexLink($def->definition)
                    ];
                }
            }

            return;
        }

        //
        // likely just a normal one
        //
        $obj->{$index} = $def->name;
        return;
    }

    private function handleGroupLink($members)
    {
        $structure = [];

        foreach ($members as $def) {
            $obj = (Object)[];
            $this->handleDefinition($obj, $def);

            $structure[] = $obj;
        }

        return $structure;
    }

    private function handleComplexLink($def)
    {
        $complex = [];
        $field   = null;

        foreach ($def->converter->links as $link) {
            $field = $link->when->key;
            $complex[$link->when->value] = $link->sheet;
        }

        return [
            'field' => $field,
            'links' => $complex
        ];
    }
}

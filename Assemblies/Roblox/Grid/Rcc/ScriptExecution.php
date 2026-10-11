<?php

namespace Roblox\Grid\Rcc;

class ScriptExecution {
    /* Variables */
    public $name;
    public $script;
    public $arguments = [];

    /* Constructor */
    function __construct($name, $script, $arguments = []) {
        $this->name = $name;
        $this->script = $script;
        foreach ($arguments as $arg) {
            array_push($this->arguments, new LuaValue($arg));
        }
    }
}
?>

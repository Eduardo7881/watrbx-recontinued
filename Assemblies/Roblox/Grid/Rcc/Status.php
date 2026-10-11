<?php

namespace Roblox\Grid\Rcc;

class Status {
    /* Variables */
    public $version;
    public $environmentCount;

    /* Constructor */
    function __construct($version, $environmentCount) {
        $this->version = $version;
        $this->environmentCount = $environmentCount;
    }
}
?>

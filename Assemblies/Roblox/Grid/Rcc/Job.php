<?php

namespace Roblox\Grid\Rcc;

class Job {
    /* Variables */
    public $id;
    public $expirationInSeconds;
    public $category;
    public $cores;

    /* Constructor */
    function __construct($id, $expirationInSeconds = 10, $category = 0, $cores = 1) {
        $this->id = $id;
        $this->expirationInSeconds = $expirationInSeconds;
        $this->category = $category;
	$this->cores = $cores;
    }
}

?>

<?php
/* My first php script yay */

namespace rcc;
use Roblox\Grid\Rcc;

/* Class to make it easier to manage RCCServiceSoap */
class RCCv2 {
    /* Variables */
    private $RCC;
    private $ip;
    private $port;
    private $baseUrl;
    private $currentJobs;

    /* Constructor */
    function __construct($ip = "127.0.0.1", $port = 64989, $url = "http://localhost:8080") {
        $this->RCC = new RCCServiceSoap($ip, $port);
        $this->ip = $ip;
        $this->port = $port;
        $this->baseUrl = $url;
        $this->currentJobs = [];
    }

    /* Methods */

    /* Creates a new job by name, with a optional script to run when it's created. */
    public function CreateJob($jobName) {
        if (isset($this->currentJobs[$jobName])) {
            return $this->currentJobs[$jobName];
        }

        $job = new Job($jobName);
        $this->currentJobs[$jobName] = $job;
        return $job;
    }

    /* Closes a job by name */
    public function CloseJobByName($jobName) {
        foreach ($this->currentJobs as $name => $job) {
            if ($name == $jobName) { $this->RCC->CloseJob($job); return true; }
        }
        return false; // didn't exist
    }

    /* Closes a job by it's handle */
    public function CloseJob($job) {
        if ($job == null) return false;

        foreach ($this->currentJobs as $name => $jobB) {
            if ($jobB == $job) { unset($this->currentJobs, $name); } // removes the entry there
        }

        $this->RCC->CloseJob($job);
        return true;
    }
   
    /* Executes a script on a job and returns the result */
    public function ExecuteScript($job, $script) {
        if (gettype($job) === "string") {
            $job = $this->CreateJob($jobName);
        }

        return $this->RCC->BatchJob($job, $script);
    }

    /* Renders a player's avatar and returns a PNG thumbnail */
    public function GeneratePlayerThumbnail($userId) {
        $jobId = rand(1, getrandmax());
        $job = $this->OpenJob($jobId);

        $script = file_get_contents("../../storage/lua/avatarRenderer.lua");
        $script = str_replace("%target%", $userId, $script);
        $script = str_replace("%baseurl%", $this->baseUrl, $script);

        $scriptExec = new ScriptExecution("RenderScript", $script);
        $render = $this->ExecuteScript($job, $scriptExec);
        $this->CloseJob($jobId); // clean up

        return $render;
    }

    /* Renders a model and returns a PNG thumbnail */
    public function GenerateAssetThumbnail($assetId) {
        $jobId = rand(1, getrandmax());
        $job = $this->OpenJob($jobId);

        $script = file_get_contents("../../storage/lua/assetRenderer.lua");
        $script = str_replace("%assetId%", $assetId, $script);
        $script = str_replace("%baseurl%", $this->baseUrl, $script);

        $scriptExec = new ScriptExecution("RenderScript", $script);
        $render = $this->ExecuteScript($job, $scriptExec);
        $this->CloseJob($jobId); // clean up

        return $render;
    }

    /* Getters */
    public function getRCC() { return $this->RCC; } // Returns the RCCServiceSoap instance.
    public function getIP() { return $this->ip; } // Returns the IP.
    public function getPort() { return $this->port; } // Returns the port.
    public function getURL() { return $this->baseUrl; } // Returns the current BaseUrl that's being used for thumbnails and renderers.

    /* Setters */
    public function setURL($newUrl) { $this->baseUrl = $newUrl; } // Sets the BaseUrl to a new one.
}

?>

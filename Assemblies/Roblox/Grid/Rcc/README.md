# PHP-RCC
A PHP framework for RCCService SOAP interaction.

A PHP autoloader is recommended in order to utilize this framework.

Further documentation can be found inside the source files.

###### Example Code
```php
$RCCServiceSoap = new Roblox\Grid\Rcc\RCCServiceSoap("127.0.0.1", 64989);

echo("HelloWorld(): " .($RCCServiceSoap->HelloWorld() ?? "Failed!"). "\n");
echo("GetVersion(): " .($RCCServiceSoap->GetVersion() ?? "Failed!"). "\n");
echo("GetStatus(): " .($RCCServiceSoap->GetStatus() ?? "Failed!"). "\n");

print_r($RCCServiceSoap->GetAllJobs());
//echo("GetAllJobs(): " .($RCCServiceSoap->GetAllJobs() ?? "Failed!"). "\n");

$job = new Roblox\Grid\Rcc\Job("StringTest1");
$scriptText = 'print("Received job with ID " .. game.JobId)
return "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua."';
$script = new Roblox\Grid\Rcc\ScriptExecution("StringTest1-Script", $scriptText);
$value = $RCCServiceSoap->BatchJob($job, $script);
echo("StringTest1: ", .(!is_soap_fault($value) ? ($value !== null ? $value : "null") : "Failed!")."\n");
```

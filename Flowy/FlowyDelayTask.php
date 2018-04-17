<?php
namespace Flowy;

use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\PluginTask;

if(!class_exists('Flowy\FlowyDelayTask')){

class FlowyDelayTask extends PluginTask{
	function __construct(PluginBase $owner){
		$this->owner = $owner;
	}

	function onRun(int $tick){
		$this->owner->handleDelay($this->getTaskId());
	}
}

}// class_exists
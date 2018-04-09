<?php
namespace Flowy;

use pocketmine\scheduler\PluginTask;
use pocketmine\plugin\PluginBase;

if(!class_exists('Flowy\FlowyDelayTask')){

class FlowyDelayTask extends PluginTask{
	public function __construct(PluginBase $owner){
		if(!$owner instanceof Flowy)
			throw new FlowyException("FlowyDelayTaskはFlowy以外からは起動できません");
		$this->owner = $owner;
	}

	public function onRun(int $tick){
		$this->owner->handleDelay($this->getTaskId());
	}
}

}// class_exists
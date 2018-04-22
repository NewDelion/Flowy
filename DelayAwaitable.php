<?php
namespace Flowy;

if(!class_exists('Flowy\DelayAwaitable')){

class DelayAwaitable extends Awaitable{
	/** @var int */
	protected $delay;

	/** @var int */
	protected $taskId;

	public function setDelay(int $delay){
		if($delay <= 0)
			throw new FlowyException("Tick ​​number must be a value of 1 or more.");
		$this->delay = $delay;
	}

	public function getDelay(){
		return $this->delay;
	}

	public function setTaskId(int $taskId){
		if($taskId === -1)
			throw new FlowyException("Invalid TaskId({$taskId})");
		$this->taskId = $taskId;
	}

	public function getTaskId(){
		return $this->taskId;
	}
}

}// class_exists
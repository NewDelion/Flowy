<?php
namespace Flowy\Awaitable;

if(!class_exists('Flowy\DelayAwaitable')){

class DelayAwaitable extends Awaitable{

	/** @var int */
	public $delay;

	/** @var int */
	public $taskId;

	public function setDelay(int $delay) : void{
		if($delay <= 0)
			throw new FlowyException("tick数は1以上の値を指定してください");
		$this->delay = $delay;
	}

	public function getDelay() : int{
		return $this->delay;
	}

	public function setTaskId(int $taskId) : void{
		if($taskId === -1)
			throw new FlowyException("無効なTaskId({$taskId})");
		$this->taskId = $taskId;
	}

	public function getTaskId() : int{
		return $this->taskId;
	}
}

}// class_exists
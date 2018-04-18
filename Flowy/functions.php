<?php
namespace Flowy;

if(!function_exists('Flowy\listen')){

function listen(string ...$events){
	if(count($events) === 0)
		throw new FlowyException("Please specify at least one event to wait.");
	$awaitable = new ListenAwaitable();
	foreach($events as $event)
		$awaitable->addListenTarget($event);
	return $awaitable;
}

function delay(int $delay){
	$awaitable = new DelayAwaitable();
	$awaitable->setDelay($delay);
	return $awaitable;
}

function done(){
	return new FlowDone();
}

function cancel(){
	return new FlowCancel();
}

}// function_exists
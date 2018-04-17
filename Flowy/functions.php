<?php
namespace Flowy;

if(!function_exists('Flowy\listen')){

function listen(string ...$events){
	if(count($events) === 0)
		throw new FlowyException("listenするイベントを1つ以上指定してください");
	$awaitable = new ListenAwaitable();
	foreach($events as $event)
		$awaitable->addListenTarget($event);
	return $awaitable;
}

function done(){
	return new FlowDone();
}

function cancel(){
	return new FlowCancel();
}

}// function_exists
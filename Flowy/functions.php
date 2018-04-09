<?php
namespace Flowy;

if(!function_exists('Flowy\listen') && !function_exists('Flowy\delay')){

function listen(string ...$events){
	if(count($events) === 0)
		throw new FlowyException("listenするイベントを1つ以上指定してください");
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

}// function_exists
<?php namespace Illuminate\Events; use Closure;

class Dispatcher {

	/**
	 * All of the registered events.
	 *
	 * @var array
	 */
	protected $events = array();

	/**
	 * All of the queued event payloads.
	 *
	 * @var array
	 */
	protected $queued = array();

	/**
	 * Register an evenet listener.
	 *
	 * @param  string  $event
	 * @param  mixed   $callable
	 * @return void
	 */
	public function listen($event, $callable)
	{
		if ( ! is_callable($callable))
		{
			throw new \InvalidArgumentException("Event listener must be callable.");
		}

		$this->events[$event][] = $callable;
	}

	/**
	 * Override all other registered event listeners.
	 *
	 * @param  string  $event
	 * @param  mixed   $callable
	 * @return void
	 */
	public function override($event, $callable)
	{
		if ( ! is_callable($callable))
		{
			throw new \InvalidArgumentException("Event listener must be callable.");			
		}

		$this->events[$event] = array($callable);
	}

	/**
	 * Add a payload to an event queue.
	 *
	 * @param  string  $queue
	 * @param  string  $key
	 * @param  array   $payload
	 * @return void
	 */
	public function queue($queue, $key, array $payload = array())
	{
		$this->queued[$queue][$key] = $payload;
	}

	/**
	 * Register an event "flusher" to handles the flushing of a queue.
	 *
	 * @param  string   $queue
	 * @param  Closure  $callable
	 * @return void
	 */
	public function flusher($queue, $callable)
	{
		if ( ! is_callable($callable))
		{
			throw new \InvalidArgumentException("Event listener must be callable.");
		}

		$this->flushers[$queue][] = $callable;
	}

	/**
	 * Run an event flusher for all of the payloads in a given queue.
	 *
	 * @param  string  $queue
	 * @return void
	 */
	public function flush($queue)
	{
		foreach ($this->flushers[$queue] as $flusher)
		{
			if ( ! isset($this->queued[$queue]))
			{
				return;
			}

			// We will simply spin through each payload registered for the event and
			// fire the flusher, passing each payloads as we go. This allows all
			// the events on teh queue to be processed by the flusher easily.
			foreach ($this->queued[$queue] as $key => $payload)
			{
				array_unshift($payload, $key);

				call_user_func_array($flusher, $payload);
			}
		}
	}

	/**
	 * Get the first event listener response.
	 *
	 * @param  string  $event
	 * @param  array   $payload
	 * @return mixed
	 */
	public function first($event, array $payload = array())
	{
		return $this->fire($event, $payload, true);
	}

	/**
	 * Fire all of the listeners for a given event.
	 *
	 * @param  string  $event
	 * @param  array   $payload
	 * @param  bool    $halt
	 * @return array
	 */
	public function fire($event, array $payload = array(), $halt = false)
	{
		$responses = array();

		foreach ($this->events[$event] as $callable)
		{
			$response = call_user_func_array($callable, $payload);

			// If the response is not null and halting is enabled, we will stop
			// firing the events and return the response. This allows us to
			// get just the first valid response from an event listener.
			if ( ! is_null($response) and $halt)
			{
				return $response;
			}

			$responses[] = $response;
		}

		return $responses;
	}

}
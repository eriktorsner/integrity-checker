<?php
namespace integrityChecker;

/**
 * Class Log
 * A simple psr-3 logging class adopted for WordPress.
 *
 * @package integrityChecker
 */
class Log
{
	const EMERGENCY = 'emergency';
	const ALERT     = 'alert';
	const CRITICAL  = 'critical';
	const ERROR     = 'error';
	const WARNING   = 'warning';
	const NOTICE    = 'notice';
	const INFO      = 'info';
	const DEBUG     = 'debug';

	/**
	 * @var int[]
	 */
	protected static $rankings = array(
		self::DEBUG     => 7,
		self::INFO      => 6,
		self::NOTICE    => 5,
		self::WARNING   => 4,
		self::ERROR     => 3,
		self::CRITICAL  => 2,
		self::ALERT     => 1,
		self::EMERGENCY => 0,
	);

	/**
	 * @var callable
	 * @see Echolog::defaultMessageFormatter()
	 */
	private $messageFormatter;

	/**
	 * @var string
	 */
	private $level;

	/**
	 * @param string $level
	 */
	public function __construct($level = self::DEBUG)
	{
		$this->level = $level;
		$this->messageFormatter = array($this, 'defaultMessageFormatter');
	}


	/**
	 * System is unusable.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function emergency($message, array $context = array())
	{
		$this->log(self::EMERGENCY, $message, $context);
	}

	/**
	 * Action must be taken immediately.
	 *
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function alert($message, array $context = array())
	{
		$this->log(self::ALERT, $message, $context);
	}

	/**
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function critical($message, array $context = array())
	{
		$this->log(self::CRITICAL, $message, $context);
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function error($message, array $context = array())
	{
		$this->log(self::ERROR, $message, $context);
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function warning($message, array $context = array())
	{
		$this->log(self::WARNING, $message, $context);
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function notice($message, array $context = array())
	{
		$this->log(self::NOTICE, $message, $context);
	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function info($message, array $context = array())
	{
		$this->log(self::INFO, $message, $context);
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function debug($message, array $context = array())
	{
		$this->log(self::DEBUG, $message, $context);
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed $level
	 * @param string $message
	 * @param array $context
	 */
	public function log($level, $message, array $context = array())
	{
		if (self::$rankings[$level] <= self::$rankings[$this->level]) {
			$formatter = $this->messageFormatter;
			$logMessage =  $formatter($level, $message, $context);

			// write it somewhere...
		}
	}

	/**
	 * @param  string $level
	 * @param  string $message
	 * @param  array  $context
	 * @return string
	 */
	private function defaultMessageFormatter($level, $message, array $context = array())
	{
		$message = sprintf(
			'[%s] %s %s',
			date('Y-m-d H:i:s'),
			strtoupper($level),
			$this->interpolate($message, $context)
		);

		return $message . PHP_EOL;
	}

	/**
	 * Interpolates context values into the message placeholders.
	 *
	 * Builds a replacement array with braces around the context keys.
	 * It replaces {foo} with the value from $context['foo'].
	 *
	 * @param  string $message
	 * @param  array  $context
	 * @return string
	 */
	private function interpolate($message, array $context = array())
	{
		$replaces = array();
		foreach ($context as $key => $val) {
			if (is_bool($val)) {
				$val = '[bool: ' . (int) $val . ']';
			} elseif (is_null($val)
			          || is_scalar($val)
			          || ( is_object($val) && method_exists($val, '__toString') )
			) {
				$val = (string) $val;
			} elseif (is_array($val) || is_object($val)) {
				$val = @json_encode($val);
			} else {
				$val = '[type: ' . gettype($val) . ']';
			}
			$replaces['{' . $key . '}'] = $val;
		}
		return strtr($message, $replaces);
	}

}
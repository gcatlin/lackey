<?php

class LoggerTest extends PHPUnit_Framework_TestCase {
	public function setup() {
		$this->log_file = tempnam(sys_get_temp_dir(), __CLASS__ . '-');
		$this->logger = new Logger($this->log_file);
		$this->logger->setLevel(Logger::Trace);
	}
	
	public function teardown() {
		unlink($this->log_file);
	}
	
	public function test_LoggingCanBeDisabled() {
		$this->logger->log(Logger::Off, 'test 1');
		self::assertNotContains('test 1', $this->getLog());

		$this->logger->setLevel(Logger::Off);
		$this->logger->debug('test 2');
		self::assertNotContains('test 2', $this->getLog());

		$this->logger->setLevel(Logger::Debug);
		$this->logger->debug('test 3');
		self::assertContains('test 3', $this->getLog());
	}

	public function test_NewlineAppendedToMessages() {
		$this->logger->warn('test');
		self::assertContains("\n", $this->getLog());
	}
	
	public function test_ArgumentsMergedIntoMessage() {
		$this->logger->info('this %s a %s', 'is', 'test');
		self::assertContains('this is a test', $this->getLog());
	}
	
	public function test_CallbackArgumentsExecutedAndMergedIntoMessage() {
		$this->logger->info('%s', array('strtoupper', 'test'));
		self::assertContains('TEST', $this->getLog());
	}
	
	public function test_InvalidCallbackArgumentsMergesParametersIntoMessage() {
		$this->logger->info('%s', array('invalid callback', array('test1', 'test2')));
		self::assertContains('test1 test2', $this->getLog());
	}
	
	public function test_DatePrependedToMessage() {
		$this->logger->error('test');
		self::assertContains(date(Logger::DefaultDateFormat), $this->getLog(), 'Might have failed due to time sensitivity');
	}

	public function test_CustomDateFormat() {
		$format = 'F j, Y';
		$this->logger->setDateFormat($format);
		$this->logger->error('test');
		self::assertContains(date($format), $this->getLog());
	}

	public function test_LogLevelPrependedToMessage() {
		$this->logger->error('test');
		self::assertContains('ERROR', $this->getLog());
	}

	public function test_InvalidLogLevelsAreIgnored() {
		$this->logger->log('invalid_level', 'test');
		self::assertNotContains('test', $this->getLog());
	}
	
	public function test_LowerLevelMessagesAreIgnored() {
		$this->logger->setLevel(Logger::Error);
		$this->logger->debug('test');
		self::assertNotContains('test', $this->getLog());
	}
	
	public function test_HigherLevelMessagesAreNotIgnored() {
		$this->logger->setLevel(Logger::Trace);
		$this->logger->warn('test');
		self::assertContains('test', $this->getLog());
	}
	
	public function test_CheckLogLevel() {
		$this->logger->setLevel(Logger::Error);
		self::assertEquals(Logger::Error, $this->logger->getLevel());
	}
	
	public function test_NullFirstParameterAccepted() {
		$this->logger->error('test %s%s', null, 1);
		self::assertContains('test 1', $this->getLog());
	}
	
	protected function getLog() {
		return file_get_contents($this->log_file);
	}
}
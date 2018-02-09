<?php

namespace eiriksm\CosyComposer;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\Output;

class ArrayOutput extends Output
{

    private $lines;

    private $delta;

    public function clear()
    {
        $this->lines = [];
        $this->delta = 0;
    }

    public function fetch()
    {
        return $this->lines;
    }

    public function __construct($verbosity = self::VERBOSITY_NORMAL, $decorated = false, $formatter = null)
    {
        parent::__construct($verbosity, $decorated, $formatter);
        $this->lines = [];
        $this->delta = 0;
    }

  /**
   * Writes a message to the output.
   *
   * @param string $message A message to write to the output
   * @param bool $newline Whether to add a newline or not
   */
    protected function doWrite($message, $newline)
    {
        if (empty($this->lines[$this->delta])) {
            $this->lines[$this->delta] = [];
        }
        if ($message) {
            $this->lines[$this->delta][] = trim($message);
        }
        if ($newline) {
            $this->delta++;
        }
    }
}

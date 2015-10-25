<?php
abstract class Report implements Iterator {
    protected $db;
    protected $period;
    protected $commission;

    protected $items = [];
    protected $position = 0;

    abstract protected function run();

    public function __construct($db, $period = 12, $commission = 0.10) {
        $this->db         = $db;
        $this->period     = $period;
        $this->commission = $commission;
    }

    protected function add($month, $bookers, $bookings, $turnover, $LTV) {
        $this->items[] = new Month($month, $bookers, $bookings, $turnover, $LTV);
    }

    public function debug() {
        var_dump($this->items);
    }

    /* Iterator implementation */
    public function current() {
        return $this->items[$this->position];
    }

    public function key() {
        return $this->position;
    }

    public function next() {
        return ++$this->position;
    }

    public function valid() {
        return isset($this->items[$this->position]);
    }

    public function rewind() {
        $this->position = 0;
    }
}
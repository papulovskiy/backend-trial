<?php
class Month {
    public $month;
    public $label;
    public $bookers;
    public $bookings;
    public $turnover;
    public $LTV;

    public function __construct($month, $bookers, $bookings, $turnover, $LTV) {
        $this->month    = $month;
        $this->label    = date("M Y", strtotime($month));
        $this->bookers  = $bookers;
        $this->bookings = $bookings;
        $this->turnover = $turnover;
        $this->LTV      = $LTV;
    }
}
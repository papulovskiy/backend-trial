<?php
class CodeReport extends Report {
    /*
     * Here it might be a good idea to split this "huge" method to several,
     * but I think it's okay to do in case of growing complexity and not now.
     */
    public function run() {
        $report   = [];
        $bookers  = [];
        $bookings = [];
        $items    = [];


        // Load all bookings
        $bookings_raw = $this->db->prepare('SELECT * FROM bookings')->run();
        foreach($bookings_raw as $b) {
            $bookings[$b->id] = $b;
        }


        // Load all bookingitems
        $items_raw = $this->db->prepare('SELECT * FROM bookingitems')->run();
        foreach($items_raw as $item) {
            $booking = $bookings[$item->booking_id];
            if(!isset($bookers[$booking->booker_id])) {
                $bookers[$booking->booker_id] = [
                    'first_booking' => null,
                    'bookings'      => [],
                    'sum'           => []
                ];
            }
            $booker = &$bookers[$booking->booker_id];

            // Saving first booking
            if($booker['first_booking'] > $item->end_timestamp || is_null($booker['first_booking'])) {
                $booker['first_booking'] = $item->end_timestamp;
            }

            // I'd consider here that "average number of bookings" in the report is actually number of bookings, not items
            $day = (int) date("Ymd", $item->end_timestamp); // for grouping
            $booker['bookings'][$day][$booking->id] = 1;
            if(!isset($booker['sum'][$day])) {
                $booker['sum'][$day] = 0;
            }
            $booker['sum'][$day] += $item->locked_total_price;
        }


        // Mix it!
        $today = (int) date("Ym");
        $maximum_first_date = (new DateTime())
                                ->sub(new DateInterval('P' . $this->period . 'M'))
                                ->modify('midnight')
                                ->format("U");
        foreach($bookers as $id => $booker) {
            if($booker['first_booking'] >= $maximum_first_date) {
                continue;
            }
            $maximum_day = (new DateTime('@' . $booker['first_booking']))
                                ->add(new DateInterval('P' . $this->period . 'M'))
                                ->format("Ymd");
            $report_month = date("Y-m", $booker['first_booking']);
            $booker_bookings = [];
            $booker_sum = 0;
            // Filtering only bookings for period
            foreach($booker['bookings'] as $day => $bkngs) {
                if($day <= $maximum_day) {
                    foreach($bkngs as $id => $v) {
                        $booker_bookings[$id] = $v;
                    }
                    $booker_sum += $booker['sum'][$day];
                }
            }
            $report[$report_month]['bookers'][$id] = [ 'bookings' => $booker_bookings, 'sum' => $booker_sum ];
        }


        // Generate a report
        $result = [];
        $rows = array_keys($report);
        sort($rows);
        foreach($rows as $month) {
            $bookers_count  = count($report[$month]['bookers']);
            $bookings_count = array_sum(array_map(function($i) { return count($i['bookings']); }, $report[$month]['bookers']));
            $bookings_sum   = array_sum(array_map(function($i) { return $i['sum']; }, $report[$month]['bookers']));
            $this->add(
                        $month,
                        $bookers_count,
                        $bookers_count  > 0 ? ($bookings_count/$bookers_count) : 0,
                        $bookings_count > 0 ? ($bookings_sum/$bookings_count) : 0,
                        $bookings_count > 0 ? ($this->commission * $bookings_sum/$bookings_count) : 0
            );
        }
    }
}
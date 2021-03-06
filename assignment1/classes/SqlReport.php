<?php
class SqlReport extends Report {
    public function run() {
        $sql = <<<SQL
        SELECT
            month,
            count(booker_id) as bookers,
            avg(bookings_count) as number_of_bookings,
            sum(total_price)/sum(bookings_count) as turnover,
            $this->commission*sum(total_price)/sum(bookings_count) as LTV
        FROM
            (
                SELECT
                    by_month.booker_id,
                    month,
                    count(DISTINCT booking_id) as bookings_count,
                    sum(locked_total_price) as total_price
                FROM
                    (   -- Selecting bookers with first booking month
                        SELECT
                            booker_id,
                            min(i.end_timestamp) as first_booking_timestamp,
                            strftime('%Y-%m', datetime(min(i.end_timestamp), 'unixepoch')) as month
                        FROM
                            bookings b
                        JOIN
                            bookingitems i
                        ON (i.booking_id=b.id)
                        GROUP BY
                            b.booker_id
                    ) as by_month
                JOIN
                    bookings b
                    ON (b.booker_id = by_month.booker_id)
                JOIN
                    bookingitems i
                    ON (i.booking_id=b.id AND i.end_timestamp <= strftime('%s', date(datetime(by_month.first_booking_timestamp, 'unixepoch'), '+$this->period month', 'start of day', '+1 day')))
                WHERE
                    datetime(by_month.first_booking_timestamp, 'unixepoch') < date('now', '-$this->period month')
                GROUP BY
                    by_month.booker_id,
                    month
            ) as grouped
        GROUP BY
            month
        ORDER BY
            month
SQL;
        $result = $this->db->prepare($sql)->run();
        foreach($result as $item) {
            $this->add($item->month, $item->bookers, $item->number_of_bookings, $item->turnover, $item->LTV);
        }
    }
}
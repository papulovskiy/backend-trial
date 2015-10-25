<?php

// Load database connection, helpers, etc.
require_once(__DIR__ . '/errors.php');
require_once(__DIR__ . '/include.php');

$periods     = [3, 12, 18];
$commissions = [0.10, 0.15];

// Default values
$period = 12; // Life-Time of 12 months
$commission = 0.10; // 10% commission

// Checking arguments
if(isset($_GET['period']) && in_array($_GET['period'], $periods)) {
	$period = (int) $_GET['period'];
}
if(isset($_GET['commission']) && in_array($_GET['commission'], $commissions)) {
	$commission = (float) $_GET['commission'];
}


// Prepare query
$sql = <<<SQL
SELECT
    month,
    count(*) as bookers,
    printf("%.2f", avg(bookings_count)) as number_of_bookings,
    printf("%.2f", avg(total_price)) as turnover,
    printf("%.2f", $commission*avg(total_price)) as LTV
FROM
    (
        SELECT
            by_month.booker_id,
            month,
            count(booking_id) as bookings_count,
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
            ON (i.booking_id=b.id AND i.end_timestamp < strftime('%s', date(datetime(by_month.first_booking_timestamp, 'unixepoch'), '+$period month')))
        GROUP BY
            by_month.booker_id,
            month
    ) as grouped
GROUP BY
    month
ORDER BY
    month
SQL;
$result = $db->prepare($sql)->run();


?>
<!doctype html>
<html>
	<head>
		<title>Assignment 1: Create a Report (SQL)</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<style type="text/css">
			.report-table
			{
				width: 100%;
				border: 1px solid #000000;
			}
			.report-table td,
			.report-table th
			{
				text-align: left;
				border: 1px solid #000000;
				padding: 5px;
			}
			.report-table .right
			{
				text-align: right;
			}
		</style>
	</head>
	<body>
		<form method="get">
			Period: <select name="period"><?php echo implode('', array_map(function($p) use ($period) {
				return sprintf('<option value="%d" %s>%d months</option>', $p, $p === $period ? 'selected' : '', $p);
			}, $periods)); ?></select><br/>
			Commission: <select name="commission"><?php echo implode('', array_map(function($c) use ($commission) {
				return sprintf('<option value="%f" %s>%d %%</option>', $c, $c === $commission ? 'selected' : '', $c*100);
			}, $commissions)); ?></select><br/>
			<button type="submit">Generate</button>
		</form>
		<h1>Report:</h1>
		<table class="report-table">
			<thead>
				<tr>
					<th>Start</th>
					<th>Bookers</th>
					<th># of bookings (avg)</th>
					<th>Turnover (avg)</th>
					<th>LTV</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($result as $index => $row): ?>
					<tr>
						<td><?php echo $row->month;?></td>
						<td><?php echo $row->bookers;?></td>
						<td class="right"><?php echo $row->number_of_bookings;?></td>
						<td class="right"><?php echo $row->turnover;?></td>
						<td class="right"><?php echo $row->LTV;?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="4" class="right"><strong>Total rows:</strong></td>
					<td><?= $index + 1 ?></td>
				</tr>
			</tfoot>
		</table>
	</body>
</html>
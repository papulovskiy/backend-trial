<?php

// Load database connection, helpers, etc.
require_once(__DIR__ . '/errors.php');
require_once(__DIR__ . '/include.php');

date_default_timezone_set('Europe/Amsterdam');

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

$report   = [];
$bookers  = [];
$bookings = [];
$items    = [];
// Load all bookings
$bookings_raw = $db->prepare('SELECT * FROM bookings')->run();
foreach($bookings_raw as $b) {
	$bookings[$b->id] = $b;
	// $bookers[$b->booker_id]['bookings'][] = $b->id;
}

// Load all bookingitems
$items_raw = $db->prepare('SELECT * FROM bookingitems')->run();
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
	$month = (int) date("Ym", $item->end_timestamp); // month here is for grouping by months
	$booker['bookings'][$month][$booking->id] = 1;
	if(!isset($booker['sum'][$month])) {
		$booker['sum'][$month] = 0;
	}
	$booker['sum'][$month] += $item->locked_total_price;
}
// Mix it!
$today = (int) date("Ym");
foreach($bookers as $id => $booker) {
	$first_month  = (int) date("Ym", $booker['first_booking']);
	if($today - $first_month < $period) {
		continue;
	}
	$report_month = date("Y-m", $booker['first_booking']);
	$booker_bookings = 0;
	$booker_sum = 0;
	// Filtering only bookings for period
	foreach($booker['bookings'] as $month => $bkngs) {
		if($month - $first_month <= $period) {
			$booker_bookings += count($bkngs);
			$booker_sum += $booker['sum'][$month];
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
	$bookings_count = array_sum(array_map(function($i) { return $i['bookings']; }, $report[$month]['bookers']));
	$bookings_sum   = array_sum(array_map(function($i) { return $i['sum']; }, $report[$month]['bookers']));
	$result[] = [
		'month'    => $month,
		'label'    => date("M Y", strtotime($month)),
		'bookers'  => $bookers_count,
		'bookings' => $bookers_count  > 0 ? sprintf("%.2f", $bookings_count/$bookers_count) : 0,
		'turnover' => $bookings_count > 0 ? sprintf("%.2f", $bookings_sum/$bookings_count) : 0,
		'LTV'      => $bookings_count > 0 ? sprintf("%.2f", $commission * $bookings_sum/$bookings_count) : 0,
	];
}

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
						<td><?php echo $row['label']?></td>
						<td><?php echo $row['bookers']?></td>
						<td class="right"><?php echo $row['bookings']?></td>
						<td class="right"><?php echo $row['turnover']?></td>
						<td class="right"><?php echo $row['LTV']?></td>
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
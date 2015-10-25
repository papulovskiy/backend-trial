<?php

// Load database connection, helpers, etc.
require_once(__DIR__ . '/errors.php');
require_once(__DIR__ . '/include.php');

require_once(__DIR__ . '/classes/Month.php');
require_once(__DIR__ . '/classes/Report.php');

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
$result = $db
	->prepare('
		SELECT * FROM bookings LIMIT 3
	')
	->run()
;
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
						<td>TODO</td>
						<td>TODO</td>
						<td>TODO</td>
						<td>TODO</td>
						<td>TODO</td>
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
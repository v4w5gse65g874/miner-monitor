<?php

define('SLUSH_APIKEY', '415529-0e5b82d2f4491e52172fe7ef79a90122');
define('GIVEMECOINS_APIKEY', '0e84d188e9f4d76e863e306f346398b2144ba483d61cfc97a409225d407b4ac3');

function get_data($url)
{
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$cr = curl_exec($ch);
	curl_close($ch);

	return json_decode($cr, true);
}

//need to make the data serialized for the foreach in the html
function massage_slush($o)
{
	$o['data']['total_hashrate'] = $o['data']['hashrate'];
	$o['data']['round_estimate'] = $o['data']['unconfirmed_reward'];
	$o['data']['confirmed_rewards'] = $o['data']['confirmed_reward'];

	foreach($o['data']['workers'] as $k => $v)
	{
		$o['data']['workers'][$k]['last_share_timestamp'] = $o['data']['workers'][$k]['last_share'];
	}

	return $o;
}

function total_hashrate($o)
{
	$p = '';

	switch($o['name'])
	{
	case 'Slush Pool':
		$p = (($o['data']['total_hashrate'] / 1000) . ' GH/s');
		break;
	case 'Give Me Coins Pool':
		$p = (($o['data']['total_hashrate'] / 1000) . ' MH/s');
		break;
	}

	return $p;
}

function worker_hashrate($o, $name)
{
	$p = '';

	switch($name)
	{
	case 'Slush Pool':
		$p = ($o['hashrate'] / 1000) . ' GH/s';
		break;
	case 'Give Me Coins Pool':
		$p = $o['hashrate'] . ' KH/s';
		break;
	}

	return $p;
}

$output = array(
	array(
		'name' => 'Slush Pool',
		'label' => 'BTC',
		'data' => get_data('https://mining.bitcoin.cz/accounts/profile/json/' . SLUSH_APIKEY)
	),
	array(
		'name' => 'Give Me Coins Pool',
		'label' => 'LTC',
		'data' => get_data('https://give-me-coins.com/pool/api-ltc?api_key=' . GIVEMECOINS_APIKEY)
	)
);

$output[0] = massage_slush($output[0]);

foreach($output as $vv)
{
	$vv['found'] = 1;
	foreach($vv['data']['workers'] as $k => $v)
	{
		if((bool)$v['alive'] === false)
		{
			$vv['found'] = 0;
			break;
		}
	}
}

?>
<html>
	<head>
		<link href="assets/css/bootstrap.min.css" rel="stylesheet">
		<script src="assets/js/jquery-latest.js"></script>
		<script src="assets/js/bootstrap.min.js"></script>
	</head>
	<body>
		<div class="container">
			<?php foreach($output as $o) { ?>
			<h1><?php echo $o['name']; ?></h1>
			<table class="table">
				<thead>
					<tr>
						<th>Total Hashrate</th>
						<th>Unconfirmed reward</th>
						<th>Confirmed reward</th>
					</tr>
				</thead>
				<tbody>
					<tr class="<?php echo ($o['found'] === 0) ? 'warning' : 'success'; ?>">
						<td><?php echo total_hashrate($o); ?></td>
						<td><?php echo $o['data']['round_estimate'] . ' ' . $o['label']; ?></td>
						<td><?php echo $o['data']['confirmed_rewards'] . ' ' . $o['label']; ?></td>
					</tr>
				</tbody>
			</table>
			<div class="col-md-offset-1">
				<table class="table">
					<thead>
						<tr>
							<th>Worker Name</th>
							<th>Alive</th>
							<th>Worker Hashrate</th>
							<th>Last Share Time</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach($o['data']['workers'] as $k => $v) { ?>
						<tr class="<?php echo ((bool)$v['alive'] === true && (int)$v['hashrate'] > 0) ? 'success' : 'danger'; ?>">
							<td><?php echo $k; ?></td>
							<td><?php echo $v['alive']; ?></td>
							<td><?php echo worker_hashrate($v, $o['name']); ?></td>
							<td><?php echo date("F j, Y, g:i a", $v['last_share_timestamp']); ?></td>
						</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
			<?php } ?>
		</div>
	</body>
</html>

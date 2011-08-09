<?php

/**
 * This file is part of Linfo (c) 2010 Joseph Gillotti.
 * 
 * Linfo is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * Linfo is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Linfo.  If not, see <http://www.gnu.org/licenses/>.
 * 
*/

/**
 * Keep out hackers...
 */
defined('IN_INFO') or exit;

/**
 * Show it all... in very minimal HTML
 * @param array $info the system information
 * @param array $settings linfo settings
 */
function showInfoHTML($info, $settings) {

	// Gain access to language array
	global $lang;

	// Fun icons
	$show_icons = array_key_exists('icons', $settings) ? !empty($settings['icons']) : true;
	$os_icon = defined('IS_WINDOWS') ? 'windows' : strtolower(str_replace(' ', '', current(explode('(', $info['OS']))));
	$distro_icon = $info['OS'] == 'Linux' && is_array($info['Distro']) && $info['Distro']['name'] ? strtolower(str_replace(' ', '', $info['Distro']['name'])) : false;

	// Start compressed output buffering
	if (!isset($settings['compress_content']) || $settings['compress_content']) {
		ob_start('ob_gzhandler');
	}

	// Proceed to letting it all out
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>'.AppName.' - '.$info['HostName'].'</title>
	<link href="'.WEB_PATH.'layout/favicon.ico" type="image/x-icon" rel="shortcut icon" />
	<link href="'.WEB_PATH.'layout/styles.css" type="text/css" rel="stylesheet" />'.( $show_icons ? '
	<link href="'.WEB_PATH.'layout/icons.css" type="text/css" rel="stylesheet" />' : ''
	).'
	<script src="'.WEB_PATH.'layout/scripts.min.js" type="text/javascript"></script>
	<meta name="generator" content="'.AppName.' ('.VERSION.')" />
	<meta name="author" content="Joseph Gillotti &amp; friends" />
	<!--[if lt IE 8]>
	<link href="'.WEB_PATH.'layout/old_ie.css" type="text/css" rel="stylesheet" />
	<![endif]-->
</head>
<body id="info">
<div class="header">
	<h1>'.$info['HostName'].'</h1>
	<div class="subtitle">'.$lang['header'].'</div>
</div>
<!--<div class="time">
	<h1>'.date('H:i:s').'</h1>
	<div class="subtitle">'.date('Y-m-d, T').'</div>
</div>-->
<br clear="all" />
<div class="col2">
	<div class="col">
		<div class="infoTable">
			<h2>'.$lang['core'].'</h2>
			<table>';
			
	// Linfo Core. Decide what to show.
	$core = array();

	// OS? (with icon, if we have it)
	if (!empty($settings['show']['os']))
		$core[] = array($lang['os'], ($show_icons && (file_exists(LOCAL_PATH . 'layout/icons/os_'.$os_icon.'.gif') || file_exists(LOCAL_PATH . 'layout/icons/os_'.$os_icon.'.png')) ? '<span class="icon icon_os_'.$os_icon.'"></span>' : '') . $info['OS']);
	
	// Distribution? (with icon, if we have it)
	if (!empty($settings['show']['distro']) && is_array($info['Distro']))
		$core[] = array($lang['distro'], ($show_icons && $distro_icon && (file_exists(LOCAL_PATH . 'layout/icons/distro_'.$distro_icon.'.gif') || file_exists(LOCAL_PATH . 'layout/icons/distro_'.$distro_icon.'.png')) ? '<span class="icon icon_distro_'.$distro_icon.'"></span>' : '') . $info['Distro']['name'] . ($info['Distro']['version'] ? ' - '.$info['Distro']['version'] : ''));
	
	// Kernel
	if (!empty($settings['show']['kernel']))
		$core[] = array($lang['kernel'], $info['Kernel']);

	// Model?
	if (!empty($settings['show']['model']) && array_key_exists('Model', $info) && !empty($info['Model']))
		$core[] = array($lang['model'], $info['Model']);

	// IP
	$core[] = array($lang['accessed_ip'], isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : 'Unknown');

	// Uptime
	if (!empty($settings['show']['uptime']))
		$core[] = array($lang['uptime'], $info['UpTime']);
	
	// Hostname
	if (!empty($settings['show']['hostname']))
		$core[] = array($lang['hostname'], $info['HostName']);
	
	// The CPUs
	if (!empty($settings['show']['cpu'])) {
		$cpus = '';
		foreach ((array) $info['CPU'] as $cpu) 
			$cpus .=
				(array_key_exists('Vendor', $cpu) ? $cpu['Vendor'] . ' - ' : '') .
				$cpu['Model'] .
				(array_key_exists('MHz', $cpu) ?
					($cpu['MHz'] < 1000 ? ' ('.$cpu['MHz'].' MHz)' : ' ('.round($cpu['MHz'] / 1000, 3).' GHz)') : '') .
					'<br />';
		$core[] = array('CPUs ('.count($info['CPU']).')', $cpus);
	}

	// CPU architecture. Permissions goes hand in hand with normal CPU
	if (!empty($settings['show']['cpu']) && array_key_exists('CPUArchitecture', $info)) 
		$core[] = array($lang['cpu_arch'], $info['CPUArchitecture']);
	
	// System Load
	if (!empty($settings['show']['load']))
		$core[] = array($lang['load'], implode(' ', (array) $info['Load']));
	
	// We very well may not have process stats
	if (!empty($settings['show']['process_stats']) && $info['processStats']['exists']) {

		// Different os' have different keys of shit
		$proc_stats = array();
		
		// Load the keys
		if (array_key_exists('totals', $info['processStats']) && is_array($info['processStats']['totals']))
			foreach ($info['processStats']['totals'] as $k => $v) 
				$proc_stats[] = $k . ': ' . number_format($v);

		// Total as well
		$proc_stats[] = 'total: ' . number_format($info['processStats']['proc_total']);

		// Show them
		$core[] = array($lang['processes'], implode('; ', $proc_stats));

		// We might not have threads
		if ($info['processStats']['threads'] !== false)
			$core[] = array($lang['threads'], number_format($info['processStats']['threads']));
	}

	// Show
	for ($i = 0, $core_num = count($core); $i < $core_num; $i++) {
		echo '
				<tr>
					<th>'.$core[$i][0].'</th>
					<td>'.$core[$i][1].'</td>
				</tr>
				';
	}

	echo '
			</table>
		</div>';


	// Show memory?
	if (!empty($settings['show']['ram'])) {
		echo '
		<div class="infoTable">
			<h2>'.$lang['memory'].'</h2>
			<table>
				<colgroup>
					<col style="width: 10%;" />
					<col style="width: 30%;" />
					<col style="width: 30%;" />
					<col style="width: 30%;" />
				</colgroup>
				<tr>
					<th>'.$lang['type'].'</th>
					<th>'.$lang['free'].'</th>
					<th>'.$lang['used'].'</th>
					<th>'.$lang['size'].'</th>
				</tr>
				<tr>
					<td>'.$info['RAM']['type'].'</td>
					<td>'.byte_convert($info['RAM']['free']).'</td>
					<td>'.byte_convert($info['RAM']['total'] - $info['RAM']['free']).'</td>
					<td>'.byte_convert($info['RAM']['total']).'</td>
				</tr>';
				$have_swap = (isset($info['RAM']['swapFree']) || isset($info['RAM']['swapTotal']));
				if ($have_swap) {
					// Show detailed swap info?
					$show_detailed_swap = is_array($info['RAM']['swapInfo']) && count($info['RAM']['swapInfo']) > 0;
					echo'
					<tr>
						<td'.($show_detailed_swap ? ' rowspan="2"' : '').'>Swap</td>
						<td>'.byte_convert(@$info['RAM']['swapFree']).'</td>
						<td>'.byte_convert(@$info['RAM']['swapTotal'] - $info['RAM']['swapFree']).'</td>
						<td>'.byte_convert(@$info['RAM']['swapTotal']).'</td>
					</tr>';
					
					// As in we have at least one swap device present. Show them.
					if ($show_detailed_swap) {
						echo '
						<tr>
							<td colspan="3">
								<table class="mini center">
									<colgroup>
										<col style="width: 25%;" />
										<col style="width: 25%;" />
										<col style="width: 25%;" />
										<col style="width: 25%;" />
									</colgroup>
									<tr>
										<th>'.$lang['device'].'</th>
										<th>'.$lang['type'].'</th>
										<th>'.$lang['size'].'</th>
										<th>'.$lang['used'].'</th>
									</tr>';
									foreach($info['RAM']['swapInfo'] as $swap)
										echo '
										<tr>
											<td>'.$swap['device'].'</td>
											<td>'.ucfirst($swap['type']).'</td>
											<td>'.byte_convert($swap['size']).'</td>
											<td>'.byte_convert($swap['used']).'</td>
										</tr>
										';
									echo '
								</table>
							</td>
						</tr>';
					}
				}

				echo '
			</table>
		</div>';
	}

	// Network Devices?
	if (!empty($settings['show']['network'])) {
		$show_type = array_key_exists('nic_type', $info['contains']) ? ($info['contains']['nic_type'] === false ? false : true) : true;
		echo '
		<div class="infoTable">
			<h2>'.$lang['network_devices'].'</h2>
			<table>
				<tr>
					<th>'.$lang['device_name'].'</th>',$show_type ? '
					<th>'.$lang['type'].'</th>' : '','
					<th>'.$lang['amount_sent'].'</th>
					<th>'.$lang['amount_received'].'</th>
					<th>'.$lang['state'].'</th>
				</tr>';

			if (count($info['Network Devices']) > 0)
				foreach($info['Network Devices'] as $device => $stats)
					echo '
				<tr>
					<td>'.$device.'</td>', $show_type ? '
					<td>'.$stats['type'].'</td>' : '','
					<td>'.byte_convert($stats['sent']['bytes']).'</td>
					<td>'.byte_convert($stats['recieved']['bytes']).'</td>
					<td class="net_'.$stats['state'].'">'.ucfirst($stats['state']).'</td>
				</tr>';
			else
				echo '<tr><td colspan="5" class="none">'.$lang['none_found'].'</td></tr>';
			echo '
			</table>
		</div>';
	}

	// Show temps?
	if (!empty($settings['show']['temps']) && count($info['Temps']) > 0) {
		echo '
		<div class="infoTable">
			<h2>'.$lang['temps_voltages'].'</h2>
			<table>
				<tr><th>'.$lang['path'].'</th><th>'.$lang['device'].'</th><th>'.$lang['value'].'</th></tr>
				';
			$num_temps = count($info['Temps']);
			if ($num_temps > 0) {
					for($i = 0; $i < $num_temps; $i++) {
					$stat = $info['Temps'][$i];
					echo '
					<tr>
						<td>'.$stat['path'].'</td>
						<td>'.$stat['name'].'</td>
						<td>'.$stat['temp'].' '.$stat['unit'].'</td>
					</tr>
					';
					}
			}
			else
				echo '<tr><td colspan="3" class="none">'.$lang['none_found'].'</td></tr>';
				echo '
			</table>
		</div>';
	}

	// Show battery?
	if (!empty($settings['show']['battery']) && count($info['Battery']) > 0) {
		echo '
		<div class="infoTable">
			<h2>'.$lang['batteries'].'</h2>
			<table>
				<tr><th>'.$lang['device'].'</th><th>'.$lang['state'].'</th><th>'.$lang['charge'].' %</th></tr>
				';
		foreach ($info['Battery'] as $bat) 
			echo '
					<tr>
						<td>'.$bat['device'].'</td>
						<td>'.$bat['state'].'</td>
						<td>'.$bat['percentage'].($bat['percentage'] < 0 ? ' <span class="caption">(wtf?)</span>' : '').'</td>
					</tr>
					';
		echo '
			</table>
		</div>';
	}

	// Show services?
	if (!empty($settings['show']['services']) && count($info['services']) > 0) {
		echo '
		<div class="infoTable">
			<h2>'.$lang['services'].'</h2>
			<table>
				<tr>
					<th>'.$lang['service'].'</th><th>'.$lang['state'].'</th><th>'.$lang['pid'].'</th><th>Threads</th><th>'.$lang['memory_usage'].'</th>
				</tr>
				';

		// Show them
		foreach ($info['services'] as $service => $state) {
			$state_parts = explode(' ', $state['state'], 2);
			echo '
				<tr>
					<td>'.$service.'</td>
					<td>
						<span class="service_'.strtolower($state_parts[0]).'">'.$state_parts[0].'</span>
						'.(array_key_exists(1, $state_parts) ? '<span class="faded">'.$state_parts[1].'</span>' : '').'</td>
					<td>'.$state['pid'].'</td>
					<td>',$state['threads'] ? $state['threads'] : '?','</td>
					<td>',$state['memory_usage'] ? byte_convert($state['memory_usage']) : '?','</td>
				</tr>
			';
		}

		echo '
			</table>
		</div>';

	}

	echo '
	</div>
	<div class="col">';

	// Show hardware?
	if (!empty($settings['show']['devices'])) {

		// Don't show vendor?
		$show_vendor = array_key_exists('hw_vendor', $info['contains']) ? ($info['contains']['hw_vendor'] === false ? false : true) : true;

		echo '
		<div class="infoTable">
			<h2>'.$lang['hardware'].'</h2>
			<table>
				<tr>
					<th>'.$lang['type'].'</th>
					',($show_vendor ? '<th>'.$lang['vendor'].'</th>' : ''),'
					<th>'.$lang['device'].'</th>
				</tr>
				';
		$num_devs = count($info['Devices']);
		if ($num_devs > 0) {
			for ($i = 0; $i < $num_devs; $i++) {
				echo '
				<tr>
					<td class="center">'.$info['Devices'][$i]['type'].'</td>
					',$show_vendor ? '<td>'.($info['Devices'][$i]['vendor'] ? $info['Devices'][$i]['vendor'] : 'Unknown').'</td>' : '','
					<td>'.$info['Devices'][$i]['device'].'</td>
				</tr>';
			}
		}
		else
			echo '<tr><td colspan="3" class="none">'.$lang['none_found'].'</td></tr>';
		echo '
			</table>
		</div>';
	}

	// Show drives?
	if (!empty($settings['show']['hd'])) {

		// Should we not show the Reads and Writes columns?
		$show_stats = array_key_exists('drives_rw_stats', $info['contains']) ? ($info['contains']['drives_rw_stats'] === false ? false : true) : true;

		// Or vendor columns?
		$show_vendor = array_key_exists('drives_vendor', $info['contains']) ? ($info['contains']['drives_vendor'] === false ? false : true) : true;


		echo '
		<div class="infoTable">
			<h2>Drives</h2>
			<table>
				<tr>
					<th>'.$lang['path'].'</th>
					',$show_vendor ? '<th>'.$lang['vendor'] : '','</th>
					<th>'.$lang['name'].'</th>
					',$show_stats ? '<th>'.$lang['reads'].'</th>
					<th>'.$lang['writes'].'</th>' : '','
					<th>'.$lang['size'].'</th>
				</tr>';
		if (count($info['HD']) > 0)
			foreach($info['HD'] as $drive) {
				echo '
				<tr>
					<td>'.$drive['device'].'</td>
					',$show_vendor ? '<td>'.($drive['vendor'] ? $drive['vendor'] : $lang['unknown']).'</td>' : '','
					<td>',$drive['name'] ? $drive['name'] : $lang['unknown'],'</td>
					', $show_stats ? '<td>'.($drive['reads'] !== false ? number_format($drive['reads']) : $lang['unknown']).'</td>
					<td>'.($drive['writes'] !== false ? number_format($drive['writes']) : $lang['unknown']).'</td>' : '','
					<td>',$drive['size'] ? byte_convert($drive['size']) : $lang['unknown'],'</td>
				</tr>';

				// If we've got partitions for this drive, show them too
				if (is_array($drive['partitions']) && count($drive['partitions']) > 0) {
					echo '
				<tr>
					<td colspan="6">';
					
					// Each
					foreach ($drive['partitions'] as $partition)
						echo '
						&#9492; '. (isset($partition['number']) ? $drive['device'].$partition['number'] : $partition['name']) .' - '.byte_convert($partition['size']).'<br />';

					echo '
					</td>
				</tr>
				';
					}
				}
			else
				echo '<tr><td colspan="6" class="none">'.$lang['none_found'].'</td></tr>';

			echo '
			</table>
		</div>';
	}

	// Show sound card stuff?
	if (!empty($settings['show']['sound']) && count($info['SoundCards']) > 0) {
		echo '
		<div class="infoTable">
			<h2>'.$lang['sound_cards'].'</h2>
			<table>
				<tr>
					<th>'.$lang['number'].'</th>
					<th>'.$lang['vendor'].'</th>
					<th>'.$lang['card'].'</th>
				</tr>';
		foreach ($info['SoundCards'] as $card) {
			if (empty($card['vendor'])) 
				$card['vendor'] = 'Unknown';
			echo '
				<tr>
					<td>'.$card['number'].'</td>
					<td>'.$card['vendor'].'</td>
					<td>'.$card['card'].'</td>
				</tr>';
		}
		echo '
			</table>
		</div>
		';
	}

	echo '
	</div>
</div>';


	// Show file system mounts?
	if (!empty($settings['show']['mounts'])) {
		$has_devices = false;
		$has_labels = false;
		$has_types = false;
		foreach($info['Mounts'] as $mount) {
			if (!empty($mount['device'])) {
				$has_devices = true;
			}
			if (!empty($mount['label'])) {
				$has_labels = true;
			}
			if (!empty($mount['devtype'])) {
				$has_types = true;
			}
		}
		$addcolumns = 0;
		if ($settings['show']['mounts_options'])
			$addcolumns++;
		if ($has_devices)
			$addcolumns++;
		if ($has_labels)
			$addcolumns++;
		if ($has_types)
			$addcolumns++;
		echo '
<div class="infoTable">
	<h2>'.$lang['filesystem_mounts'].'</h2>
	<table>
		<tr>';
		if ($has_types) {
			echo '<th>'.$lang['type'].'</th>';
		}
		if ($has_devices) {
			echo '<th>'.$lang['device'].'</th>';
		}
			echo '<th>'.$lang['mount_point'].'</th>';
		if ($has_labels) {
			echo '<th>'.$lang['label'].'</th>';
		}
		echo'
			<th>'.$lang['filesystem'].'</th>',$settings['show']['mounts_options'] ? '
			<th>'.$lang['mount_options'].'</th>' : '','
			<th>'.$lang['size'].'</th>
			<th>'.$lang['used'].'</th>
			<th>'.$lang['free'].'</th>
			<th style="width: 12%;">'.$lang['percent_used'].'</th>
		</tr>
		';

		// Calc totals
		$total_size = 0;
		$total_used = 0;
		$total_free = 0;
		
		// Don't add totals for duplicates. (same filesystem mount twice in different places)
		$done_devices = array();
		
		// Are there any?
		if (count($info['Mounts']) > 0)

			// Go through each
			foreach($info['Mounts'] as $mount) {

				// Only add totals for this device if we haven't already
				if (!in_array($mount['device'], $done_devices)) {
					$total_size += $mount['size'];
					$total_used += $mount['used'];
					$total_free += $mount['free'];
					if (!empty($mount['device'])) {
						$done_devices[] = $mount['device'];
					}
				}

				// If it's an NFS mount it's likely in the form of server:path (without a trailing slash), 
				// but if the path is just / it likely just shows up as server:,
				// which is vague. If there isn't a /, add one
				if (preg_match('/^.+:$/', $mount['device']) == 1)
					$mount['device'] .= DIRECTORY_SEPARATOR;

				echo '<tr>';
				if ($has_types) {
					echo '<td>'.$mount['devtype'].'</td>';
				}
				if ($has_devices) {
					echo '<td>'.$mount['device'].'</td>';
				}
					echo '<td>'.$mount['mount'].'</td>';
				if ($has_labels) {
					echo '<td>'.$mount['label'].'</td>';
				}
				echo'
					<td>'.$mount['type'].'</td>', $settings['show']['mounts_options'] ? '
					<td>'.(empty($mount['options']) ? '<em>unknown</em>' : '<ul><li>'.implode('</li><li>', $mount['options']).'</li></ul>').'</td>' : '','
					<td>'.byte_convert($mount['size']).'</td>
					<td>'.byte_convert($mount['used']).
					' <span class="perc">('.($mount['used_percent'] !== false ? $mount['used_percent'] : 'N/A').'%)</span></td>
					<td>'.byte_convert($mount['free']).
					' <span class="perc">('.($mount['free_percent'] !== false ? $mount['free_percent'] : 'N/A').'%)</span></td>	
					<td>
						<div class="bar_chart">
							<div class="bar_inner" style="width: '.(int) $mount['used_percent'].'%;">
								<div class="bar_text">
									'.($mount['used_percent'] ? $mount['used_percent'].'%' : 'N/A').'
								</div>
							</div>
						</div>
					</td>
				</tr>';
			}
		else {
			echo '<tr><td colspan="',6 + $addcolumns,'" class="none">None found</td></tr>';
		}

		// Show totals and finish table
		$total_used_perc = $total_size > 0 && $total_used > 0 ?  round($total_used / $total_size, 2) * 100 : 0;
		echo '
		<tr class="alt">
			<td colspan="',2 + $addcolumns,'">Totals: </td>
			<td>'.byte_convert($total_size).'</td>
			<td>'.byte_convert($total_used).'</td>
			<td>'.byte_convert($total_free).'</td>
			<td>
				<div class="bar_chart">
					<div class="bar_inner" style="width: '.$total_used_perc.'%;">
						<div class="bar_text">
							'.$total_used_perc.'%
						</div>
					</div>
				</div>
			</td>
		</tr>
	</table>
</div>';
	}

	// Show RAID Arrays?
	if (!empty($settings['show']['raid']) && count($info['Raid']) > 0) {
		echo '
<div class="infoTable">
	<h2>'.$lang['raid_arrays'].'</h2>
	<table>
		<colgroup>
			<col style="width: 10%;" />
			<col style="width: 30%;" />
			<col style="width: 10%;" />
			<col style="width: 10%;" />
			<col style="width: 30%;" />
			<col style="width: 10%;" />
		</colgroup>
		<tr>
			<th>'.$lang['name'].'</th>
			<th>'.$lang['level'].'</th>
			<th>'.$lang['status'].'</th>
			<th>'.$lang['size'].'</th>
			<th>'.$lang['devices'].'</th>
			<th>'.$lang['active'].'</th>
		</tr>
		';
		if (count($info['Raid']) > 0)
			foreach ($info['Raid'] as $raid) {
				$active = explode('/', $raid['count']);
				// http://en.wikipedia.org/wiki/Standard_RAID_levels
				switch ($raid['level']) {
					case 0:
						$type = 'Stripe';
					break;
					case 1:
						$type = 'Mirror';
					break;
					case 5:
					case 6:
						$type = 'Distributed Parity Block-Level Striping';
					break;
					default:
						$type = false;
					break;
				}
				echo '
				<tr>
				<td>'.$raid['device'].'</td>
				<td>'.$raid['level'].($type ? ' <span class="caption">('.$type.')</span>' : '').'</td>
				<td>'.ucfirst($raid['status']).'</td>
				<td>'.$raid['size'].'</td>
				<td><table class="mini center margin_auto"><tr><th>'.$lang['device'].'</th><th>'.$lang['state'].'</th></tr>';
				
				foreach ($raid['drives'] as $drive)
					echo '<tr><td>'.$drive['drive'].'</td><td class="raid_'.$drive['state'].'">'.ucfirst($drive['state']).'</td></tr>';

				echo '</table></td>
				<td>'.$active[1].'/'.$active[0].'</td>
				</tr>
				';
			}
		else
			echo '<tr><td colspan="6" class="none">'.$lang['none_found'].'</td></tr>';

		echo '
	</table>
</div>';
	}

	// Feel like showing errors? Are there any even?
	if (!empty($settings['show_errors']) && LinfoError::Fledging()->num() > 0) {
		echo '
	<div id="errorList" class="infoTable">
		<h2>'.$lang['error_head'].'</h2>
		<table>
			<tr>
				<th>'.$lang['from_where'].'</th>
				<th>'.$lang['message'].'</th>
			</tr>';

			foreach (LinfoError::Fledging()->show() as $error) {
				echo '
				<tr>
					<td>'.$error[0].'</td>
					<td>'.$error[1].'</td>
				</tr>
				';
			}

			echo '
		</table>
	</div>
	';
	}

	// Additional extensions
	if (count($info['extensions']) > 0) {
		foreach ($info['extensions'] as $ext)
			if (is_array($ext) && count($ext) > 0) {
				
				// Decide how to show something extra
				switch (array_key_exists('extra_type', $ext) && !empty($ext['extra_vals']) ? $ext['extra_type'] : false) {
					
					// Table with a key->value table to the right of it
					// Useful for stats or other stuff pertaining to  
					// the main info to the left
					case 'k->v':
						echo '
<div class="col2_side">
	<div class="col2_side_left">
	'.create_table($ext).'
	</div>
	<div class="col2_side_right">
		<div class="infoTable">
			<h2>'.$ext['extra_vals']['title'].'</h2>
			<table>';

			// Give each value
			foreach(array_filter($ext['extra_vals']['values']) as $v)
				echo '
				<tr>
					<th>'.$v[0].'</th>
					<td>'.$v[1].'</td>
				</tr>';
			echo'
			</table>
		</div>
	</div>
</div>
						';
					break;

					// Nothing extra; just the table
					default:
						echo create_table($ext);
					break;
				}
			}
	}

	// Feel like showing timed results?
	if (!empty($settings['timer'])) {
		echo '
	<div id="timerList" class="infoTable">
		<h2>'.$lang['timer'].'</h2>
		<table>
			<tr>
				<th>'.$lang['area'].'</th>
				<th>'.$lang['time_taken'].'</th>
			</tr>';

			foreach (LinfoTimer::Fledging()->getResults() as $result) {
				echo '
				<tr>
					<td>'.$result[0].'</td>
					<td>'.round($result[1], 3).' '.$lang['seconds'].'</td>
				</tr>
				';
			}

			echo '
		</table>
	</div>
	';
	}

	echo '
<div id="foot">
	'.sprintf($lang['footer_app'], '<a href="http://linfo.sf.net"><em>'.AppName.' ('.VERSION.')</em></a>',  round(microtime(true) - TIME_START,2)).'<br />
	<em>'.AppName.'</em> &copy; 2010 &ndash; 2011 Joseph Gillotti &amp; friends. Source code licensed under GPL.
</div>
<div id="foot_time">
	<br />
	Generated on '.date('m/d/y h:i A').'
</div>
<script type="text/javascript">Linfo.init()</script>
</body>
</html>';

	// End output buffering
	if (!isset($settings['compress_content']) || $settings['compress_content']) {
		ob_end_flush();
	}
}


/**
 * Show it all... in simplexml
 * @param array $info the system information
 * @param array $settings linfo settings
 */
 function showInfoSimpleXML($info, $settings) {
 	global $lang;

 	try {
		// Start it up
		$xml = new SimpleXMLElement('<?xml version="1.0"?><linfo></linfo>');

		// Deal with core stuff
		$core_elem = $xml->addChild('core');
		$core = array();
		if (!empty($settings['show']['os']))
			$core[] = array('os', $info['OS']);
		if (!empty($settings['show']['distro']) && is_array($info['Distro']))
			$core[] = array($lang['distro'],  $info['Distro']['name'] . ($info['Distro']['version'] ? ' - '.$info['Distro']['version'] : ''));
		if (!empty($settings['show']['kernel']))
			$core[] = array('kernel', $info['Kernel']);
		$core[] = array('accessed_ip', (isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : 'Unknown'));
		if (!empty($settings['show']['uptime']))
			$core[] = array('uptime', $info['UpTime']);
		if (!empty($settings['show']['hostname']))
			$core[] = array('hostname', $info['HostName']);
		if (!empty($settings['show']['cpu'])) {
			$cpus = '';
			foreach ((array) $info['CPU'] as $cpu) 
				$cpus .=
					(array_key_exists('Vendor', $cpu) ? $cpu['Vendor'] . ' - ' : '') .
					$cpu['Model'] .
					(array_key_exists('MHz', $cpu) ?
						($cpu['MHz'] < 1000 ? ' ('.$cpu['MHz'].' MHz)' : ' ('.round($cpu['MHz'] / 1000, 3).' GHz)') : '') .
						'<br />';
			$core[] = array('CPU', $cpus);
		}
		if (!empty($settings['show']['model']) && array_key_exists('Model', $info) && !empty($info['Model']))
			$core[] = array($lang['model'], $info['Model']);
		if (!empty($settings['show']['load']))
			$core[] = array('load', implode(' ', (array) $info['Load']));
		if (!empty($settings['show']['process_stats']) && $info['processStats']['exists']) {
			$proc_stats = array();
			if (array_key_exists('totals', $info['processStats']) && is_array($info['processStats']['totals']))
				foreach ($info['processStats']['totals'] as $k => $v) 
					$proc_stats[] = $k . ': ' . number_format($v);
			$proc_stats[] = 'total: ' . number_format($info['processStats']['proc_total']);
			$core[] = array('processes', implode('; ', $proc_stats));
			if ($info['processStats']['threads'] !== false)
				$core[] = array('threads', number_format($info['processStats']['threads']));
		}
		for ($i = 0, $core_num = count($core); $i < $core_num; $i++) 
			$core_elem->addChild($core[$i][0], $core[$i][1]);

		// RAM
		if (!empty($settings['show']['ram'])) {
			$mem = $xml->addChild('memory');
			$core_mem = $mem->addChild($info['RAM']['type']);
			$core_mem->addChild('free', $info['RAM']['free']);
			$core_mem->addChild('total', $info['RAM']['total']);
			$core_mem->addChild('used', $info['RAM']['total'] - $info['RAM']['free']);
			if (isset($info['RAM']['swapFree']) || isset($info['RAM']['swapTotal'])){
				$swap = $mem->addChild('swap');
				$swap_core = $swap->addChild('core');
				$swap_core->addChild('free', $info['RAM']['swapFree']);
				$swap_core->addChild('total', $info['RAM']['swapTotal']);
				$swap_core->addChild('used', $info['RAM']['swapTotal'] - $info['RAM']['swapFree']);
				if (is_array($info['RAM']['swapInfo']) && count($info['RAM']['swapInfo']) > 0) {
					$swap_devices = $swap->addChild('devices');
					foreach($info['RAM']['swapInfo'] as $swap_dev) {
						$swap_dev_elem = $swap_devices->addChild('device');
						$swap_dev_elem->addAttribute('device', $swap_dev['device']);
						$swap_dev_elem->addAttribute('type', $swap_dev['type']);
						$swap_dev_elem->addAttribute('size', $swap_dev['size']);
						$swap_dev_elem->addAttribute('used', $swap_dev['used']);
					}
				}
			}
		}
	
		// NET
		if (!empty($settings['show']['network'])) {
			$net = $xml->addChild('net');
			foreach ($info['Network Devices'] as $device => $stats) {
				$nic = $net->addChild('interface');
				$nic->addAttribute('device', $device);
				$nic->addAttribute('type', $stats['type']);
				$nic->addAttribute('sent', $stats['sent']['bytes']);
				$nic->addAttribute('recieved', $stats['recieved']['bytes']);
			}
		}

		// TEMPS
		if (!empty($settings['show']['temps']) && count($info['Temps']) > 0) {
			$temps = $xml->addChild('temps');
			for($i = 0, $num_temps = count($info['Temps']); $i < $num_temps; $i++) {
				$temp = $temps->addChild('temp');
				$temp->addAttribute('path', $info['Temps'][$i]['path']);
				$temp->addAttribute('name', $info['Temps'][$i]['name']);
				$temp->addAttribute('temp', $info['Temps'][$i]['temp'].' '.$info['Temps'][$i]['unit']);
			}
		}

		// Batteries
		if (!empty($settings['show']['battery']) && count($info['Battery']) > 0) {
			$bats = $xml->addChild('batteries');
			foreach ($info['Battery'] as $bat)  {
				$bat = $bats->addChild('battery');
				$bat->addAttribute('device', $bat['device']);
				$bat->addAttribute('state', $bat['state']);
				$bat->addAttribute('percentage', $bat['percentage']);
			}
		}

		// SERVICES
		if (!empty($settings['show']['services']) && count($info['services']) > 0) {
			$services = $xml->addChild('services');
			foreach ($info['services'] as $service => $state) {
				$state_parts = explode(' ', $state['state'], 2);
				$service_elem = $services->addChild('service');
				$service_elem->addAttribute('name', $service);
				$service_elem->addAttribute('state', $state_parts[0] . (array_key_exists(1, $state_parts) ? ' '.$state_parts[1] : ''));
				$service_elem->addAttribute('pid', $state['pid']);
				$service_elem->addAttribute('threads', $state['threads'] ? $state['threads'] : '?');
				$service_elem->addAttribute('mem_usage', $state['memory_usage'] ? $state['memory_usage'] : '?');
			}
		}

		// DEVICES
		if (!empty($settings['show']['devices'])) {
			$show_vendor = array_key_exists('hw_vendor', $info['contains']) ? ($info['contains']['hw_vendor'] === false ? false : true) : true;
			$devices = $xml->addChild('devices');
			for ($i = 0, $num_devs = count($info['Devices']); $i < $num_devs; $i++) {
				$device = $devices->addChild('device');
				$device->addAttribute('type', $info['Devices'][$i]['type']);
				if ($show_vendor)
					$device->addAttribute('vendor', $info['Devices'][$i]['vendor']);
				$device->addAttribute('name', $info['Devices'][$i]['device']);
			}
		}

		// DRIVES
		if (!empty($settings['show']['hd'])) {
			$show_stats = array_key_exists('drives_rw_stats', $info['contains']) ? ($info['contains']['drives_rw_stats'] === false ? false : true) : true;
			$drives = $xml->addChild('drives');
			foreach($info['HD'] as $drive) {
				$drive_elem = $drives->addChild('drive');
				$drive_elem->addAttribute('device', $drive['device']);
				$drive_elem->addAttribute('vendor', $drive['vendor'] ? $drive['vendor'] : $lang['unknown']);
				$drive_elem->addAttribute('name', $drive['name']);
				if ($show_stats) {
					$drive_elem->addAttribute('reads', $drive['reads'] ? $drive['reads'] : 'unknown');
					$drive_elem->addAttribute('writes', $drive['writes'] ? $drive['writes'] : 'unknown');
				}
				$drive_elem->addAttribute('size', $drive['size'] ? $drive['size'] : 'unknown');
				if (is_array($drive['partitions']) && count($drive['partitions']) > 0) {
					$partitions = $drive_elem->addChild('partitions');
					foreach ($drive['partitions'] as $partition) {
						$partition_elem = $partitions->addChild('partition');
						$partition_elem->addAttribute('name', isset($partition['number']) ? $drive['device'].$partition['number'] : $partition['name']);
						$partition_elem->addAttribute('size', $partition['size']);
					}
				}
			}

		}

		// Sound cards? lol
		if (!empty($settings['show']['sound']) && count($info['SoundCards']) > 0) {
			$cards = $xml->addChild('soundcards');
			foreach ($info['SoundCards'] as $card) {
				$card_elem = $cards->addChild('card');
				$card_elem->addAttribute('number', $card['number']);
				$card_elem->addAttribute('vendor', empty($card['vendor']) ? 'unknown' : $card['vendor']);
				$card_elem->addAttribute('card', $card['card']);
			}
		}

		// File system mounts
		if (!empty($settings['show']['mounts'])) {
			$has_devices = false;
			$has_labels = false;
			$has_types = false;
			foreach($info['Mounts'] as $mount) {
				if (!empty($mount['device'])) {
					$has_devices = true;
				}
				if (!empty($mount['label'])) {
					$has_labels = true;
				}
				if (!empty($mount['devtype'])) {
					$has_types = true;
				}
			}
			$mounts = $xml->addChild('mounts');
			foreach ($info['Mounts'] as $mount) {
				$mount_elem = $mounts->addChild('mount');
				if (preg_match('/^.+:$/', $mount['device']) == 1)
					$mount['device'] .= DIRECTORY_SEPARATOR;
				if ($has_types) 
					$mount_elem->addAttribute('type', $mount['devtype']);
				if ($has_devices) 
					$mount_elem->addAttribute('device', $mount['device']);
				$mount_elem->addAttribute('mountpoint', $mount['mount']);
				if ($has_labels) 
					$mount_elem->addAttribute('label', $mount['label']);
				$mount_elem->addAttribute('fstype', $mount['type']);
				if ($settings['show']['mounts_options'] && !empty($mount['options'])) {
					$options_elem = $mount_elem->addChild('mount_options');
					foreach ($mount['options'] as $option)
						$options_elem->addChild($option);
				}
				$mount_elem->addAttribute('size', $mount['size']);
				$mount_elem->addAttribute('used', $mount['used']);
				$mount_elem->addAttribute('free', $mount['free']);
			}
		}

		// RAID arrays
		if (!empty($settings['show']['raid']) && count($info['Raid']) > 0) {
			$raid_elem = $xml->addChild('raid');
			foreach ($info['Raid'] as $raid) {
				$array = $raid_elem->addChild('array');
				$active = explode('/', $raid['count']);
				$array->addAttribute('device', $raid['device']);
				$array->addAttribute('level', $raid['level']);
				$array->addAttribute('status', $raid['status']);
				$array->addAttribute('size', $raid['size']);
				$array->addAttribute('active', $active[1].'/'.$active[0]);
				$drives = $array->addChild('drives');
				foreach ($raid['drives'] as $drive) {
					$drive_elem = $drives->addChild('drive');
					$drive_elem->addAttribute('drive', $drive['drive']);
					$drive_elem->addAttribute('state', $drive['state']);
				}
			}
		}
		
		// Timestamp
		$xml->addChild('timestamp', $info['timestamp']);

		// Extensions
		if (count($info['extensions']) > 0) {
			$extensions = $xml->addChild('extensions');
			foreach ($info['extensions'] as $ext) {
				$header = false;
				if (is_array($ext) && count($ext) > 0) {
					$this_ext = $extensions->addChild(string_xml_tag_unfuck($ext['root_title']));
					foreach ((array) $ext['rows'] as $i => $row) {
						if ($row['type'] == 'header') {
							$header = $i;
						}
						elseif ($row['type'] == 'values') {
							$this_row = $this_ext->addChild('row');
							if ($header !== false && array_key_exists($header, $ext['rows'])) {
								foreach ($ext['rows'][$header]['columns'] as $ri => $rc) {
									$this_row->addChild(
										string_xml_tag_unfuck($rc),
										$ext['rows'][$i]['columns'][$ri]
									);
								}
							}
						}
					}
				}
			}
		}
		
		// Out it
		header('Content-type: text/xml');
		echo $xml->asXML();

		// Comment which has stats and generator
		echo '<!-- Generated in '.round(microtime(true) - TIME_START,2).' seconds by '.AppName.' ('.VERSION.')-->';
	}
	catch (Exception $e) {
		exit('Creation of XML error: '.$e->getMessage());
	}
 }

/**
 * Show it all... in XMLWriter
 * @param array $info the system information
 * @param array $settings linfo settings
 */
 function showInfoXMLWriter($info, $settings) {
 	exit ('So far only php\'s simple xml library is supported for XML output. Sorry!');
 }

/**
 * Show it all... in JSON
 * @param array $info the system information
 * @param array $settings linfo settings
 */
 function showInfoJSON($info, $settings) {

 	// Make sure we have JSON
 	if (!function_exists('json_encode'))  {
		exit('{error:\'JSON extension not loaded\'}');
		return;
	}
	
	header("Content-Type: application/json");

	// Output buffering, along with compression (if supported)
	if (!isset($settings['compress_content']) || $settings['compress_content']) {
		ob_start('ob_gzhandler');
	}

	// Give it
	echo json_encode($info);

	// Send it all out
	if (!isset($settings['compress_content']) || $settings['compress_content']) {
		ob_end_flush();
	}
 }

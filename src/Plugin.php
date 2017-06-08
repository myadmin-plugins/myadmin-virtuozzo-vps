<?php

namespace Detain\MyAdminVirtuozzo;

use Detain\Virtuozzo\Virtuozzo;
use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public function __construct() {
	}

	public static function Activate(GenericEvent $event) {
		// will be executed when the licenses.license event is dispatched
		$license = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_FANTASTICO) {
			myadmin_log('licenses', 'info', 'Virtuozzo Activation', __LINE__, __FILE__);
			function_requirements('activate_virtuozzo');
			activate_virtuozzo($license->get_ip(), $event['field1']);
			$event->stopPropagation();
		}
	}

	public static function ChangeIp(GenericEvent $event) {
		if ($event['category'] == SERVICE_TYPES_FANTASTICO) {
			$license = $event->getSubject();
			$settings = get_module_settings('licenses');
			$virtuozzo = new Virtuozzo(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
			myadmin_log('licenses', 'info', "IP Change - (OLD:".$license->get_ip().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			$result = $virtuozzo->editIp($license->get_ip(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log('licenses', 'error', 'Virtuozzo editIp('.$license->get_ip().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__);
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $license->get_ip());
				$license->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			}
			$event->stopPropagation();
		}
	}

	public static function Menu(GenericEvent $event) {
		// will be executed when the licenses.settings event is dispatched
		$menu = $event->getSubject();
		$module = 'licenses';
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link($module, 'choice=none.reusable_virtuozzo', 'icons/database_warning_48.png', 'ReUsable Virtuozzo Licenses');
			$menu->add_link($module, 'choice=none.virtuozzo_list', 'icons/database_warning_48.png', 'Virtuozzo Licenses Breakdown');
			$menu->add_link($module.'api', 'choice=none.virtuozzo_licenses_list', 'whm/createacct.gif', 'List all Virtuozzo Licenses');
		}
	}

	public static function Requirements(GenericEvent $event) {
		// will be executed when the licenses.loader event is dispatched
		$loader = $event->getSubject();
		$loader->add_requirement('crud_virtuozzo_list', '/../vendor/detain/crud/src/crud/crud_virtuozzo_list.php');
		$loader->add_requirement('crud_reusable_virtuozzo', '/../vendor/detain/crud/src/crud/crud_reusable_virtuozzo.php');
		$loader->add_requirement('get_virtuozzo_licenses', '/../vendor/detain/myadmin-virtuozzo-vps/src/virtuozzo.inc.php');
		$loader->add_requirement('get_virtuozzo_list', '/../vendor/detain/myadmin-virtuozzo-vps/src/virtuozzo.inc.php');
		$loader->add_requirement('virtuozzo_licenses_list', '/../vendor/detain/myadmin-virtuozzo-vps/src/virtuozzo_licenses_list.php');
		$loader->add_requirement('virtuozzo_list', '/../vendor/detain/myadmin-virtuozzo-vps/src/virtuozzo_list.php');
		$loader->add_requirement('get_available_virtuozzo', '/../vendor/detain/myadmin-virtuozzo-vps/src/virtuozzo.inc.php');
		$loader->add_requirement('activate_virtuozzo', '/../vendor/detain/myadmin-virtuozzo-vps/src/virtuozzo.inc.php');
		$loader->add_requirement('get_reusable_virtuozzo', '/../vendor/detain/myadmin-virtuozzo-vps/src/virtuozzo.inc.php');
		$loader->add_requirement('reusable_virtuozzo', '/../vendor/detain/myadmin-virtuozzo-vps/src/reusable_virtuozzo.php');
		$loader->add_requirement('class.Virtuozzo', '/../vendor/detain/virtuozzo-vps/src/Virtuozzo.php');
		$loader->add_requirement('vps_add_virtuozzo', '/vps/addons/vps_add_virtuozzo.php');
	}

	public static function Settings(GenericEvent $event) {
		// will be executed when the licenses.settings event is dispatched
		$settings = $event->getSubject();
		$settings->add_text_setting('licenses', 'Virtuozzo', 'virtuozzo_username', 'Virtuozzo Username:', 'Virtuozzo Username', $settings->get_setting('FANTASTICO_USERNAME'));
		$settings->add_text_setting('licenses', 'Virtuozzo', 'virtuozzo_password', 'Virtuozzo Password:', 'Virtuozzo Password', $settings->get_setting('FANTASTICO_PASSWORD'));
		$settings->add_dropdown_setting('licenses', 'Virtuozzo', 'outofstock_licenses_virtuozzo', 'Out Of Stock Virtuozzo Licenses', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_LICENSES_FANTASTICO'), array('0', '1'), array('No', 'Yes', ));
	}

}
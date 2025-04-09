<?php
/**
 * Skydropx Trait
 *
 * @package  Skydropx\Helper
 */
namespace Skydropx\Helper;

trait Skydropx_trait {
	public static function get_status() {
		return array(
			'CREATING_LABEL'   => __('Por crear', 'skydropx'),
			'FULFILLMENT'      => __('Creado', 'skydropx'),
			'CREATED'          => __('Creado', 'skydropx'),
			'PICKED_UP'        => __('Recolectado', 'skydropx'),
			'IN_TRANSIT'       => __('En camino', 'skydropx'),
			'LAST_MILE'        => __('Por llegar', 'skydropx'),
			'DELIVERED'        => __('Entregado', 'skydropx'),
			'DELIVERY_ATTEMPT' => __('Con Incidencia', 'skydropx'),
			'EXCEPTION'        => __('Por cancelar', 'skydropx'),
			'REVIEWING'        => __('Por cancelar', 'skydropx'),
			'PENDING'          => __('Por crear', 'skydropx'),
			'CANCELLED'        => __('Por cancelar', 'skydropx'),
			'ERROR'            => __('Datos con error', 'skydropx'),
			'RESTORED'         => __('Con Incidencia', 'skydropx'),
		);
	}

}

<?php

namespace Skydropx\ShippingMethod;

defined( 'ABSPATH' ) || exit;

$settings = array(
	'title' => array(
		'title'       => __( 'Nombre Método Envío', 'skydropx' ),
		'type'        => 'text',
		'description' => __( 'Nombre con el que aparecerá el tipo de envío en tu tienda.', 'skydropx' ),
		'default'     => __( 'Skydropx', 'skydropx' ),
		'desc_tip'    => true,
	),
	'enabled' => array(
        'title'       => __( 'Activar/Desactivar', 'skydropx' ),
        'type'        => 'checkbox',
        'label'       => __( 'Activar Método de Envío Skydropx', 'skydropx' ),
        'default'     => 'yes',
    )
);

return $settings;

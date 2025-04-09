=== Skydropx ===
Contributors: Skydropx
Donate link: https://skydropx.com
Tags: WooCommerce, Shipping Method, Skydropx
Requires at least: 3.0.1
Tested up to: 6.7.0
Stable tag: 1.1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Con esta versión de Skydropx podrás aprovechar más funcionalidades para realizar tus envíos y automatizar fácilmente tus procesos logísticos.

== Description ==

Skydropx: cotizador y envíos Con esta versión de Skydropx podrás aprovechar más funcionalidades pararealizar tus envíos y automatizar fácilmente tus procesos logísticos.
* Ofrece un cotizador dinámico de envíos en el carrito de compras de tus clientes.
* Compara precios fácilmente y crea envíos locales, nacionales e internacionales.
* Optimiza tiempos al crear guías y gestionar pedidos dentro de tu tienda.
* Monitorea tus órdenes de manera simple y centralizada.
* Obtén ganancias extra de tus envíos y ofrece descuentos a tus clientes.

¡Deja de preocuparte por la logística de envío de tu negocio, utilizando una herramienta gratuita!
Si necesitas información adicional o tienes alguna duda podes consultar el 
[manual](https://ayuda.skydropx.com/knowledge-base/conectar-y-configurar-mi-tienda-woocommerce-con-skydropx-cotizador-y-envios/) o no dudes en contactarnos a través de [integraciones@skydropx.com](mailto:integraciones@skydropx.com)
Estaremos felices de ayudarte.

== Third party services ==

El servicio base a donde se conecta este plugin es https://ecommerce.skydropx.com, del cual consuminos los siguientes recursos:

* Vincular tu tienda con la plataforma de Skydropx, enviando las llaves/credenciales necesarias para consumir la API de WooCommerce.
* Suscribirse a los eventos de order.update y order.create, para poder sincronizar ordenes y estar actualizando sus estados.
* Desvincular la tienda. Al momento de desactivar la tienda, realizamos una peticion para apagar tu tienda en la plataforma de Skydropx.

A su vez, creamos nuevos endpoints/rutas partiendo de tu tienda. Todo los endpoints requieren authenticacion basica usando la API KEY / API SECRET de woocommerce, vinculadas con skydropx.

* /wp-json/skydropx/v1/quotation-toggle: Activa/desactiva la funcionalidad de ofrecer rates/precios de Skydropx.
* /wp-json/skydropx/v1/quotation-status: Obtiene el estado actual de la funcionalidad de cotizacion en tu tienda de woo.
* /wp-json/skydropx/v1/configs: Guarda informacion necesaria para que se pueda vincular tu tienda con nuestra plataforma Skydropx.
* /wp-json/skydropx/v1/uninstall: Permite la eliminacion del plugin desde la plataforma Skydropx.

Enpoints legacy

* /wp-api/skydropx-quotation-toggle: Activa/desactiva la funcionalidad de ofrecer rates/precios de Skydropx.
* /wp-api/skydropx-quotation-status: Obtiene el estado actual de la funcionalidad de cotizacion en tu tienda de woo.
* /wp-api/skydropx-configs: Guarda informacion necesaria para que se pueda vincular tu tienda con nuestra plataforma Skydropx.
* /wp-api/skydropx-uninstall: Permite la eliminacion del plugin desde la plataforma Skydropx.

* Nuestro sitio web es [skydropx.com](http://skydropx.com/)
* Puedes leer mas sobre [terminos y condiciones de skydropx.](https://logistica.skydropx.com/terminos-y-condiciones)
* [Aviso de privacidad](https://logistica.skydropx.com/aviso-privacidad).

== Installation ==
Para instalar el plugin desde el repositorio, sigue estos pasos:

* Ve al menú de administración de tu tienda y allí selecciona Plugins > Agregar nuevo.
* En el buscador, escribe Skydropx para WooCommerce y selecciona el botón Buscar.
* Selecciona el botón Instalar que corresponda al plugin de Skydropx.

Para instalar el módulo desde el archivo .ZIP, sigue estos pasos:
	
* Ve al menú de administración y allí selecciona Plugins > Agregar nuevo.
* En la parte superior de la pantalla selecciona el botón Subir plugin.
* Selecciona el archivo .zip guardado previamente en tu computadora y selecciona el botón Instalar ahora.

== Important Technical Instructions ==
La opción de configuración SKYDROPX_SHOP_ID es un requisito crítico para el funcionamiento adecuado del plugin Skydropx.
Esta opción permite que el plugin se integre correctamente con los servicios de Skydropx, asegurando la ejecución de todas las funcionalidades necesarias.

Es fundamental señalar que esta opción solo puede ser configurada a través de un proceso interno durante la instalación del plugin.
Esta restricción se implementa por razones de seguridad y para garantizar la integridad operativa del plugin.
Modificaciones a esta configuración por medios externos no son permitidas para prevenir configuraciones incorrectas que puedan comprometer el funcionamiento del plugin.

Adicionalmente, para que el plugin funcione correctamente, es necesario que los enlaces permanentes (permalinks) de WordPress estén configurados.
Se recomienda seleccionar la opción “Nombre de la entrada” (%postname%) en la configuración de enlaces permanentes. Esto es indispensable para que el sitio pueda aceptar solicitudes externas a través de las APIs necesarias para la integración con Skydropx.

En caso de que la opción de configuración SKYDROPX_SHOP_ID no esté disponible o los enlaces permanentes no estén configurados correctamente, el plugin mostrará notificaciones en el área de administración para informar sobre los ajustes necesarios.
Estas notificaciones están diseñadas para guiar al usuario y asegurar que todas las configuraciones requeridas se completen de manera adecuada.

Además, puede verificar el estado de la conexión con Skydropx en el apartado del menú Skydropx que se agrega en la administración de su tienda. Este apartado proporciona información detallada sobre la conexión y las configuraciones del plugin, permitiendo resolver cualquier inconveniente que impida su funcionamiento correcto.

Se recomienda revisar estas notificaciones y el apartado del menú Skydropx para garantizar la correcta configuración y operación del plugin.

== Screenshots ==
1. Plugin activado con éxito.
2. La tienda se ha conectado a la plataforma Skydropx, lista para sincronizar pedidos y tarifas.
3. Visualización de las tarifas dinámicas generadas en tiempo real para el carrito de compras.

== Changelog ==
== 1.1.3 =
* Modify endpoints paths and add security layer by required Basic authenticacion.
* Increase tested support for wordpress v6.7
* Mark as legacy public endpoints
* Remove automatic redirections
* Modify installation and synchronization with skydropx platform workflow

== 1.1.2 =
* Remove support for linking store with skydropx using /authorize page from plugin.
* Add support for old flow of creating api keys from plugin.

= 1.1.1 =
* Fix support for PHP ^v7

= 1.1.0 =
* Add new connection flow with user interaction
* Modify activation/deactivation flows by using an iFrame
* Repair links/redirects syntaxis.

= 1.0.7 =
* Change event name when deactivate plugin
* Implements iFrame when install plugin

= 1.0.6 =
* add validation of plugin name for activation_hook
* No deactivate Cotizador process when install other plugin

= 1.0.5 =
* add Content-Type header for responses on custom endpoints
* add validation of plugin name for desactivation_hook
* fix deactivation_from_V3 method flow

= 1.0.2 =
* add Endpoint to uninstall plugin added
* add skydropx screens

= 1.0.1 =
* modify flujo de eliminar integracion v3 woo desde plugin

= 1.0.0 =
* add shipping zone by @xavier-arias-skydropx in #3
* modify logging and post request on deactivate action/event 
* add latest plugin changes 
* modify redirection after activation
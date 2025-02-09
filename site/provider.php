<?php
defined('_JEXEC') or die();

use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\DI\Container;
use Joomla\CMS\Extension\Service\Provider\ComponentRouting;
use Joomla\DI\ServiceProviderInterface;
use CustomTablesRouter;

die('CustomTables router provider loaded');

return new class implements ServiceProviderInterface {
	public function register(Container $container)
	{
		$container->registerServiceProvider(new ComponentRouting('com_customtables'));
		$container->set(
			RouterFactoryInterface::class,
			function () {
				return new CustomTablesRouter();
			}
		);
	}
};

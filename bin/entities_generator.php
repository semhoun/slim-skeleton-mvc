<?php
include __DIR__ . '/../vendor/autoload.php';
$settings = require __DIR__ . '/../config/settings.php';

$classLoader = new \Doctrine\Common\ClassLoader('Entities', __DIR__);
$classLoader->register();
$classLoader = new \Doctrine\Common\ClassLoader('Proxies', __DIR__);
$classLoader->register();
// config
$config = new \Doctrine\ORM\Configuration();
$config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(__DIR__ . '/../var/tmp_entity'));
$config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
$config->setProxyDir(__DIR__ . '/../var/proxies');
$config->setProxyNamespace('Proxies');
$em = \Doctrine\ORM\EntityManager::create($settings['settings']['doctrine']['connection'], $config);
// custom datatypes (not mapped for reverse engineering)
$em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('set', 'string');
$em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
// fetch metadata
$driver = new \Doctrine\ORM\Mapping\Driver\DatabaseDriver(
    $em->getConnection()->getSchemaManager()
);
$driver->setNamespace('\\App\\Entity\\');
$em->getConfiguration()->setMetadataDriverImpl($driver);
$cmf = new \Doctrine\ORM\Tools\DisconnectedClassMetadataFactory($em);
$cmf->setEntityManager($em);
$classes = $driver->getAllClassNames();
$metadata = $cmf->getAllMetadata();
$generator = new Doctrine\ORM\Tools\EntityGenerator();
$generator->setUpdateEntityIfExists(true);
$generator->setGenerateStubMethods(true);
$generator->setGenerateAnnotations(true);
$generator->generate($metadata, __DIR__ . '/../var/tmp_entity');
// Move files in the good directory
$files = glob(__DIR__ . '/../var/tmp_entity/App/Entity/*.{php}', GLOB_BRACE);
foreach($files as $fullpath) {
    $file = basename($fullpath);
    $src = file_get_contents($fullpath);
	if (preg_match('#^namespace\s+(.+?);$#sm', $src)) {
		continue;
	}
	$php = preg_replace("/<\?php/", "<?php\nnamespace App\\Entity;", $src);
	file_put_contents(__DIR__ . '/../src/Entity/' . $file, $php);
}
print '-------------------------------------------' . PHP_EOL;
print ' Done! Generated entities to `src/Entity`  ' . PHP_EOL;
print '        Please remove var/tmp_entity       ' . PHP_EOL;
print '-------------------------------------------' . PHP_EOL;

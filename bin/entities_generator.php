<?php
$namespace = "App\\Entity";

include __DIR__ . '/../vendor/autoload.php';
$settings = require __DIR__ . '/../config/settings.php';

$classLoader = new \Doctrine\Common\ClassLoader('Entities', __DIR__);
$classLoader->register();
$classLoader = new \Doctrine\Common\ClassLoader('Proxies', __DIR__);
$classLoader->register();
// config
$config = new \Doctrine\ORM\Configuration();
$config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(__DIR__ . '/../src/Entity'));
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
$em->getConfiguration()->setMetadataDriverImpl($driver);
$cmf = new \Doctrine\ORM\Tools\DisconnectedClassMetadataFactory($em);
$cmf->setEntityManager($em);
$classes = $driver->getAllClassNames();
$metadata = $cmf->getAllMetadata();
$generator = new Doctrine\ORM\Tools\EntityGenerator();
$generator->setUpdateEntityIfExists(true);
$generator->setGenerateStubMethods(true);
$generator->setGenerateAnnotations(true);
$generator->generate($metadata, __DIR__ . '/../src/Entity');
// add namespace to all files
$files = glob(__DIR__ . '/../src/Entity/*.{php}', GLOB_BRACE);
foreach($files as $file) {
	$src = file_get_contents($file);
	if (preg_match('#^namespace\s+(.+?);$#sm', $src, $m)) {
		continue;
	}
	$php = preg_replace("/<\?php/", "<?php\nnamespace " . $namespace . ";", $src);
	file_put_contents($file, $php);
}
print '-------------------------------------------' . PHP_EOL;
print ' Done! Generated entities to `src\Entity`  ' . PHP_EOL;
print '-------------------------------------------' . PHP_EOL;

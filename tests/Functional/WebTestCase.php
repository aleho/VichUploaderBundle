<?php

namespace Vich\UploaderBundle\Tests\Functional;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class WebTestCase extends BaseWebTestCase
{
    protected static function getKernelClass(): string
    {
        require_once __DIR__.'/../Fixtures/App/app/AppKernel.php';

        return 'AppKernel';
    }

    protected function getUploadedFile(KernelBrowser $client, string $name, string $mimeType = 'image/png'): UploadedFile
    {
        return new UploadedFile(
            $this->getImagesDir($client).\DIRECTORY_SEPARATOR.$name,
            $name,
            $mimeType
        );
    }

    protected function getUploadsDir(KernelBrowser $client): string
    {
        return $client->getKernel()->getCacheDir().'/images';
    }

    protected function getImagesDir(KernelBrowser $client): string
    {
        return $client->getKernel()->getProjectDir().'/app/Resources/images';
    }

    protected static function getKernelContainer(KernelBrowser $client): ContainerInterface
    {
        return $client->getKernel()->getContainer();
    }

    protected function loadFixtures(KernelBrowser $client): void
    {
        $container = self::getKernelContainer($client);
        $registry = $container->get('doctrine');
        if ($registry instanceof ManagerRegistry) {
            $om = $registry->getManager();
        } else {
            $om = $registry->getEntityManager();
        }

        $cacheDriver = $om->getMetadataFactory()->getCacheDriver();
        if (null !== $cacheDriver) {
            $cacheDriver->deleteAll();
        }

        $connection = $om->getConnection();
        $params = $connection->getParams();
        $name = isset($params['path']) ? $params['path'] : (isset($params['dbname']) ? $params['dbname'] : false);

        if (!$name) {
            throw new \InvalidArgumentException("Connection does not contain a 'path' or 'dbname' parameter and cannot be dropped.");
        }

        $metadatas = $om->getMetadataFactory()->getAllMetadata();

        $schemaTool = new SchemaTool($om);
        $schemaTool->dropDatabase();
        if (!empty($metadatas)) {
            $schemaTool->createSchema($metadatas);
        }
    }
}

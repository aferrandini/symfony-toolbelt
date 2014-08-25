<?php

namespace Symfony\Toolbelt\Installer;

use ZipArchive;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/*
 * This class is heavily inspired by Laravel Installer and
 * uses some parts of its code.
 * (c) Taylor Otwell: https://github.com/laravel/installer
 */
class NewCommand extends Command
{
    private $fs;

    protected function configure()
    {
        $this
            ->setName('new')
            ->setDescription('Creates a new Symfony project.')
            ->addArgument('name', InputArgument::REQUIRED)
            // TODO: allow to select the Symfony version
            // ->addArgument('version', InputArgument::OPTIONAL)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->fs = new Filesystem();

        if (is_dir($dir = getcwd().DIRECTORY_SEPARATOR.$input->getArgument('name'))) {
            throw new \RuntimeException(sprintf("Project directory already exists:\n%s", $dir));
        }

        $this->fs->mkdir($dir);

        // TODO: verify the format of the Symfony version

        $output->writeln("\n Downloading Symfony...");

        $zipFilePath = $dir.DIRECTORY_SEPARATOR.'.symfony_'.uniqid(time()).'.zip';

        $this->download($zipFilePath);

        $output->writeln(' Preparing project...');

        $this->extract($zipFilePath, $dir);

        $this->cleanUp($zipFilePath, $dir);

        $message = <<<MESSAGE

 <info>✔</info>  Symfony was <info>successfully installed</info>. Now you can:

    * Configure your application in <comment>app/config/parameters.yml</comment> file.

    * Run your application:
        1. Execute the <comment>php app/console server:run</comment> command.
        2. Browse to the <comment>http://localhost:8000</comment> URL.

    * Read the documentation at symfony.com/doc
MESSAGE;

        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $message = str_replace('✔', 'OK', $message);
        }

        $output->writeln($message);
    }

    private function download($targetPath)
    {
        // TODO: show a progressbar when downloading the file
        $response = \GuzzleHttp\get('http://symfony.com/download?v=Symfony_Standard_Vendors_2.5.3.zip');
        $this->fs->dumpFile($targetPath, $response->getBody());

        return $this;
    }

    private function extract($zipFilePath, $projectDir)
    {
        $archive = new ZipArchive;

        $archive->open($zipFilePath);
        $archive->extractTo($projectDir);
        $archive->close();

        $extractionDir = $projectDir.DIRECTORY_SEPARATOR.'Symfony';

        $iterator = new \FilesystemIterator($extractionDir);

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            $subPath = $this->fs->makePathRelative($file->getRealPath(), $extractionDir);

            $this->fs->rename($file->getRealPath(), $projectDir.DIRECTORY_SEPARATOR.$subPath);
        }

        return $this;
    }

    private function cleanUp($zipFile, $projectDir)
    {
        $this->fs->remove($zipFile);
        $this->fs->remove($projectDir.DIRECTORY_SEPARATOR.'Symfony');

        return $this;
    }
}

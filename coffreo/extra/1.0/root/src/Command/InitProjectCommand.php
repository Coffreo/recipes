<?php

declare(strict_types=1);

namespace App\Command;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

final class InitProjectCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected static $defaultName = 'app:init-project';

    protected function configure()
    {
        $this
            ->setDescription('Configure a freshly created project.')
            ->setHelp('Configure a freshly created project.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getHelper('question');

        $fullName = $this->retrieveFullName($input, $output, $questionHelper);

        if (is_null($fullName)) {
            return;
        }

        $name        = explode('/', $fullName)[1];
        $description = $questionHelper->ask($input, $output, new Question('Description []:', ""));

        $this->updateComposerJson($fullName, $description);
        $this->updatePackageJson($name, $description);
        $this->addSonarProjectProperties($name);

        exec('composer update --lock');

        $output->writeln('<info>Initialization done !</info>');
    }

    protected function retrieveFullName(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper): ?string
    {
        $nameQuestion = new Question(
            'Project name (<vendor>/<name>)[<comment>coffreo/myProject</comment>]:',
            "coffreo/myProject"
        );

        $fullName = $questionHelper->ask($input, $output, $nameQuestion);
        if (is_null($fullName)) {
            $output->writeln("<error>Name can't be empty initialization aborted !</error>");
            return null;
        }

        if (!preg_match('#^[^/]*/[^/]*$#', $fullName)) {
            $output->writeln("<error>Name wrong format initialization aborted !</error>");
            return null;
        }

        return $fullName;
    }

    /**
     * @param string $fullName
     * @param string $description
     */
    protected function updateComposerJson(string $fullName, string $description): void
    {
        $composerFile = __DIR__ . '/../../composer.json';
        $composer     = json_decode(file_get_contents($composerFile), true);

        unset($composer['name']);
        unset($composer['description']);
        unset($composer['authors']);

        $composer = array_merge(
            [
                'name'        => $fullName,
                'description' => $description,
                'type'        => $composer['type'],
                'license'     => $composer['license'],
                'authors'     => [],
            ],
            $composer
        );

        file_put_contents($composerFile, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @param string $fullName
     * @param string $description
     */
    protected function updatePackageJson(string $name, string $description): void
    {
        $packageFile = __DIR__ . '/../../package.json';

        $package = json_decode(file_get_contents($packageFile), true);

        unset($package['name']);
        unset($package['description']);

        $package = array_merge(
            [
                'name'        => $name,
                'description' => $description,
            ],
            $package
        );

        file_put_contents($packageFile, json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    protected function addSonarProjectProperties(string $name): void
    {
        $fileName    = __DIR__ . '/../../sonar-project.properties';
        $fileContent = <<<text
sonar.projectKey=$name
sonar.projectName=$name
sonar.projectVersion=1.0
sonar.sources=src
text;

        file_put_contents($fileName, $fileContent);
    }
}

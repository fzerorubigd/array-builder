<?php

namespace Cybits\ArrayBuilder\Command;

use Cybits\ArrayBuilder\Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Build
 *
 * @package Cybits\ArrayBuilder\Command
 */
class Build extends Command
{
    /**
     * Configure this instance
     */
    protected function configure()
    {
        $this->setName("build")
            ->setDescription("Build arrays base on pattern.")
            ->setDefinition(array())
            ->addOption('target-dir', 'd', InputOption::VALUE_REQUIRED, 'Target build directory')
            ->addOption('pattern', 'f', InputOption::VALUE_REQUIRED, 'The pattern file')
            ->setHelp(
                <<<EOT
    Build all array_loader
EOT
            );
    }

    /**
     * Execute the command
     *
     * @param InputInterface  $input  the input interface
     * @param OutputInterface $output the output interface
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $targetDir = $input->getOption('target-dir');
        if (!is_dir($targetDir)) {
            $output->writeln("<error>The $targetDir is not directory</error>");

            return;
        }
        $pattern = $input->getOption('pattern');
        if (!is_readable($pattern)) {
            $output->writeln("<error>The $pattern is not exists.</error>");

            return;
        }
        $array = json_decode(file_get_contents($pattern), true);
        $builder = new Generator($array);

        $builder->save($targetDir);

        $output->writeln("<info>All done.</info>");
    }
}

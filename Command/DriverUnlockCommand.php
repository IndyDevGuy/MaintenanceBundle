<?php

namespace IndyDevGuy\MaintenanceBundle\Command;

use ErrorException;
use IndyDevGuy\MaintenanceBundle\Drivers\DriverFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DriverUnlockCommand extends Command
{
    protected static $defaultName = 'idg_maintenance:unlock';
    protected static $defaultDescription = 'Disables Maintenance Mode.';
    private DriverFactory $driverFactory;

    public function __construct(DriverFactory $driverFactory)
    {
        parent::__construct();
        $this->driverFactory = $driverFactory;
    }

    protected function configure():void
    {
        $this
            ->setName('idg_maintenance:unlock')
            ->setDescription('Disables Maintenance Mode.');
    }

    /**
     * @throws ErrorException
     */
    protected function execute(InputInterface $input, OutputInterface $output):int
    {
        if (!$this->confirmUnlock($input, $output)) {
            return 1;
        }

        $driver = $this->driverFactory->getDriver();
        $unlockMessage = $driver->getMessageUnlock($driver->unlock());
        $output->writeln('<info>'.$unlockMessage.'</info>');

        return 0;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     */
    protected function confirmUnlock(InputInterface $input, OutputInterface $output): bool
    {
        $formatter = $this->getHelperSet()->get('formatter');

        if ($input->getOption('no-interaction')) {
            $confirmation = true;
        } else {
            // confirm
            $output->writeln(array(
                '',
                $formatter->formatBlock('You are about to unlock your server.', 'bg=green;fg=white', true),
                '',
            ));

            $confirmation = $this->askConfirmation(
                'WARNING! Are you sure you wish to continue? (y/n) ',
                $input,
                $output
            );
        }

        if (!$confirmation) {
            $output->writeln('<error>Action cancelled!</error>');
        }

        return $confirmation;
    }

    /**
     * This method ensure that we stay compatible with symfony console 2.3 by using the deprecated dialog helper
     * but use the ConfirmationQuestion when available.
     *
     * @param $question
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function askConfirmation($question, InputInterface $input, OutputInterface $output) {
        if (!$this->getHelperSet()->has('question')) {
            return $this->getHelper('dialog')
                ->askConfirmation($output, '<question>' . $question . '</question>', 'y');
        }

        return $this->getHelper('question')
            ->ask($input, $output, new ConfirmationQuestion($question));
    }
}
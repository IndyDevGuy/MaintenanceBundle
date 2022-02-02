<?php

namespace IndyDevGuy\MaintenanceBundle\Command;

use ErrorException;
use IndyDevGuy\MaintenanceBundle\Drivers\AbstractDriver;
use IndyDevGuy\MaintenanceBundle\Drivers\DriverFactory;
use IndyDevGuy\MaintenanceBundle\Drivers\DriverTTLInterface;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class DriverLockCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'idg_maintenance:lock';
    protected static $defaultDescription = 'Activates Maintenance Mode for a specified time (ttl).';
    protected ?int $ttl;

    private DriverFactory $driverFactory;

    public function __construct(DriverFactory $driverFactory)
    {
        parent::__construct();
        $this->driverFactory = $driverFactory;
    }

    protected function configure():void
    {
        $this
            ->setName('idg_maintenance:lock')
            ->setDescription('Activates Maintenance Mode for a specified time (ttl).')
            ->addArgument('ttl', InputArgument::OPTIONAL, 'Overwrite time to life from the configuration, does not work with file or shm driver. Time is in seconds.');
    }

    /**
     * @throws ErrorException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $driver = $this->getDriver();

        if ($input->isInteractive()) {
            if (!$this->askConfirmation('WARNING! Are you sure you wish to continue? (y/n)', $input, $output)) {
                $output->writeln('<error>Maintenance cancelled!</error>');
                return 1;
            }
        } elseif (null !== $input->getArgument('ttl')) {
            $this->ttl = $input->getArgument('ttl');
        } elseif ($driver instanceof DriverTTLInterface) {
            $this->ttl = $driver->getTtl();
        }

        // set ttl from command line if given and driver supports it
        if ($driver instanceof DriverTTLInterface) {
            $driver->setTtl($this->ttl);
        }

        $output->writeln('<info>'.$driver->getMessageLock($driver->lock()).'</info>');
        return 0;
    }

    /**
     * @throws ErrorException
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $driver = $this->getDriver();
        $default = $driver->getOptions();

        $formatter = $this->getHelperSet()->get('formatter');

        if (null !== $input->getArgument('ttl') && !is_numeric($input->getArgument('ttl'))) {
            throw new InvalidArgumentException('Time must be an integer');
        }

        $output->writeln(array(
            '',
            $formatter->formatBlock('You are about to launch maintenance', 'bg=red;fg=white', true),
            '',
        ));

        $ttl = null;
        if ($driver instanceof DriverTtlInterface) {
            if (null === $input->getArgument('ttl')) {
                $output->writeln(array(
                    '',
                    'Do you want to redefine maintenance life time ?',
                    'If yes enter the number of seconds. Press enter to continue',
                    '',
                ));

                $ttl = $this->askAndValidate(
                    $input,
                    $output,
                    sprintf('<info>%s</info> [<comment>Default value in your configuration: %s</comment>]%s ', 'Set time', $driver->hasTtl() ? $driver->getTtl() : 'unlimited', ':'),
                    function($value) use ($default) {
                        if (!is_numeric($value) && null === $default) {
                            return null;
                        } elseif (!is_numeric($value)) {
                            throw new InvalidArgumentException('Time must be an integer');
                        }
                        return $value;
                    },
                    1,
                    $default['ttl'] ?? 0
                );
            }

            $ttl = (int) $ttl;
            $this->ttl = $ttl ?: $input->getArgument('ttl');
        } else {
            $output->writeln(array(
                '',
                sprintf('<fg=red>Ttl doesn\'t work with %s driver</>', get_class($driver)),
                '',
            ));
        }
    }

    /**
     * Get driver
     *
     * @return AbstractDriver
     * @throws ErrorException
     */
    private function getDriver(): AbstractDriver
    {
        return $this->driverFactory->getDriver();
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

    /**
     * This method ensure that we stay compatible with symfony console 2.3 by using the deprecated dialog helper
     * but use the ConfirmationQuestion when available.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param $question
     * @param $validator
     * @param int $attempts
     * @param null $default
     * @return mixed
     */
    protected function askAndValidate(InputInterface $input, OutputInterface $output, $question, $validator, int $attempts = 1, $default = null) {
        if (!$this->getHelperSet()->has('question')) {
            return $this->getHelper('dialog')
                ->askAndValidate($output, $question, $validator, $attempts, $default);
        }

        $question = new Question($question, $default);
        $question->setValidator($validator);
        $question->setMaxAttempts($attempts);

        return $this->getHelper('question')
            ->ask($input, $output, $question);
    }
}
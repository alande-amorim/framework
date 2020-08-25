<?php

/**
 * This file is part of Zero Framework.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace LaravelZero\Framework\Commands\App;

use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use LaravelZero\Framework\Commands\AbstractCommand;

/**
 * The is the Zero Framework renamer command class.
 *
 * @author Nuno Maduro <enunomaduro@gmail.com>
 */
class Renamer extends AbstractCommand
{
    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $name = 'app:rename';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform an application rename';

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        $this->alert('Renaming the application...');

        $this->rename();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addArgument('name', InputArgument::OPTIONAL);
    }

    /**
     * Perform project modifications in order to apply the
     * application name on the composer and on the binary.
     *
     * @return $this
     */
    protected function rename()
    {
        $name = $this->asksForApplicationName();

        return $this->renameBinary($name)
            ->updateComposer($name);
    }

    /**
     * Display an welcome message.
     *
     * @return $this
     */
    protected function displayWelcomeMessage()
    {
        return $this;
    }

    /**
     * Asks for the application name.
     *
     * If there is no interaction, we take the folder basename.
     *
     * @return string
     */
    protected function asksForApplicationName()
    {
        if (empty($name = $this->input->getArgument('name'))) {
            $name = $this->ask('What is your application name?');
        }

        if (empty($name)) {
            $name = trim(basename(BASE_PATH));
        }

        return Str::lower($name);
    }

    /**
     * Update composer json with related information.
     *
     * @param string $name
     *
     * @return $this
     */
    protected function updateComposer($name)
    {
        $this->setComposer(
            Str::replaceFirst(
                '"bin": ["' . $this->getCurrentBinaryName() . '"]',
                '"bin": ["' . $name . '"]',
                $this->getComposer()
            )
        );

        $this->output->writeln('Updating composer: <info>✔</info>');

        return $this;
    }

    /**
     * Renames the application binary.
     *
     * @param string $name
     *
     * @return $this
     */
    protected function renameBinary($name)
    {
        rename(BASE_PATH . '/' . $this->getCurrentBinaryName(), BASE_PATH . '/' . $name);

        $this->output->writeln("Renaming application to: <info>$name</info>");

        return $this;
    }

    /**
     * Set composer file.
     *
     * @param string $composer
     *
     * @return $this
     */
    protected function setComposer($composer)
    {
        file_put_contents(BASE_PATH . '/composer.json', $composer);

        return $this;
    }

    /**
     * Returns the current binary name.
     *
     * @return string
     */
    protected function getCurrentBinaryName()
    {
        $composer = $this->getComposer();

        return current(@json_decode($composer)->bin);
    }

    /**
     * Get composer file.
     *
     * @return string
     */
    protected function getComposer()
    {
        $file = BASE_PATH . '/composer.json';

        if (!file_exists($file)) {
            $this->error('composer.json not found.');
            exit(0);
        }

        return file_get_contents($file);
    }
}

<?php

/**
 * This file is part of Zero Framework.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace LaravelZero\Framework;

use ArrayAccess;
use Illuminate\Config\Repository;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use LaravelZero\Framework\Commands\Component;
use Illuminate\Support\Traits\CapsuleManagerTrait;
use Symfony\Component\Console\Input\InputInterface;
use Illuminate\Console\Application as BaseApplication;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Contracts\Container\Container as ContainerContract;

/**
 * The is the Zero Framework application class.
 *
 * @author Nuno Maduro <enunomaduro@gmail.com>
 */
class Application extends BaseApplication implements ArrayAccess
{
    use CapsuleManagerTrait, ContainerProxyTrait;

    /**
     * The application event dispatcher.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $dispatcher;

    /**
     * The application configuration.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * The application's core commands.
     *
     * @var string[]
     */
    protected $commands = [
        Commands\App\Builder::class,
        Commands\App\Renamer::class,
        Commands\Component\Installer::class,
    ];

    /**
     * The application's core providers.
     *
     * @var string[]
     */
    protected $providers = [
        \Illuminate\Events\EventServiceProvider::class,
    ];

    /**
     * The application's core components.
     *
     * @var string[]
     */
    protected $components = [
        Component\Illuminate\Database\ComponentProvider::class,
    ];

    /**
     * The application core aliases.
     *
     * @var array
     */
    protected $aliases = [
        'app' => [\Illuminate\Contracts\Container\Container::class],
        'events' => [\Illuminate\Events\Dispatcher::class, \Illuminate\Contracts\Events\Dispatcher::class],
        'config' => [\Illuminate\Config\Repository::class, \Illuminate\Contracts\Config\Repository::class],
    ];

    /**
     * Creates a new instance of the application class.
     *
     * @param \Illuminate\Contracts\Container\Container|null $container
     * @param \Illuminate\Contracts\Events\Dispatcher|null $dispatcher
     */
    public function __construct(ContainerContract $container = null, DispatcherContract $dispatcher = null)
    {
        $this->setupContainer($container ?: new Container);
        $this->dispatcher = $dispatcher ?: new Dispatcher($this->container);

        parent::__construct($this->container, $this->dispatcher, '');

        $this->setCatchExceptions(true);

        $this->registerBindings()
            ->registerServiceProviders()
            ->registerContainerAliases()
            ->configure();
    }

    /**
     * Gets the name of the command based on input.
     *
     * Proxies to the Laravel default command if there is no app
     * default command.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input The input interface
     *
     * @return string|null With the command name that should be executed.
     */
    protected function getCommandName(InputInterface $input)
    {
        if (($name = parent::getCommandName($input))
            || (!$defaultCommand = $this->config->get('app.default-command'))
        ) {
            return $name;
        }

        return $this->container->make($defaultCommand)->getName();
    }

    /**
     * Configures the console application.
     *
     * Takes in consideration the app name and the app version. Also
     * adds all the application commands.
     *
     * @return $this
     */
    protected function configure()
    {
        if ($name = $this->config->get('app.name')) {
            $this->setName($name);
        }

        if ($version = $this->config->get('app.version')) {
            $this->setVersion($version);
        }

        $commands = collect($this->config->get('app.commands'));

        if (!$this->config->get('app.production')) {
            $commands = $commands->merge($this->commands);
        }

        $commands->push($this->config->get('app.default-command'))
            ->each(
                function ($command) {
                    if ($command) {
                        $this->add($this->container->make($command));
                    }
                }
            );

        return $this;
    }

    /**
     * Register the basic bindings into the container.
     *
     * @return $this
     */
    protected function registerBindings()
    {
        Container::setInstance($this->container);

        $this->container->instance('app', $this->container);

        $this->container->instance(Container::class, $this->container);

        $this->container->instance(
            'config',
            new Repository(
                require BASE_PATH . '/' . 'config/config.php'
            )
        );

        $this->config = $this->container->make('config');

        return $this;
    }

    /**
     * Register the services into the container.
     *
     * @return $this
     */
    protected function registerServiceProviders()
    {
        collect($this->providers)
            ->merge($this->components)
            ->merge($this->config->get('app.providers'))
            ->each(function ($serviceProvider) {
                $instance = new $serviceProvider($this);
                if (method_exists($instance, 'register')) {
                    $instance->register();
                }

                if (method_exists($instance, 'boot')) {
                    $instance->boot();
                }
            });

        return $this;
    }

    /**
     * Register the class aliases in the container.
     *
     * @return $this
     */
    protected function registerContainerAliases()
    {
        foreach ($this->aliases as $key => $aliases) {
            foreach ($aliases as $alias) {
                $this->container->alias($key, $alias);
            }
        }

        return $this;
    }
}

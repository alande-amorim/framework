<?php

/**
 * This file is part of Zero Framework.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace LaravelZero\Framework\Commands\Component;

use LaravelZero\Framework\Contracts\Commands\Component\Finder as FinderContract;

/**
 * The is the Zero Framework component finder class.
 *
 * @author Nuno Maduro <enunomaduro@gmail.com>
 */
class Finder implements FinderContract
{
    /**
     * Finds all the available components.
     *
     * @return string[]
     */
    public function find()
    {
        $components = [];

        foreach ($this->folders(__DIR__) as $organization) {
            foreach ($this->folders($organization) as $project) {
                $components[] = $this->getProjectName($project);
            }
        }

        return $components;
    }

    /**
     * Returns all the folders of the provided dir.
     *
     * @param  string $dir
     *
     * @return string[]
     */
    private function folders($dir)
    {
        return glob($dir . '/*', GLOB_ONLYDIR);
    }

    /**
     * Returns the project name in the form "vendor/package".
     *
     * @param  string $project
     *
     * @return string
     */
    private function getProjectName($project)
    {
        $parts = explode('/', $project);

        $package = strtolower(array_pop($parts));
        $vendor = strtolower(last($parts));

        return "$vendor/$package";
    }
}

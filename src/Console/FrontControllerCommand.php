<?php

namespace Novius\LaravelNovaPageManager\Console;

use Illuminate\Console\GeneratorCommand;

class FrontControllerCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'page-manager:publish-front';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate front controller and add route for pages';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Controller';

    public function handle()
    {
        if (false !== parent::handle()) {
            if (!is_file(base_path('routes/web.php'))) {
                $this->warn('There is no routes/web.php file. Abort without generated new route.');

                return;
            }

            $routeToAppend = file_get_contents(__DIR__.'/stubs/routes.front.stub');
            $routeToAppend = str_replace(
                '{{frontPageRouteName}}',
                config('laravel-nova-page-manager.front_route_name'),
                $routeToAppend
            );

            file_put_contents(
                base_path('routes/web.php'),
                $routeToAppend,
                FILE_APPEND
            );
        }
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        return 'FrontPageController';
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/controller.front.stub');
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Http\Controllers';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }
}

<?php namespace Cyvelnet\Laravel5Fractal;

use Illuminate\Support\ServiceProvider;
use League\Fractal\Manager;

class Laravel5FractalServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {

        // register our alias
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('Fractal', 'Cyvelnet\Laravel5Fractal\Facades\Fractal');

        $source_config = __DIR__ . '/../../config/fractal.php';
        $this->publishes([$source_config => config_path('fractal.php')], 'config');

        $this->loadViewsFrom(__DIR__ . '/../../views', 'fractal');

    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $source_config = __DIR__ . '/../../config/fractal.php';
        $this->mergeConfigFrom($source_config, 'fractal');

        $this->app->singleton('fractal', function ($app) {

            // retrieves if autoload config is set.

            $autoload = $app['config']->get('fractal.autoload');
            $input_key = $app['config']->get('fractal.input_key');
            $serializer = $app['config']->get('fractal.serializer');

            // creating fractal manager instance
            $manager = new Manager();
            $factalNamespace = 'League\\Fractal\\Serializer\\';


            $loadSerializer = (class_exists($factalNamespace . $serializer)) ?
                $factalNamespace . $serializer : $serializer;

            $manager->setSerializer(new $loadSerializer);

            if ($autoload === true AND $includes = $app['request']->input($input_key)) {
                $manager->parseIncludes($includes);
            }

            return new FractalServices($manager, $app['app']);
        });

        // register our command here

        $this->app['command.transformer.generate'] = $this->app->share(
            function ($app) {
                return $app->make('Cyvelnet\Laravel5Fractal\Commands\TransformerGeneratorCommand');
            }
        );
        $this->commands('command.transformer.generate');

    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['fractal', 'command.transformer.generate'];
    }

}

<?php namespace Jenssegers\AB\Commands;

use Jenssegers\AB\Models\Experiment;
use Jenssegers\AB\Models\Goal;

use Config, Schema;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class InstallCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ab:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prepare the A/B testing database.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $connection = Config::get('ab::connection');

        // Create ab_experiments table.
        if ( ! Schema::connection($connection)->hasTable('ab_experiments')) {
            Schema::connection($connection)->create('ab_experiments', function($table)
            {
                $table->increments('id');
                $table->string('name');
                $table->integer('visitors')->unsigned()->default(0);
                $table->integer('engagement')->unsigned()->default(0);
                $table->timestamps();
            });
        }

        // Create ab_goals table.
        if ( ! Schema::connection($connection)->hasTable('ab_goals')) {
            Schema::connection($connection)->create('ab_goals', function($table)
            {
                $table->increments('id');
                $table->string('name');
                $table->string('experiment_id')->index();
                $table->integer('count')->unsigned()->default(0);
                $table->timestamps();
            });
        }

        $this->info('Database schema initialized.');

        $experiments = Config::get('ab.experiments');

        if ( ! $experiments or empty($experiments))
        {
            return $this->error('No experiments configured.');
        }

        $goals = Config::get('ab.goals');

        if ( ! $goals or empty($goals))
        {
            return $this->error('No goals configured.');
        }

        // Populate experiments and goals.
        foreach ($experiments as $experiment)
        {
            $experiment = Experiment::firstOrCreate(['name' => $experiment]);

            foreach ($goals as $goal)
            {
                Goal::firstOrCreate(['name' => $goal, 'experiment_id' => $experiment->id]);
            }
        }

        $this->info('Added ' . count($experiments) . ' experiments.');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array();
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array();
    }

}

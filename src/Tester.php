<?php

namespace Jenssegers\AB;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use Jenssegers\AB\Session\SessionInterface;
use Jenssegers\AB\Models\Experiment;
use Jenssegers\AB\Models\Goal;

class Tester {

    /**
     * The Session instance.
     *
     * @var SessionInterface
     */
    protected $session;

    /**
     * Constructor.
     *
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * Track clicked links and form submissions.
     *
     * @param  Request $request
     * @return void
     */
    public function track(Request $request, $checking = false)
    {
        // Don't track if there is no active experiment.
        if ( ! $this->session->get('experiment')) return;

        // Since there is an ongoing experiment, increase the pageviews.
        // This will only be incremented once during the whole experiment.
        $this->pageview();

        // Check current and previous urls.
        $root = $request->root();
        $from = ltrim(str_replace($root, '', $request->headers->get('referer')), '/');
        $to = ltrim(str_replace($root, '', $request->getPathInfo()), '/');

        // Don't track refreshes.
        if ($from == $to) return;

        // Because the visitor is viewing a new page, trigger engagement.
        // This will only be incremented once during the whole experiment.
        $this->interact();

        $goals = $this->getGoals();

        // Detect goal completion based on the current url.
        if (in_array($to, $goals) || in_array('/' . $to, $goals)) {
            $this->complete($to, $checking);
        }

        // Detect goal completion based on the current route name.
        if ($route = Route::currentRouteName() && in_array($route, $goals)) {
            $this->complete($route, $checking);
        }
    }

    /**
     * Get or compare the current experiment for this session.
     *
     * @param  string  $target
     * @return bool|string
     */
    public function experiment($target = null, $checking = false)
    {
        // Get the existing or new experiment.
        try {
            $experiment = $this->session->get('experiment') ? : $this->nextExperiment(null, $checking);

            if (is_null($target)) {
                return $experiment;
            }

            return $experiment == $target;
        } catch (\Exception $e) {
            \Log::error('Experiments on front may be deleted');
            return false;
        }
    }

    /**
     * Increment the pageviews for the current experiment.
     *
     * @return void
     */
    public function pageview()
    {
        // Only interact once per experiment.
        if ($this->session->get('pageview')) return;

        Experiment::where('name', $this->experiment())->increment('visitors');

        // Mark current experiment as interacted.
        $this->session->set('pageview', 1);
    }

    /**
     * Increment the engagement for the current experiment.
     *
     * @return void
     */
    public function interact()
    {
        // Only interact once per experiment.
        if ($this->session->get('interacted')) return;

        Experiment::where('name', $this->experiment())->increment('engagement');

        // Mark current experiment as interacted.
        $this->session->set('interacted', 1);
    }

    /**
     * Mark a goal as completed for the current experiment.
     *
     * @return void
     */
    public function complete($name, $checking = false)
    {
        // Only complete once per experiment.
        if ($this->session->get("completed_$name")) return;

        // Verify that the goals are in the database.
        $this->checkGoals($checking)

        $goal = Goal::whereHas('experiment', function ($query)
        {
           $query->where('name', $this->experiment());
        })->where('name', $name)->increment('count');

        // Mark current experiment as completed.
        $this->session->set("completed_$name", 1);
    }

    /**
     * Set the current experiment for this session manually.
     *
     * @param string $experiment
     */
    public function setExperiment($experiment, $checking = false)
    {
        if ($this->session->get('experiment') != $experiment) {
            $this->session->set('experiment', $experiment);

            // Increase pageviews for new experiment.
            $this->nextExperiment($experiment, $checking);
        }
    }

    /**
     * Get all experiments.
     *
     * @return array
     */
    public function getExperiments()
    {
        return Config::get('ab.experiments');
    }

    /**
     * Get all goals.
     *
     * @return array
     */
    public function getGoals()
    {
        return Config::get('ab.goals');
    }

    /**
     * Get the session instance.
     *
     * @return SessionInterface
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Set the session instance.
     *
     * @param $session SessionInterface
     */
    public function setSession(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * Clear the session instance.
     *
     * @return SessionInterface
     */
    public function clearSession()
    {
        return $this->session->clear();
    }

    /**
     * If an experiment has initialized get his string.
     *
     * @return string
     */
    public function currentExperiment($checking = false)
    {
        // Verify that the experiments are in the database.
        $this->checkExperiments($checking);

        if ($this->session->get('experiment') != '') {
            $experiment = $this->session->get('experiment');
        } else {
            $experiment = Experiment::orderBy('updated_at', 'asc')->firstOrFail();
            $experiment = $experiment->name;
        }

        return $experiment;
    }

    /**
     * Prepare an experiment for this session.
     *
     * @return string
     */
    protected function nextExperiment($experiment = null, $checking = false)
    {
        // Verify that the experiments are in the database.
        $this->checkExperiments($checking);

        // Clear all session of experiment_, pageview_, interacted_, completed_
        $this->clearSession();

        if ($experiment) {
            $experiment = Experiment::findOrfail($experiment);
        } else {
            $experiment = Experiment::orderBy('visitors', 'asc')->firstOrFail();
        }

        $this->session->set('experiment', $experiment->name);

        // Since there is an ongoing experiment, increase the pageviews.
        // This will only be incremented once during the whole experiment.
        $this->pageview();

        return $experiment->name;
    }

    /**
     * Add experiments to the database.
     *
     * @return void
     */
    protected function checkExperiments($checking = false)
    {
        // Check if the database contains all experiments.
        if ($checking && Experiment::count() != count($this->getExperiments())) {
            // Insert all experiments.
            foreach ($this->getExperiments() as $experiment) {
                Experiment::firstOrCreate(['name' => $experiment]);
            }
        }
    }

    /**
     * Check if there are active experiments.
     *
     * @return string
     */
    public function hasExperiments()
    {
        $count = Experiment::count();

        return $count > 1;
    }

    /**
     * Add goals to the database.
     *
     * @return void
     */
    protected function checkGoals($checking = false)
    {
        // Check if the database contains all goals.
        if ($checking && Goal::count() != count($this->getGoals())) {

            $experiments = Experiment::all();

            // Insert all goals for particular experiment.
            foreach ($experiments as $experiment) {
                foreach ($this->getGoals() as $goal) {
                    Goal::firstOrCreate(['name' => $goal, 'experiment_id' => $experiment->id]);
                }
            }
        }
    }

}

<?php

namespace App\Providers;

use App\Nova\App;
use App\Nova\User;
use App\Nova\Layer;
use App\Nova\Quote;
use App\Nova\Product;
use App\Nova\Project;
use App\Nova\Customer;
use App\Nova\Deadline;
use Laravel\Nova\Nova;
use App\Enums\UserRole;
use App\Nova\ArchivedStoryShowedByCustomer;
use App\Nova\ArchivedStories;
use App\Nova\StoryShowedByCustomer;
use App\Nova\AssignedToMeStory;
use App\Nova\CustomerStory;
use App\Nova\CustomerFeatureStory;
use App\Nova\DeveloperStory;
use Illuminate\Http\Request;
use App\Nova\RecurringProduct;
use App\Nova\ToBeTestedStory;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Nova\Dashboards\Main;
use Laravel\Nova\Menu\MenuSection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Blade;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\NovaApplicationServiceProvider;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
        Nova::withBreadcrumbs(true);


        Nova::style('nova-custom', public_path('/nova-custom.css'));


        Nova::mainMenu(function (Request $request) {
            $newStoryUrl = '/resources/stories/new';
            if (auth()->user()->hasRole(UserRole::Customer)) {
                $newStoryUrl = '/resources/story-showed-by-customers/new';
            }

            return [
                MenuSection::dashboard(Main::class)->icon('chart-bar')->canSee(function ($request) {
                    if ($request->user() == null)
                        return false;
                    return $request->user()->hasRole(UserRole::Admin) || $request->user()->hasRole(UserRole::Manager) || $request->user()->hasRole(UserRole::Developer);
                }),

                MenuSection::make('ADMIN', [
                    MenuItem::resource(User::class),
                ])->icon('user')->collapsable(),

                MenuSection::make('APP', [
                    MenuItem::resource(App::class),
                    MenuItem::resource(Layer::class),
                ])->icon('document-text')->collapsable(),

                MenuSection::make('CRM', [
                    MenuItem::resource(Customer::class),
                    MenuItem::resource(Project::class),
                    MenuItem::resource(Product::class),
                    MenuItem::resource(RecurringProduct::class),
                    MenuItem::resource(Quote::class),
                ])->icon('users')->collapsable(),

                MenuSection::make('DEV', [
                    MenuItem::resource(AssignedToMeStory::class),
                    MenuItem::resource(ToBeTestedStory::class),
                    MenuItem::resource(ArchivedStories::class),
                    MenuItem::resource(Deadline::class),
                    MenuItem::resource(CustomerStory::class),
                    MenuItem::resource(CustomerFeatureStory::class),
                    MenuItem::resource(DeveloperStory::class)
                ])->icon('code')->collapsable()->canSee(function ($request) {
                    if ($request->user() == null)
                        return false;
                    return $request->user()->hasRole(UserRole::Admin) || $request->user()->hasRole(UserRole::Manager) || $request->user()->hasRole(UserRole::Developer);
                }),

                MenuSection::make('CUSTOMER', [
                    MenuItem::resource(StoryShowedByCustomer::class),
                    MenuItem::resource(ArchivedStoryShowedByCustomer::class)
                ])->canSee(function ($request) {
                    if ($request->user() == null)
                        return false;
                    return $request->user()->hasRole(UserRole::Customer);
                })->icon('at-symbol')->collapsable(),

                MenuSection::make('ACTIONS', [
                    MenuItem::link('Create a new story', $newStoryUrl),
                ])->icon('pencil')->collapsable(),
            ];
        });

        $this->getFooter();
    }

    /**
     * Register the Nova routes.
     *
     * @return void
     */
    protected function routes()
    {
        Nova::routes()
            ->withAuthenticationRoutes()
            ->withPasswordResetRoutes()
            ->register();
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewNova', function ($user) {
            $userIsAdmin = $user->hasRole(UserRole::Admin);
            $userIsEditor = $user->hasRole(UserRole::Editor);
            $userIsDeveloper = $user->hasRole(UserRole::Developer);
            $userIsManager = $user->hasRole(UserRole::Manager);
            $userIsCustomer = $user->hasRole(UserRole::Customer);
            $debug = config('services.app_environment');

            if (config('services.app_environment') == 'production' || config('services.app_environment') == 'develop') {
                return $userIsAdmin || $userIsEditor || $userIsDeveloper || $userIsManager || $userIsCustomer;
            }
            return true;
        });
    }

    /**
     * Get the dashboards that should be listed in the Nova sidebar.
     *
     * @return array
     */
    protected function dashboards()
    {
        return [
            new \App\Nova\Dashboards\Main,
        ];
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     *
     * @return array
     */
    public function tools()
    {
        return [];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Nova::initialPath(function (Request $request) {
            if (!$request->user() == null) {
                $user = $request->user();
                if ($user->hasRole(UserRole::Customer)) {
                    return $user->initialPath();
                }
            }
        });
    }

    //create a footer
    private function getFooter()
    {
        Nova::footer(function () {
            return Blade::render('nova/footer');
        });
    }
}

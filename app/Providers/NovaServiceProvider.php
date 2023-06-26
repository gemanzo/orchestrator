<?php

namespace App\Providers;

use App\Nova\App;
use App\Nova\Epic;
use App\Nova\User;
use App\Nova\Layer;
use App\Nova\Quote;
use App\Nova\NewEpic;
use App\Nova\Product;
use App\Nova\Project;
use App\Nova\Customer;
use App\Nova\DoneEpic;
use App\Nova\TestEpic;
use Laravel\Nova\Nova;
use App\Enums\UserRole;
use App\Nova\Deadline;
use App\Nova\Milestone;
use App\Nova\ProjectEpic;
use App\Nova\ProgressEpic;
use App\Nova\RejectedEpic;
use Illuminate\Http\Request;
use App\Nova\RecurringProduct;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Nova\Dashboards\Main;
use Laravel\Nova\Menu\MenuSection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Blade;
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
        $createStoryUrl = 'stories/new';
        parent::boot();

        Nova::style('nova-custom', public_path('/nova-custom.css'));

        Nova::withBreadcrumbs(true);

        Nova::mainMenu(function (Request $request) {
            return [
                MenuSection::dashboard(Main::class)->icon('chart-bar'),

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
                    MenuItem::resource(Deadline::class)
                ])->icon('users')->collapsable(),

                MenuSection::make('DEV', [
                    MenuItem::resource(Milestone::class),
                    MenuItem::resource(Epic::class),
                    MenuItem::resource(NewEpic::class),
                    MenuItem::resource(ProjectEpic::class),
                    MenuItem::resource(ProgressEpic::class),
                    MenuItem::resource(TestEpic::class),
                    MenuItem::resource(DoneEpic::class),
                    MenuItem::resource(RejectedEpic::class),
                ])->icon('code')->collapsable(),

                MenuSection::make('ACTIONS', [
                    MenuItem::link('Create a new story', '/resources/stories/new'),
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
            $isInDevelopment = env('APP_ENV') == 'develop';
            $isInProduction = env('APP_ENV') == 'production';

            if ($isInDevelopment || $isInProduction) {
                return $userIsAdmin && $userIsEditor && $userIsDeveloper && $userIsManager;
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
        //
    }

    //create a footer
    private function getFooter()
    {
        Nova::footer(function () {
            return Blade::render('nova/footer');
        });
    }
}

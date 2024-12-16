<?php

namespace App\Observers;

use App\Models\Story;
use App\Models\StoryLog;
use App\Enums\StoryStatus;
use App\Actions\StoryTimeService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;

class StoryObserver
{
    /**
     * Handle the Story "created" event.
     */
    public function created(Story $story): void
    {
        //
    }

    /**
     * Handle the Story "updated" event.
     */
    public function updated(Story $story): void
    {
        $this->syncStoryCalendarIfStatusChanged($story);
        $this->createStoryLog($story);
    }

    /**
     * Handle the Story "updated" event.
     */
    public function saving(Story $story): void
    {
        // Only one progress story per user is allowed
        // moves all other progress stories in Todo
        if ($story->isDirty('status') && $story->status === StoryStatus::Progress->value) {
            Story::where('user_id', $story->user_id)
                ->where('status', StoryStatus::Progress->value)
                ->whereNot('id', $story->id)
                //->update('status', StoryStatus::Todo->value); -> this doesn't trigger events
                ->get()
                ->each(function (Story $progressStory) {
                    $progressStory->status = StoryStatus::Todo->value;
                    $progressStory->save(); // -> this triggers events (like the gogle calendar update)
                });
        }
    }

    private function syncStoryCalendarIfStatusChanged(Story $story): void
    {
        if ($story->isDirty('status') && $story->status === StoryStatus::Todo->value) {
            $developerId = $story->user_id;
            if ($developerId) {
                $developer = DB::table('users')->where('id', $developerId)->first();
                if ($developer && $developer->email) {
                    Artisan::call('sync:stories-calendar', ['developerEmail' => $developer->email]);
                }
            }
        }
    }

    private function createStoryLog(Story $story): void
    {
        $dirtyFields = $story->getDirty();

        if (app()->runningInConsole())
            $user = User::where('email', 'orchestrator_artisan@webmapp.it')->first(); //there is a seeder for this user (PhpArtisanUserSeeder)
        else
            $user = Auth::user();

        $jsonChanges = [];

        foreach ($dirtyFields as $field => $newValue) {
            if ($field === 'description') {
                $newValue = 'change description';
            }
            $jsonChanges[$field] = $newValue;
        }

        if (count($jsonChanges) > 0) {
            $timestamp = now()->format('Y-m-d H:i');
            $storyLog = StoryLog::create([
                'story_id' => $story->id,
                'user_id' => $user->id,
                'viewed_at' => $timestamp,
                'changes' => $jsonChanges,
            ]);
            $story->saveQuietly();
            StoryTimeService::run($storyLog->story);
        }
    }

    /**
     * Handle the Story "deleted" event.
     */
    public function deleted(Story $story): void
    {
        //
    }

    /**
     * Handle the Story "restored" event.
     */
    public function restored(Story $story): void
    {
        //
    }

    /**
     * Handle the Story "force deleted" event.
     */
    public function forceDeleted(Story $story): void
    {
        //
    }
}

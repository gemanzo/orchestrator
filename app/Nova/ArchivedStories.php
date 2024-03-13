<?php

namespace App\Nova;

use App\Enums\StoryStatus;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Http\Requests\NovaRequest;

class ArchivedStories extends Story
{

    public static function indexQuery(NovaRequest $request, $query)
    {
        $allowedStatuses = [StoryStatus::Released, StoryStatus::Rejected];
        return $query->whereIn('status', $allowedStatuses)->where('user_id', $request->user()->id);
    }

    public static function authorizedToCreate(Request $request)
    {
        return false;
    }
}

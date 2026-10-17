<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Resources\Resource;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * Every CMS resource is guarded by a permission slug. Resources whose model
 * has a policy (confidential records) additionally defer to that policy.
 */
abstract class BaseResource extends Resource
{
    /** Permission required to see this resource at all. */
    protected static string $permission = 'settings.manage';

    /** Actions never allowed through the CMS (e.g. deleting financial records). */
    protected static array $deniedActions = [];

    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        $action = $action instanceof UnitEnum ? ($action->value ?? $action->name) : $action;
        $user = auth()->user();

        if (! $user instanceof User || ! $user->canAccessPanel(filament()->getCurrentPanel() ?? filament()->getDefaultPanel())) {
            return Response::deny();
        }
        if (in_array($action, static::$deniedActions, true)) {
            return Response::deny();
        }

        $model = $record ?? static::getModel();
        if (Gate::getPolicyFor($model)) {
            return Gate::forUser($user)->inspect($action, $model);
        }

        return $user->hasPermission(static::$permission) ? Response::allow() : Response::deny();
    }
}

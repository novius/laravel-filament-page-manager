<?php

namespace Novius\LaravelFilamentPageManager\Filament\Resources\Concerns;

use BackedEnum;
use Closure;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Concerns\EvaluatesClosures;
use Illuminate\Support\Arr;

/**
 * @mixin EvaluatesClosures
 */
trait InteractWithGuards
{
    protected array|Closure $guards = [];

    public function setGuards(array|Closure $guards): static
    {
        $this->guards = $guards;

        return $this;
    }

    public function getGuards(): array
    {
        $guards = (array) $this->evaluate($this->guards);

        return array_intersect($guards, array_keys(config('auth.guards', [])));
    }

    protected function getGuardResource(?string $guard): ?string
    {
        $guard_config = Arr::get(config('auth.guards'), $guard);
        if (is_array($guard_config)) {
            $provider = Arr::get($guard_config, 'provider');
            if ($provider !== null) {
                $provider_config = Arr::get(config('auth.providers'), $provider);
                if (is_array($provider_config)) {
                    $model = Arr::get($provider_config, 'model');
                    if ($model !== null) {
                        return Filament::getModelResource($model);
                    }
                }
            }
        }

        return null;
    }

    protected function getGuardName(?string $guard): ?string
    {
        /** @var class-string<resource>|null $resource */
        $resource = $this->getGuardResource($guard);

        return $resource !== null ? $resource::getModelLabel() : $guard;
    }

    protected function getGuardIcon(?string $guard): string|BackedEnum|null
    {
        /** @var class-string<resource>|null $resource */
        $resource = $this->getGuardResource($guard);
        if ($resource !== null) {
            $icon = $resource::getNavigationIcon() ?? 'heroicon-o-lock';
        } elseif ($guard !== null) {
            $icon = 'heroicon-o-lock';
        }

        return $icon ?? null;
    }
}

<?php

namespace Filament;

use Closure;
use Filament\Events\ServingFilament;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;

class FilamentManager
{
    protected bool $isNavigationMounted = false;

    protected array $navigationGroups = [];

    protected array $navigationItems = [];

    protected array $pages = [];

    protected array $resources = [];

    protected array $scripts = [];

    protected array $scriptData = [];

    protected array $styles = [];

    protected ?string $themeUrl = null;

    protected array $widgets = [];

    public function auth(): StatefulGuard
    {
        return auth()->guard(config('filament.auth.guard'));
    }

    public function mountNavigation(): void
    {
        foreach (static::getPages() as $page) {
            $page::registerNavigationItems();
        }

        foreach (static::getResources() as $resource) {
            $resource::registerNavigationItems();
        }

        $this->isNavigationMounted = true;
    }

    public function registerNavigationGroups(array $groups): void
    {
        $this->navigationGroups = array_merge($this->navigationGroups, $groups);
    }

    public function registerNavigationItems(array $items): void
    {
        $this->navigationItems = array_merge($this->navigationItems, $items);
    }

    public function registerPages(array $pages): void
    {
        $this->pages = array_merge($this->pages, $pages);
    }

    public function registerResources(array $resources): void
    {
        $this->resources = array_merge($this->resources, $resources);
    }

    public function registerScripts(array $scripts): void
    {
        $this->scripts = array_merge($this->scripts, $scripts);
    }

    public function registerScriptData(array $data): void
    {
        $this->scriptData = array_merge($this->scriptData, $data);
    }

    public function registerStyles(array $styles): void
    {
        $this->styles = array_merge($this->styles, $styles);
    }

    public function registerTheme(string $url): void
    {
        $this->themeUrl = $url;
    }

    public function registerWidgets(array $widgets): void
    {
        $this->widgets = array_merge($this->widgets, $widgets);
    }

    public function serving(Closure $callback): void
    {
        Event::listen(ServingFilament::class, $callback);
    }

    public function getNavigation(): array
    {
        if (! $this->isNavigationMounted) {
            $this->mountNavigation();
        }

        $groupedItems = collect($this->navigationItems)
            ->sortBy(fn (Navigation\NavigationItem $item): int => $item->getSort())
            ->groupBy(fn (Navigation\NavigationItem $item): ?string => $item->getGroup());

        $sortedGroups = $groupedItems
            ->keys()
            ->sortBy(function (?string $group): int {
                if (! $group) {
                    return -1;
                }

                $sort = array_search($group, $this->navigationGroups);

                if ($sort === false) {
                    return count($this->navigationGroups);
                }

                return $sort;
            });

        return $sortedGroups
            ->mapWithKeys(function (?string $group) use ($groupedItems): array {
                return [$group => $groupedItems->get($group)];
            })
            ->toArray();
    }

    public function getNavigationGroups(): array
    {
        return $this->navigationGroups;
    }

    public function getNavigationItems(): array
    {
        return $this->navigationItems;
    }

    public function getPages(): array
    {
        return $this->pages;
    }

    public function getResources(): array
    {
        return $this->resources;
    }

    public function getScripts(): array
    {
        return $this->scripts;
    }

    public function getScriptData(): array
    {
        return $this->scriptData;
    }

    public function getStyles(): array
    {
        return $this->styles;
    }

    public function getThemeUrl(): string
    {
        return $this->themeUrl ?? route('filament.asset', [
            'id' => get_asset_id('app.css'),
            'path' => 'app.css',
        ]);
    }

    public function getUrl(): ?string
    {
        $flatNavigation = Arr::flatten($this->getNavigation());

        $firstItem = $flatNavigation[0] ?? null;

        if (! $firstItem) {
            return null;
        }

        return $firstItem->getUrl();
    }

    public function getUserAvatarUrl(Authenticatable $user): string
    {
        $avatar = null;

        if ($user instanceof HasAvatar) {
            $avatar = $user->getFilamentAvatarUrl();
        }

        if ($avatar) {
            return $avatar;
        }

        $provider = config('filament.default_avatar_provider');

        return (new $provider())->get($user);
    }

    public function getUserName(Authenticatable $user): string
    {
        if ($user instanceof HasName) {
            return $user->getFilamentName();
        }

        return $user->name;
    }

    public function getWidgets(): array
    {
        return collect($this->widgets)
            ->sortBy(fn (string $widget): int => $widget::getSort())
            ->toArray();
    }
}

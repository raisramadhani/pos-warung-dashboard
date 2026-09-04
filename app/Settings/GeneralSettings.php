<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public ?string $brandName;

    public ?string $brandLogo;

    public ?int $brandLogoHeight;

    public ?string $darkModeBrandLogo;

    public bool $breadcrumbs;

    public bool $collapsibleNavigationGroups;

    public bool $sidebarCollapsibleOnDesktop;

    public bool $sidebarFullyCollapsibleOnDesktop;

    public ?string $favicon;

    public bool $errorNotifications;

    public bool $revealablePasswords;

    public bool $topNavigation;

    public bool $topbar = false;

    public ?string $resourceCreatePageRedirect;

    public ?string $resourceEditPageRedirect;

    public bool $readOnlyRelationManagersOnResourceViewPagesByDefault;

    public bool $unsavedChangesAlerts;

    public ?string $subNavigationPosition;

    public ?string $databaseNotificationsPolling = null;

    public ?string $loginPageBackgroundImage = null;

    public string $loginFormPanelPosition = 'right';

    public string $loginMobileFormPanelPosition = 'top';

    public string $loginEmptyPanelBackgroundImageOpacity = '100%';

    public static function group(): string
    {
        return 'general';
    }
}

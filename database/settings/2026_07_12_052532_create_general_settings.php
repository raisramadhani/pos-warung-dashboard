<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.brandName', 'Abra POS');
        $this->migrator->add('general.brandLogo', null);
        $this->migrator->add('general.brandLogoHeight', null);
        $this->migrator->add('general.darkModeBrandLogo', null);
        $this->migrator->add('general.breadcrumbs', true);
        $this->migrator->add('general.collapsibleNavigationGroups', true);
        $this->migrator->add('general.sidebarCollapsibleOnDesktop', true);
        $this->migrator->add('general.sidebarFullyCollapsibleOnDesktop', true);
        $this->migrator->add('general.favicon', null);
        $this->migrator->add('general.errorNotifications', true);
        $this->migrator->add('general.revealablePasswords', true);
        $this->migrator->add('general.topNavigation', false);
        $this->migrator->add('general.topbar', true);
        $this->migrator->add('general.resourceCreatePageRedirect', 'view');
        $this->migrator->add('general.resourceEditPageRedirect', 'view');
        $this->migrator->add('general.unsavedChangesAlerts', true);
        $this->migrator->add('general.subNavigationPosition', 'top');
        $this->migrator->add('general.databaseNotificationsPolling', null);
        $this->migrator->add('general.readOnlyRelationManagersOnResourceViewPagesByDefault', true);
    }

    public function down(): void
    {
        $this->migrator->delete('general.brandName');
        $this->migrator->delete('general.brandLogo');
        $this->migrator->delete('general.brandLogoHeight');
        $this->migrator->delete('general.darkModeBrandLogo');
        $this->migrator->delete('general.breadcrumbs');
        $this->migrator->delete('general.collapsibleNavigationGroups');
        $this->migrator->delete('general.sidebarCollapsibleOnDesktop');
        $this->migrator->delete('general.sidebarFullyCollapsibleOnDesktop');
        $this->migrator->delete('general.favicon');
        $this->migrator->delete('general.errorNotifications');
        $this->migrator->delete('general.revealablePasswords');
        $this->migrator->delete('general.topNavigation');
        $this->migrator->delete('general.topbar');
        $this->migrator->delete('general.resourceCreatePageRedirect');
        $this->migrator->delete('general.resourceEditPageRedirect');
        $this->migrator->delete('general.unsavedChangesAlerts');
        $this->migrator->delete('general.subNavigationPosition');
        $this->migrator->delete('general.databaseNotificationsPolling');
        $this->migrator->delete('general.readOnlyRelationManagersOnResourceViewPagesByDefault');
    }
};

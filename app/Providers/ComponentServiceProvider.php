<?php

namespace App\Providers;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Schema;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Livewire\Component;

class ComponentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureTables();
        $this->configureForms();
        $this->configureFilters();

        Schema::configureUsing(function (Schema $schema): void {
            $schema->columns(1);
        });
    }

    private function configureActions(): void
    {
        ActionGroup::configureUsing(function (ActionGroup $actionGroup): void {
            $actionGroup
                ->label('Aksi')
                ->button();
        });
        CreateAction::configureUsing(function (CreateAction $action): void {
            $action->icon('tabler-plus');
        });
        EditAction::configureUsing(function (EditAction $action): void {
            $action->icon('tabler-edit');
        });
        DeleteAction::configureUsing(function (DeleteAction $action): void {
            $action
                ->icon('tabler-trash')
                ->tooltip('Pindah ke Sampah');
            $action->after(function (Component $livewire): void {
                $livewire->dispatch('refresh-sidebar');
            });
        });

        ForceDeleteAction::configureUsing(function (ForceDeleteAction $action): void {
            $action
                ->icon('tabler-trash-x')
                ->color('danger')
                ->tooltip('Hapus Permanen');
            $action->after(function (Component $livewire): void {
                $livewire->dispatch('refresh-sidebar');
            });
        });

        RestoreAction::configureUsing(function (RestoreAction $action): void {
            $action
                ->icon('tabler-arrow-back-up')
                ->color('success')
                ->tooltip('Pulihkan dari Sampah');
            $action->after(function (Component $livewire): void {
                $livewire->dispatch('refresh-sidebar');
            });
        });

        DeleteBulkAction::configureUsing(function (DeleteBulkAction $action): void {
            $action
                ->fetchSelectedRecords(false)
                ->after(function (Component $livewire): void {
                    $livewire->dispatch('refresh-sidebar');
                });
        });

        ForceDeleteBulkAction::configureUsing(function (ForceDeleteBulkAction $action): void {
            $action
                ->fetchSelectedRecords(false)
                ->failureNotificationBody('Pastikan data yang dipilih telah dipindahkan ke Sampah sebelum dihapus permanen.')
                ->successNotificationTitle('Data segera dihapus permanen di latar belakang')
                ->after(function (Component $livewire): void {
                    $livewire->dispatch('refresh-sidebar');
                });
        });

        RestoreBulkAction::configureUsing(function (RestoreBulkAction $action): void {
            $action
                ->fetchSelectedRecords(false)
                ->after(function (Component $livewire): void {
                    $livewire->dispatch('refresh-sidebar');
                });
        });

        BulkAction::configureUsing(function (BulkAction $action): void {
            $action->deselectRecordsAfterCompletion();
        });
    }

    private function configureTables(): void
    {
        // @codeCoverageIgnoreStart
        Table::configureUsing(function (Table $table): void {
            $table->defaultSort('id', 'desc');
            $table->stackedOnMobile();
            $table->paginationPageOptions([10, 25, 50, 100]);
            $table->defaultPaginationPageOption(25);
            $table->defaultDateDisplayFormat('l, d F Y');
            $table->defaultTimeDisplayFormat('H:i');
            $table->defaultDateTimeDisplayFormat('l, d F Y H:i');
            $table->defaultCurrency('IDR');
            $table->defaultNumberLocale('id-ID');
            $table->recordClasses(function (Model|array $record): ?string {
                if (\is_array($record)) {
                    return null;
                }

                if (isset($record->deleted_at) && $record->deleted_at) {
                    return 'data-deleted';
                }

                return null;
            });
            $table->deferFilters(false);
            $table->deselectAllRecordsWhenFiltered(false);
            // $table->filtersTriggerAction(
            //     fn (Action $action) => $action
            //         ->button()
            //         ->label('Filter'),
            // );
        });
        // @codeCoverageIgnoreEnd
    }

    private function configureForms(): void
    {
        RichEditor::configureUsing(function (RichEditor $richEditor): void {
            $richEditor->fileAttachmentsDisk('s3')
                ->fileAttachmentsDirectory('attachments')
                ->fileAttachmentsVisibility('private')
                ->fileAttachmentsMaxSize(5120);
        });

        TagsInput::configureUsing(function (TagsInput $tagsInput): void {
            $tagsInput->trim();
            $tagsInput->afterStateUpdated(fn (Component $livewire, Field $component): mixed => $livewire->validateOnly($component->getStatePath()));
        });

        TextInput::configureUsing(function (TextInput $textInput): void {
            $textInput->trim();
            $textInput->afterStateUpdated(fn (Component $livewire, Field $component): mixed => $livewire->validateOnly($component->getStatePath()));
        });

        Textarea::configureUsing(function (Textarea $textarea): void {
            $textarea->trim();
        });

        DatePicker::configureUsing(function (DatePicker $datePicker): void {
            $datePicker->displayFormat('l, d F Y');
            $datePicker->native(false);
            $datePicker->closeOnDateSelection();
            $datePicker->live();
            $datePicker->afterStateUpdated(fn (Component $livewire, Field $component): mixed => $livewire->validateOnly($component->getStatePath()));
        });

        Select::configureUsing(function (Select $select): void {
            $select->native(false);
        });

        ToggleButtons::configureUsing(function (ToggleButtons $toggle): void {
            $toggle->inline();
        });
    }

    private function configureFilters(): void
    {
        TrashedFilter::configureUsing(function (TrashedFilter $filter): void {
            $filter->native(false);
            $filter->default(true);
        });

        SelectFilter::configureUsing(function (SelectFilter $filter): void {
            $filter->native(false);
        });
    }
}

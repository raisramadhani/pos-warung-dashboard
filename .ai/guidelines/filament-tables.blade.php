# Filament Tables — Heading & Description

Saat membuat atau mengubah Table di Filament, patuhi aturan ini untuk menghindari duplikasi heading:

- Jangan menambahkan `->heading()` dan `->description()` di dalam Table untuk
table milik **Resource** atau **custom Page**. Karena Heading/subheading sebaiknya
didefinisikan di `Pages/List*` (`$heading` / `$subheading`), jadi menambahkannya
di table hanya duplikasi UI.
- Untuk table di dalam **widget** (`TableWidget`): cukup `->heading()` saja,
tanpa `->description()`.

## Soft Deletes — View vs. System

Beberapa model master data menggunakan `SoftDeletes` agar data tidak hilang
permanen. Namun dari sisi tampilan (view) **tidak perlu** menampilkan:

- `TrashedFilter::make()` (filter untuk menampilkan data yang sudah dihapus)
- `ForceDeleteAction::make()` (tombol "Hapus Permanen")
- `RestoreAction::make()` (tombol "Pulihkan dari Sampah")

Cukup gunakan `DeleteAction::make()` dengan label "Hapus" yang akan melakukan
soft delete. Komponen `DeleteAction`, `ForceDeleteAction`, `RestoreAction`, dan
`TrashedFilter` sudah dikonfigurasi secara global di `ComponentServiceProvider`,
jadi per table hanya perlu **tidak menambahkannya** (`ForceDeleteAction`,
`RestoreAction`, `TrashedFilter`) — `DeleteAction` sudah otomatis tampil dengan
ikon dan label yang sesuai.

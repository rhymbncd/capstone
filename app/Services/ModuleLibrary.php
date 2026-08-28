<?php

namespace App\Services;

use App\Models\ModuleStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\StorageAttributes;

/**
 * The shared module-library logic behind the teacher and admin dashboards:
 * merge the Supabase Storage bucket listing with the module_status
 * moderation rows, and handle uploads / metadata / deletion.
 */
class ModuleLibrary
{
    private const DISK = 'supabase';

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        $disk = Storage::disk(self::DISK);

        $paths = collect($disk->getDriver()->listContents('', false)->toArray())
            ->filter(fn (StorageAttributes $a) => $a->isFile())
            ->reject(fn (StorageAttributes $a) => str_starts_with(basename($a->path()), '.'))
            ->keyBy(fn (StorageAttributes $a) => $a->path());

        $statuses = ModuleStatus::all()->keyBy('file_name');

        // A file with no moderation row yet enters the queue as "pending".
        foreach ($paths->keys() as $path) {
            if (! $statuses->has($path)) {
                $statuses->put($path, ModuleStatus::create(['file_name' => $path, 'status' => 'pending']));
            }
        }

        return $paths->map(function (StorageAttributes $attr, string $path) use ($statuses) {
            /** @var ModuleStatus $row */
            $row = $statuses->get($path);
            $rawName = preg_replace('/^\d+_/', '', basename($path));

            return [
                'id' => $row->id,
                'dbId' => $row->id,
                'storageName' => $path,
                'title' => $row->module_title ?: pathinfo($rawName, PATHINFO_FILENAME),
                'desc' => $row->module_desc ?? '',
                'topic' => $row->module_topic ?? '',
                'dbStatus' => $row->status,
                'status' => match ($row->status) {
                    'approved' => 'Published',
                    'rejected' => 'Rejected',
                    default => 'Pending Review',
                },
                'fileName' => $rawName,
                'fileSize' => $attr->fileSize() ?: 0,
                'date' => Carbon::createFromTimestamp($attr->lastModified() ?: $row->created_at?->timestamp ?? now()->timestamp)->toIso8601String(),
                'fileUrl' => route('modules.file', $row->id),
            ];
        })->values()->all();
    }

    /**
     * Upload a new module file and open a pending moderation row for it.
     *
     * @param  array{module_title: ?string, module_desc: ?string, module_topic: ?string}  $meta
     */
    public function upload(UploadedFile $file, array $meta): ModuleStatus
    {
        $path = $this->store($file);

        return ModuleStatus::updateOrCreate(
            ['file_name' => $path],
            [...$this->cleanMeta($meta), 'status' => 'pending'],
        );
    }

    /**
     * @param  array{module_title: ?string, module_desc: ?string, module_topic: ?string}  $meta
     */
    public function update(ModuleStatus $module, array $meta, ?UploadedFile $file = null): ModuleStatus
    {
        $attributes = $this->cleanMeta($meta);

        if ($file) {
            Storage::disk(self::DISK)->delete($module->file_name);
            $attributes['file_name'] = $this->store($file);
        }

        $module->update($attributes);

        return $module;
    }

    public function delete(ModuleStatus $module): void
    {
        Storage::disk(self::DISK)->delete($module->file_name);
        $module->delete();
    }

    private function store(UploadedFile $file): string
    {
        $safeName = preg_replace('/[^a-zA-Z0-9.\-_]/', '_', $file->getClientOriginalName());

        return Storage::disk(self::DISK)->putFileAs('', $file, now()->timestamp.'_'.$safeName);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, ?string>
     */
    private function cleanMeta(array $meta): array
    {
        return array_filter([
            'module_title' => $meta['module_title'] ?? null,
            'module_desc' => $meta['module_desc'] ?? null,
            'module_topic' => $meta['module_topic'] ?? null,
        ], fn ($v) => $v !== null);
    }
}

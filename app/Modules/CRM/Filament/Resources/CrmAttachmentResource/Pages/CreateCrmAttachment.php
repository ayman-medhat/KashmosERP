<?php

namespace App\Modules\CRM\Filament\Resources\CrmAttachmentResource\Pages;

use App\Modules\CRM\Filament\Resources\CrmAttachmentResource;
use App\Modules\CRM\Models\CrmAttachment;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateCrmAttachment extends CreateRecord
{
    protected static string $resource = CrmAttachmentResource::class;

    protected function handleRecordCreation(array $data): CrmAttachment
    {
        $data['uuid'] ??= (string) Str::uuid();
        $data['disk'] ??= 'public';
        $data['file_name'] ??= basename((string) ($data['file_path'] ?? 'file'));
        $data['created_by'] = auth()->id();

        if (is_string($data['file_path'] ?? null) && Storage::disk($data['disk'])->exists($data['file_path'])) {
            $data['size_bytes'] = Storage::disk($data['disk'])->size($data['file_path']) ?: 0;
            $data['mime_type'] ??= Storage::disk($data['disk'])->mimeType($data['file_path']) ?: null;
        }

        return CrmAttachment::query()->create($data);
    }
}


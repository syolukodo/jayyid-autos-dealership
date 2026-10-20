<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * Profile photos are stored on users.avatar as a data URI (JPG/PNG up to
 * 300KB), so no upload directory or symlink is needed.
 */
final class Avatar
{
    public const FIELDS = ['avatar', 'avatar_camera'];

    /**
     * Validation rules for both upload inputs (the button and the camera badge).
     *
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return array_fill_keys(self::FIELDS, ['nullable', 'file', 'mimes:jpeg,jpg,png', 'max:300']);
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        $messages = [];
        foreach (self::FIELDS as $field) {
            $messages[$field.'.mimes'] = 'Profile photos must be JPG or PNG images.';
            $messages[$field.'.max'] = 'Profile photos must be 300KB or smaller.';
            $messages[$field.'.uploaded'] = 'The photo upload failed. Please try again with a JPG or PNG under 300KB.';
        }

        return $messages;
    }

    /**
     * The new avatar value: a data URI, null to remove the photo, or false to leave it unchanged.
     */
    public static function fromRequest(Request $request): string|null|false
    {
        foreach (self::FIELDS as $field) {
            $file = $request->file($field);
            if ($file instanceof UploadedFile) {
                return 'data:'.$file->getMimeType().';base64,'.base64_encode((string) $file->get());
            }
        }

        return $request->input('remove_avatar') === '1' ? null : false;
    }
}

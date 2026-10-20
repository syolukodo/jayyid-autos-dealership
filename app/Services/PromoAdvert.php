<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Landing page popup advert managed in Admin > Settings. The version changes
 * whenever the image is replaced so returning visitors see the new advert.
 */
class PromoAdvert
{
    private const UPLOAD_DIRECTORY = 'promo';

    public function __construct(private readonly SettingsRepository $settings) {}

    public function enabled(): bool
    {
        return $this->settings->enabled('promo_enabled');
    }

    /**
     * Image path under public/, falling back to the bundled default if the file is gone.
     */
    public function image(): string
    {
        $image = $this->settings->get('promo_image', $this->defaultImage());

        return is_file(public_path($image)) ? $image : $this->defaultImage();
    }

    public function imageUrl(): string
    {
        return asset($this->image()).'?v='.rawurlencode($this->version());
    }

    public function link(): string
    {
        return $this->settings->get('promo_link');
    }

    /**
     * Where the advert's image and button lead.
     */
    public function linkUrl(): string
    {
        $link = $this->link();

        return $link === '' ? route('inventory.index') : url($link);
    }

    public function version(): string
    {
        return $this->settings->get('promo_version', 'default');
    }

    public function isCustom(): bool
    {
        return $this->image() !== $this->defaultImage();
    }

    public function update(bool $enabled, string $link, ?UploadedFile $image, bool $restoreDefault): void
    {
        $values = ['promo_enabled' => $enabled ? '1' : '0', 'promo_link' => $link];

        $newImage = match (true) {
            $image !== null => 'uploads/'.$image->store(self::UPLOAD_DIRECTORY, 'uploads'),
            $restoreDefault => $this->defaultImage(),
            default => null,
        };

        if ($newImage !== null) {
            $this->deleteCustomImage();
            $values['promo_image'] = $newImage;
            $values['promo_version'] = (string) now()->getTimestamp();
        }

        $this->settings->save($values);
    }

    private function deleteCustomImage(): void
    {
        $current = $this->image();
        if ($this->isCustom() && str_starts_with($current, 'uploads/')) {
            Storage::disk('uploads')->delete(substr($current, strlen('uploads/')));
        }
    }

    private function defaultImage(): string
    {
        return config('showroom.advert.default_image');
    }
}

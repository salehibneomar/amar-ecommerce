<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\User;
use App\Traits\Pdf\MpdfTrait;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class InvoiceService
{
    use MpdfTrait;

    public function canDownload(Order $order, ?User $owner = null): bool
    {
        return $order->has_invoice && ($owner === null || $order->user_id === $owner->id);
    }

    public function download(Order $order): Response
    {
        $order->loadMissing(['user', 'items']);

        return $this->downloadMpdf(view('pdf.invoice', [
            'order' => $order,
            'currency' => appCurrency(),
            'invoiceNumber' => $this->invoiceNumber($order),
            'logo' => $this->logo(),
            'supportEmail' => shopSupportEmail(),
        ]), $this->invoiceNumber($order).'.pdf');
    }

    private function invoiceNumber(Order $order): string
    {
        return 'INV-'.$order->public_order_id;
    }

    private function logo(): ?string
    {
        $path = public_path('assets/logo/logo-compressed.png');

        if (! is_file($path)) {
            return null;
        }

        return Cache::rememberForever('invoice-logo:'.filemtime($path), function () use ($path): ?string {
            $image = imagecreatefrompng($path);

            if ($image === false) {
                return null;
            }

            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagefilter($image, IMG_FILTER_GRAYSCALE);

            ob_start();
            imagepng($image, null, 9);

            return 'data:image/png;base64,'.base64_encode((string) ob_get_clean());
        });
    }
}

<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Support\Business;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * Server-built PDF of a receipt, downloaded with its file name already set
 * (a browser's "print to PDF" depends on the printer the visitor picks).
 */
class ReceiptPdf
{
    public function __construct(private readonly Business $business) {}

    public function download(Payment $payment): Response
    {
        return Pdf::loadView('receipts.pdf', ['payment' => $payment])
            ->setPaper('a4')
            // Embed only the glyphs used; the full DejaVu font would make every receipt ~900 KB.
            ->setOption('isFontSubsettingEnabled', true)
            ->download($payment->receiptFileName($this->business->name).'.pdf');
    }
}

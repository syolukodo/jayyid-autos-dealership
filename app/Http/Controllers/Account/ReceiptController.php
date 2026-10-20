<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\ReceiptPdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Printable receipt for one confirmed payment. Pending, failed and other
 * customers' payments are reported as missing.
 */
class ReceiptController extends Controller
{
    public function show(Request $request, string $reference): View
    {
        $payment = $this->paidPayment($request, $reference);

        return view('receipts.show', [
            'payment' => $payment,
            'backUrl' => route('account.payments'),
            'pdfUrl' => route('account.payments.receipt.pdf', $payment->reference),
        ]);
    }

    public function pdf(Request $request, string $reference, ReceiptPdf $pdf): Response
    {
        return $pdf->download($this->paidPayment($request, $reference));
    }

    private function paidPayment(Request $request, string $reference): Payment
    {
        return Payment::query()
            ->paidBy($request->user())
            ->where('reference', $reference)
            ->firstOrFail();
    }
}

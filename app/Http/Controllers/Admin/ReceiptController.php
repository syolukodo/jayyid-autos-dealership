<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Mail\PaymentReceipt;
use App\Models\Payment;
use App\Services\Payments\ReceiptPdf;
use App\Support\TransactionalMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Staff copy of a customer receipt (Payments module only). Read-only: the
 * receipt is rebuilt from the stored payment, so it cannot be altered here.
 */
class ReceiptController extends Controller
{
    public function show(string $reference): View
    {
        $payment = $this->paidPayment($reference);

        return view('receipts.show', [
            'payment' => $payment,
            'backUrl' => route('admin.payments.index').'#customer-payments',
            'resendUrl' => route('admin.payments.receipt.resend', $payment->reference),
            'pdfUrl' => route('admin.payments.receipt.pdf', $payment->reference),
        ]);
    }

    public function pdf(string $reference, ReceiptPdf $pdf): Response
    {
        return $pdf->download($this->paidPayment($reference));
    }

    public function resend(string $reference): RedirectResponse
    {
        $payment = $this->paidPayment($reference);
        $back = redirect()->route('admin.payments.receipt', $payment->reference);

        if (! TransactionalMail::enabled()) {
            return $back->with('error', 'Email delivery is not configured (MAIL_MAILER), so the receipt was not sent. Print or save it as a PDF instead.');
        }

        return TransactionalMail::send($payment->customer_email, new PaymentReceipt($payment))
            ? $back->with('status', 'Receipt emailed to '.$payment->customer_email.'.')
            : $back->with('error', 'The receipt email could not be sent. Please try again later.');
    }

    private function paidPayment(string $reference): Payment
    {
        return Payment::query()
            ->where('reference', $reference)
            ->where('status', PaymentStatus::Paid)
            ->firstOrFail();
    }
}

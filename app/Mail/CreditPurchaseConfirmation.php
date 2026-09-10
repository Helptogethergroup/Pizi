<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CreditPurchaseConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $owner;
    public $plan;
    public $amount;
    public $credits;
    public $previousBalance;
    public $currentBalance;
    public $transactionId;
    public $purchaseDate;

    public function __construct($owner, $plan, $amount, $credits, $previousBalance, $currentBalance, $transactionId, $purchaseDate)
    {
        $this->owner = $owner;
        $this->plan = $plan;
        $this->amount = $amount;
        $this->credits = $credits;
        $this->previousBalance = $previousBalance;
        $this->currentBalance = $currentBalance;
        $this->transactionId = $transactionId;
        $this->purchaseDate = $purchaseDate;
    }

    public function build()
    {
        return $this->subject('Credit Purchase Confirmation - Pizi.in')
                    ->view('emails.credit-purchase');
    }
}
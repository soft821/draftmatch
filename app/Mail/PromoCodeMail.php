<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class PromoCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $promocode;
    public $logoPath;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($promocode)
    {
        $this->promocode = $promocode;
        $this->logoPath = './assets/images/img_mail_bg.jpg';
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from("postmaster@draftmatch.com", 'Draftmatch service')->view('emails.promocode')->subject('Enjoy our service');
    }
}

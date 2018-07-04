<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class RankingUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public $logoPath;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->logoPath = './assets/images/img_mail_bg.jpg';
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from("postmaster@draftmatch.com", 'Draftmatch service')->view('emails.rankingUpdate')->subject('Enjoy our service');
    }
}

<?php

namespace App\Mail;

use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignWorkflowMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Campaign $campaign,
        public string $event,
        public ?string $notes = null,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->event) {
            'approved' => 'Your campaign was approved — Ads of Iraq',
            'needs_changes' => 'Changes requested on your campaign — Ads of Iraq',
            'rejected' => 'Campaign submission update — Ads of Iraq',
            'featured' => 'Your campaign was featured — Ads of Iraq',
            default => 'Campaign update — Ads of Iraq',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.campaign-workflow',
            with: [
                'campaign' => $this->campaign,
                'event' => $this->event,
                'notes' => $this->notes,
                'url' => route('campaigns.show', $this->campaign),
            ],
        );
    }
}

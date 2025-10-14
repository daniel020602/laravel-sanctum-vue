<?php

namespace Tests\Unit;

use App\Mail\ReservationCode;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Content;
use Tests\TestCase;

class ReservationCodeTest extends TestCase
{
    public function test_envelope_contains_correct_from_and_subject()
    {
        $mail = new ReservationCode('ABC123', 1);
        $envelope = $mail->envelope();
        $this->assertInstanceOf(Envelope::class, $envelope);
        $this->assertEquals('Reservation Code', $envelope->subject);
        $this->assertEquals('asd@asd.com', $envelope->from->address);
        $this->assertEquals('Example Name', $envelope->from->name);
    }

    public function test_content_contains_correct_view_and_data()
    {
        $code = 'XYZ789';
        $mail = new ReservationCode($code, 2);
        $content = $mail->content();
        $this->assertInstanceOf(Content::class, $content);
        $this->assertEquals('mail', $content->view);
        $this->assertArrayHasKey('reservation_code', $content->with);
        $this->assertEquals($code, $content->with['reservation_code']);
    }

    public function test_attachments_is_empty()
    {
        $mail = new ReservationCode('SAMPLE', 3);
        $this->assertEquals([], $mail->attachments());
    }
}

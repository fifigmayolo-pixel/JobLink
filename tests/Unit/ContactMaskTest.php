<?php

namespace Tests\Unit;

use App\Support\ContactMask;
use PHPUnit\Framework\TestCase;

class ContactMaskTest extends TestCase
{
    private const HIDDEN = '[contact hidden]';

    public function test_email_addresses_are_hidden(): void
    {
        $this->assertSame(
            'Email me at '.self::HIDDEN.' today',
            ContactMask::text('Email me at jane.doe@example.com today')
        );
        $this->assertSame(self::HIDDEN, ContactMask::text('jane [at] example [dot] com'));
        $this->assertSame(self::HIDDEN, ContactMask::text('jane (at) example (dot) com'));
        $this->assertSame(self::HIDDEN, ContactMask::text('jane@example'));
    }

    public function test_phone_numbers_are_hidden_in_the_usual_formats(): void
    {
        foreach (['09171234567', '+63 917 123 4567', '0917-123-4567', '0917.123.4567', '(075) 552-1234', '09 17 12 34 567'] as $number) {
            $this->assertSame(self::HIDDEN, ContactMask::text($number), $number);
        }

        $this->assertSame(
            'Call '.self::HIDDEN.' anytime',
            ContactMask::text('Call 0917 123 4567 anytime')
        );
    }

    public function test_years_and_short_numbers_are_kept(): void
    {
        foreach ([
            "Dean's lister 2021 2022 2023",
            'SY 2024-2025',
            'Scored 95 out of 100',
            'Salary grade 25,000 per month',
            'Handled 120 customers daily',
            'Graduated 2025',
        ] as $text) {
            $this->assertSame($text, ContactMask::text($text), $text);
        }
    }

    public function test_web_addresses_are_hidden(): void
    {
        $this->assertSame('See '.self::HIDDEN.' for my work', ContactMask::text('See https://example.com/me for my work'));
        $this->assertSame(self::HIDDEN, ContactMask::text('www.mysite.com'));
        $this->assertSame(self::HIDDEN, ContactMask::text('facebook.com/jane.doe'));
        $this->assertSame(self::HIDDEN, ContactMask::text('linkedin.com/in/jane'));
        $this->assertSame(self::HIDDEN, ContactMask::text('janedoe.com'));
        $this->assertSame(self::HIDDEN, ContactMask::text('t.me/janedoe'));
    }

    public function test_a_link_at_the_end_of_a_sentence_keeps_the_full_stop(): void
    {
        $this->assertSame('See '.self::HIDDEN.'. Then call.', ContactMask::text('See https://example.com/cv. Then call.'));
        $this->assertSame('Visit '.self::HIDDEN.', thanks', ContactMask::text('Visit www.example.com, thanks'));
        $this->assertSame('('.self::HIDDEN.')', ContactMask::text('(janedoe.com)'));
        $this->assertSame('Find '.self::HIDDEN.'!', ContactMask::text('Find facebook.com/jane.doe!'));
    }

    public function test_short_fields_keep_company_websites_but_still_lose_contacts(): void
    {
        $this->assertSame('Amazon.com', ContactMask::text('Amazon.com', links: false));
        $this->assertSame(self::HIDDEN, ContactMask::text('jane@example.com', links: false));
        $this->assertSame(self::HIDDEN, ContactMask::text('09171234567', links: false));
    }

    public function test_technology_names_are_not_mistaken_for_web_addresses(): void
    {
        $text = 'Skilled in Node.js, Vue.js, Next.js, ASP.NET, asp.net, VB.NET and Socket.io';

        $this->assertSame($text, ContactMask::text($text));
    }

    public function test_social_handles_are_hidden_but_an_at_sign_in_a_sentence_is_not(): void
    {
        $this->assertSame('Find me '.self::HIDDEN, ContactMask::text('Find me @vera_dev'));
        $this->assertSame('Intern @ City Hall', ContactMask::text('Intern @ City Hall'));
    }

    public function test_empty_input_gives_an_empty_string(): void
    {
        $this->assertSame('', ContactMask::text(null));
        $this->assertSame('', ContactMask::text(''));
    }

    public function test_broken_text_is_dropped_instead_of_shown_unmasked(): void
    {
        $this->assertSame('', ContactMask::text("call \xB1\xB2 09171234567"));
    }

    public function test_search_words_that_look_like_contact_details_are_recognised(): void
    {
        foreach (['jane@example.com', '09171234567', 'facebook.com/jane', '@jane', '0917123'] as $term) {
            $this->assertTrue(ContactMask::looksLikeContact($term), $term);
        }

        foreach (['php', 'web developer', 'PHP, Excel', '2024', 'Node.js', '100%', ''] as $term) {
            $this->assertFalse(ContactMask::looksLikeContact($term), $term);
        }
    }
}

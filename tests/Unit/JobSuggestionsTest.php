<?php

namespace Tests\Unit;

use App\Models\JobPosting;
use App\Support\JobSuggestions;
use Tests\TestCase;

/**
 * The keyword matching behind "Suggested jobs based on your profile".
 * No database: these cover the words, the weights and the whole-word rule.
 */
class JobSuggestionsTest extends TestCase
{
    public function test_tokens_split_into_whole_words_and_keep_c_plus_plus_and_c_sharp(): void
    {
        $this->assertSame(['C++', 'C#', 'node', 'js', 'MS', 'Office'], JobSuggestions::tokens('C++, C# & node.js (MS Office)'));
        $this->assertSame([], JobSuggestions::tokens(null));
        $this->assertSame([], JobSuggestions::tokens('   '));
        $this->assertSame([], JobSuggestions::tokens('!!! ---'));
    }

    public function test_normalize_lowers_case_and_drops_a_plural_s(): void
    {
        $this->assertSame('skill', JobSuggestions::normalize('Skills'));
        $this->assertSame('sale', JobSuggestions::normalize('Sales'));
        $this->assertSame('analyst', JobSuggestions::normalize('ANALYSTS'));
        $this->assertSame('sql', JobSuggestions::normalize('SQL'));
        $this->assertSame('business', JobSuggestions::normalize('Business'), 'words ending in ss are kept');
        $this->assertSame('bus', JobSuggestions::normalize('bus'), 'short words are left alone');
    }

    public function test_the_title_counts_most_then_requirements_then_description(): void
    {
        $keywords = ['bookkeeping' => 'Bookkeeping', 'excel' => 'Excel', 'filing' => 'Filing', 'welding' => 'Welding'];

        $match = JobSuggestions::score($keywords, $this->job([
            'title' => 'Bookkeeping Assistant',
            'requirements' => 'Can use Excel.',
            'description' => 'Daily filing of receipts.',
        ]));

        $this->assertSame(3 + 2 + 1, $match['score']);
        $this->assertSame(['Bookkeeping', 'Excel', 'Filing'], $match['matched'], 'strongest matches first, no unmatched words');
    }

    public function test_qualifications_count_like_requirements(): void
    {
        $match = JobSuggestions::score(['accountancy' => 'Accountancy'], $this->job([
            'title' => 'Office Staff',
            'qualifications' => 'Graduate of Accountancy.',
        ]));

        $this->assertSame(JobSuggestions::WEIGHT_REQUIREMENTS, $match['score']);
    }

    public function test_a_keyword_counts_once_at_its_strongest_place(): void
    {
        $match = JobSuggestions::score(['excel' => 'Excel'], $this->job([
            'title' => 'Excel Encoder',
            'requirements' => 'Excel',
            'description' => 'Excel Excel Excel',
        ]));

        $this->assertSame(JobSuggestions::WEIGHT_TITLE, $match['score']);
    }

    public function test_only_whole_words_match(): void
    {
        $match = JobSuggestions::score(['java' => 'Java'], $this->job([
            'title' => 'JavaScript Developer',
            'description' => 'We build with javascript.',
        ]));

        $this->assertSame(0, $match['score'], 'java must not match javascript');
    }

    public function test_plural_and_case_differences_still_match(): void
    {
        $match = JobSuggestions::score(['sale' => 'Sales'], $this->job(['title' => 'SALE Associate']));
        $this->assertSame(JobSuggestions::WEIGHT_TITLE, $match['score']);

        $match = JobSuggestions::score(['analyst' => 'Analyst'], $this->job(['title' => 'Data Analysts']));
        $this->assertSame(JobSuggestions::WEIGHT_TITLE, $match['score']);
    }

    public function test_missing_text_is_not_a_problem(): void
    {
        $match = JobSuggestions::score(['excel' => 'Excel'], $this->job([
            'title' => 'Clerk', 'requirements' => null, 'qualifications' => null, 'description' => null,
        ]));

        $this->assertSame(0, $match['score']);
        $this->assertSame([], $match['matched']);
    }

    private function job(array $attributes): JobPosting
    {
        return (new JobPosting)->forceFill(array_merge([
            'title' => '', 'description' => '', 'requirements' => '', 'qualifications' => '',
        ], $attributes));
    }
}

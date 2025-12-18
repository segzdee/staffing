<?php

namespace Database\Seeders;

use App\Models\BlockedPhrase;
use Illuminate\Database\Seeder;

/**
 * BlockedPhrasesSeeder
 *
 * COM-005: Communication Compliance
 * Seeds default blocked phrases for content moderation.
 */
class BlockedPhrasesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Profanity phrases (common inappropriate words)
        // Note: Using mild versions and keeping list professional
        $profanityPhrases = [
            ['phrase' => 'f***', 'action' => 'flag'],
            ['phrase' => 's***', 'action' => 'flag'],
            ['phrase' => 'a**hole', 'action' => 'flag'],
            ['phrase' => 'b*tch', 'action' => 'flag'],
            ['phrase' => 'd*mn', 'action' => 'flag'],
            ['phrase' => 'h*ll', 'action' => 'flag'],
            // Common letter substitution patterns (regex)
            ['phrase' => 'f[u\*@0]+[c\*@]+[k\*@]+', 'action' => 'flag', 'is_regex' => true],
            ['phrase' => 's[h\*@]+[i\*@1]+[t\*@]+', 'action' => 'flag', 'is_regex' => true],
        ];

        // Harassment phrases (threatening or abusive language)
        $harassmentPhrases = [
            ['phrase' => 'i will kill you', 'action' => 'block'],
            ['phrase' => 'kill yourself', 'action' => 'block'],
            ['phrase' => 'kys', 'action' => 'block'],
            ['phrase' => 'go die', 'action' => 'block'],
            ['phrase' => 'you should die', 'action' => 'block'],
            ['phrase' => 'i will hurt you', 'action' => 'block'],
            ['phrase' => 'i will find you', 'action' => 'flag'],
            ['phrase' => 'i know where you live', 'action' => 'block'],
            ['phrase' => 'watch your back', 'action' => 'flag'],
            ['phrase' => 'you will regret', 'action' => 'flag'],
            ['phrase' => 'i will get you', 'action' => 'flag'],
            ['phrase' => 'you are worthless', 'action' => 'flag'],
            ['phrase' => 'you are pathetic', 'action' => 'flag'],
            ['phrase' => 'you are useless', 'action' => 'flag'],
            ['phrase' => 'you are disgusting', 'action' => 'flag'],
            // Slurs and hate speech patterns
            ['phrase' => 'retard', 'action' => 'flag'],
            ['phrase' => 'r[e3]+t[a@]+rd', 'action' => 'flag', 'is_regex' => true],
        ];

        // Spam patterns (common spam indicators)
        $spamPhrases = [
            ['phrase' => 'click here to win', 'action' => 'flag'],
            ['phrase' => 'make money fast', 'action' => 'flag'],
            ['phrase' => 'work from home earn', 'action' => 'flag'],
            ['phrase' => 'free iphone', 'action' => 'block'],
            ['phrase' => 'you have won', 'action' => 'flag'],
            ['phrase' => 'congratulations you have been selected', 'action' => 'flag'],
            ['phrase' => 'send me your bank', 'action' => 'block'],
            ['phrase' => 'wire transfer', 'action' => 'flag'],
            ['phrase' => 'western union', 'action' => 'flag'],
            ['phrase' => 'bitcoin payment', 'action' => 'flag'],
            ['phrase' => 'cryptocurrency payment', 'action' => 'flag'],
            // URL shorteners often used for spam
            ['phrase' => 'bit\.ly\/\w+', 'action' => 'flag', 'is_regex' => true],
            ['phrase' => 'tinyurl\.com\/\w+', 'action' => 'flag', 'is_regex' => true],
            ['phrase' => 't\.co\/\w+', 'action' => 'flag', 'is_regex' => true],
        ];

        // Contact info patterns (to prevent bypassing platform)
        $contactInfoPhrases = [
            ['phrase' => 'text me at', 'action' => 'flag'],
            ['phrase' => 'call me at', 'action' => 'flag'],
            ['phrase' => 'email me at', 'action' => 'flag'],
            ['phrase' => 'whatsapp me', 'action' => 'flag'],
            ['phrase' => 'message me on whatsapp', 'action' => 'flag'],
            ['phrase' => 'contact me outside', 'action' => 'flag'],
            ['phrase' => 'reach me at', 'action' => 'flag'],
            ['phrase' => 'my number is', 'action' => 'flag'],
            ['phrase' => 'my email is', 'action' => 'flag'],
            ['phrase' => 'add me on', 'action' => 'flag'],
            ['phrase' => 'follow me on', 'action' => 'flag'],
            ['phrase' => 'my instagram', 'action' => 'flag'],
            ['phrase' => 'my facebook', 'action' => 'flag'],
            ['phrase' => 'my snapchat', 'action' => 'flag'],
            ['phrase' => 'my telegram', 'action' => 'flag'],
        ];

        // PII patterns (regex for sensitive data)
        $piiPhrases = [
            // Note: These are supplementary to the built-in PII detection in ContentModerationService
            ['phrase' => 'my ssn is', 'action' => 'redact'],
            ['phrase' => 'social security', 'action' => 'flag'],
            ['phrase' => 'my bank account', 'action' => 'flag'],
            ['phrase' => 'routing number', 'action' => 'flag'],
            ['phrase' => 'credit card number', 'action' => 'redact'],
            ['phrase' => 'my card number', 'action' => 'redact'],
            ['phrase' => 'cvv', 'action' => 'flag'],
            ['phrase' => 'security code', 'action' => 'flag'],
        ];

        // Seed profanity phrases
        foreach ($profanityPhrases as $data) {
            $this->createPhrase($data, BlockedPhrase::TYPE_PROFANITY);
        }

        // Seed harassment phrases
        foreach ($harassmentPhrases as $data) {
            $this->createPhrase($data, BlockedPhrase::TYPE_HARASSMENT);
        }

        // Seed spam phrases
        foreach ($spamPhrases as $data) {
            $this->createPhrase($data, BlockedPhrase::TYPE_SPAM);
        }

        // Seed contact info phrases
        foreach ($contactInfoPhrases as $data) {
            $this->createPhrase($data, BlockedPhrase::TYPE_CONTACT_INFO);
        }

        // Seed PII phrases
        foreach ($piiPhrases as $data) {
            $this->createPhrase($data, BlockedPhrase::TYPE_PII);
        }

        $this->command->info('Blocked phrases seeded successfully.');
    }

    /**
     * Create a blocked phrase if it doesn't exist.
     */
    protected function createPhrase(array $data, string $type): void
    {
        $phrase = $data['phrase'];

        // Check if phrase already exists
        if (BlockedPhrase::where('phrase', $phrase)->where('type', $type)->exists()) {
            return;
        }

        BlockedPhrase::create([
            'phrase' => $phrase,
            'type' => $type,
            'action' => $data['action'] ?? 'flag',
            'is_regex' => $data['is_regex'] ?? false,
            'case_sensitive' => $data['case_sensitive'] ?? false,
            'is_active' => true,
        ]);
    }
}

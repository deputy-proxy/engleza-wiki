<?php

namespace Database\Seeders;

use App\Models\Audience;
use App\Models\CardResource;
use App\Models\Level;
use App\Models\ListeningResource;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\ReadingResource;
use App\Models\Resource;
use App\Models\ResourceCard;
use App\Models\Skill;
use App\Models\Topic;
use App\Models\User;
use App\Models\WritingResource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InteractiveResourcesSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@engleza.wiki')->first();
        $L = fn ($s) => Level::where('slug', $s)->first()?->id;
        $S = fn ($s) => Skill::where('slug', Str::slug($s))->first();
        $T = fn ($s) => Topic::where('slug', Str::slug($s))->first();
        $A = fn ($s) => Audience::where('slug', Str::slug($s))->first();

        $this->seedCards($admin, $L, $S, $T, $A);
        $this->seedListening($admin, $L, $S, $T, $A);
        $this->seedReading($admin, $L, $S, $T, $A);
        $this->seedWriting($admin, $L, $S, $T, $A);
    }

    private function attachTaxonomy(Resource $r, $level, $skill, $topic, array $audiences = ['Learners']): void
    {
        $L = fn ($s) => Level::where('slug', $s)->first()?->id;
        $r->level_id = $L($level);
        $r->save();
        if ($sk = Skill::where('slug', Str::slug($skill))->first()) {
            $r->skills()->syncWithoutDetaching([$sk->id]);
        }
        if ($t = Topic::where('slug', Str::slug($topic))->first()) {
            $r->topics()->syncWithoutDetaching([$t->id]);
        }
        foreach ($audiences as $a) {
            if ($au = Audience::where('slug', Str::slug($a))->first()) {
                $r->audiences()->syncWithoutDetaching([$au->id]);
            }
        }
    }

    private function makeResource(string $title, string $type, string $excerpt, User $admin): Resource
    {
        return Resource::updateOrCreate(
            ['slug' => Str::slug($title)],
            [
                'title' => $title, 'excerpt' => $excerpt, 'content' => $excerpt,
                'type' => $type, 'status' => 'published', 'author_id' => $admin->id,
                'featured' => false, 'published_at' => now()->subDays(rand(1, 15)),
                'seo_title' => $title.' — engleza.wiki', 'seo_description' => $excerpt,
            ]
        );
    }

    private function seedCards($admin, $L, $S, $T, $A): void
    {
        // 1) Vocabulary flashcards
        $r = $this->makeResource('Everyday English Vocabulary Flashcards', 'card', '15 flashcards for building your everyday English vocabulary — definition to word.', $admin);
        $this->attachTaxonomy($r, 'a2', 'Vocabulary', 'Vocabulary', ['Learners']);
        $cr = CardResource::updateOrCreate(['resource_id' => $r->id], ['instructions' => 'Read the definition, then click to reveal the word.']);
        $cr->cards()->delete();
        $vocab = [
            ['A building where you can borrow books.', 'LIBRARY'],
            ['A person who designs buildings.', 'ARCHITECT'],
            ['A person who writes books.', 'AUTHOR'],
            ['You use this to unlock a door.', 'KEY'],
            ['A place where you buy medicine.', 'PHARMACY'],
            ['The meal you eat in the morning.', 'BREAKFAST'],
            ['A large vehicle that carries many passengers.', 'BUS'],
            ['The person who teaches at a school.', 'TEACHER'],
            ['A place where you watch films on a big screen.', 'CINEMA'],
            ['The season between summer and winter.', 'AUTUMN'],
            ['A fruit that is yellow and long.', 'BANANA'],
            ['You wear these on your feet outdoors.', 'SHOES'],
            ['A tool used to write on paper.', 'PEN'],
            ['You use this to see when it is dark.', 'LAMP'],
            ['A person who cures sick people.', 'DOCTOR'],
        ];
        foreach ($vocab as $i => [$f,$b]) {
            ResourceCard::create(['card_resource_id' => $cr->id, 'front' => $f, 'back' => $b, 'sort_order' => $i]);
        }

        // 2) Irregular verbs
        $r = $this->makeResource('English Irregular Verbs Flashcards', 'card', '15 essential irregular verbs — infinitive to past to past participle.', $admin);
        $this->attachTaxonomy($r, 'a2', 'Grammar', 'Past Simple', ['Learners']);
        $cr = CardResource::updateOrCreate(['resource_id' => $r->id], ['instructions' => 'See the infinitive, then reveal the past and past participle.']);
        $cr->cards()->delete();
        $irregular = [
            ['GO', 'went → gone'], ['EAT', 'ate → eaten'], ['SEE', 'saw → seen'], ['TAKE', 'took → taken'],
            ['DO', 'did → done'], ['BE', 'was/were → been'], ['HAVE', 'had → had'], ['MAKE', 'made → made'],
            ['COME', 'came → come'], ['DRINK', 'drank → drunk'], ['DRIVE', 'drove → driven'], ['WRITE', 'wrote → written'],
            ['SPEAK', 'spoke → spoken'], ['GIVE', 'gave → given'], ['BUY', 'bought → bought'],
        ];
        foreach ($irregular as $i => [$f,$b]) {
            ResourceCard::create(['card_resource_id' => $cr->id, 'front' => $f, 'back' => $b, 'sort_order' => $i]);
        }

        // 3) Grammar Q&A
        $r = $this->makeResource('Basic English Grammar Q&A Flashcards', 'card', '10 grammar questions and answers for quick review.', $admin);
        $this->attachTaxonomy($r, 'a1', 'Grammar', 'Present Simple', ['Learners']);
        $cr = CardResource::updateOrCreate(['resource_id' => $r->id], ['instructions' => 'Try to answer before you flip the card.']);
        $cr->cards()->delete();
        $grammar = [
            ['What is the past tense of "eat"?', 'ATE'],
            ['What is the plural of "child"?', 'CHILDREN'],
            ['Fill in: She ___ (go) to school every day.', 'GOES'],
            ['What is the past participle of "write"?', 'WRITTEN'],
            ['Fill in: If it ___ (rain), we will stay home.', 'RAINS'],
            ['What is the comparative of "good"?', 'BETTER'],
            ['Which article: ___ hour ago.', 'AN (an hour ago)'],
            ['Fill in: I have ___ (never) been to Paris.', 'NEVER'],
            ['What is the opposite of "always"?', 'NEVER'],
            ['Modal for permission: ___ I open the window?', 'MAY / CAN'],
        ];
        foreach ($grammar as $i => [$f,$b]) {
            ResourceCard::create(['card_resource_id' => $cr->id, 'front' => $f, 'back' => $b, 'sort_order' => $i]);
        }
    }

    private function seedListening($admin, $L, $S, $T, $A): void
    {
        $items = [
            ['A2 Listening: At the Restaurant', 'a2', 'Practise everyday restaurant English — ordering food, asking about the menu.', 'At the Restaurant',
                [['Where are the speakers?', 'At a restaurant', ['At home', 'At a restaurant', 'At school', 'At work']],
                    ['What does the customer order to start?', 'A tomato soup', ['A steak', 'A tomato soup', 'A salad', 'A pizza']],
                    ['What does the customer drink?', 'Water', ['Coffee', 'Water', 'Wine', 'Juice']],
                    ['Does the customer want dessert?', 'No', ['Yes', 'No', 'Not sure', 'A little']],
                    ['How does the customer pay?', 'By card', ['Cash', 'By card', 'With crypto', 'Voucher']],
                ],
            ],
            ['B1 Listening: A Job Interview', 'b1', 'Understand a typical job interview — questions about experience and motivation.', 'Work',
                [['Where does the interview take place?', 'In an office', ['In a cafe', 'In an office', 'Online', 'In a park']],
                    ['How many years of experience does the candidate have?', '3 years', ['1 year', '3 years', '5 years', '10 years']],
                    ['What is the candidate\'s greatest strength?', 'Teamwork', ['Teamwork', 'Speed', 'Perfectionism', 'Silence']],
                    ['When can the candidate start?', 'In two weeks', ['Immediately', 'In two weeks', 'In a month', 'Next year']],
                    ['Does the candidate speak French?', 'Yes, fluently', ['No', 'A little', 'Yes, fluently', 'Only to write']],
                ],
            ],
        ];
        foreach ($items as [$title,$level,$excerpt,$topic,$qs]) {
            $r = $this->makeResource($title, 'listening', $excerpt, $admin);
            $this->attachTaxonomy($r, $level, 'Listening', $topic, ['Learners']);
            $lr = ListeningResource::updateOrCreate(['resource_id' => $r->id], [
                'instructions' => 'Listen carefully and answer the questions below.',
                'media_type' => 'audio', 'mime_type' => 'audio/mpeg',
                // media_path intentionally null — administrators upload the file through Filament
            ]);
            $lr->questions()->delete();
            foreach ($qs as $i => [$qtext,$correct,$opts]) {
                $q = Question::create(['listening_resource_id' => $lr->id, 'type' => 'single_choice', 'question' => $qtext, 'points' => 1, 'sort_order' => $i]);
                foreach ($opts as $j => $val) {
                    QuestionOption::create(['question_id' => $q->id, 'label' => chr(65 + $j), 'value' => $val, 'is_correct' => $val === $correct, 'sort_order' => $j]);
                }
            }
        }
    }

    private function seedReading($admin, $L, $S, $T, $A): void
    {
        $items = [
            ['A2 Reading: A Day in London', 'a2', 'A short reading passage about a typical day exploring London.', 'Reading',
                'London is one of the most exciting cities in Europe. Every morning, thousands of tourists visit famous places like the British Museum, the Tower of London and Big Ben. In the afternoon, many people take a walk in Hyde Park or shop on Oxford Street. In the evening, the West End is popular for musicals and theatre. The London Underground makes it easy to travel across the city. Even a rainy day cannot stop the fun — there is always a warm café or an interesting museum nearby.',
                [['Which is a famous museum mentioned in the text?', 'The British Museum', ['The Louvre', 'The British Museum', 'The Prado', 'The MoMA']],
                    ['What do people do in Hyde Park?', 'Take a walk', ['Take a walk', 'Ride horses', 'Swim', 'Play football']],
                    ['Where do people watch musicals?', 'The West End', ['Soho', 'The West End', 'Camden', 'The City']],
                    ['How can you travel across London?', 'By Underground', ['By boat', 'By Underground', 'On foot only', 'By horse']],
                    ['On a rainy day, where can you go?', 'A warm café or museum', ['A warm café or museum', 'The beach', 'The forest', 'The zoo']],
                ],
            ],
            ['B1 Reading: Why Learning Languages Matters', 'b1', 'A short article about the benefits of learning a foreign language.', 'Reading',
                'Learning a new language does much more than help you order coffee abroad. Studies show that people who speak more than one language make decisions more carefully, remember information better and adapt more quickly to new situations. Bilingual employees are often preferred by employers because they can communicate with clients around the world. Learning a language also opens the door to a new culture — its music, books and films become accessible in their original form. Best of all, you do not need to be young to start. Adults can learn effectively when they practise a little every day.',
                [['What is one cognitive benefit mentioned?', 'Better memory', ['Faster running', 'Better memory', 'Perfect vision', 'Louder voice']],
                    ['Why do employers prefer bilingual employees?', 'They can talk to global clients', ['They work for free', 'They can talk to global clients', 'They eat less', 'They travel less']],
                    ['What does a new language give access to?', 'A new culture', ['A new car', 'A new culture', 'A new city', 'A new job title']],
                    ['Do you need to be young to start?', 'No', ['Yes', 'No', 'Only under 18', 'Only under 30']],
                    ['How should adults learn?', 'A little every day', ['A lot on Sundays', 'A little every day', 'Only at university', 'From films only']],
                ],
            ],
            ['B2 Reading: The Future of Remote Work', 'b2', 'An analytical text about the changing landscape of remote work.', 'Work',
                'In just a few years, remote work has moved from a rare exception to a widespread reality. Modern collaboration tools allow entire teams to work productively from different cities, and even different continents. Employers report cost savings on office space, while employees frequently mention improved work-life balance. However, the shift is not without challenges. Managers must find new ways to build trust and communicate priorities, while workers can feel isolated without the daily social contact of an office. The most successful organisations combine flexibility with clear expectations, offering hybrid models that suit different roles and personalities.',
                [['What has helped remote work grow?', 'Collaboration tools', ['Higher salaries', 'Collaboration tools', 'Bigger offices', 'Faster cars']],
                    ['One benefit for employers is…', 'Cost savings on office space', ['Cost savings on office space', 'Louder meetings', 'Fewer emails', 'Larger cafeterias']],
                    ['One challenge for workers is…', 'Feeling isolated', ['Feeling isolated', 'Too many parties', 'Traffic jams', 'Loud music']],
                    ['What model suits many companies best?', 'Hybrid', ['Fully remote only', 'Fully in-office only', 'Hybrid', 'No work']],
                    ['What must managers build?', 'Trust', ['Trust', 'Fences', 'Deadlines only', 'Reports only']],
                ],
            ],
        ];
        foreach ($items as [$title,$level,$excerpt,$topic,$passage,$qs]) {
            $r = $this->makeResource($title, 'reading', $excerpt, $admin);
            $this->attachTaxonomy($r, $level, 'Reading', $topic, ['Learners']);
            $rr = ReadingResource::updateOrCreate(['resource_id' => $r->id], ['title' => $title, 'instructions' => 'Read the passage carefully, then answer the questions.', 'content' => $passage]);
            $rr->questions()->delete();
            foreach ($qs as $i => [$qtext,$correct,$opts]) {
                $q = Question::create(['reading_resource_id' => $rr->id, 'type' => 'single_choice', 'question' => $qtext, 'points' => 1, 'sort_order' => $i]);
                foreach ($opts as $j => $val) {
                    QuestionOption::create(['question_id' => $q->id, 'label' => chr(65 + $j), 'value' => $val, 'is_correct' => $val === $correct, 'sort_order' => $j]);
                }
            }
        }
    }

    private function seedWriting($admin, $L, $S, $T, $A): void
    {
        $items = [
            ['A2 Writing: Describe Your Daily Routine', 'a2', 'A short writing task about your typical day.', 'Present Simple',
                'Write about your daily routine. What do you usually do in the morning, afternoon and evening?',
                'Use present simple tense. Include at least three activities for each part of the day.', 80, 120, 15],
            ['A2 Writing: Write an Email to a Friend', 'a2', 'Informal email writing task.', 'Writing',
                'Write an email to a friend inviting them to visit you next weekend.',
                'Include: a greeting, the reason for writing, the plan for the weekend, and a friendly ending.', 80, 120, 15],
            ['B1 Writing: Describe Your Last Holiday', 'b1', 'Past tenses in context — describe a memorable holiday.', 'Past Simple',
                'Describe your last holiday. Where did you go? Who did you go with? What did you do?',
                'Use past simple and past continuous. Try to include feelings and one memorable moment.', 120, 180, 20],
            ['B2 Writing: Should Students Use Smartphones at School?', 'b2', 'An opinion essay about smartphones in schools.', 'Writing',
                'Should students be allowed to use smartphones at school? Discuss both sides and give your opinion.',
                'Write an opinion essay. Present at least one advantage and one disadvantage before giving your opinion.', 150, 220, 30],
        ];
        foreach ($items as [$title,$level,$excerpt,$topic,$prompt,$instr,$min,$max,$time]) {
            $r = $this->makeResource($title, 'writing', $excerpt, $admin);
            $this->attachTaxonomy($r,$level,'Writing',$topic,['Learners']);
            WritingResource::updateOrCreate(['resource_id' => $r->id], [
                'prompt' => $prompt, 'instructions' => $instr,
                'minimum_words' => $min, 'maximum_words' => $max, 'recommended_time_minutes' => $time,
            ]);
        }
    }
}
